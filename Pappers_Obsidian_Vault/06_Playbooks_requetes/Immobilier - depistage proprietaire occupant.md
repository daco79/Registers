# Playbook — Immobilier, dépistage propriétaire / occupant

## Objectif

Identifier propriétaire, occupant, syndic et signaux utiles sur une parcelle.

## Étape 1 — Lieu

```json
{
  "q": "10 rue Ordener Paris",
  "index": "address",
  "limit": 5,
  "return_fields": [
    "label",
    "code_postal",
    "code_commune",
    "ville",
    "latitude",
    "longitude"
  ]
}
```

## Étape 2 — Parcelle

```json
{
  "adresse": "10 Rue Ordener",
  "nom_commune": "Paris",
  "code_postal": "75018",
  "return_fields": [
    "numero",
    "adresse",
    "code_commune",
    "commune",
    "contenance",
    "proprietaires_siren",
    "proprietaires_nom_entreprise",
    "occupants_siren",
    "occupants_nom_entreprise",
    "ventes",
    "batiments",
    "dpe",
    "coproprietes",
    "documents_urbanisme"
  ],
  "par_page": 5
}
```

## Étape 3 — Enrichissement entreprises

Pour chaque SIREN intéressant :

```json
{
  "siren": "XXXXXXXXX",
  "return_fields": [
    "siren",
    "nom_entreprise",
    "forme_juridique",
    "code_naf",
    "siege",
    "representants",
    "finances",
    "procedures_collectives",
    "sanctions"
  ]
}
```

## Analyse à produire

- propriétaire(s) ;
- occupant(s) ;
- syndic / représentant légal ;
- mutations récentes ;
- DPE ;
- contraintes urbanisme ;
- opportunité ou alerte.
