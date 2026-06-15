# CLAUDE.md - Registers

## Projet

Registers est un projet local dont l'objectif est de fournir un moteur de recherche interne de type Pappers, mais base sur des sources publiques interrogees a la demande.

Le projet conserve une structure relationnelle locale inspiree de Pappers pour servir de modele de sortie JSON et de cache eventuel, mais l'experience principale n'est pas l'import massif de bases nationales.

Deux domaines principaux sont couverts :

- entreprises ;
- immobilier.

La base locale du projet s'appelle `Registers`.

## Source de reference

La documentation de structure Pappers est conservee dans :

- `pappers.md`
- `Pappers_Obsidian_Vault/`

Ces fichiers decrivent la structure exploitable via MCP/API Pappers. Ils sont la source de reference pour le modele local, mais ils ne doivent pas etre modifies sans demande explicite.

### Role de pappers.md

`pappers.md` est le fichier de reference operationnel pour tout ce qui concerne Pappers MCP. Il contient :

- les regles d'economie de credits (section 0) ;
- les structures YAML des entites retournees par Pappers (sections 6 a 9) — utiles pour mapper les reponses vers le schema local ;
- l'ordre d'appel recommande et les fieldsets types (sections 4, 12, 16) ;
- les corrections de types validees par appels reels (section 13).

Il ne contient pas les `CREATE TABLE` — ceux-ci sont dans `sql/001_create_registers_schema.sql` qui est la source autoritaire du schema.

`Pappers_Obsidian_Vault/` contient la documentation complete generee, plus verbose. Utiliser `pappers.md` en priorite pour les decisions rapides, le vault pour les details.

## Priorites

1. Creer et maintenir le schema local `Registers`.
2. Utiliser ce schema comme modele de sortie pour les reponses live.
3. Interroger les sources publiques a la demande, sans importer massivement les bases nationales.
4. Stocker eventuellement en cache uniquement sur decision explicite.
5. Garder le schema tolerant aux champs absents, `null`, tableaux vides et objets variables.
6. Ne pas chercher de coherence fonctionnelle avec les scripts Python historiques presents a la racine.

## Regles Pappers / credits

- Ne pas appeler Pappers sans demande explicite.
- Ne jamais faire d'appel large sans filtre strict.
- Toujours utiliser `return_fields`.
- Ne jamais appeler les scorings, contacts enrichis ou documents complets sans demande explicite.
- Sauvegarder les reponses brutes avant normalisation.

## Base locale

La migration principale est :

- `sql/001_create_registers_schema.sql`

Le schema est ecrit pour MySQL 8 / MariaDB compatible XAMPP autant que possible.

### Corrections de types validees sur appels Pappers reels (2025-06)

Trois corrections appliquees apres validation live sur BNP Paribas (552100554) et parcelle 75118000CL0016 :

- `coproprietes.mandat_en_cours` : `BOOLEAN` → `VARCHAR(128)` — Pappers retourne la chaine `"MANDAT EN COURS"`, pas un booleen.
- `parcelle_occupants.siege` → renomme `siege_json JSON` — Pappers retourne un objet `{siret, pays, ville, code_postal, adresse_ligne_1}` dans ce contexte, pas un booleen.
- `comptes_deposes.confidentialite_compte_de_resultat BOOLEAN NULL` : colonne absente ajoutee — champ systematiquement present a cote de `confidentialite` dans les comptes Pappers.

## Mapping sources publiques

Le mapping entre la structure locale `Registers` et les sources publiques est documente dans :

- `docs/mapping-data-gouv.md` : version lisible et commentee.
- `data/registers_data_gouv_mapping.csv` : version tabulaire exploitable par les futurs importeurs.

Ce mapping remplace progressivement Pappers comme source d'alimentation. Il classe chaque correspondance en `direct`, `jointure`, `partiel`, `a_confirmer` ou `non_couvert`.

## Interface locale

Une premiere interface web interne est disponible a la racine du projet :

- `index.php` : exploration visuelle, generation JSON et consultation du schema.
- `api/search.php` : recherche locale multi-entites.
- `api/entity.php` : fiche JSON par type et identifiant.
- `api/export.php` : export telechargeable JSON.
- `api/schema.php` : inventaire des tables/vues locales.
- `api/live_search.php` : recherche federatrice live, type Pappers-like.
- `api/live_detail.php` : fiche structuree live selon le modele Registers.
- `api/import_company.php` : import cible d'une entreprise via `recherche-entreprises.api.gouv.fr`.
- `api/geocode.php` : geocodage cible d'une adresse via `api-adresse.data.gouv.fr`.

L'onglet `Explorer` et l'onglet `JSON` utilisent les endpoints live. Ils interrogent les APIs publiques referencees par data.gouv et recomposent les donnees dans la structure Registers.
L'onglet `Schema` lit uniquement la base MySQL locale `Registers`.
Les endpoints historiques `import_company.php` et `geocode.php` existent comme tests techniques, mais l'experience principale ne doit pas importer les bases.

URL locale XAMPP :

- `http://localhost/Registers/`

Sources live branchees :

- `recherche-entreprises.api.gouv.fr` : societes, etablissements, recherche par dirigeant, finances partielles.
- `data.geopf.fr/geocodage` : adresses, coordonnees, parcelles cadastrales proches.
- `bodacc-datadila.opendatasoft.com` : annonces commerciales BODACC par SIREN.
- `tabular-api.data.gouv.fr` / RNIC : coproprietes par adresse ou reference cadastrale.
- `data.ademe.fr/data-fair` : DPE logements existants par adresse.
- `files.data.gouv.fr/geo-dvf` : ventes DVF par parcelle en streaming CSV departemental.

Important : DVF est lu a la demande dans les fichiers departementaux compresses. Cette liaison peut etre plus lente que les autres et doit rester limitee en nombre de lignes/annees.

