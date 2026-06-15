import json
import time
import pandas as pd
import re
import requests
from pathlib import Path
from typing import Optional
import os


# === CONFIG A MODIFIER ===
VALEUR_PROPRIETAIRE = "5"     # nombre max de propriétaires de l'immeuble
VALEUR_POSTAL       = "94230" # code postal de la zone de recherche
VALEUR_PAGE         = "9999"     # nb de résultats — TEST: garder à 5. En prod passer à 100 (max 9999)
VALEUR_CESSATION    = "false" # inclure les entreprises cessées ?

DELAI_ENTRE_APPELS  = 0.3     # secondes entre chaque appel API entreprise (évite le throttling)

OUTPUT_XLSX        = Path("EXCEL_parcelles_entreprise_final.xlsx")
PARCELLES_FILE     = Path("PARCELLESDERETOUR.json")
ENTREPRISES_FILE   = Path("entreprises.json")
SIREN_FILE         = Path("sirens.txt")

API_KEY              = os.environ.get("PAPPERS_API_KEY")
API_URL_PARCELLE     = "https://api-immobilier.pappers.fr/v1/parcelles"
API_URL_ENTREPRISE   = "https://api.pappers.fr/v2/entreprise"

COLONNES_FINALES = [
    "siren", "nom_entreprise", "denomination", "prenom", "nom",
    "siege.numero_voie", "siege.indice_repetition", "siege.type_voie",
    "siege.libelle_voie", "siege.complement_adresse",
    "siege.adresse_ligne_1", "siege.adresse_ligne_2",
    "siege.code_postal", "siege.ville", "siege.pays", "adresse_siege"
]

CATEGORIES_JURIDIQUES = [
    1000, 2110, 2120, 2210, 2220, 2310, 2320, 2385, 2400, 2900,
    3110, 3120, 3205, 3210, 3220, 3290, 5202, 5203, 5306, 5307,
    5308, 5309, 5310, 5370, 5385, 5426, 5430, 5431, 5432, 5442,
    5443, 5451, 5453, 5454, 5455, 5458, 5459, 5460, 5470, 5485,
    5499, 5710, 5785, 6521, 6539, 6540, 6541, 6599, 6901
]


# --- UTILS ---

def require_api_key() -> str:
    if not API_KEY:
        raise RuntimeError("Variable d'environnement PAPPERS_API_KEY manquante.")
    return API_KEY

def normalize_siren(value) -> Optional[str]:
    if pd.isna(value):
        return None
    digits = re.sub(r"\D", "", str(value))
    return digits.zfill(9) if digits else None


def cleanup():
    """Supprime les fichiers intermédiaires avant chaque lancement."""
    for f in [PARCELLES_FILE, ENTREPRISES_FILE, SIREN_FILE, OUTPUT_XLSX]:
        if f.exists():
            f.unlink()
            print(f"🗑️  Supprimé : {f}")


# --- PARCELLES ---

def fetch_parcelles() -> list:
    """Récupère les parcelles et filtre localement par catégorie juridique."""
    # Le filtre categorie_juridique_proprietaire est appliqué localement
    # car passer 49 valeurs dans l'URL la tronque côté serveur et renvoie 0 résultats
    params = {
        "api_token": require_api_key(),
        "code_postal": VALEUR_POSTAL,
        "bases": "proprietaires",
        "nombre_proprietaires_min": "1",
        "nombre_proprietaires_max": VALEUR_PROPRIETAIRE,
        "par_page": VALEUR_PAGE,
    }

    req = requests.Request("GET", API_URL_PARCELLE, params=params).prepare()
    print("🌍 Appel API parcelles...")
    print(f"   URL : {req.url}")
    response = requests.Session().send(req, timeout=30)
    if response.status_code != 200:
        print("❌ Erreur API parcelles :", response.status_code, response.text)
        exit()

    toutes = response.json().get("resultats", [])

    cats_set = set(str(c) for c in CATEGORIES_JURIDIQUES)
    parcelles = []
    for p in toutes:
        props_filtres = [
            prop for prop in p.get("proprietaires", [])
            if str(prop.get("categorie_juridique", "")) in cats_set
        ]
        if props_filtres:
            parcelles.append({**p, "proprietaires": props_filtres})

    with open(PARCELLES_FILE, "w", encoding="utf-8") as f:
        json.dump(parcelles, f, ensure_ascii=False, indent=2)
    print(f"✅ {len(parcelles)}/{len(toutes)} parcelles retenues après filtre -> {PARCELLES_FILE}")
    return parcelles


