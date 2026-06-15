# Modèle — Parcelle

## Clé primaire conseillée

`numero` ou `parcelle_cadastrale`.

## Champs de base

| Champ | Commentaire |
|---|---|
| `numero` | Identifiant parcelle |
| `section` | Section cadastrale |
| `prefixe` | Préfixe |
| `numero_plan` | Numéro de plan |
| `adresse` | Adresse |
| `code_commune` | Code INSEE |
| `commune` | Commune |
| `code_departement` | Département |
| `departement` | Nom département |
| `code_region` | Région |
| `region` | Nom région |
| `codes_postaux` | Codes postaux |
| `contenance` | Surface cadastrale en m² |
| `arpente` | Booléen |
| `bounding_box` | Champ supplémentaire possible |

## Relations

- [[Schema - Proprietaires et occupants]]
- [[Schema - Ventes]]
- [[Schema - Batiments et DPE]]
- [[Schema - Coproprietes]]
- [[Schema - Permis, fonds de commerce, urbanisme]]
