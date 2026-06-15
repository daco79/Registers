# Schema Registers

`Registers` est la base locale du projet Registers. Elle reprend la structure Pappers exploitable en local, avec deux domaines complets :

- entreprises ;
- immobilier.

La migration principale est :

```text
sql/001_create_registers_schema.sql
```

## Principes

- MySQL 8 / XAMPP.
- Donnees normalisees pour les champs frequents.
- `raw_json` dans chaque table importante pour conserver la reponse Pappers complete.
- Tables d'audit `import_logs` et `raw_payloads` pour tracer les sources.
- Relations souples entre immobilier et entreprises : les SIREN proprietaires, occupants et syndics sont indexes meme si la fiche entreprise n'a pas encore ete importee.

## Domaines Entreprises

Tables principales :

- `entreprises`
- `etablissements`
- `representants`
- `beneficiaires_effectifs`
- `finances_annuelles`
- `comptes_deposes`
- `depots_actes`
- `depot_acte_items`
- `publications_bodacc`
- `procedures_collectives`
- `sanctions`
- `observations`
- `marques`
- `sites_internet`
- `cartographie_noeuds`
- `cartographie_liens`

## Domaines Immobilier

Tables principales :

- `lieux_geocodes`
- `parcelles`
- `parcelle_proprietaires`
- `parcelle_proprietaire_locaux`
- `parcelle_occupants`
- `ventes_immobilieres`
- `vente_lots`
- `vente_parcelles_associees`
- `batiments`
- `dpe`
- `coproprietes`
- `copropriete_autres_parcelles`
- `permis_urbanisme`
- `fonds_de_commerce`
- `documents_urbanisme`
- `zones_urbanisme`
- `amenagements`

## Vues

- `vue_parcelles_proprietaires`
- `vue_parcelles_occupants`
- `vue_entreprises_immobilier`

## Creation locale

Depuis `/Applications/XAMPP/xamppfiles/htdocs/Registers` :

```bash
mysql -uroot < sql/001_create_registers_schema.sql
```

Si le serveur MySQL XAMPP utilise un mot de passe :

```bash
mysql -uroot -p < sql/001_create_registers_schema.sql
```
