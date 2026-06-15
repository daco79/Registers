# Schéma — `informations-entreprise`

## Usage

Récupère la fiche détaillée d’une entreprise française à partir du SIREN.

## Entrée

| Paramètre | Type | Obligatoire | Remarque |
|---|---|---:|---|
| `siren` | string | oui | 9 chiffres |
| `return_fields` | array | non | Permet de limiter les champs et donc d’éviter des données inutiles |

## Champs disponibles principaux

### Identité

`"siren"`, `"siren_formate"`, `"diffusable"`, `"nom_entreprise"`, `"personne_morale"`, `"denomination"`, `"nom"`, `"prenom"`, `"sexe"`.

### Activité

`"code_naf"`, `"libelle_code_naf"`, `"nomenclature_code_naf"`, `"domaine_activite"`, `"objet_social"`, `"conventions_collectives"`.

### Statut et juridique

`"categorie_juridique"`, `"forme_juridique"`, `"micro_entreprise"`, `"statut_rcs"`, `"statut_rne"`, `"statut_consolide"`, `"associe_unique"`, `"societe_a_mission"`.

### Siège et établissements

`"siege"`, `"etablissements"`, `"etablissement"`.

### Gouvernance

`"representants"`, `"representants_legaux"`, `"beneficiaires_effectifs"`, `"entreprises_dirigees"`.

### Financier

`"finances"`, `"comptes"`, `"finances_estimations"`.

⚠️ `scoring_financier` et `scoring_non_financier` sont signalés comme consommateurs de crédits payants. À éviter sans demande explicite.

### Documents et annonces

`"depots_actes"`, `"derniers_statuts"`, `"extrait_immatriculation"`, `"publications_bodacc"`.

### Risque / conformité

`"procedures_collectives"`, `"procedure_collective_existe"`, `"procedure_collective_en_cours"`, `"sanctions"`, `"personne_politiquement_exposee"`, `"deces"`, `"observations"`, `"decisions"`.

### Immobilier

`"parcelles_detenues"`.

### Assets / propriété intellectuelle

`"marques"`, `"brevets"`, `"dessins"`.

### Marchés / actualité

`"appels_offres_gagnes"`, `"appels_offres_lances"`, `"actualite_presse"`, `"aides_europeennes"`.

### Groupe / capital

`"filiales"`, `"maison_mere"`, `"actionnaires"`.

## Structure observée

```json
{
  "siren": "...",
  "nom_entreprise": "...",
  "siege": {},
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
  },
  "sanctions": [],
  "observations": []
}
```

## Points d’attention

- Certains sous-objets sont très variables selon l’entreprise.
- Les bénéficiaires effectifs peuvent retourner un message d’habilitation au lieu d’un tableau.
- Les représentants peuvent être personnes physiques ou morales.
- Les publications BODACC changent fortement selon le type : création, modification, vente, radiation, dépôt de comptes.
