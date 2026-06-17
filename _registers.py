"""
_registers.py — pipeline Registers : parcelles + sociétés propriétaires
Usage : python3 _registers.py [code_postal]
        Si code_postal absent, demande interactivement.

Étape 1 : Pappers Immobilier → PARCELLESDERETOUR.json
Étape 2 : data.gouv (Registers) → enrichissement sociétés
Étape 3 : fusion → CSV + Excel Registers
"""

import json
import sys
import time
import re
import os
import requests
import pandas as pd
from pathlib import Path
from dotenv import load_dotenv
from typing import Optional

# ── Config ────────────────────────────────────────────────────────────────────
BASE_DIR = Path(__file__).parent
load_dotenv(BASE_DIR / '.env')

# Code postal : argument CLI ou saisie manuelle
if len(sys.argv) > 1:
    CODE_POSTAL = sys.argv[1].strip()
else:
    CODE_POSTAL = input("Code postal de la zone (ex: 75012) : ").strip()

if not re.match(r'^\d{5}$', CODE_POSTAL):
    print(f"Code postal invalide : {CODE_POSTAL}")
    sys.exit(1)

# Filtres
NB_PROPRIO_MIN  = "1"   # nombre min de propriétaires sociétés
NB_PROPRIO_MAX  = "4"   # nombre max — 1 = monoproprietaire uniquement
INCLURE_CESSEES = False  # inclure les sociétés cessées

DELAI_APPELS    = 0.15  # secondes entre appels data.gouv

# Chemins de sortie
OUT_DIR            = BASE_DIR / "Export" / CODE_POSTAL
PARCELLES_FILE     = OUT_DIR / "PARCELLESDERETOUR.json"
SIRENS_FILE        = OUT_DIR / "sirens.txt"
DATAGOUV_CACHE     = OUT_DIR / f"entreprises_{CODE_POSTAL}_datagouv_raw.json"
OUTPUT_CSV         = OUT_DIR / "parcelles_societes_fusion.csv"
OUTPUT_XLSX        = OUT_DIR / f"EXCEL_parcelles_entreprise_{CODE_POSTAL}.xlsx"

# APIs
PAPPERS_IMMO_TOKEN = os.getenv('PAPPERS_IMMOBILIER_TOKEN')
API_PARCELLES      = "https://api-immobilier.pappers.fr/v1/parcelles"
API_DATAGOUV       = "https://recherche-entreprises.api.gouv.fr/search"

# Filtres catégories juridiques (sociétés patrimoniales, foncières, gérance)
CATEGORIES_JURIDIQUES = {str(c) for c in [
    1000, 2110, 2120, 2210, 2220, 2310, 2320, 2385, 2400, 2900,
    3110, 3120, 3205, 3210, 3220, 3290, 5202, 5203, 5306, 5307,
    5308, 5309, 5310, 5370, 5385, 5426, 5430, 5431, 5432, 5442,
    5443, 5451, 5453, 5454, 5455, 5458, 5459, 5460, 5470, 5485,
    5499, 5710, 5785, 6521, 6539, 6540, 6541, 6599, 6901
]}

# ── Utils ─────────────────────────────────────────────────────────────────────
def normalize_siren(value) -> Optional[str]:
    if not value or (isinstance(value, float) and pd.isna(value)):
        return None
    digits = re.sub(r"\D", "", str(value))
    return digits.zfill(9) if digits else None


