# Nulls, tableaux et objets variables

## Problème

Les JSON Pappers sont riches mais très variables :

- certains champs sont `null` ;
- certains tableaux sont vides ;
- certains objets changent de forme selon la source ;
- un champ attendu en tableau peut être remplacé par un message ;
- les DPE anciens et récents n’ont pas les mêmes colonnes ;
- les publications BODACC varient selon le type.

## Recommandation technique

Toujours stocker :

1. des champs normalisés pour les usages fréquents ;
2. le `raw_json` complet ;
3. une date d’import ;
4. la liste des `return_fields` utilisés.

## Exemple

```sql
CREATE TABLE pappers_import_log (
  id bigserial primary key,
  domain text,
  tool text,
  query jsonb,
  return_fields jsonb,
  imported_at timestamptz default now(),
  response_hash text
);
```

## Normalisation progressive

Commencer par :

- `entreprises`
- `etablissements`
- `representants`
- `finances_annuelles`
- `parcelles`
- `parcelle_proprietaires`
- `parcelle_occupants`
- `ventes`
- `batiments`
- `dpe`
- `coproprietes`

Puis ajouter :

- documents ;
- BODACC ;
- urbanisme ;
- permis ;
- fonds de commerce.
