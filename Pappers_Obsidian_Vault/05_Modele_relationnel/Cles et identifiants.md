# Clés et identifiants

## Entreprise

| Identifiant | Niveau | Remarque |
|---|---|---|
| `siren` | entreprise | Clé principale |
| `siret` | établissement | `siren` + `nic` |
| `nic` | établissement | 5 chiffres |
| `numero_rcs` | registre | RCS |
| `code_greffe` | greffe | Greffe |

## Immobilier

| Identifiant | Niveau | Remarque |
|---|---|---|
| `numero` | parcelle | Ex. `75118000CL0016` |
| `parcelle_cadastrale` | parcelle | Même logique |
| `batiment_groupe_id` | bâtiment | ID BDNB |
| `identifiant_dpe` | DPE | ID DPE |
| `numero_immatriculation` | copropriété | RNIC |
| `vente.id` | mutation | Peut être null |
| `lot.numero` | lot copro | Non unique seul |
| `document_urbanisme.id` | document urbanisme | ID document |
| `token` | document Pappers | Actes/comptes |

## Clés composites recommandées

| Entité | Clé |
|---|---|
| Finance annuelle | `(siren, annee, date_de_cloture_exercice)` |
| Représentant physique | `(siren_entreprise, nom_complet, date_de_naissance_rgpd, qualite, date_prise_de_poste)` |
| Vente sans id | `(parcelle_numero, date, valeur_fonciere, type_local, lots_hash)` |
| Lot de vente | `(vente_id_surrogate, numero)` |
| Zone urbanisme | `(document_id, parcelle_numero, libelle, type_psc, stype_psc)` |

## Conseil

Toujours stocker le JSON brut en plus du modèle relationnel, car les sous-objets sont très variables.
