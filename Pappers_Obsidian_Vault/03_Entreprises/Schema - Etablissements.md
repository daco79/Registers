# Schéma — Établissements

## Source

- `informations-entreprise.siege`
- `informations-entreprise.etablissements[]`

## Clé primaire conseillée

`siret`

## Structure observée

| Champ | Commentaire |
|---|---|
| `type_etablissement` | Type |
| `siret` | Identifiant établissement |
| `siret_formate` | Lisible |
| `nic` | Numéro interne classement |
| `numero_voie` | Adresse |
| `indice_repetition` | Adresse |
| `type_voie` | Rue, avenue, route |
| `libelle_voie` | Nom voie |
| `complement_adresse` | Complément |
| `adresse_ligne_1` | Adresse normalisée |
| `adresse_ligne_2` | Complément |
| `code_postal` | CP |
| `ville` | Ville |
| `pays` | Pays |
| `code_pays` | Code pays |
| `latitude` / `longitude` | Coordonnées parfois à 0 |
| `code_naf` | Activité établissement |
| `libelle_code_naf` | Libellé |
| `etablissement_employeur` | Booléen |
| `effectif` | Libellé |
| `effectif_min/max` | Bornes |
| `tranche_effectif` | Code |
| `annee_effectif` | Année |
| `date_de_creation` | Création établissement |
| `etablissement_cesse` | Booléen |
| `date_cessation` | Cessation |
| `siege` | Booléen |
| `enseigne` | Enseigne |
| `nom_commercial` | Nom commercial |
| `predecesseurs[]` | Historique |
| `successeurs[]` | Historique |
| `consolidation_diffusion_partielle` | Adresse enrichie |

## Sous-objet `predecesseurs` / `successeurs`

```json
{
  "siret": "...",
  "date": "YYYY-MM-DD",
  "transfert_siege": false,
  "continuite_economique": true
}
```

## Sous-objet `consolidation_diffusion_partielle`

Contient souvent :

- adresse consolidée ;
- `code_commune`;
- `departement`;
- `code_departement`;
- `region`;
- `code_region`;
- enseigne RNE éventuelle.
