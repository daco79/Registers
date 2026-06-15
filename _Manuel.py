# Il faut mettre le fichier Parcelles

import json
import pandas as pd
import re
import requests
from pathlib import Path
from typing import Optional
import os

# === CONFIG ===
PARCELLES_FILE = Path("PARCELLESDERETOUR.json")  # JSON généré par _automatique.py
SIREN_FILE = Path("sirens_m.txt")               # liste de siren extraits
ENTREPRISES_FILE = Path("entreprises_m.json")   # JSON sauvegardé des réponses API
OUTPUT_XLSX = Path("fusion_finale_m.xlsx")      # Excel final fusionné

API_KEY = os.environ.get("PAPPERS_API_KEY")
API_URL = "https://api.pappers.fr/v2/entreprise"

# Colonnes attendues dans l'Excel final
COLONNES_FINALES = [
    "siren","nom_entreprise","denomination","prenom","nom",
    "siege.numero_voie","siege.indice_repetition","siege.type_voie",
    "siege.libelle_voie","siege.complement_adresse",
    "siege.adresse_ligne_1","siege.adresse_ligne_2",
    "siege.code_postal","siege.ville","siege.pays","adresse_siege"
]


# --- UTILS ---

def require_api_key() -> str:
    if not API_KEY:
        raise RuntimeError("Variable d'environnement PAPPERS_API_KEY manquante.")
    return API_KEY

def normalize_siren(value) -> Optional[str]:
    """Nettoie le siren, garde 9 chiffres uniquement."""
    if pd.isna(value):
        return None
    digits = re.sub(r"\D", "", str(value))
    return digits.zfill(9) if digits else None


def load_json_file(path: Path):
    with open(path, encoding="utf-8") as f:
        return json.load(f)


def save_json_file(data, path: Path):
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)


# --- PARCELLES ---

CATEGORIES_JURIDIQUES = set(str(c) for c in [
    1000, 2110, 2120, 2210, 2220, 2310, 2320, 2385, 2400, 2900,
    3110, 3120, 3205, 3210, 3220, 3290, 5202, 5203, 5306, 5307,
    5308, 5309, 5310, 5370, 5385, 5426, 5430, 5431, 5432, 5442,
    5443, 5451, 5453, 5454, 5455, 5458, 5459, 5460, 5470, 5485,
    5499, 5710, 5785, 6521, 6539, 6540, 6541, 6599, 6901
])


def extract_parcelles(json_data) -> pd.DataFrame:
    """Extrait siren + adresse depuis le JSON des parcelles, filtrés par catégorie juridique."""
    records = []
    for parcelle in json_data:
        adresse = parcelle.get("adresse", "").strip().lower()
        for prop in parcelle.get("proprietaires", []):
            if str(prop.get("categorie_juridique", "")) not in CATEGORIES_JURIDIQUES:
                continue
            siren = normalize_siren(prop.get("siren"))
            if siren:
                records.append({"siren": siren, "adresse": adresse})
    return pd.DataFrame(records).drop_duplicates()


# --- API ENTREPRISES ---

def fetch_api_for_siren(siren: str):
    """Appelle l'API Pappers pour un SIREN donné."""
    try:
        resp = requests.get(API_URL, params={"api_token": require_api_key(), "siren": siren}, timeout=10)
        if resp.status_code == 200:
            return resp.json()
        else:
            print(f"⚠️ Erreur API {siren}: {resp.status_code}")
            return None
    except Exception as e:
        print(f"⚠️ Exception API {siren}: {e}")
        return None


def fetch_all_api(sirens, save_path: Path):
    """Boucle sur tous les SIREN et sauvegarde le JSON brut."""
    results = []
    for s in sirens:
        data = fetch_api_for_siren(s)
        if data:
            results.append(data)
    save_json_file(results, save_path)
    return results


# --- ENTREPRISES ---

def prepare_entreprises(api_results) -> pd.DataFrame:
    """Normalise les résultats API et garde les colonnes utiles."""
    if not api_results:
        return pd.DataFrame(columns=COLONNES_FINALES)

    df = pd.json_normalize(api_results)

    if "siren" not in df.columns:
        return pd.DataFrame(columns=COLONNES_FINALES)

    df["siren"] = df["siren"].apply(normalize_siren)

    # Adresse simplifiée
    if "siege.adresse_ligne_1" in df.columns:
        df["adresse_siege"] = (
            df["siege.adresse_ligne_1"].fillna("").astype(str).str.strip().str.lower()
            + " " + df.get("siege.code_postal", "").fillna("").astype(str)
            + " " + df.get("siege.ville", "").fillna("").astype(str).str.lower()
        )
    else:
        df["adresse_siege"] = ""

    colonnes_presentes = [c for c in COLONNES_FINALES if c in df.columns]
    if "adresse_siege" not in colonnes_presentes:
        colonnes_presentes.append("adresse_siege")

    return df[colonnes_presentes].drop_duplicates(subset=["siren", "adresse_siege"])


# --- MAIN ---

def main():
    # Charger parcelles
    parcelles_data = load_json_file(PARCELLES_FILE)
    df_parcelles = extract_parcelles(parcelles_data)

    # Sauvegarder liste SIREN
    df_parcelles["siren"].dropna().drop_duplicates().to_csv(SIREN_FILE, index=False, header=False)
    print(f"✅ Sirens extraits -> {SIREN_FILE}")

    # Charger ou appeler l'API
    if ENTREPRISES_FILE.exists():
        print("📂 Chargement du fichier entreprises JSON déjà existant...")
        api_results = load_json_file(ENTREPRISES_FILE)
    else:
        print("🌍 Appels API en cours...")
        sirens = df_parcelles["siren"].dropna().unique().tolist()
        api_results = fetch_all_api(sirens, ENTREPRISES_FILE)
        print(f"✅ Données API sauvegardées -> {ENTREPRISES_FILE}")

    # Préparer entreprises
    df_entreprises = prepare_entreprises(api_results)

    # Fusion
    fusion = pd.merge(
        df_parcelles,
        df_entreprises,
        on="siren",
        how="outer",
        suffixes=("_parcelles", "_entreprise"),
        indicator=True
    ).sort_values(by="siren")

    # Séparer les cas
    fusion_complete = fusion[fusion["_merge"] == "both"].drop(columns=["_merge"])
    seulement_parcelles = fusion[fusion["_merge"] == "left_only"].drop(columns=["_merge"])
    seulement_entreprises = fusion[fusion["_merge"] == "right_only"].drop(columns=["_merge"])

    # Export Excel
    with pd.ExcelWriter(OUTPUT_XLSX, engine="openpyxl") as writer:
        fusion_complete.to_excel(writer, index=False, sheet_name="fusion")
        seulement_parcelles.to_excel(writer, index=False, sheet_name="seulement_parcelles")
        seulement_entreprises.to_excel(writer, index=False, sheet_name="seulement_entreprises")

    print("✅ Fichier Excel généré :", OUTPUT_XLSX.resolve())


if __name__ == "__main__":
    main()
