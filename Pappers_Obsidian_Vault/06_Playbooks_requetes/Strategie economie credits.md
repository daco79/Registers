# Stratégie économie crédits

## Principe

Ne jamais commencer par une fiche complète massive si une recherche légère suffit.

## Ordre recommandé — Entreprise

1. `sirenisateur` si le SIREN est inconnu.
2. `recherche-entreprises` avec `return_fields` minimum.
3. `informations-entreprise` uniquement sur les SIREN utiles.
4. `comptes-entreprise` uniquement si les finances de la fiche sont insuffisantes.
5. `lire-documents` uniquement sur les tokens nécessaires.
6. `cartographie-entreprise` uniquement pour analyse relationnelle.

## Ordre recommandé — Immobilier

1. `recherche-lieux` pour sécuriser code commune / adresse / coordonnées.
2. `recherche-parcelles` avec `return_fields` minimum.
3. Ajouter progressivement ventes, DPE, copro, urbanisme.
4. Appeler `informations-entreprise` sur les propriétaires/occupants seulement si nécessaire.

## Champs à éviter par défaut

- `scoring_financier`
- `scoring_non_financier`
- champs supplémentaires emails/téléphones/sites
- lecture complète des documents
- bilan complet sauf besoin comptable réel

## Profils de requêtes

### Ultra léger entreprise

```json
{
  "siren": "...",
  "return_fields": [
    "siren",
    "nom_entreprise",
    "forme_juridique",
    "code_naf",
    "siege",
    "statut_rcs"
  ]
}
```

### Entreprise due diligence

```json
{
  "siren": "...",
  "return_fields": [
    "siren",
    "nom_entreprise",
    "forme_juridique",
    "code_naf",
    "siege",
    "representants",
    "finances",
    "procedures_collectives",
    "procedure_collective_en_cours",
    "sanctions",
    "publications_bodacc"
  ]
}
```

### Parcelle légère

```json
{
  "parcelle_cadastrale": "...",
  "return_fields": [
    "numero",
    "adresse",
    "contenance",
    "proprietaires_siren",
    "proprietaires_nom_entreprise"
  ]
}
```

### Parcelle investisseur

```json
{
  "adresse": "...",
  "code_commune": "...",
  "return_fields": [
    "numero",
    "adresse",
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
  ]
}
```