def flatten_company(company: dict) -> dict:
    """Retourne les champs société Registers depuis une réponse data.gouv."""
    if not company:
        return {}
    siege      = company.get('siege') or {}
    coords     = siege.get('coordonnees') or {}
    lat        = siege.get('latitude') or (coords.get('lat') if isinstance(coords, dict) else None)
    lon        = siege.get('longitude') or (coords.get('lon') if isinstance(coords, dict) else None)
    dirigeants = company.get('dirigeants') or []
    premier    = dirigeants[0] if dirigeants else {}
    return {
        'nom_complet':           company.get('nom_complet'),
        'nom_raison_sociale':    company.get('nom_raison_sociale'),
        'sigle':                 company.get('sigle'),
        'nature_juridique':      company.get('nature_juridique'),
        'activite_principale':   company.get('activite_principale'),
        'section_activite':      company.get('section_activite_principale'),
        'etat_administratif':    company.get('etat_administratif'),
        'date_creation':         company.get('date_creation'),
        'date_fermeture':        company.get('date_fermeture'),
        'categorie_entreprise':  company.get('categorie_entreprise'),
        'caractere_employeur':   company.get('caractere_employeur'),
        'tranche_effectif':      company.get('tranche_effectif_salarie'),
        'nombre_etablissements': company.get('nombre_etablissements'),
        'nombre_etab_ouverts':   company.get('nombre_etablissements_ouverts'),
        'siege_siret':           siege.get('siret'),
        'siege_adresse':         siege.get('adresse'),
        'siege_code_postal':     siege.get('code_postal'),
        'siege_commune':         siege.get('libelle_commune'),
        'siege_departement':     siege.get('departement'),
        'siege_region':          siege.get('region'),
        'siege_latitude':        lat,
        'siege_longitude':       lon,
        'siege_geo_adresse':     siege.get('geo_adresse'),
        'nombre_dirigeants':     len(dirigeants),
        'dirigeant_nom':         premier.get('nom') or premier.get('nom_complet'),
        'dirigeant_prenom':      premier.get('prenom'),
        'dirigeant_qualite':     premier.get('qualite') or premier.get('titre'),
    }


# ── Étape 1 : parcelles Pappers Immobilier ────────────────────────────────────
def fetch_parcelles() -> list:
    if not PAPPERS_IMMO_TOKEN:
        print("Erreur : PAPPERS_IMMOBILIER_TOKEN absent du .env")
        sys.exit(1)

    if PARCELLES_FILE.exists():
        print(f"Cache trouvé : {PARCELLES_FILE.name} — chargement sans rappel API")
        with open(PARCELLES_FILE, encoding='utf-8') as f:
            return json.load(f)

    print(f"Appel Pappers Immobilier — code_postal={CODE_POSTAL} proprietaires={NB_PROPRIO_MIN}-{NB_PROPRIO_MAX}…")
    toutes = []
    page   = 1

    while True:
        resp = requests.get(API_PARCELLES, params={
            'api_token':               PAPPERS_IMMO_TOKEN,
            'code_postal':             CODE_POSTAL,
            'bases':                   'proprietaires',
            'nombre_proprietaires_min': NB_PROPRIO_MIN,
            'nombre_proprietaires_max': NB_PROPRIO_MAX,
            'par_page':                100,
            'page':                    page,
        }, timeout=30)

        if resp.status_code != 200:
            print(f"Erreur Pappers Immobilier HTTP {resp.status_code}")
            sys.exit(1)

        data  = resp.json()
        batch = data.get('resultats', [])
        toutes.extend(batch)
        total = data.get('total', 0)
        print(f"  Page {page} — {len(toutes)}/{total}")

        if len(toutes) >= total or not batch:
            break
        page += 1
        time.sleep(0.3)

    # Filtre local par catégorie juridique (ignoré en mode particuliers proprietaire=0)
    if NB_PROPRIO_MAX == "0":
        parcelles = toutes
        print(f"{len(parcelles)} parcelles (mode particuliers — filtre catégorie ignoré)")
    else:
        parcelles = []
        for p in toutes:
            props = [
                pr for pr in (p.get('proprietaires') or [])
                if str(pr.get('categorie_juridique', '')) in CATEGORIES_JURIDIQUES
            ]
            if props:
                parcelles.append({**p, 'proprietaires': props})
        print(f"{len(parcelles)}/{len(toutes)} parcelles retenues après filtre catégorie juridique")

    with open(PARCELLES_FILE, 'w', encoding='utf-8') as f:
        json.dump(parcelles, f, ensure_ascii=False, indent=2)

    return parcelles