def extract_sirens(parcelles: list) -> pd.DataFrame:
    """Extrait les SIRENs uniques + adresse depuis les parcelles (déjà filtrées)."""
    records = []
    for parcelle in parcelles:
        adresse = parcelle.get("adresse", "").strip().lower()
        for prop in parcelle.get("proprietaires", []):
            siren = normalize_siren(prop.get("siren"))
            if siren:
                records.append({"siren": siren, "adresse": adresse})

    if not records:
        print("⚠️  Aucun SIREN trouvé dans les parcelles.")
        print(f"   Exemple de parcelle reçue : {parcelles[0] if parcelles else 'aucune'}")
        return pd.DataFrame(columns=["siren", "adresse"])

    return pd.DataFrame(records).drop_duplicates()


# --- API ENTREPRISES ---

def fetch_entreprises(sirens: list) -> list:
    """1 appel API par SIREN. Sauvegarde progressive en cas d'interruption."""
    total = len(sirens)
    print(f"🌍 {total} SIRENs à fetcher...")

    results = []
    for i, siren in enumerate(sirens, 1):
        print(f"  ({i}/{total}) SIREN {siren}...", end=" ", flush=True)
        try:
            resp = requests.get(
                API_URL_ENTREPRISE,
                params={"api_token": require_api_key(), "siren": siren, "entreprise_cessee": VALEUR_CESSATION},
                timeout=10
            )
            if resp.status_code == 200:
                results.append(resp.json())
                print("✅")
            else:
                print(f"⚠️ HTTP {resp.status_code}")
        except Exception as e:
            print(f"⚠️ Erreur : {e}")

        with open(ENTREPRISES_FILE, "w", encoding="utf-8") as f:
            json.dump(results, f, ensure_ascii=False, indent=2)

        if i < total:
            time.sleep(DELAI_ENTRE_APPELS)

    print(f"✅ {len(results)} entreprises récupérées -> {ENTREPRISES_FILE}")
    return results


# --- PRÉPARATION ENTREPRISES ---

def prepare_entreprises(api_results: list) -> pd.DataFrame:
    if not api_results:
        return pd.DataFrame(columns=COLONNES_FINALES)

    df = pd.json_normalize(api_results)
    if "siren" not in df.columns:
        return pd.DataFrame(columns=COLONNES_FINALES)

    df["siren"] = df["siren"].apply(normalize_siren)

    if "siege.adresse_ligne_1" in df.columns:
        df["adresse_siege"] = (
            df["siege.adresse_ligne_1"].fillna("").astype(str).str.strip().str.lower()
            + " " + df.get("siege.code_postal", pd.Series(dtype=str)).fillna("").astype(str)
            + " " + df.get("siege.ville", pd.Series(dtype=str)).fillna("").astype(str).str.lower()
        )
    else:
        df["adresse_siege"] = ""

    colonnes_presentes = [c for c in COLONNES_FINALES if c in df.columns]
    if "adresse_siege" not in colonnes_presentes:
        colonnes_presentes.append("adresse_siege")

    return df[colonnes_presentes].drop_duplicates(subset=["siren", "adresse_siege"])


# --- MAIN ---

def main():
    cleanup()

    # 1. Parcelles
    parcelles = fetch_parcelles()
    df_parcelles = extract_sirens(parcelles)

    sirens_uniques = df_parcelles["siren"].dropna().unique().tolist()
    df_parcelles["siren"].dropna().drop_duplicates().to_csv(SIREN_FILE, index=False, header=False)
    print(f"✅ {len(sirens_uniques)} SIRENs uniques -> {SIREN_FILE}")

    # 2. Entreprises
    api_results = fetch_entreprises(sirens_uniques)
    df_entreprises = prepare_entreprises(api_results)

    # 3. Fusion
    fusion = pd.merge(
        df_parcelles,
        df_entreprises,
        on="siren",
        how="outer",
        suffixes=("_parcelles", "_entreprise"),
        indicator=True
    ).sort_values(by="siren")

    fusion_complete       = fusion[fusion["_merge"] == "both"].drop(columns=["_merge"])
    seulement_parcelles   = fusion[fusion["_merge"] == "left_only"].drop(columns=["_merge"])
    seulement_entreprises = fusion[fusion["_merge"] == "right_only"].drop(columns=["_merge"])

    # 4. Export Excel
    with pd.ExcelWriter(OUTPUT_XLSX, engine="openpyxl") as writer:
        fusion_complete.to_excel(writer, index=False, sheet_name="fusion")
        seulement_parcelles.to_excel(writer, index=False, sheet_name="seulement_parcelles")
        seulement_entreprises.to_excel(writer, index=False, sheet_name="seulement_entreprises")

    print(f"\n✅ Fichier Excel généré : {OUTPUT_XLSX.resolve()}")
    print(f"   - fusion complète      : {len(fusion_complete)} lignes")
    print(f"   - seulement parcelles  : {len(seulement_parcelles)} lignes")
    print(f"   - seulement entreprises: {len(seulement_entreprises)} lignes")


if __name__ == "__main__":
    main()
