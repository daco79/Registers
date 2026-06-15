# Modèle — Entreprise

## Clé primaire conseillée

`entreprise.siren`

## Champs d’identité

| Champ | Type attendu | Commentaire |
|---|---|---|
| `siren` | string | 9 chiffres |
| `siren_formate` | string | Format lisible |
| `nom_entreprise` | string | Nom consolidé |
| `personne_morale` | boolean | Société ou personne physique |
| `denomination` | string/null | Dénomination morale |
| `nom` | string/null | Personne physique |
| `prenom` | string/null | Personne physique |
| `sexe` | string/null | Personne physique |
| `diffusable` | boolean | Diffusion Sirene |
| `opposition_utilisation_commerciale` | boolean | Opposition usage commercial |

## Activité

| Champ | Commentaire |
|---|---|
| `code_naf` | Code activité principale |
| `libelle_code_naf` | Libellé NAF |
| `nomenclature_code_naf` | Nomenclature |
| `domaine_activite` | Domaine agrégé |
| `objet_social` | Objet social RCS |
| `conventions_collectives` | Liste éventuelle |

## Statut juridique

| Champ | Commentaire |
|---|---|
| `categorie_juridique` | Code INSEE |
| `forme_juridique` | Forme lisible |
| `micro_entreprise` | Booléen |
| `forme_exercice` | Forme d’exercice |
| `statut_rcs` | Inscrit, radié, etc. |
| `statut_rne` | Statut RNE |
| `statut_consolide` | Statut agrégé |
| `associe_unique` | Booléen éventuel |
| `societe_a_mission` | Booléen |
| `economie_sociale_solidaire` | Booléen |

## Dates importantes

| Champ | Commentaire |
|---|---|
| `date_creation` | Date de création |
| `date_creation_formate` | Format FR |
| `date_cessation` | Date de cessation |
| `date_reouverture` | Date de réouverture |
| `date_immatriculation_rcs` | RCS |
| `date_premiere_immatriculation_rcs` | Première immatriculation |
| `date_radiation_rcs` | Radiation |
| `date_immatriculation_rne` | RNE |
| `date_radiation_rne` | RNE |
| `date_debut_activite` | Début activité |
| `date_debut_premiere_activite` | Première activité |
| `dernier_traitement` | Dernier traitement |
| `derniere_mise_a_jour_sirene` | Source Sirene |
| `derniere_mise_a_jour_rcs` | Source RCS |

## Capital

| Champ | Commentaire |
|---|---|
| `capital` | Montant numérique |
| `capital_formate` | Format lisible |
| `capital_actuel_si_variable` | Capital variable |
| `devise_capital` | EUR, etc. |

## Relations majeures

- [[Schema - Etablissements]]
- [[Schema - Dirigeants et representants]]
- [[Schema - Comptes et finances]]
- [[Schema - Actes, comptes, BODACC]]
- [[Schema - Procedures, sanctions et signaux faibles]]
- [[../04_Immobilier/Schema - Parcelles]]