# ── Étape 2 : enrichissement data.gouv ───────────────────────────────────────
def enrich_datagouv(sirens: list) -> dict:
    if DATAGOUV_CACHE.exists():
        print(f"Cache trouvé : {DATAGOUV_CACHE.name} — chargement sans rappel API")
        with open(DATAGOUV_CACHE, encoding='utf-8') as f:
            return json.load(f)

    print(f"Appel data.gouv pour {len(sirens)} SIRENs…")
    cache = {}

    for i, siren in enumerate(sirens, 1):
        try:
            resp = requests.get(API_DATAGOUV, params={'q': siren, 'per_page': 1}, timeout=10)
            if resp.status_code == 200:
                results = resp.json().get('results', [])
                if results:
                    cache[siren] = results[0]
            else:
                print(f"  [{i}/{len(sirens)}] {siren} — HTTP {resp.status_code}")
        except Exception as e:
            print(f"  [{i}/{len(sirens)}] {siren} — erreur : {e}")

        if i % 50 == 0:
            print(f"  {i}/{len(sirens)} traités…")
        time.sleep(DELAI_APPELS)

    with open(DATAGOUV_CACHE, 'w', encoding='utf-8') as f:
        json.dump(cache, f, ensure_ascii=False, indent=2)

    print(f"{len(cache)}/{len(sirens)} trouvés sur data.gouv")
    return cache


# ── Étape 3 : fusion Registers ────────────────────────────────────────────────
def build_fusion(parcelles: list, datagouv: dict) -> pd.DataFrame:
    rows = []
    for p in parcelles:
        base = {
            'parcelle_numero':       p.get('numero'),
            'parcelle_adresse':      p.get('adresse'),
            'parcelle_section':      p.get('section'),
            'parcelle_numero_plan':  p.get('numero_plan'),
            'parcelle_contenance':   p.get('contenance'),
            'parcelle_code_commune': p.get('code_commune'),
            'parcelle_commune':      p.get('commune'),
        }
        for prop in (p.get('proprietaires') or []):
            siren = normalize_siren(prop.get('siren'))
            if not siren:
                continue
            rows.append({
                **base,
                'parcelle_nb_locaux':    len(prop.get('locaux') or []),
                'siren':                 siren,
                'monoproprietaire':      prop.get('monoproprietaire'),
                'capital':               prop.get('capital'),
                'devise_capital':        prop.get('devise_capital'),
                'lmnp':                  prop.get('lmnp'),
                'proprietaire_occupant': prop.get('proprietaire_occupant'),
                **flatten_company(datagouv.get(siren, {})),
            })
    return pd.DataFrame(rows)


# ── Main ──────────────────────────────────────────────────────────────────────
def main():
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    print(f"\n=== Registers — {CODE_POSTAL} ===\n")

    # 1. Parcelles
    parcelles = fetch_parcelles()
    if not parcelles:
        print("Aucune parcelle trouvée.")
        sys.exit(0)

    # Extraction SIRENs uniques
    sirens = sorted({
        normalize_siren(pr.get('siren'))
        for p in parcelles
        for pr in (p.get('proprietaires') or [])
        if pr.get('siren')
    } - {None})

    with open(SIRENS_FILE, 'w') as f:
        f.write('\n'.join(sirens))
    print(f"{len(sirens)} SIRENs uniques -> {SIRENS_FILE.name}")

    if not sirens:
        print("Aucun SIREN (proprietaires particuliers uniquement) — arrêt après PARCELLESDERETOUR.json")
        sys.exit(0)

    # 2. Enrichissement data.gouv
    datagouv = enrich_datagouv(sirens)

    # 3. Fusion
    df = build_fusion(parcelles, datagouv)
    enrichis = df['nom_complet'].notna().sum() if 'nom_complet' in df.columns else 0
    print(f"\n{len(df)} lignes | {enrichis} enrichies via data.gouv")

    # Export CSV
    df.to_csv(OUTPUT_CSV, index=False, encoding='utf-8-sig')
    print(f"CSV    : {OUTPUT_CSV}")

    # Export Excel (3 feuilles)
    non_enrichis = df[df['nom_complet'].isna()]
    with pd.ExcelWriter(OUTPUT_XLSX, engine='openpyxl') as writer:
        df.to_excel(writer, index=False, sheet_name='fusion')
        if not non_enrichis.empty:
            non_enrichis.to_excel(writer, index=False, sheet_name='non_enrichis')
    print(f"Excel  : {OUTPUT_XLSX}")

    print(f"\n=== Terminé : {CODE_POSTAL} — {len(parcelles)} parcelles, {len(sirens)} sociétés ===")


if __name__ == "__main__":
    main()
