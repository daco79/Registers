# CLAUDE.md - Registers

## Comportement general

- Lire les fichiers existants avant d'ecrire. Ne pas relire sauf si modifie.
- Raisonnement approfondi, reponses concises.
- Ignorer les fichiers de plus de 100 Ko sauf necessite.
- Pas d'ouvertures sycophantes ni de conclusions superflues.
- Pas d'emojis ni de tirets cadratins.
- Ne pas deviner APIs, versions, flags, commit SHAs ou noms de packages. Verifier dans le code ou la doc avant d'affirmer.

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
- `api/live_entreprises.php` : recherche d'entreprises avec filtres (NAF, CP, departement, forme juridique, etat...) via `recherche-entreprises.api.gouv.fr`. Meme interface que Pappers mais sur open data.
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
- `data.ademe.fr/data-fair` : DPE logements existants, neufs et tertiaires par adresse (trois datasets distincts).
- `files.data.gouv.fr/geo-dvf` : ventes DVF par parcelle en streaming CSV departemental.
- `georisques.gouv.fr/api/v1` : risques naturels par code commune — trois endpoints distincts : `/radon?code_insee=`, `/zonage_sismique?code_insee=`, `/mvt?code_insee=`. Pas d'endpoint generique `/risques`.
- `rnb-api.beta.gouv.fr/api/alpha/buildings/` : batiments du Referentiel National des Batiments par bbox GPS. Parametre `bbox=minLon,minLat,maxLon,maxLat`. Endpoint pluriel (`buildings/`, pas `building/`).
- `apicarto.ign.fr/api/gpu/zone-urba` : zonage urbanisme PLU/PLUi par coordonnees. Parametre `geom` en JSON brut — ne pas passer via `http_build_query` (les accolades encodees cassent l'endpoint).
- `geo.api.gouv.fr/communes` : decoupage administratif (commune, departement, region, codes postaux, population) par coordonnees lat/lon.

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
  - commune / decoupage administratif (geo.api.gouv.fr) ;
  - parcelles proches ;
  - georisques par commune (radon, zone sismique, mouvements de terrain) ;
  - batiments RNB par bbox ;
  - zonage urbanisme GPU/PLU par coordonnees ;
  - coproprietes RNIC par adresse ;
  - DPE ADEME logements existants par adresse ;
  - DPE ADEME logements neufs par adresse ;
  - DPE ADEME tertiaire par adresse.
- Recherche parcelle cadastrale via Geoplateforme.
- Fiche parcelle live avec :
  - reference cadastrale ;
  - coordonnees parcelle ;
  - georisques par commune ;
  - batiments RNB par bbox ;
  - ventes DVF par parcelle ;
  - coproprietes RNIC par reference cadastrale quand disponible.
- Export JSON live via l'onglet `JSON`.

### Partiel / indicatif

- Finances : uniquement les informations exposees par API Recherche d'Entreprises, pas les comptes complets.
- BODACC : annonces commerciales brutes/normalisees, mais toutes les procedures/sanctions ne sont pas encore structurees finement.
- DPE neufs (`dpe02neuf`) et tertiaires (`dpe-tertiaire`) : branches et fonctionnels, mais schema different du dataset existants. DPE tertiaire utilise `geo_adresse`, `classe_consommation_energie`, `classe_estimation_ges`, `surface_utile` et `id` au lieu des champs standard. Peut retourner 0 resultat sur des adresses purement residentielles.
- DVF : lu en streaming dans les CSV departementaux, exhaustif sur les annees configurees mais plus lent.
- Coproprietes : RNIC fonctionne par adresse et reference cadastrale, mais certaines coproprietes n'ont pas de reference cadastrale renseignee.
- Georisques MVT (mouvements de terrain) : retourne les evenements historiques enregistres, pas une carte de risque systematique par commune.

### Non connecte ou token requis

- BDNB CSTB (base nationale des batiments enrichie) : API retourne HTTP 403 sans token. Inscription requise sur bdnb.io. Stub vide en place dans `live_sources.php` — connecteur a activer quand token disponible.
- Documents GPU complets (reglement, zonage detaille) : seul le zonage de zone (`zone-urba`) est connecte via APICarto IGN ; les documents reglementaires lies ne sont pas recuperes.
- Permis urbanisme SITADEL.
- Proprietaires fonciers actuels nominatifs.
- Locaux de proprietaires issus MAJIC.
- Droits de propriete / comptes-proprietaires complets.
- Beneficiaires effectifs complets.
- Actes/statuts/documents RNE/INPI.
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

## Scripts Python d'analyse offline

Ces scripts travaillent sur des exports JSON locaux, sans appel live. Ils servent a valider la structure et produire des CSV/Excel pour analyses ponctuelles — ils ne sont pas des importeurs Registers.

### `_Parcelle_indi.py`

Script polyvalent d'aplatissage JSON vers CSV. Detecte automatiquement le format en entree :

- `parcelles_societes` : liste de parcelles dont les proprietaires ont un SIREN (ex: `Export/75011/PARCELLESDERETOUR.json`). Produit une ligne par couple (parcelle x societe) avec l'adresse du bien immobilier + enrichissement data.gouv depuis le cache. Sortie : `Export/75011/parcelles_societes_fusion.csv`.
- `parcelles_individus` : liste de parcelles avec proprietaires particuliers (ex: `Export/RetourPappers_parcelles_75017.json`). Produit une ligne par parcelle. Enrichissement optionnel depuis Registers DB (profil_proprietaire).
- `entreprises` : dict `{siren: company}` issu data.gouv (ex: `Export/entreprises_75011_datagouv_raw.json`). Produit une ligne par societe avec tous les champs aplatis (siege, dirigeants, coordonnees...).

Le fichier cache data.gouv est configure via `DATAGOUV_CACHE` en haut du script. Le format est detecte sur le contenu, pas le nom de fichier.

### `_Manuel.py`

Enrichit une liste de SIRENs Pappers via `recherche-entreprises.api.gouv.fr` au lieu de l'API Pappers Entreprises. Cache les reponses brutes dans `Export/entreprises_75011_datagouv_raw.json` pour eviter de rappeler l'API. Produit un Excel multi-feuilles (trouves / non trouves).

### `_registers.py`

Pipeline unifie remplacant `_automatique.py`. Utilise data.gouv au lieu de Pappers Entreprises pour l'enrichissement societes.

Usage : `python3 _registers.py [code_postal]` — saisie interactive si absent.

Parametres configurables en tete de fichier :

- `NB_PROPRIO_MIN` / `NB_PROPRIO_MAX` : nombre de proprietaires societes par parcelle (ex: `"1"` / `"4"`). Mettre `"0"` / `"0"` pour le mode particuliers (arret apres PARCELLESDERETOUR.json, pas d'enrichissement SIREN).
- `INCLURE_CESSEES` : inclure les societes cessees (defaut `False`).
- `DELAI_APPELS` : delai entre appels data.gouv en secondes (defaut `0.15`).

Etapes :

1. Pappers Immobilier → `PARCELLESDERETOUR.json` (mis en cache, non rappele si existant)
2. data.gouv par SIREN → `entreprises_{CP}_datagouv_raw.json` (cache idem)
3. Fusion → `parcelles_societes_fusion.csv` + `EXCEL_parcelles_entreprise_{CP}.xlsx`

Filtre `CATEGORIES_JURIDIQUES` (49 codes societes patrimoniales/foncieres) applique localement apres l'appel Pappers — ne pas le passer en parametre URL (trop long, tronque cote serveur).

Mode particuliers (`NB_PROPRIO_MAX="0"`) : le filtre categories est ignore et le script s'arrete apres PARCELLESDERETOUR.json si aucun SIREN n'est present.

### `_automatique.py`

Script historique Pappers (conserve, ne pas supprimer). Appelle Pappers Immobilier pour les parcelles puis Pappers Entreprises par SIREN. Remplace par `_registers.py` pour les nouveaux arrondissements.

### Structure Export

```
Export/
  75011/
    PARCELLESDERETOUR.json          — 554 parcelles 75011, proprietaires societes (Pappers Immobilier)
    entreprises.json                — 464 societes format Pappers Entreprises (historique)
    EXCEL_parcelles_entreprise_final_75011.xlsx — sortie historique via _automatique.py
    parcelles_societes_fusion.csv   — sortie courante : adresse bien + infos data.gouv
    sirens.txt                      — liste SIRENs extraits
  75012/
    PARCELLESDERETOUR.json          — 1286 parcelles 75012, proprietaires societes (NB_PROPRIO_MAX=1)
    entreprises_75012_datagouv_raw.json — cache data.gouv 491/528 SIRENs
    parcelles_societes_fusion.csv   — 1286 lignes, 1245 enrichies
    EXCEL_parcelles_entreprise_75012.xlsx
    sirens.txt
  75010/
    PARCELLESDERETOUR.json          — 1172 parcelles 75010, proprietaires societes (NB_PROPRIO_MIN=1, MAX=4)
    entreprises_75010_datagouv_raw.json — cache data.gouv 1964/1988 SIRENs
    parcelles_societes_fusion.csv   — 2352 lignes, 2327 enrichies
    EXCEL_parcelles_entreprise_75010.xlsx
    sirens.txt
  75010_PROP=1/
    PARCELLESDERETOUR.json          — 357 parcelles 75010, monoproprietaires societes uniquement (NB_PROPRIO_MAX=1)
    entreprises_75010_datagouv_raw.json — cache data.gouv 314 SIRENs
    parcelles_societes_fusion.csv
    EXCEL_parcelles_entreprise_75010.xlsx
    sirens.txt
  Pappers_entreprises_75011.json    — 464 societes format Pappers Entreprises, fichier brut historique
  entreprises_75011_datagouv_raw.json — cache data.gouv par SIREN (463 societes)
  entreprises_75011_registers.xlsx    — Excel enrichi via _Manuel.py
  entreprises_75011_datagouv_flat.csv — CSV aplati format entreprises
  RetourPappers_parcelles_75016.json  — 833 parcelles 75016, particuliers uniquement
  RetourPappers_parcelles_75017.json  — 489 parcelles 75017, particuliers uniquement
  parcelles_75017_individus.csv       — CSV aplati 75017 individus
```

Validation croisee effectuee entre `parcelles_societes_fusion.csv` et `EXCEL_parcelles_entreprise_final_75011.xlsx` : 464 SIRENs communs, donnees coherentes (adresse bien, nom societe, siege).

## Bases locales (Base/)

Fallback offline si les APIs data.gouv sont indisponibles. Structure :

```
Base/
  COG/
    communes_2026.csv       — 3.4 MB (telecharge)
    departements_2026.csv   — 6 KB (telecharge)
    regions_2026.csv        — 1.2 KB (telecharge)
  RNIC/
    rnic_t3_2025.csv        — 387 MB, source actualisation quotidienne ANAH
                              (telechargement initial : 2026-06-16)
  SIRENE/
    stockunitelegale_2026-06-01.zip — 915 MB, source INSEE SIRENE UniteLegale
                              (telechargement initial : 2026-06-16)
  DVF/                      — vide (deja disponible dans Estimatiz)
  DPE/                      — vide (ADEME : API seulement, pas de bulk download)
  BODACC/                   — vide (a completer si besoin)
  download.sh               — script de telechargement avec toutes les URLs et tailles
```

Le script `Base/download.sh` permet de rerelecharger ou mettre a jour les bases. Utiliser `curl -C -` pour reprendre un download interrompu.

Source RNIC : resource ID `3ea8e2c3-0038-464a-b17e-cd5c91f65ce2` — mettre a jour l'URL dans download.sh quand une nouvelle version trimestrielle est publiee.
Source SIRENE : resource ID `825f4199-cadd-486c-ac46-a65a8ea1a047` — mise a jour mensuelle.

## Ne pas faire

- Ne pas supprimer les fichiers historiques sans demande explicite.
- Ne pas commiter de cle API.
- Ne pas remplacer `pappers.md` par ce fichier.
- Ne pas lancer d'appels Pappers pour remplir la base tant que le schema et les importeurs locaux ne sont pas valides.
