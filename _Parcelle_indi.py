"""
_Parcelle_indi.py — aplatissage JSON vers CSV
Supporte trois formats, détectés automatiquement :
  - parcelles_societes  : liste de parcelles dont les proprietaires ont un siren
                          (ex: Export/75011/PARCELLESDERETOUR.json)
                          → une ligne par (parcelle × société)
                          → adresse du bien + infos société data.gouv
  - parcelles_individus : liste de parcelles dont les proprietaires sont des particuliers
                          (ex: Export/RetourPappers_parcelles_75017.json)
                          → une ligne par parcelle
  - entreprises         : dict {siren: company} issu data.gouv
                          (ex: Export/entreprises_75011_datagouv_raw.json)
                          → une ligne par société
"""

import json
import os
import mysql.connector
import pandas as pd
from dotenv import load_dotenv

# ── Config ────────────────────────────────────────────────────────────────────
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
load_dotenv(os.path.join(BASE_DIR, '.env'))

# Changer INPUT_FILE selon le fichier à traiter
INPUT_FILE    = os.path.join(BASE_DIR, 'Export', '75012', 'PARCELLESDERETOUR.json')
DATAGOUV_CACHE= os.path.join(BASE_DIR, 'Export', '75012', 'entreprises_75012_datagouv_raw.json')

# ── Lecture JSON ──────────────────────────────────────────────────────────────
with open(INPUT_FILE, 'r', encoding='utf-8') as f:
    data = json.load(f)

# ── Détection format ──────────────────────────────────────────────────────────
def detect_format(d):
    if isinstance(d, dict) and 'resultats' not in d and 'parcelles' not in d:
        first = next(iter(d.values()), {})
        if isinstance(first, dict) and 'siren' in first and 'siege' in first:
            return 'entreprises'
    items = d.get('resultats', d.get('parcelles', d)) if isinstance(d, dict) else d
    if isinstance(items, list) and items:
        first = items[0]
        props = first.get('proprietaires') or []
        if any(pr.get('siren') for pr in props):
            return 'parcelles_societes'
    return 'parcelles_individus'

fmt = detect_format(data)
print(f"Format détecté : {fmt}")

# ── Connexion DB ──────────────────────────────────────────────────────────────
def db_connect():
    socket  = os.getenv('REGISTERS_DB_SOCKET', '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock')
    host    = os.getenv('REGISTERS_DB_HOST', 'localhost')
    args = dict(
        database=os.getenv('REGISTERS_DB_NAME', 'Registers'),
        user    =os.getenv('REGISTERS_DB_USER', 'root'),
        password=os.getenv('REGISTERS_DB_PASS', ''),
    )
    if os.path.exists(socket):
        args['unix_socket'] = socket
    else:
        args['host'] = host
    return mysql.connector.connect(**args)

# ── Helpers data.gouv ─────────────────────────────────────────────────────────
def load_datagouv_cache(path):
    if not os.path.exists(path):
        print(f"Avertissement : cache data.gouv introuvable ({path})")
        return {}
    with open(path, encoding='utf-8') as f:
        return json.load(f)

def flatten_company(siren, company):
    """Retourne un dict aplati des champs data.gouv pour une société."""
    if not company:
        return {}
    siege  = company.get('siege') or {}
    coords = siege.get('coordonnees') or {}
    lat = siege.get('latitude') or (coords.get('lat') if isinstance(coords, dict) else None)
    lon = siege.get('longitude') or (coords.get('lon') if isinstance(coords, dict) else None)
    dirigeants = company.get('dirigeants') or []
    premier = dirigeants[0] if dirigeants else {}
    return {
        'nom_complet':          company.get('nom_complet'),
        'nom_raison_sociale':   company.get('nom_raison_sociale'),
        'sigle':                company.get('sigle'),
        'nature_juridique':     company.get('nature_juridique'),
        'activite_principale':  company.get('activite_principale'),
        'section_activite':     company.get('section_activite_principale'),
        'etat_administratif':   company.get('etat_administratif'),
        'date_creation':        company.get('date_creation'),
        'date_fermeture':       company.get('date_fermeture'),
        'categorie_entreprise': company.get('categorie_entreprise'),
        'caractere_employeur':  company.get('caractere_employeur'),
        'tranche_effectif':     company.get('tranche_effectif_salarie'),
        'nombre_etablissements':company.get('nombre_etablissements'),
        'nombre_etab_ouverts':  company.get('nombre_etablissements_ouverts'),
        'siege_siret':          siege.get('siret'),
        'siege_adresse':        siege.get('adresse'),
        'siege_code_postal':    siege.get('code_postal'),
        'siege_commune':        siege.get('libelle_commune'),
        'siege_departement':    siege.get('departement'),
        'siege_region':         siege.get('region'),
        'siege_latitude':       lat,
        'siege_longitude':      lon,
        'siege_geo_adresse':    siege.get('geo_adresse'),
        'nombre_dirigeants':    len(dirigeants),
        'dirigeant_nom':        premier.get('nom') or premier.get('nom_complet'),
        'dirigeant_prenom':     premier.get('prenom'),
        'dirigeant_qualite':    premier.get('qualite') or premier.get('titre'),
    }