## Etat des connecteurs live

### Fonctionnel

- Recherche entreprise par nom, SIREN ou SIRET via `recherche-entreprises.api.gouv.fr`.
- Recherche personne/dirigeant via `recherche-entreprises.api.gouv.fr`, en retournant les societes associees quand le dirigeant apparait dans les resultats.
- Fiche entreprise live avec :
  - entreprise normalisee ;
  - siege / etablissements retournes par l'API ;
  - dirigeants ;
  - finances partielles quand disponibles ;
  - annonces BODACC par SIREN.
- Recherche adresse via `data.geopf.fr/geocodage`.
- Fiche adresse live avec :
  - adresse normalisee ;
  - coordonnees ;
  - parcelles proches ;
  - coproprietes RNIC par adresse ;
  - DPE ADEME logements existants par adresse.
- Recherche parcelle cadastrale via Geoplateforme.
- Fiche parcelle live avec :
  - reference cadastrale ;
  - coordonnees parcelle ;
  - ventes DVF par parcelle ;
  - coproprietes RNIC par reference cadastrale quand disponible.
- Export JSON live via l'onglet `JSON`.

### Partiel / indicatif

- Finances : uniquement les informations exposees par API Recherche d'Entreprises, pas les comptes complets.
- BODACC : annonces commerciales brutes/normalisees, mais toutes les procedures/sanctions ne sont pas encore structurees finement.
- DPE : logements existants uniquement ; DPE neufs et tertiaires pas encore branches.
- DVF : lu en streaming dans les CSV departementaux, exhaustif sur les annees configurees mais plus lent.
- Coproprietes : RNIC fonctionne par adresse et reference cadastrale, mais certaines coproprietes n'ont pas de reference cadastrale renseignee.

### Non connecte ou non disponible en open data simple

- Proprietaires fonciers actuels nominatifs.
- Locaux de proprietaires issus MAJIC.
- Droits de propriete / comptes-proprietaires complets.
- Beneficiaires effectifs complets.
- Actes/statuts/documents RNE/INPI.
- Permis urbanisme SITADEL.
- RNB / batiments.
- Documents et zones d'urbanisme GPU.
- Fonds de commerce structure finement depuis BODACC.
- Marques, sites internet et signaux annexes.

## Proprietaires fonciers

Conclusion actuelle : les proprietaires fonciers complets ne sont pas disponibles via une API open data simple.

La vraie source est `Fichiers fonciers / MAJIC`, retraitee par le Cerema. Elle contient bien :

- parcelles ;
- locaux ;
- proprietaires ;
- comptes-proprietaires ;
- droits de propriete ;
- informations sur personnes morales et personnes physiques.

Mais ces donnees sont d'origine fiscale et l'acces nominatif est restreint aux ayants droit via le Portail Donnees Foncieres / API Donnees foncieres Cerema.

Sans acces Cerema/Datafoncier, Registers ne doit pas pretendre fournir le proprietaire foncier actuel certifie.

Approches possibles sans acces :

- afficher les ventes DVF/DVF+ comme historique de mutation ;
- calculer un `dernier_acquereur_probable` si une source ouverte donne un acheteur/vendeur exploitable ;
- afficher le syndic / copropriete RNIC ;
- afficher les societes a l'adresse via API Recherche d'Entreprises ;
- afficher les occupants probables par geocodage d'etablissements, a construire plus tard.

DVF+ open-data a ete analyse comme piste : utile pour structurer les mutations et potentiellement obtenir des acheteurs/vendeurs anonymises ou des SIREN de personnes morales selon le format disponible, mais ce n'est pas une source de proprietaire actuel certifie. Le bloc devra etre nomme `proprietaires_probables` ou `mutations_acheteurs_vendeurs`, pas `proprietaires_fonciers`.

Si un acces Cerema est obtenu plus tard, prevoir un connecteur configurable :

- `REGISTERS_FONCIER_PROVIDER=cerema`
- `REGISTERS_CEREMA_API_BASE=...`
- `REGISTERS_CEREMA_TOKEN=...`
- `REGISTERS_CEREMA_ACCESS_LEVEL=anonymized|full`

Comportement attendu :

- sans token : retourner un statut `non_disponible_open_data` ;
- token anonymise : remplir les champs autorises et anonymises ;
- token complet : remplir `parcelle_proprietaires` et `parcelle_proprietaire_locaux` selon le modele Registers.

## Domaines couverts

### Entreprises

- entreprises
- etablissements
- representants
- beneficiaires effectifs
- finances annuelles
- comptes deposes
- actes et documents
- publications BODACC
- procedures collectives
- sanctions
- observations
- marques, sites internet et signaux annexes
- cartographie relationnelle

### Immobilier

- lieux geocodes
- parcelles
- proprietaires
- locaux de proprietaires
- occupants
- ventes et lots
- batiments
- DPE
- coproprietes
- permis d'urbanisme
- fonds de commerce
- documents et zones d'urbanisme
- amenagements

## Convention technique

- Identifiants SIREN/SIRET/parcelles : `VARCHAR`, pas `INT`, pour conserver les zeros et formats.
- Dates : `DATE` quand le champ est normalisable, sinon garder dans `raw_json`.
- Montants/surfaces : `DECIMAL`.
- Booleens : `BOOLEAN`.
- Donnees variables : `JSON`.
- Import : toute operation doit etre journalisee dans `import_logs`.

## Ne pas faire

- Ne pas supprimer les fichiers historiques sans demande explicite.
- Ne pas commiter de cle API.
- Ne pas remplacer `pappers.md` par ce fichier.
- Ne pas lancer d'appels Pappers pour remplir la base tant que le schema et les importeurs locaux ne sont pas valides.
