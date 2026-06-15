# Schéma — `recherche-parcelles`

## Usage

Recherche dans la base Pappers Immobilier.

## Entrées de localisation

| Filtre | Commentaire |
|---|---|
| `parcelle_cadastrale` | Parcelle exacte |
| `adresse` | Adresse sans code postal ni commune |
| `code_postal` | Code postal |
| `code_commune` | Code INSEE |
| `nom_commune` | Commune exacte |
| `departement` | Département |
| `region` | Région |
| `latitude` / `longitude` / `distance` | Recherche géographique |

## Filtres propriétaires

- `siren_proprietaire`
- `denomination_proprietaire`
- `nom_complet_proprietaire`
- `code_naf_proprietaire`
- `categorie_juridique_proprietaire`
- `tranche_effectif_proprietaire_min/max`
- `monoproprietaire_proprietaire`
- `lmnp_proprietaire`
- `proprietaire_occupant`
- `age_proprietaire_min/max`
- `en_activite_proprietaire`
- `type_procedure_collective_proprietaire`
- `scoring_financier_proprietaire_min/max`
- `chiffre_affaires_proprietaire_min/max`

## Filtres occupants

- `siren_occupant`
- `denomination_occupant`
- `nom_complet_occupant`
- `code_naf_occupant`
- `categorie_juridique_occupant`
- `tranche_effectif_occupant_min/max`
- `age_occupant_min/max`
- `date_entree_lieux_min/max`
- `en_activite_occupant`
- `type_procedure_collective_occupant`
- `scoring_financier_occupant_min/max`
- `chiffre_affaires_occupant_min/max`

## Filtres ventes

- `nature_vente`
- `type_local_vente`
- `date_vente_min/max`
- `prix_vente_min/max`
- `surface_bati_vente_min/max`
- `nombre_pieces_vente_min/max`
- `surface_terrain_vente_min/max`

## Filtres bâtiment / DPE

- `annee_construction_batiment_min/max`
- `nombre_logements_batiment_min/max`
- `surface_batiment_min/max`
- `usage_batiment`
- `nature_batiment`
- `classe_bilan_dpe`
- `type_energie_chauffage_dpe`
- `type_batiment_dpe`
- `type_installation_chauffage_dpe`
- `date_reception_dpe_min/max`

## Filtres urbanisme

- `type_zone_urbanisme`
- `libelle_zone_urbanisme`
- `type_document_urbanisme`
- `titre_document_urbanisme`
- `date_approbation_zone_urbanisme_min/max`

## Filtres copropriété

- `type_syndic_copropriete`
- `nom_copropriete`
- `nombre_lots_copropriete_min/max`
- `periode_construction_copropriete`

## Filtres permis / fonds / aménagements

- `statut_permis`
- `type_permis`
- `date_autorisation_permis_min/max`
- `prix_fonds_de_commerce_min/max`
- `date_fonds_de_commerce_min/max`
- `type_amenagement`
- `surface_amenagement_min/max`

## Filtres de comptage

Permettent de ne garder que les parcelles avec certaines entités :

- `nombre_proprietaires_min/max`
- `nombre_batiments_min/max`
- `nombre_ventes_min/max`
- `nombre_occupants_min/max`
- `nombre_fonds_de_commerce_min/max`
- `nombre_permis_min/max`
- `nombre_coproprietes_min/max`
- `nombre_documents_urbanisme_min/max`
- `nombre_amenagements_min/max`

## Champs supplémentaires

`champs_supplementaires` permet de récupérer emails, téléphones, sites, liens sociaux, personnes physiques, représentants, etc.

À utiliser seulement si nécessaire.
