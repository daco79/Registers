# Schéma — `recherche-entreprises`

## Usage

Recherche d’entreprises françaises par critères.

## Entrées clés

### Pagination

| Paramètre | Commentaire |
|---|---|
| `page` | Pagination classique |
| `par_page` | Nombre de résultats |
| `curseur` | Alternative pour parcourir au-delà de la limite de pagination |
| `par_curseur` | Taille page curseur, jusqu’à 1000 |

## Filtres principaux

### Localisation

- `departement`
- `code_postal`
- `siege`
- `region`

### Activité

- `code_naf`
- `objet_social`
- `convention_collective`

### Juridique

- `categorie_juridique`
- `entreprise_cessee`
- `statut_rcs`
- `micro_entrepreneur`
- `entreprise_cotee_bourse`

### Dates

- `date_creation_min/max`
- `date_immatriculation_rcs_min/max`
- `date_radiation_rcs_min/max`
- `date_depot_document_min/max`
- `date_publication_min/max`
- `date_prise_de_poste_min/max`

### Finances

- `chiffre_affaires_min/max`
- `resultat_min/max`
- `capital_min/max`
- `annee_finances`
- nombreux ratios : marge, BFR, trésorerie, dettes, liquidité, rentabilité, fonds propres, valeur ajoutée, salaires.

### Dirigeants

- `type_dirigeant`
- `qualite_dirigeant`
- `nom_dirigeant`
- `prenom_dirigeant`
- `nationalite_dirigeant`
- `sexe_dirigeant`
- `age_dirigeant_min/max`
- `nb_dirigeants_min/max`
- `exclure_commissaires_aux_comptes`

### Bénéficiaires

- `age_beneficiaire_min/max`
- `nationalite_beneficiaire`
- dates de naissance bénéficiaire.

## Champs de sortie principaux

`return_fields` est obligatoire dans le schéma MCP observé.  
Champs utiles minimum :

```json
[
  "siren",
  "nom_entreprise",
  "personne_morale",
  "denomination",
  "forme_juridique",
  "date_creation",
  "code_naf",
  "libelle_code_naf",
  "siege",
  "effectif",
  "chiffre_affaires",
  "resultat",
  "annee_finances"
]
```

## Recommandation économie crédits

Toujours commencer par peu de champs, puis appeler `informations-entreprise` uniquement sur les SIREN intéressants.
