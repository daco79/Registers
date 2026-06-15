# Schéma — `recherche-lieux`

## Usage

Rechercher une adresse, une rue, une commune ou une parcelle pour obtenir des coordonnées et codes administratifs.

## Entrées

| Paramètre | Commentaire |
|---|---|
| `q` | Texte libre : adresse, rue, commune, parcelle |
| `index` | `address`, `poi`, `parcel` |
| `limit` | Max 50 |
| `lat` / `lon` | Favoriser les résultats proches |
| `type` | `adresse`, `rue`, `lieu-dit`, `ville` |
| `return_fields` | Limiter les champs |

## Champs de sortie

| Champ | Commentaire |
|---|---|
| `latitude` | Latitude |
| `longitude` | Longitude |
| `label` | Libellé |
| `code_postal` | Code postal |
| `code_commune` | Code INSEE commune |
| `ville` | Ville |
| `contexte` | Département / région |
| `type` | Type de résultat |
| `rue` | Rue |
| `numero` | Numéro |
| `municipalite` | Municipalité |

## Playbook

1. Utiliser `recherche-lieux` pour sécuriser `code_commune`, `code_postal`, coordonnées.
2. Appeler `recherche-parcelles` avec des critères propres.
