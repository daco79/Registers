# Exemple JSON observé — `informations-entreprise`

## Appel réalisé

```json
{
  "siren": "552100554",
  "return_fields": [
    "siren",
    "nom_entreprise",
    "personne_morale",
    "denomination",
    "code_naf",
    "libelle_code_naf",
    "domaine_activite",
    "date_creation",
    "entreprise_cessee",
    "categorie_juridique",
    "forme_juridique",
    "effectif",
    "capital",
    "statut_rcs",
    "siege",
    "etablissements",
    "finances",
    "representants",
    "beneficiaires_effectifs",
    "depots_actes",
    "comptes",
    "publications_bodacc",
    "procedures_collectives",
    "procedure_collective_existe",
    "procedure_collective_en_cours",
    "parcelles_detenues"
  ]
}
```

## Forme synthétique observée

```json
{
  "siren": "552100554",
  "nom_entreprise": "PEUGEOT SA",
  "personne_morale": true,
  "denomination": "PEUGEOT SA",
  "code_naf": "70.10Z",
  "libelle_code_naf": "Activités des sièges sociaux",
  "domaine_activite": "Activités des sièges sociaux ; conseil de gestion",
  "date_creation": "1955-01-01",
  "entreprise_cessee": true,
  "categorie_juridique": "5699",
  "forme_juridique": "SA à directoire (s.a.i.)",
  "effectif": "0 salarié",
  "capital": 894828213,
  "statut_rcs": "Radié",
  "siege": {
    "siret": "55210055400054",
    "adresse_ligne_1": "RTE DE GIZY",
    "code_postal": "78140",
    "ville": "VELIZY-VILLACOUBLAY",
    "code_naf": "70.10Z",
    "etablissement_cesse": true,
    "siege": true
  },
  "etablissements": [],
  "finances": [],
  "representants": [],
  "depots_actes": [],
  "comptes": [],
  "publications_bodacc": [],
  "procedures_collectives": [],
  "procedure_collective_existe": false,
  "procedure_collective_en_cours": false,
  "parcelles_detenues": {
    "resultats": [],
    "total": 0,
    "incomplet": false
  }
}
```

## Enseignements

- La fiche entreprise peut être très volumineuse.
- Les `return_fields` sont indispensables pour contrôler le coût et la lisibilité.
- Les tableaux `depots_actes` et `publications_bodacc` peuvent contenir beaucoup d’historique.
- `beneficiaires_effectifs` peut être remplacé par un message d’habilitation.
- Les représentants peuvent contenir des données de conformité : PPE et sanctions.
