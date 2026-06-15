# Tables candidates — Immobilier

## `parcelles`

| Colonne | Type |
|---|---|
| `numero` | text PK |
| `section` | text |
| `prefixe` | text |
| `numero_plan` | text |
| `adresse` | text |
| `code_commune` | text |
| `commune` | text |
| `code_departement` | text |
| `departement` | text |
| `code_region` | text |
| `region` | text |
| `contenance` | numeric |
| `surface_batie` | numeric |
| `surface_disponible` | numeric |
| `raw_json` | jsonb |

## `parcelle_proprietaires`

| Colonne | Type |
|---|---|
| `id` | generated PK |
| `parcelle_numero` | text FK |
| `siren` | text nullable |
| `nom_entreprise` | text |
| `personne_physique` | bool |
| `lmnp` | bool |
| `proprietaire_occupant` | bool |
| `monoproprietaire` | bool |
| `raw_json` | jsonb |

## `parcelle_occupants`

| Colonne | Type |
|---|---|
| `id` | generated PK |
| `parcelle_numero` | text FK |
| `siren` | text nullable |
| `siret` | text nullable |
| `nom_entreprise` | text |
| `enseigne` | text |
| `date_entree_lieux` | date |
| `date_sortie_lieux` | date |
| `fiabilite_appartenance_parcelle` | text |
| `raw_json` | jsonb |

## `ventes`

| Colonne | Type |
|---|---|
| `id` | text nullable |
| `id_surrogate` | generated PK |
| `parcelle_numero` | text FK |
| `date` | date |
| `nature` | text |
| `valeur_fonciere` | numeric |
| `type_local` | text |
| `surface_reelle_bati` | numeric |
| `surface_reelle_bati_totale` | numeric |
| `surface_terrain` | numeric |
| `nombre_pieces` | int |
| `nombre_lots` | int |
| `raw_json` | jsonb |

## `vente_lots`

| Colonne | Type |
|---|---|
| `id` | generated PK |
| `vente_id_surrogate` | FK |
| `numero` | text |
| `surface_carrez` | numeric |

## `batiments`

| Colonne | Type |
|---|---|
| `batiment_groupe_id` | text PK |
| `parcelle_numero` | text FK |
| `parcelle_principale` | bool |
| `surface` | numeric |
| `annee_construction` | int |
| `nombre_logements` | int |
| `hauteur_moyenne` | numeric |
| `materiaux_mur` | text |
| `materiaux_toit` | text |
| `natures` | jsonb |
| `usages` | jsonb |
| `raw_json` | jsonb |

## `dpe`

| Colonne | Type |
|---|---|
| `identifiant_dpe` | text PK |
| `parcelle_numero` | text FK |
| `batiment_groupe_id` | text FK |
| `source` | text |
| `arrete_2021` | bool |
| `date_etablissement_dpe` | date |
| `date_reception_dpe` | date |
| `classe_bilan_dpe` | text |
| `classe_emission_ges` | text |
| `type_batiment_dpe` | text |
| `surface_habitable_logement` | numeric |
| `conso_5_usages_ep_m2` | numeric |
| `raw_json` | jsonb |

## `coproprietes`

| Colonne | Type |
|---|---|
| `numero_immatriculation` | text PK |
| `parcelle_numero` | text FK |
| `nom` | text |
| `mandat_en_cours` | text |
| `nombre_total_lots` | int |
| `nombre_lots_habitation` | int |
| `type_syndic` | text |
| `siren_syndic` | text |
| `siren_representant_legal` | text |
| `date_immatriculation` | date |
| `date_reglement_copropriete` | date |
| `date_fin_dernier_mandat` | date |
| `raw_json` | jsonb |

## `documents_urbanisme`

| Colonne | Type |
|---|---|
| `id` | text PK |
| `parcelle_numero` | text FK |
| `titre` | text |
| `nom` | text |
| `statut` | text |
| `type` | text |
| `statut_legal` | text |
| `raw_json` | jsonb |

## `zones_urbanisme`

| Colonne | Type |
|---|---|
| `id` | generated PK |
| `document_id` | text FK |
| `parcelle_numero` | text FK |
| `libelle` | text |
| `libelle_long` | text |
| `type_psc` | text |
| `stype_psc` | text |
| `type_zone` | text |
| `nom_fichier` | text |
| `raw_json` | jsonb |
