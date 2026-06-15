# Playbook — Entreprise, fiche complète minimale

## Objectif

Obtenir une fiche entreprise exploitable sans demander tous les champs.

## Étape 1 — Trouver le SIREN

Si SIREN inconnu :

```json
{
  "country_code": "FR",
  "company_name": "NOM ENTREPRISE",
  "company_city": "VILLE",
  "return_fields": [
    "company_number",
    "company_name",
    "company_postal_code",
    "company_city",
    "company_activity"
  ]
}
```

## Étape 2 — Fiche ciblée

```json
{
  "siren": "XXXXXXXXX",
  "return_fields": [
    "siren",
    "nom_entreprise",
    "personne_morale",
    "denomination",
    "code_naf",
    "libelle_code_naf",
    "domaine_activite",
    "date_creation",
    "entreprise_cessee",
    "categorie_juridique",
    "forme_juridique",
    "effectif",
    "capital",
    "statut_rcs",
    "siege",
    "etablissements",
    "finances",
    "representants",
    "depots_actes",
    "comptes",
    "publications_bodacc",
    "procedures_collectives",
    "procedure_collective_existe",
    "procedure_collective_en_cours",
    "parcelles_detenues",
    "sanctions",
    "observations"
  ]
}
```

## Étape 3 — Documents

Lire seulement les tokens nécessaires :

```json
{
  "documentIds": ["TOKEN"]
}
```

## Sortie recommandée

- résumé juridique ;
- dirigeants ;
- établissements ;
- chiffres clés ;
- risques ;
- documents disponibles ;
- liens immobiliers.