def first_dirigeant(dirigeants):
    if not dirigeants:
        return None, None, None
    d = dirigeants[0]
    return (
        d.get('nom') or d.get('nom_complet') or '',
        d.get('prenom') or '',
        d.get('qualite') or d.get('titre') or '',
    )

# ══════════════════════════════════════════════════════════════════════════════
# FORMAT : parcelles_societes
# Produit une ligne par (parcelle × société)
# Colonnes : adresse du bien + infos Pappers de base + enrichissement data.gouv
# ══════════════════════════════════════════════════════════════════════════════
if fmt == 'parcelles_societes':
    OUTPUT_FILE = os.path.join(os.path.dirname(INPUT_FILE), 'parcelles_societes_fusion.csv')

    parcelles = data.get('resultats', data.get('parcelles', data)) if isinstance(data, dict) else data
    print(f"{len(parcelles)} parcelles lues depuis {os.path.basename(INPUT_FILE)}")

    datagouv = load_datagouv_cache(DATAGOUV_CACHE)
    print(f"{len(datagouv)} sociétés dans le cache data.gouv")

    rows = []
    for p in parcelles:
        parcelle_base = {
            'parcelle_numero':      p.get('numero'),
            'parcelle_adresse':     p.get('adresse'),   # adresse du bien immobilier
            'parcelle_section':     p.get('section'),
            'parcelle_numero_plan': p.get('numero_plan'),
            'parcelle_contenance':  p.get('contenance'),
            'parcelle_code_commune':p.get('code_commune'),
            'parcelle_commune':     p.get('commune'),
        }

        for prop in (p.get('proprietaires') or []):
            siren = str(prop.get('siren') or '').strip()
            if not siren:
                continue

            # Infos Pappers déjà dans le fichier (base légère)
            row = {
                **parcelle_base,
                'parcelle_nb_locaux':    len(prop.get('locaux') or []),
                # Lien propriétaire
                'siren':                 siren,
                # Champs propres à la relation immo (absents de data.gouv)
                'monoproprietaire':      prop.get('monoproprietaire'),
                'capital':               prop.get('capital'),
                'devise_capital':        prop.get('devise_capital'),
                'lmnp':                  prop.get('lmnp'),
                'proprietaire_occupant': prop.get('proprietaire_occupant'),
                # Données société — Registers / data.gouv uniquement
                **flatten_company(siren, datagouv.get(siren)),
            }
            rows.append(row)

    df = pd.DataFrame(rows)
    nb_enrichis = df['nom_complet'].notna().sum() if 'nom_complet' in df.columns else 0
    print(f"{len(df)} lignes (parcelle × société) | {nb_enrichis} enrichies via data.gouv")

# ══════════════════════════════════════════════════════════════════════════════
# FORMAT : parcelles_individus
# ══════════════════════════════════════════════════════════════════════════════
elif fmt == 'parcelles_individus':
    OUTPUT_FILE = os.path.join(BASE_DIR, 'Export', 'parcelles_individus_flat.csv')

    parcelles = data.get('resultats', data.get('parcelles', [])) if isinstance(data, dict) else data
    print(f"{len(parcelles)} parcelles lues depuis {os.path.basename(INPUT_FILE)}")

    rows = []
    for p in parcelles:
        adresse = p.get('adresse') or ''
        if isinstance(adresse, dict):
            adresse = adresse.get('libelle') or ''
        rows.append({
            'numero':              p.get('numero'),
            'adresse':             adresse,
            'code_commune':        p.get('code_commune'),
            'commune':             p.get('commune'),
            'code_departement':    p.get('code_departement'),
            'section':             p.get('section'),
            'numero_plan':         p.get('numero_plan'),
            'contenance':          p.get('contenance'),
            'surface_batie':       p.get('surface_batie'),
            'surface_disponible':  p.get('surface_disponible'),
            'nb_proprietaires_pappers': len([pr for pr in (p.get('proprietaires') or []) if pr]),
        })

    df = pd.DataFrame(rows)

    try:
        conn   = db_connect()
        cursor = conn.cursor(dictionary=True)
        numeros = df['numero'].dropna().tolist()
        if numeros:
            ph = ','.join(['%s'] * len(numeros))
            cursor.execute(
                f"SELECT numero, profil_proprietaire, nb_proprietaires_identifies "
                f"FROM parcelles WHERE numero IN ({ph})", numeros
            )
            reg_rows = {r['numero']: r for r in cursor.fetchall()}
            df['profil_proprietaire']        = df['numero'].map(lambda n: reg_rows.get(n, {}).get('profil_proprietaire'))
            df['nb_proprietaires_identifies']= df['numero'].map(lambda n: reg_rows.get(n, {}).get('nb_proprietaires_identifies'))
            print(f"{len(reg_rows)} parcelles trouvées dans Registers")
        cursor.close()
        conn.close()
    except Exception as e:
        print(f"Avertissement DB : {e}")
        df['profil_proprietaire']         = None
        df['nb_proprietaires_identifies'] = None

