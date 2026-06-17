"""
_Manuel.py — enrichissement entreprises via Registers (data.gouv)
Source : Export/Pappers_entreprises_75011.json (propriétaires=1 du 75011)
Sortie : Export/entreprises_75011_registers.xlsx
"""

import json
import time
import requests
import pandas as pd
from pathlib import Path

# ── Config ────────────────────────────────────────────────────────────────────
BASE_DIR      = Path(__file__).parent
INPUT_FILE    = BASE_DIR / "Export" / "Pappers_entreprises_75011.json"
CACHE_FILE    = BASE_DIR / "Export" / "entreprises_75011_datagouv_raw.json"
OUTPUT_XLSX   = BASE_DIR / "Export" / "entreprises_75011_registers.xlsx"

API_URL       = "https://recherche-entreprises.api.gouv.fr/search"
DELAY_SECONDS = 0.15   # pause entre appels pour ne pas saturer l'API

# ── Catégories juridiques cibles (même filtre que l'original) ────────────────
CATEGORIES_CIBLES = {str(c) for c in [
    1000, 2110, 2120, 2210, 2220, 2310, 2320, 2385, 2400, 2900,
    3110, 3120, 3205, 3210, 3220, 3290, 5202, 5203, 5306, 5307,
    5308, 5309, 5310, 5370, 5385, 5426, 5430, 5431, 5432, 5442,
    5443, 5451, 5453, 5454, 5455, 5458, 5459, 5460, 5470, 5485,
    5499, 5710, 5785, 6521, 6539, 6540, 6541, 6599, 6901
]}

# ── Lecture JSON Pappers ──────────────────────────────────────────────────────
with open(INPUT_FILE, encoding="utf-8") as f:
    pappers_data = json.load(f)

# Le JSON est un tableau direct (pas de clé resultats)
if isinstance(pappers_data, dict):
    pappers_list = list(pappers_data.values())
else:
    pappers_list = pappers_data

print(f"{len(pappers_list)} entreprises lues depuis {INPUT_FILE.name}")

# Extraire les SIRENs filtrés par catégorie juridique
sirens = []
for e in pappers_list:
    cat = str(e.get("categorie_juridique", ""))
    siren = str(e.get("siren", "")).strip().zfill(9)
    if siren and cat in CATEGORIES_CIBLES:
        sirens.append(siren)

sirens = list(dict.fromkeys(sirens))   # dédoublonnage ordre stable
print(f"{len(sirens)} SIRENs retenus après filtre catégorie juridique")

# ── Appels Registers (data.gouv) ──────────────────────────────────────────────
if CACHE_FILE.exists():
    print(f"Cache trouvé : {CACHE_FILE.name} — chargement sans rappel API")
    with open(CACHE_FILE, encoding="utf-8") as f:
        datagouv_raw = json.load(f)
else:
    print(f"Appels API data.gouv pour {len(sirens)} SIRENs…")
    datagouv_raw = {}
    for i, siren in enumerate(sirens, 1):
        try:
            resp = requests.get(API_URL, params={"q": siren, "per_page": 1}, timeout=10)
            if resp.status_code == 200:
                results = resp.json().get("results", [])
                if results:
                    datagouv_raw[siren] = results[0]
            else:
                print(f"  [{i}/{len(sirens)}] {siren} — HTTP {resp.status_code}")
        except Exception as ex:
            print(f"  [{i}/{len(sirens)}] {siren} — erreur : {ex}")
        if i % 50 == 0:
            print(f"  {i}/{len(sirens)} traités…")
        time.sleep(DELAY_SECONDS)

    with open(CACHE_FILE, "w", encoding="utf-8") as f:
        json.dump(datagouv_raw, f, ensure_ascii=False, indent=2)
    print(f"Cache sauvegardé : {CACHE_FILE.name}")

print(f"{len(datagouv_raw)} entreprises trouvées dans data.gouv")

# ── Normalisation ─────────────────────────────────────────────────────────────
def normalize(siren: str, company: dict) -> dict:
    siege = company.get("siege") or {}
    # Nom/prénom pour EI
    nom_complet = company.get("nom_complet") or ""
    est_ei = company.get("est_entrepreneur_individuel", False)
    # data.gouv ne sépare pas prénom/nom pour EI — on garde nom_complet
    return {
        "siren":                   siren,
        "nom_entreprise":          nom_complet,
        "denomination":            company.get("nom_raison_sociale"),
        "forme_juridique":         company.get("nature_juridique"),
        "code_naf":                company.get("activite_principale"),
        "libelle_naf":             company.get("libelle_activite_principale"),
        "entreprise_cessee":       (company.get("etat_administratif") == "C"),
        "date_creation":           company.get("date_creation"),
        "est_entrepreneur_individuel": est_ei,
        "est_association":         company.get("est_association", False),
        "tranche_effectif":        company.get("tranche_effectif_salarie"),
        # Siège
        "siege_siret":             siege.get("siret"),
        "siege_adresse":           siege.get("adresse"),
        "siege_code_postal":       siege.get("code_postal"),
        "siege_ville":             siege.get("libelle_commune"),
        "siege_latitude":          siege.get("latitude"),
        "siege_longitude":         siege.get("longitude"),
        # Non disponible dans data.gouv — champs Pappers non couverts
        "siege_numero_voie":       None,
        "siege_type_voie":         None,
        "siege_libelle_voie":      None,
        "siege_pays":              "France",
        # Source
        "source":                  "recherche-entreprises.api.gouv.fr",
        "trouve_datagouv":         True,
    }

rows_found    = []
rows_notfound = []

for siren in sirens:
    if siren in datagouv_raw:
        rows_found.append(normalize(siren, datagouv_raw[siren]))
    else:
        rows_notfound.append({"siren": siren, "trouve_datagouv": False})

df_found    = pd.DataFrame(rows_found)
df_notfound = pd.DataFrame(rows_notfound)

# ── Export Excel ──────────────────────────────────────────────────────────────
with pd.ExcelWriter(OUTPUT_XLSX, engine="openpyxl") as writer:
    df_found.to_excel(writer, index=False, sheet_name="entreprises_registers")
    if not df_notfound.empty:
        df_notfound.to_excel(writer, index=False, sheet_name="non_trouves")

print(f"\n=== Résultat ===")
print(f"Trouvées dans data.gouv : {len(df_found)}")
print(f"Non trouvées            : {len(df_notfound)}")
print(f"Export : {OUTPUT_XLSX}")
print()
print(df_found[["siren","nom_entreprise","forme_juridique","siege_code_postal","siege_ville"]].head(10).to_string())
