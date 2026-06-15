-- Registers
-- Schema local inspire de Pappers : entreprises + immobilier.
-- Cible : MySQL 8.x / MariaDB recent.

CREATE DATABASE IF NOT EXISTS `Registers`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `Registers`;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Import, audit et payloads bruts
-- ============================================================

CREATE TABLE IF NOT EXISTS import_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  domain VARCHAR(64) NOT NULL,
  source_tool VARCHAR(128) NULL,
  source_file VARCHAR(1024) NULL,
  query_json JSON NULL,
  return_fields JSON NULL,
  response_hash CHAR(64) NULL,
  rows_seen INT UNSIGNED NULL,
  rows_inserted INT UNSIGNED NULL,
  rows_updated INT UNSIGNED NULL,
  imported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  notes TEXT NULL,
  PRIMARY KEY (id),
  KEY idx_import_logs_domain (domain),
  KEY idx_import_logs_source_tool (source_tool),
  KEY idx_import_logs_response_hash (response_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS raw_payloads (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  import_log_id BIGINT UNSIGNED NULL,
  domain VARCHAR(64) NOT NULL,
  entity_type VARCHAR(64) NOT NULL,
  entity_key VARCHAR(255) NULL,
  source_file VARCHAR(1024) NULL,
  payload_hash CHAR(64) NULL,
  raw_json JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_raw_payloads_import_log (import_log_id),
  KEY idx_raw_payloads_domain_entity (domain, entity_type),
  KEY idx_raw_payloads_entity_key (entity_key),
  KEY idx_raw_payloads_payload_hash (payload_hash),
  CONSTRAINT fk_raw_payloads_import_log
    FOREIGN KEY (import_log_id) REFERENCES import_logs(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Entreprises
-- ============================================================

CREATE TABLE IF NOT EXISTS entreprises (
  siren VARCHAR(9) NOT NULL,
  siren_formate VARCHAR(32) NULL,
  diffusable BOOLEAN NULL,
  opposition_utilisation_commerciale BOOLEAN NULL,
  nom_entreprise VARCHAR(512) NULL,
  personne_morale BOOLEAN NULL,
  denomination VARCHAR(512) NULL,
  nom VARCHAR(255) NULL,
  prenom VARCHAR(255) NULL,
  sexe VARCHAR(32) NULL,
  sigle VARCHAR(255) NULL,
  code_naf VARCHAR(16) NULL,
  libelle_code_naf VARCHAR(512) NULL,
  nomenclature_code_naf VARCHAR(64) NULL,
  domaine_activite VARCHAR(512) NULL,
  objet_social TEXT NULL,
  categorie_juridique VARCHAR(16) NULL,
  forme_juridique VARCHAR(512) NULL,
  forme_exercice VARCHAR(255) NULL,
  micro_entreprise BOOLEAN NULL,
  entreprise_cessee BOOLEAN NULL,
  date_creation DATE NULL,
  date_cessation DATE NULL,
  date_reouverture DATE NULL,
  entreprise_employeuse BOOLEAN NULL,
  societe_a_mission BOOLEAN NULL,
  economie_sociale_solidaire BOOLEAN NULL,
  effectif VARCHAR(255) NULL,
  effectif_min INT NULL,
  effectif_max INT NULL,
  tranche_effectif VARCHAR(16) NULL,
  annee_effectif INT NULL,
  capital DECIMAL(20,2) NULL,
  capital_formate VARCHAR(128) NULL,
  capital_actuel_si_variable DECIMAL(20,2) NULL,
  devise_capital VARCHAR(64) NULL,
  statut_rcs VARCHAR(128) NULL,
  statut_rne VARCHAR(128) NULL,
  statut_consolide VARCHAR(128) NULL,
  numero_rcs VARCHAR(255) NULL,
  greffe VARCHAR(255) NULL,
  code_greffe VARCHAR(32) NULL,
  date_immatriculation_rcs DATE NULL,
  date_premiere_immatriculation_rcs DATE NULL,
  date_radiation_rcs DATE NULL,
  date_immatriculation_rne DATE NULL,
  date_radiation_rne DATE NULL,
  date_debut_activite DATE NULL,
  date_debut_premiere_activite DATE NULL,
  numero_tva_intracommunautaire VARCHAR(64) NULL,
  validite_tva_intracommunautaire BOOLEAN NULL,
  associe_unique BOOLEAN NULL,
  duree_personne_morale VARCHAR(64) NULL,
  date_cloture_exercice VARCHAR(64) NULL,
  prochaine_date_cloture_exercice DATE NULL,
  derniere_mise_a_jour_sirene DATE NULL,
  derniere_mise_a_jour_rcs DATE NULL,
  dernier_traitement DATE NULL,
  labels JSON NULL,
  conventions_collectives JSON NULL,
  formes_exercice JSON NULL,
  siege_json JSON NULL,
  procedure_collective_existe BOOLEAN NULL,
  procedure_collective_en_cours BOOLEAN NULL,
  actif_net_inferieur_moitie_capital BOOLEAN NULL,
  filiales JSON NULL,
  maison_mere JSON NULL,
  actionnaires JSON NULL,
  decisions JSON NULL,
  parcelles_detenues JSON NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (siren),
  KEY idx_entreprises_nom (nom_entreprise(191)),
  KEY idx_entreprises_code_naf (code_naf),
  KEY idx_entreprises_categorie_juridique (categorie_juridique),
  KEY idx_entreprises_statut_rcs (statut_rcs),
  KEY idx_entreprises_date_creation (date_creation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS etablissements (
  siret VARCHAR(14) NOT NULL,
  siren VARCHAR(9) NULL,
  siret_formate VARCHAR(32) NULL,
  nic VARCHAR(5) NULL,
  type_etablissement VARCHAR(64) NULL,
  diffusion_partielle BOOLEAN NULL,
  siege BOOLEAN NULL,
  numero_voie VARCHAR(32) NULL,
  indice_repetition VARCHAR(16) NULL,
  type_voie VARCHAR(64) NULL,
  libelle_voie VARCHAR(512) NULL,
  complement_adresse VARCHAR(512) NULL,
  adresse_ligne_1 VARCHAR(512) NULL,
  adresse_ligne_2 VARCHAR(512) NULL,
  code_postal VARCHAR(16) NULL,
  code_commune VARCHAR(16) NULL,
  ville VARCHAR(255) NULL,
  pays VARCHAR(128) NULL,
  code_pays VARCHAR(8) NULL,
  latitude DECIMAL(12,8) NULL,
  longitude DECIMAL(12,8) NULL,
  code_naf VARCHAR(16) NULL,
  code_naf_2025 VARCHAR(16) NULL,
  libelle_code_naf VARCHAR(512) NULL,
  etablissement_employeur BOOLEAN NULL,
  effectif VARCHAR(255) NULL,
  effectif_min INT NULL,
  effectif_max INT NULL,
  tranche_effectif VARCHAR(16) NULL,
  annee_effectif INT NULL,
  date_de_creation DATE NULL,
  date_debut_activite DATE NULL,
  etablissement_cesse BOOLEAN NULL,
  date_cessation DATE NULL,
  domiciliation JSON NULL,
  enseigne VARCHAR(512) NULL,
  nom_commercial VARCHAR(512) NULL,
  labels JSON NULL,
  predecesseurs JSON NULL,
  successeurs JSON NULL,
  departement VARCHAR(255) NULL,
  code_departement VARCHAR(8) NULL,
  region VARCHAR(255) NULL,
  code_region VARCHAR(8) NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (siret),
  KEY idx_etablissements_siren (siren),
  KEY idx_etablissements_code_postal (code_postal),
  KEY idx_etablissements_code_commune (code_commune),
  KEY idx_etablissements_siege (siege),
  CONSTRAINT fk_etablissements_entreprise
    FOREIGN KEY (siren) REFERENCES entreprises(siren)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS representants (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren_entreprise VARCHAR(9) NULL,
  personne_morale BOOLEAN NULL,
  qualite VARCHAR(255) NULL,
  qualites JSON NULL,
  actuel BOOLEAN NULL,
  date_prise_de_poste DATE NULL,
  date_depart_de_poste DATE NULL,
  sexe VARCHAR(32) NULL,
  nom VARCHAR(255) NULL,
  prenom VARCHAR(255) NULL,
  prenom_usuel VARCHAR(255) NULL,
  nom_complet VARCHAR(512) NULL,
  date_de_naissance VARCHAR(32) NULL,
  date_de_naissance_formate VARCHAR(64) NULL,
  date_de_naissance_rgpd VARCHAR(64) NULL,
  age INT NULL,
  nationalite VARCHAR(128) NULL,
  codes_nationalites JSON NULL,
  ville_de_naissance VARCHAR(255) NULL,
  pays_de_naissance VARCHAR(255) NULL,
  code_pays_de_naissance VARCHAR(8) NULL,
  adresse_ligne_1 VARCHAR(512) NULL,
  adresse_ligne_2 VARCHAR(512) NULL,
  adresse_ligne_3 VARCHAR(512) NULL,
  code_postal VARCHAR(16) NULL,
  ville VARCHAR(255) NULL,
  pays VARCHAR(128) NULL,
  code_pays VARCHAR(8) NULL,
  siren_representant VARCHAR(9) NULL,
  denomination_representant VARCHAR(512) NULL,
  forme_juridique_representant VARCHAR(512) NULL,
  sanctions_en_cours BOOLEAN NULL,
  sanctions JSON NULL,
  personne_politiquement_exposee JSON NULL,
  entreprises JSON NULL,
  nb_entreprises_total INT NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_representant_identite (siren_entreprise, nom_complet(191), date_de_naissance_rgpd, qualite, date_prise_de_poste),
  KEY idx_representants_siren_entreprise (siren_entreprise),
  KEY idx_representants_siren_representant (siren_representant),
  KEY idx_representants_nom_complet (nom_complet(191)),
  KEY idx_representants_qualite (qualite),
  CONSTRAINT fk_representants_entreprise
    FOREIGN KEY (siren_entreprise) REFERENCES entreprises(siren)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS beneficiaires_effectifs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren_entreprise VARCHAR(9) NULL,
  nom VARCHAR(255) NULL,
  nom_usage VARCHAR(255) NULL,
  prenom VARCHAR(255) NULL,
  pseudonyme VARCHAR(255) NULL,
  nom_complet VARCHAR(512) NULL,
  date_de_naissance_formate VARCHAR(64) NULL,
  date_de_naissance_complete_formatee VARCHAR(64) NULL,
  nationalite VARCHAR(128) NULL,
  codes_nationalites JSON NULL,
  pourcentage_parts DECIMAL(8,4) NULL,
  pourcentage_parts_directes DECIMAL(8,4) NULL,
  pourcentage_parts_indirectes DECIMAL(8,4) NULL,
  pourcentage_parts_vocation_titulaire DECIMAL(8,4) NULL,
  pourcentage_votes DECIMAL(8,4) NULL,
  pourcentage_votes_directs DECIMAL(8,4) NULL,
  pourcentage_votes_indirect DECIMAL(8,4) NULL,
  detention_pouvoir_decision_ag BOOLEAN NULL,
  detention_pouvoir_nom_membre_conseil_administration BOOLEAN NULL,
  detention_autres_moyens_controle BOOLEAN NULL,
  beneficiaire_representant_legal BOOLEAN NULL,
  representant_legal_placement_sans_gestion_delegation BOOLEAN NULL,
  adresse_ligne_1 VARCHAR(512) NULL,
  adresse_ligne_2 VARCHAR(512) NULL,
  adresse_ligne_3 VARCHAR(512) NULL,
  code_postal VARCHAR(16) NULL,
  ville VARCHAR(255) NULL,
  pays VARCHAR(128) NULL,
  pays_de_naissance VARCHAR(255) NULL,
  ville_de_naissance VARCHAR(255) NULL,
  access_message TEXT NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_be_siren_entreprise (siren_entreprise),
  KEY idx_be_nom_complet (nom_complet(191)),
  CONSTRAINT fk_be_entreprise
    FOREIGN KEY (siren_entreprise) REFERENCES entreprises(siren)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finances_annuelles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren VARCHAR(9) NOT NULL,
  annee INT NOT NULL,
  date_de_cloture_exercice DATE NULL,
  duree_exercice INT NULL,
  chiffre_affaires DECIMAL(22,2) NULL,
  chiffre_affaires_export DECIMAL(22,2) NULL,
  resultat DECIMAL(22,2) NULL,
  effectif DECIMAL(12,2) NULL,
  marge_brute DECIMAL(22,2) NULL,
  excedent_brut_exploitation DECIMAL(22,2) NULL,
  resultat_exploitation DECIMAL(22,2) NULL,
  taux_croissance_chiffre_affaires DECIMAL(12,4) NULL,
  taux_marge_brute DECIMAL(12,4) NULL,
  taux_marge_EBITDA DECIMAL(12,4) NULL,
  taux_marge_operationnelle DECIMAL(12,4) NULL,
  BFR_exploitation DECIMAL(22,2) NULL,
  BFR_hors_exploitation DECIMAL(22,2) NULL,
  BFR DECIMAL(22,2) NULL,
  BFR_exploitation_jours_CA DECIMAL(12,4) NULL,
  BFR_hors_exploitation_jours_CA DECIMAL(12,4) NULL,
  BFR_jours_CA DECIMAL(12,4) NULL,
  delai_paiement_clients_jours DECIMAL(12,4) NULL,
  delai_paiement_fournisseurs_jours DECIMAL(12,4) NULL,
  ratio_stock_CA_jours DECIMAL(12,4) NULL,
  capacite_autofinancement DECIMAL(22,2) NULL,
  capacite_autofinancement_CA DECIMAL(12,4) NULL,
  fonds_roulement_net_global DECIMAL(22,2) NULL,
  couverture_BFR DECIMAL(12,4) NULL,
  tresorerie DECIMAL(22,2) NULL,
  dettes_financieres DECIMAL(22,2) NULL,
  capacite_remboursement DECIMAL(12,4) NULL,
  ratio_endettement DECIMAL(12,4) NULL,
  autonomie_financiere DECIMAL(12,4) NULL,
  taux_levier DECIMAL(12,4) NULL,
  etat_dettes_1_an_au_plus DECIMAL(22,2) NULL,
  liquidite_generale DECIMAL(12,4) NULL,
  couverture_dettes DECIMAL(12,4) NULL,
  marge_nette DECIMAL(12,4) NULL,
  fonds_propres DECIMAL(22,2) NULL,
  rentabilite_fonds_propres DECIMAL(12,4) NULL,
  rentabilite_economique DECIMAL(12,4) NULL,
  valeur_ajoutee DECIMAL(22,2) NULL,
  valeur_ajoutee_CA DECIMAL(12,4) NULL,
  salaires_charges_sociales DECIMAL(22,2) NULL,
  salaires_CA DECIMAL(12,4) NULL,
  impots_taxes DECIMAL(22,2) NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_finances_siren_annee_cloture (siren, annee, date_de_cloture_exercice),
  KEY idx_finances_annee (annee),
  CONSTRAINT fk_finances_entreprise
    FOREIGN KEY (siren) REFERENCES entreprises(siren)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comptes_deposes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren VARCHAR(9) NULL,
  date_depot DATE NULL,
  date_cloture DATE NULL,
  annee_cloture INT NULL,
  type_comptes VARCHAR(64) NULL,
  confidentialite BOOLEAN NULL,
  confidentialite_compte_de_resultat BOOLEAN NULL,
  disponible BOOLEAN NULL,
  nom_fichier_pdf VARCHAR(512) NULL,
  token VARCHAR(512) NULL,
  disponible_xlsx BOOLEAN NULL,
  token_xlsx VARCHAR(512) NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_comptes_siren (siren),
  KEY idx_comptes_token (token(191)),
  CONSTRAINT fk_comptes_entreprise
    FOREIGN KEY (siren) REFERENCES entreprises(siren)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS depots_actes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren VARCHAR(9) NULL,
  token VARCHAR(512) NULL,
  date_depot DATE NULL,
  date_depot_formate VARCHAR(64) NULL,
  disponible BOOLEAN NULL,
  nom_fichier_pdf VARCHAR(512) NULL,
  url TEXT NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_depots_actes_siren (siren),
  KEY idx_depots_actes_token (token(191)),
  CONSTRAINT fk_depots_actes_entreprise
    FOREIGN KEY (siren) REFERENCES entreprises(siren)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS depot_acte_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  depot_acte_id BIGINT UNSIGNED NOT NULL,
  type_acte VARCHAR(512) NULL,
  decision TEXT NULL,
  date_acte DATE NULL,
  date_acte_formate VARCHAR(64) NULL,
  raw_json JSON NULL,
  PRIMARY KEY (id),
  KEY idx_depot_acte_items_depot (depot_acte_id),
  CONSTRAINT fk_depot_acte_items_depot
    FOREIGN KEY (depot_acte_id) REFERENCES depots_actes(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS publications_bodacc (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren VARCHAR(9) NULL,
  numero_parution VARCHAR(128) NULL,
  date_publication DATE NULL,
  numero_annonce VARCHAR(128) NULL,
  annonce_rectificative BOOLEAN NULL,
  bodacc VARCHAR(16) NULL,
  type_publication VARCHAR(128) NULL,
  rcs VARCHAR(255) NULL,
  greffe VARCHAR(255) NULL,
  nom_entreprise VARCHAR(512) NULL,
  personne_morale BOOLEAN NULL,
  denomination VARCHAR(512) NULL,
  forme_juridique VARCHAR(512) NULL,
  adresse TEXT NULL,
  capital DECIMAL(20,2) NULL,
  devise_capital VARCHAR(64) NULL,
  administration TEXT NULL,
  activite TEXT NULL,
  description TEXT NULL,
  commentaires TEXT NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_bodacc_siren (siren),
  KEY idx_bodacc_type_date (type_publication, date_publication),
  CONSTRAINT fk_bodacc_entreprise
    FOREIGN KEY (siren) REFERENCES entreprises(siren)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS procedures_collectives (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren VARCHAR(9) NULL,
  type_procedure VARCHAR(255) NULL,
  famille VARCHAR(255) NULL,
  statut VARCHAR(255) NULL,
  date_debut DATE NULL,
  date_fin DATE NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_procedures_siren (siren),
  KEY idx_procedures_en_cours (date_fin),
  CONSTRAINT fk_procedures_entreprise
    FOREIGN KEY (siren) REFERENCES entreprises(siren)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sanctions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren VARCHAR(9) NULL,
  representant_id BIGINT UNSIGNED NULL,
  autorite VARCHAR(255) NULL,
  pays VARCHAR(128) NULL,
  code_pays VARCHAR(8) NULL,
  en_cours BOOLEAN NULL,
  description TEXT NULL,
  parties JSON NULL,
  montant DECIMAL(22,2) NULL,
  recours BOOLEAN NULL,
  date_debut DATE NULL,
  date_fin DATE NULL,
  sources JSON NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sanctions_siren (siren),
  KEY idx_sanctions_representant (representant_id),
  KEY idx_sanctions_en_cours (en_cours),
  CONSTRAINT fk_sanctions_entreprise
    FOREIGN KEY (siren) REFERENCES entreprises(siren)
    ON DELETE CASCADE,
  CONSTRAINT fk_sanctions_representant
    FOREIGN KEY (representant_id) REFERENCES representants(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS observations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren VARCHAR(9) NULL,
  numero VARCHAR(128) NULL,
  date_ajout DATE NULL,
  texte TEXT NULL,
  etat VARCHAR(128) NULL,
  date_modification DATE NULL,
  date_suppression DATE NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_observations_siren (siren),
  CONSTRAINT fk_observations_entreprise
    FOREIGN KEY (siren) REFERENCES entreprises(siren)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marques (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren VARCHAR(9) NULL,
  marque_id VARCHAR(255) NULL,
  nom VARCHAR(512) NULL,
  statut VARCHAR(255) NULL,
  date_depot DATE NULL,
  date_expiration DATE NULL,
  classes JSON NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_marques_siren (siren),
  KEY idx_marques_nom (nom(191)),
  CONSTRAINT fk_marques_entreprise
    FOREIGN KEY (siren) REFERENCES entreprises(siren)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sites_internet (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren VARCHAR(9) NULL,
  url VARCHAR(1024) NULL,
  source VARCHAR(255) NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sites_siren (siren),
  CONSTRAINT fk_sites_entreprise
    FOREIGN KEY (siren) REFERENCES entreprises(siren)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cartographie_noeuds (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren_centre VARCHAR(9) NOT NULL,
  node_id VARCHAR(64) NOT NULL,
  node_type VARCHAR(64) NOT NULL,
  siren VARCHAR(9) NULL,
  nom VARCHAR(512) NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_carto_node (siren_centre, node_id),
  KEY idx_carto_noeuds_siren_centre (siren_centre),
  KEY idx_carto_noeuds_siren (siren)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cartographie_liens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siren_centre VARCHAR(9) NOT NULL,
  from_node_id VARCHAR(64) NOT NULL,
  to_node_id VARCHAR(64) NOT NULL,
  relation_type VARCHAR(128) NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_carto_liens_siren_centre (siren_centre),
  KEY idx_carto_liens_from_to (from_node_id, to_node_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Immobilier
-- ============================================================

CREATE TABLE IF NOT EXISTS lieux_geocodes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  query_text VARCHAR(1024) NULL,
  label VARCHAR(1024) NULL,
  type_lieu VARCHAR(64) NULL,
  numero VARCHAR(64) NULL,
  rue VARCHAR(512) NULL,
  ville VARCHAR(255) NULL,
  code_postal VARCHAR(16) NULL,
  code_commune VARCHAR(16) NULL,
  contexte VARCHAR(512) NULL,
  latitude DECIMAL(12,8) NULL,
  longitude DECIMAL(12,8) NULL,
  municipalite VARCHAR(255) NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lieux_code_commune (code_commune),
  KEY idx_lieux_code_postal (code_postal),
  KEY idx_lieux_label (label(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parcelles (
  numero VARCHAR(64) NOT NULL,
  parcelle_cadastrale VARCHAR(64) NULL,
  geometrie JSON NULL,
  prefixe VARCHAR(16) NULL,
  section VARCHAR(16) NULL,
  numero_plan VARCHAR(32) NULL,
  adresse TEXT NULL,
  code_commune VARCHAR(16) NULL,
  commune VARCHAR(255) NULL,
  code_departement VARCHAR(8) NULL,
  departement VARCHAR(255) NULL,
  code_region VARCHAR(8) NULL,
  region VARCHAR(255) NULL,
  codes_postaux JSON NULL,
  contenance DECIMAL(18,2) NULL,
  arpente BOOLEAN NULL,
  bounding_box JSON NULL,
  surface_batie DECIMAL(18,2) NULL,
  surface_disponible DECIMAL(18,2) NULL,
  statistiques JSON NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (numero),
  KEY idx_parcelles_code_commune (code_commune),
  KEY idx_parcelles_code_postal_region (code_departement, code_region),
  KEY idx_parcelles_section (section),
  KEY idx_parcelles_contenance (contenance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parcelle_proprietaires (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  parcelle_numero VARCHAR(64) NOT NULL,
  siren VARCHAR(9) NULL,
  nom_entreprise VARCHAR(512) NULL,
  denomination VARCHAR(512) NULL,
  personne_physique BOOLEAN NULL,
  date_creation DATE NULL,
  tranche_effectifs VARCHAR(16) NULL,
  annee_effectifs INT NULL,
  categorie_juridique VARCHAR(16) NULL,
  activite_principale VARCHAR(16) NULL,
  employeur BOOLEAN NULL,
  cessation_activite BOOLEAN NULL,
  monoproprietaire BOOLEAN NULL,
  proprietaire_occupant BOOLEAN NULL,
  lmnp BOOLEAN NULL,
  siege JSON NULL,
  personnes_physiques JSON NULL,
  representants_personnes_morales JSON NULL,
  parcelles JSON NULL,
  emails JSON NULL,
  telephones JSON NULL,
  sites_internet JSON NULL,
  lien_linkedin VARCHAR(1024) NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_parcelle_proprietaires_parcelle (parcelle_numero),
  KEY idx_parcelle_proprietaires_siren (siren),
  KEY idx_parcelle_proprietaires_categorie (categorie_juridique),
  KEY idx_parcelle_proprietaires_activite (activite_principale),
  CONSTRAINT fk_parcelle_proprietaires_parcelle
    FOREIGN KEY (parcelle_numero) REFERENCES parcelles(numero)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parcelle_proprietaire_locaux (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  parcelle_proprietaire_id BIGINT UNSIGNED NOT NULL,
  parcelle_numero VARCHAR(64) NULL,
  code_droit VARCHAR(16) NULL,
  batiment VARCHAR(64) NULL,
  entree VARCHAR(64) NULL,
  niveau VARCHAR(64) NULL,
  porte VARCHAR(64) NULL,
  numero_voie VARCHAR(64) NULL,
  nature_voie VARCHAR(128) NULL,
  nom_voie VARCHAR(512) NULL,
  departement VARCHAR(8) NULL,
  raw_json JSON NULL,
  PRIMARY KEY (id),
  KEY idx_prop_locaux_prop (parcelle_proprietaire_id),
  KEY idx_prop_locaux_parcelle (parcelle_numero),
  CONSTRAINT fk_prop_locaux_proprietaire
    FOREIGN KEY (parcelle_proprietaire_id) REFERENCES parcelle_proprietaires(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parcelle_occupants (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  parcelle_numero VARCHAR(64) NOT NULL,
  siren VARCHAR(9) NULL,
  siret VARCHAR(14) NULL,
  nom_entreprise VARCHAR(512) NULL,
  enseigne VARCHAR(512) NULL,
  date_creation DATE NULL,
  fiabilite_appartenance_parcelle VARCHAR(128) NULL,
  activite_principale VARCHAR(16) NULL,
  nomenclature_activite_principale VARCHAR(64) NULL,
  activite_principale_etablissement VARCHAR(16) NULL,
  nomenclature_activite_principale_etablissement VARCHAR(64) NULL,
  categorie_juridique VARCHAR(16) NULL,
  cessation_activite BOOLEAN NULL,
  siege_json JSON NULL,
  date_entree_lieux DATE NULL,
  etablissement_ferme BOOLEAN NULL,
  date_sortie_lieux DATE NULL,
  procedures_collectives JSON NULL,
  finances JSON NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_parcelle_occupants_parcelle (parcelle_numero),
  KEY idx_parcelle_occupants_siren (siren),
  KEY idx_parcelle_occupants_siret (siret),
  KEY idx_parcelle_occupants_activite (activite_principale),
  CONSTRAINT fk_parcelle_occupants_parcelle
    FOREIGN KEY (parcelle_numero) REFERENCES parcelles(numero)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ventes_immobilieres (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  vente_source_id VARCHAR(255) NULL,
  vente_hash CHAR(64) NULL,
  parcelle_numero VARCHAR(64) NOT NULL,
  date_vente DATE NULL,
  nature VARCHAR(255) NULL,
  valeur_fonciere DECIMAL(22,2) NULL,
  type_local VARCHAR(255) NULL,
  code_type_local VARCHAR(64) NULL,
  surface_reelle_bati DECIMAL(18,2) NULL,
  surface_reelle_bati_totale DECIMAL(18,2) NULL,
  surface_terrain DECIMAL(18,2) NULL,
  nombre_pieces INT NULL,
  nombre_lots INT NULL,
  nature_culture VARCHAR(255) NULL,
  adresse TEXT NULL,
  ancienne_parcelle_cadastrale VARCHAR(64) NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ventes_parcelle (parcelle_numero),
  KEY idx_ventes_date (date_vente),
  KEY idx_ventes_valeur (valeur_fonciere),
  KEY idx_ventes_type_local (type_local),
  KEY idx_ventes_hash (vente_hash),
  CONSTRAINT fk_ventes_parcelle
    FOREIGN KEY (parcelle_numero) REFERENCES parcelles(numero)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vente_lots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  vente_id BIGINT UNSIGNED NOT NULL,
  numero VARCHAR(128) NULL,
  surface_carrez DECIMAL(18,2) NULL,
  raw_json JSON NULL,
  PRIMARY KEY (id),
  KEY idx_vente_lots_vente (vente_id),
  CONSTRAINT fk_vente_lots_vente
    FOREIGN KEY (vente_id) REFERENCES ventes_immobilieres(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vente_parcelles_associees (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  vente_id BIGINT UNSIGNED NOT NULL,
  parcelle_numero VARCHAR(64) NULL,
  raw_json JSON NULL,
  PRIMARY KEY (id),
  KEY idx_vente_parcelles_vente (vente_id),
  KEY idx_vente_parcelles_parcelle (parcelle_numero),
  CONSTRAINT fk_vente_parcelles_vente
    FOREIGN KEY (vente_id) REFERENCES ventes_immobilieres(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS batiments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  batiment_groupe_id VARCHAR(255) NULL,
  parcelle_numero VARCHAR(64) NOT NULL,
  parcelle_principale BOOLEAN NULL,
  code_epci VARCHAR(32) NULL,
  code_iris VARCHAR(32) NULL,
  surface DECIMAL(18,2) NULL,
  annee_construction INT NULL,
  nombre_logements INT NULL,
  hauteur_moyenne DECIMAL(18,4) NULL,
  hauteur_max DECIMAL(18,4) NULL,
  altitude_moyenne_du_sol DECIMAL(18,4) NULL,
  etat VARCHAR(255) NULL,
  materiaux_mur VARCHAR(255) NULL,
  materiaux_toit VARCHAR(255) NULL,
  natures JSON NULL,
  usages JSON NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_batiments_groupe (batiment_groupe_id),
  KEY idx_batiments_parcelle (parcelle_numero),
  KEY idx_batiments_annee (annee_construction),
  CONSTRAINT fk_batiments_parcelle
    FOREIGN KEY (parcelle_numero) REFERENCES parcelles(numero)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dpe (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  identifiant_dpe VARCHAR(255) NULL,
  parcelle_numero VARCHAR(64) NOT NULL,
  batiment_id BIGINT UNSIGNED NULL,
  batiment_groupe_id VARCHAR(255) NULL,
  source VARCHAR(255) NULL,
  arrete_2021 BOOLEAN NULL,
  date_etablissement_dpe DATE NULL,
  date_reception_dpe DATE NULL,
  type_dpe VARCHAR(255) NULL,
  classe_bilan_dpe VARCHAR(8) NULL,
  classe_emission_ges VARCHAR(8) NULL,
  type_batiment_dpe VARCHAR(255) NULL,
  surface_habitable_logement DECIMAL(18,2) NULL,
  surface_habitable_immeuble DECIMAL(18,2) NULL,
  conso_5_usages_ep_m2 DECIMAL(18,4) NULL,
  emission_ges_5_usages_m2 DECIMAL(18,4) NULL,
  classe_conso_energie_arrete_2012 VARCHAR(8) NULL,
  conso_3_usages_ep_m2_arrete_2012 DECIMAL(18,4) NULL,
  type_energie_chauffage VARCHAR(255) NULL,
  type_installation_chauffage VARCHAR(255) NULL,
  type_installation_ecs VARCHAR(255) NULL,
  type_ventilation VARCHAR(255) NULL,
  deperdition_mur DECIMAL(18,4) NULL,
  deperdition_baie_vitree DECIMAL(18,4) NULL,
  deperdition_pont_thermique DECIMAL(18,4) NULL,
  periode_construction_dpe VARCHAR(255) NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dpe_identifiant (identifiant_dpe),
  KEY idx_dpe_parcelle (parcelle_numero),
  KEY idx_dpe_batiment (batiment_id),
  KEY idx_dpe_classe (classe_bilan_dpe),
  KEY idx_dpe_date (date_reception_dpe),
  CONSTRAINT fk_dpe_parcelle
    FOREIGN KEY (parcelle_numero) REFERENCES parcelles(numero)
    ON DELETE CASCADE,
  CONSTRAINT fk_dpe_batiment
    FOREIGN KEY (batiment_id) REFERENCES batiments(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coproprietes (
  numero_immatriculation VARCHAR(128) NOT NULL,
  parcelle_numero VARCHAR(64) NOT NULL,
  nom VARCHAR(512) NULL,
  numero_immatriculation_principal VARCHAR(128) NULL,
  mandat_en_cours VARCHAR(128) NULL,
  nombre_total_lots INT NULL,
  nombre_total_lots_a_usage_habitation_bureaux_commerces INT NULL,
  nombre_lots_a_usage_habitation INT NULL,
  nombre_lots_stationnement INT NULL,
  periode_construction VARCHAR(255) NULL,
  type_syndic VARCHAR(128) NULL,
  siren_syndic VARCHAR(9) NULL,
  syndic_siret VARCHAR(14) NULL,
  syndic_nom VARCHAR(512) NULL,
  syndic_professionnel JSON NULL,
  syndicat_cooperatif JSON NULL,
  syndicat_principal_ou_syndicat_secondaire VARCHAR(255) NULL,
  siren_representant_legal VARCHAR(9) NULL,
  representant_legal JSON NULL,
  date_immatriculation DATE NULL,
  date_reglement_copropriete DATE NULL,
  residence_service BOOLEAN NULL,
  appartenances JSON NULL,
  rattachements_syndicat JSON NULL,
  arretes JSON NULL,
  autres_parcelles JSON NULL,
  nombre_parcelles_cadastrales INT NULL,
  date_mise_a_jour_rnic DATE NULL,
  date_derniere_maj DATE NULL,
  date_fin_dernier_mandat DATE NULL,
  adresse TEXT NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (numero_immatriculation, parcelle_numero),
  KEY idx_copro_parcelle (parcelle_numero),
  KEY idx_copro_syndic_siren (siren_syndic),
  KEY idx_copro_lots (nombre_total_lots),
  CONSTRAINT fk_copro_parcelle
    FOREIGN KEY (parcelle_numero) REFERENCES parcelles(numero)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS copropriete_autres_parcelles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero_immatriculation VARCHAR(128) NOT NULL,
  parcelle_numero_source VARCHAR(64) NOT NULL,
  autre_parcelle_numero VARCHAR(64) NULL,
  raw_json JSON NULL,
  PRIMARY KEY (id),
  KEY idx_copro_autres_numero (numero_immatriculation),
  KEY idx_copro_autres_parcelle_source (parcelle_numero_source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permis_urbanisme (
  numero VARCHAR(255) NOT NULL,
  parcelle_numero VARCHAR(64) NOT NULL,
  type_permis VARCHAR(255) NULL,
  etat VARCHAR(255) NULL,
  date_autorisation DATE NULL,
  denomination_demandeur VARCHAR(512) NULL,
  adresse TEXT NULL,
  superficie_terrain DECIMAL(18,2) NULL,
  zone_operatoire JSON NULL,
  complet JSON NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (numero, parcelle_numero),
  KEY idx_permis_parcelle (parcelle_numero),
  KEY idx_permis_date (date_autorisation),
  KEY idx_permis_type_etat (type_permis, etat),
  CONSTRAINT fk_permis_parcelle
    FOREIGN KEY (parcelle_numero) REFERENCES parcelles(numero)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fonds_de_commerce (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  fonds_hash CHAR(64) NULL,
  parcelle_numero VARCHAR(64) NOT NULL,
  activite TEXT NULL,
  prix DECIMAL(22,2) NULL,
  devise VARCHAR(64) NULL,
  categorie_vente VARCHAR(255) NULL,
  date_debut_activite DATE NULL,
  annonce_bodacc JSON NULL,
  origine_fonds VARCHAR(512) NULL,
  acheteur JSON NULL,
  precedent_proprietaire JSON NULL,
  precedent_exploitant JSON NULL,
  fiabilite_appartenance_parcelle VARCHAR(128) NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_fonds_parcelle (parcelle_numero),
  KEY idx_fonds_hash (fonds_hash),
  KEY idx_fonds_prix (prix),
  KEY idx_fonds_date (date_debut_activite),
  CONSTRAINT fk_fonds_parcelle
    FOREIGN KEY (parcelle_numero) REFERENCES parcelles(numero)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents_urbanisme (
  id VARCHAR(255) NOT NULL,
  parcelle_numero VARCHAR(64) NOT NULL,
  titre VARCHAR(1024) NULL,
  nom VARCHAR(1024) NULL,
  statut VARCHAR(255) NULL,
  type_document VARCHAR(128) NULL,
  statut_legal VARCHAR(255) NULL,
  zones JSON NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id, parcelle_numero),
  KEY idx_documents_urbanisme_parcelle (parcelle_numero),
  KEY idx_documents_urbanisme_type (type_document),
  CONSTRAINT fk_documents_urbanisme_parcelle
    FOREIGN KEY (parcelle_numero) REFERENCES parcelles(numero)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS zones_urbanisme (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  document_id VARCHAR(255) NOT NULL,
  parcelle_numero VARCHAR(64) NOT NULL,
  libelle VARCHAR(255) NULL,
  libelle_long VARCHAR(1024) NULL,
  type_psc VARCHAR(255) NULL,
  stype_psc VARCHAR(255) NULL,
  type_zone VARCHAR(64) NULL,
  date_approbation DATE NULL,
  date_validation DATE NULL,
  nom_fichier VARCHAR(1024) NULL,
  raw_json JSON NULL,
  PRIMARY KEY (id),
  KEY idx_zones_document_parcelle (document_id, parcelle_numero),
  KEY idx_zones_type_zone (type_zone),
  KEY idx_zones_libelle (libelle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS amenagements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  parcelle_numero VARCHAR(64) NOT NULL,
  type_amenagement VARCHAR(128) NULL,
  surface DECIMAL(18,2) NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_amenagements_parcelle (parcelle_numero),
  KEY idx_amenagements_type (type_amenagement),
  CONSTRAINT fk_amenagements_parcelle
    FOREIGN KEY (parcelle_numero) REFERENCES parcelles(numero)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Vues utiles
-- ============================================================

CREATE OR REPLACE VIEW vue_parcelles_proprietaires AS
SELECT
  p.numero AS parcelle_numero,
  p.adresse,
  p.code_commune,
  p.commune,
  p.contenance,
  pp.siren AS proprietaire_siren,
  pp.nom_entreprise AS proprietaire_nom,
  pp.categorie_juridique,
  pp.activite_principale,
  pp.monoproprietaire,
  pp.proprietaire_occupant,
  pp.lmnp
FROM parcelles p
LEFT JOIN parcelle_proprietaires pp
  ON pp.parcelle_numero = p.numero;

CREATE OR REPLACE VIEW vue_parcelles_occupants AS
SELECT
  p.numero AS parcelle_numero,
  p.adresse,
  p.code_commune,
  p.commune,
  po.siren AS occupant_siren,
  po.siret AS occupant_siret,
  po.nom_entreprise AS occupant_nom,
  po.enseigne,
  po.activite_principale,
  po.date_entree_lieux,
  po.etablissement_ferme
FROM parcelles p
LEFT JOIN parcelle_occupants po
  ON po.parcelle_numero = p.numero;

CREATE OR REPLACE VIEW vue_entreprises_immobilier AS
SELECT
  e.siren,
  e.nom_entreprise,
  e.forme_juridique,
  e.code_naf,
  e.libelle_code_naf,
  COUNT(DISTINCT pp.parcelle_numero) AS nb_parcelles_proprietaire,
  COUNT(DISTINCT po.parcelle_numero) AS nb_parcelles_occupant
FROM entreprises e
LEFT JOIN parcelle_proprietaires pp
  ON pp.siren = e.siren
LEFT JOIN parcelle_occupants po
  ON po.siren = e.siren
GROUP BY
  e.siren,
  e.nom_entreprise,
  e.forme_juridique,
  e.code_naf,
  e.libelle_code_naf;

SET FOREIGN_KEY_CHECKS = 1;