# ══════════════════════════════════════════════════════════════════════════════
# FORMAT : entreprises (dict data.gouv)
# ══════════════════════════════════════════════════════════════════════════════
else:
    OUTPUT_FILE = os.path.join(BASE_DIR, 'Export', 'entreprises_datagouv_flat.csv')
    companies = list(data.values())
    print(f"{len(companies)} entreprises lues depuis {os.path.basename(INPUT_FILE)}")

    rows = []
    for c in companies:
        siege  = c.get('siege') or {}
        coords = siege.get('coordonnees') or {}
        lat = siege.get('latitude') or (coords.get('lat') if isinstance(coords, dict) else None)
        lon = siege.get('longitude') or (coords.get('lon') if isinstance(coords, dict) else None)
        nom_d, prenom_d, qualite_d = first_dirigeant(c.get('dirigeants') or [])

        rows.append({
            'siren':                       c.get('siren'),
            'nom_complet':                 c.get('nom_complet'),
            'nom_raison_sociale':          c.get('nom_raison_sociale'),
            'sigle':                       c.get('sigle'),
            'nature_juridique':            c.get('nature_juridique'),
            'activite_principale':         c.get('activite_principale'),
            'section_activite_principale': c.get('section_activite_principale'),
            'etat_administratif':          c.get('etat_administratif'),
            'date_creation':               c.get('date_creation'),
            'date_fermeture':              c.get('date_fermeture'),
            'categorie_entreprise':        c.get('categorie_entreprise'),
            'caractere_employeur':         c.get('caractere_employeur'),
            'tranche_effectif_salarie':    c.get('tranche_effectif_salarie'),
            'nombre_etablissements':       c.get('nombre_etablissements'),
            'nombre_etablissements_ouverts': c.get('nombre_etablissements_ouverts'),
            'siege_siret':                 siege.get('siret'),
            'siege_adresse':               siege.get('adresse'),
            'siege_code_postal':           siege.get('code_postal'),
            'siege_libelle_commune':       siege.get('libelle_commune'),
            'siege_commune':               siege.get('commune'),
            'siege_departement':           siege.get('departement'),
            'siege_region':                siege.get('region'),
            'siege_latitude':              lat,
            'siege_longitude':             lon,
            'siege_geo_adresse':           siege.get('geo_adresse'),
            'siege_etat_administratif':    siege.get('etat_administratif'),
            'siege_date_creation':         siege.get('date_creation'),
            'siege_date_debut_activite':   siege.get('date_debut_activite'),
            'siege_nom_commercial':        siege.get('nom_commercial'),
            'nombre_dirigeants':           len(c.get('dirigeants') or []),
            'premier_dirigeant_nom':       nom_d,
            'premier_dirigeant_prenom':    prenom_d,
            'premier_dirigeant_qualite':   qualite_d,
        })

    df = pd.DataFrame(rows)

# ── Export CSV ────────────────────────────────────────────────────────────────
df.to_csv(OUTPUT_FILE, index=False, encoding='utf-8-sig')
print(f"Export : {OUTPUT_FILE}")
print(f"{len(df)} lignes exportées")

if fmt == 'parcelles_societes':
    cols = ['parcelle_adresse', 'siren', 'nom_complet', 'monoproprietaire', 'siege_commune']
    print(df[[c for c in cols if c in df.columns]].head(10).to_string())
elif fmt == 'parcelles_individus':
    print(df[['numero', 'adresse', 'contenance', 'profil_proprietaire']].head(10).to_string())
else:
    print(df[['siren', 'nom_complet', 'nature_juridique', 'siege_code_postal', 'siege_libelle_commune']].head(10).to_string())
