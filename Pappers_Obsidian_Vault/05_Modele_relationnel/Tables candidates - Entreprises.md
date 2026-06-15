# Tables candidates — Entreprises

## `entreprises`

| Colonne | Type | Source |
|---|---|---|
| `siren` | text PK | `siren` |
| `nom_entreprise` | text | `nom_entreprise` |
| `personne_morale` | bool | `personne_morale` |
| `denomination` | text | `denomination` |
| `nom` | text | `nom` |
| `prenom` | text | `prenom` |
| `code_naf` | text | `code_naf` |
| `libelle_code_naf` | text | `libelle_code_naf` |
| `domaine_activite` | text | `domaine_activite` |
| `categorie_juridique` | text | `categorie_juridique` |
| `forme_juridique` | text | `forme_juridique` |
| `date_creation` | date | `date_creation` |
| `entreprise_cessee` | bool | `entreprise_cessee` |
| `statut_rcs` | text | `statut_rcs` |
| `statut_rne` | text | `statut_rne` |
| `capital` | numeric | `capital` |
| `devise_capital` | text | `devise_capital` |
| `effectif` | text | `effectif` |
| `raw_json` | jsonb | fiche brute |

## `etablissements`

| Colonne | Type |
|---|---|
| `siret` | text PK |
| `siren` | text FK |
| `nic` | text |
| `siege` | bool |
| `type_etablissement` | text |
| `adresse_ligne_1` | text |
| `adresse_ligne_2` | text |
| `code_postal` | text |
| `ville` | text |
| `code_commune` | text |
| `code_departement` | text |
| `code_region` | text |
| `latitude` | numeric |
| `longitude` | numeric |
| `code_naf` | text |
| `etablissement_cesse` | bool |
| `date_de_creation` | date |
| `date_cessation` | date |
| `raw_json` | jsonb |

## `representants`

| Colonne | Type |
|---|---|
| `id` | generated PK |
| `siren_entreprise` | text FK |
| `personne_morale` | bool |
| `siren_representant` | text nullable |
| `nom_complet` | text |
| `nom` | text |
| `prenom` | text |
| `denomination` | text |
| `qualite` | text |
| `qualites` | jsonb |
| `date_prise_de_poste` | date |
| `date_de_naissance_rgpd` | text |
| `date_de_naissance` | date |
| `nationalite` | text |
| `sanctions_en_cours` | bool |
| `personne_politiquement_exposee` | jsonb |
| `raw_json` | jsonb |

## `finances_annuelles`

| Colonne | Type |
|---|---|
| `siren` | text FK |
| `annee` | int |
| `date_de_cloture_exercice` | date |
| `chiffre_affaires` | numeric |
| `resultat` | numeric |
| `effectif` | numeric |
| `marge_brute` | numeric |
| `excedent_brut_exploitation` | numeric |
| `resultat_exploitation` | numeric |
| `BFR` | numeric |
| `tresorerie` | numeric |
| `dettes_financieres` | numeric |
| `fonds_propres` | numeric |
| `raw_json` | jsonb |

## `depots_documents`

| Colonne | Type |
|---|---|
| `token` | text PK |
| `siren` | text FK |
| `date_depot` | date |
| `nom_fichier_pdf` | text |
| `disponible` | bool |
| `actes` | jsonb |
| `raw_json` | jsonb |

## `publications_bodacc`

| Colonne | Type |
|---|---|
| `id` | generated PK |
| `siren` | text FK |
| `numero_parution` | text |
| `numero_annonce` | text |
| `date` | date |
| `bodacc` | text |
| `type` | text |
| `greffe` | text |
| `description` | text |
| `commentaires` | text |
| `raw_json` | jsonb |
