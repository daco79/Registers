# Schéma — Bâtiments et DPE

## Bâtiments

Source : `recherche-parcelles.batiments[]`

| Champ | Commentaire |
|---|---|
| `parcelle_principale` | Booléen |
| `batiment_groupe_id` | Identifiant bâtiment |
| `code_epci` | EPCI |
| `surface` | Surface |
| `code_iris` | IRIS |
| `natures[]` | Nature bâtiment |
| `usages[]` | Usage |
| `etat` | En service, etc. |
| `hauteur_moyenne` | Hauteur |
| `hauteur_max` | Hauteur max |
| `altitude_moyenne_du_sol` | Altitude |
| `annee_construction` | Année |
| `materiaux_mur` | Mur |
| `materiaux_toit` | Toit |
| `nombre_logements` | Logements |

## DPE

Source : `recherche-parcelles.dpe[]`

Clé candidate : `identifiant_dpe`.

### Champs principaux

| Champ | Commentaire |
|---|---|
| `batiment_groupe_id` | Liaison bâtiment |
| `parcelle_cadastrale` | Liaison parcelle |
| `source` | ADEME, BDNB |
| `identifiant_dpe` | Identifiant |
| `arrete_2021` | Booléen |
| `date_etablissement_dpe` | Date |
| `date_reception_dpe` | Date |
| `type_dpe` | Méthode |
| `classe_bilan_dpe` | A-G |
| `classe_emission_ges` | A-G |
| `type_batiment_dpe` | Appartement, maison, immeuble |
| `surface_habitable_logement` | Surface |
| `surface_habitable_immeuble` | Immeuble |
| `conso_5_usages_ep_m2` | Conso 2021 |
| `emission_ges_5_usages_m2` | GES |
| `type_energie_chauffage` | Gaz, électricité, etc. |
| `type_installation_chauffage` | Individuel/collectif |
| `type_installation_ecs` | ECS |
| `type_ventilation` | Ventilation |
| `deperdition_mur` | Déperdition |
| `deperdition_baie_vitree` | Déperdition |
| `deperdition_pont_thermique` | Déperdition |
| `periode_construction_dpe` | Période |

## Variabilité forte

Les DPE anciens et nouveaux n’ont pas les mêmes champs :

- arrêté 2012 : `classe_conso_energie_arrete_2012`, `conso_3_usages_ep_m2_arrete_2012`;
- arrêté 2021 : `classe_bilan_dpe`, `conso_5_usages_ep_m2`.

Il faut donc stocker les DPE avec un JSON brut en complément des champs normalisés.
