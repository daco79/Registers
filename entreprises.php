<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

$dbStatus = 'connectee';
try {
    registers_db();
} catch (Throwable $e) {
    $dbStatus = 'indisponible';
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entreprises — Registers</title>
    <link rel="stylesheet" href="assets/css/app.css?v=20260616-1">
</head>
<body>

<nav class="navbar" id="navbar">
    <a class="brand breadcrumb" href="index.php" aria-label="Retour à l'accueil">
        <span class="breadcrumb-home">
            <span class="brand-icon">R</span>
            <span class="brand-name">Registers</span>
        </span>
        <span class="breadcrumb-sep">/</span>
        <span class="breadcrumb-current co">Entreprises</span>
    </a>

    <div class="nav-end">
        <span class="db-pip <?php echo h($dbStatus); ?>"
              title="Base <?php echo h($dbStatus); ?>"></span>
        <a href="immobilier.php" class="nav-link">Immobilier</a>
        <button type="button" class="nav-link" id="btn-sources">Sources</button>
    </div>
</nav>

<div class="page-layout">

    <!-- ===== SIDEBAR FILTRES ===== -->
    <aside class="filter-sidebar">
        <form class="filter-body" id="filter-form" autocomplete="off">

            <div class="filter-section">
                <div class="filter-search-wrap">
                    <svg class="filter-search-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <circle cx="8.5" cy="8.5" r="5.75" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M13 13 17.5 17.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                    <input id="f-q" name="q" type="search" class="filter-input"
                           placeholder="Nom, SIREN, dirigeant…" spellcheck="false">
                </div>
            </div>

            <div class="filter-section">
                <label class="filter-label" for="f-dept">Département / CP</label>
                <input id="f-dept" name="departement" type="text" class="filter-input"
                       placeholder="ex : 75 ou 75011" maxlength="10">
            </div>

            <div class="filter-section">
                <label class="filter-label" for="f-naf">Code NAF</label>
                <input id="f-naf" name="code_naf" type="text" class="filter-input"
                       placeholder="ex : 6820A" maxlength="6">
            </div>

            <div class="filter-section">
                <span class="filter-label">État</span>
                <div class="radio-group">
                    <label class="radio-item">
                        <input type="radio" name="etat" value="A" checked> Actif
                    </label>
                    <label class="radio-item">
                        <input type="radio" name="etat" value="C"> Cessé
                    </label>
                    <label class="radio-item">
                        <input type="radio" name="etat" value=""> Tous
                    </label>
                </div>
            </div>

            <div class="filter-section">
                <label class="filter-label" for="f-effectif">Effectif salarié</label>
                <select id="f-effectif" name="tranche_effectif_salarie" class="filter-select">
                    <option value="">Tous</option>
                    <option value="00">0 salarié</option>
                    <option value="01">1 ou 2</option>
                    <option value="02">3 à 5</option>
                    <option value="03">6 à 9</option>
                    <option value="11">10 à 19</option>
                    <option value="12">20 à 49</option>
                    <option value="21">50 à 99</option>
                    <option value="22">100 à 199</option>
                    <option value="31">200 à 249</option>
                    <option value="32">250 à 499</option>
                    <option value="41">500 à 999</option>
                    <option value="42">1 000 à 1 999</option>
                    <option value="51">2 000 à 4 999</option>
                    <option value="52">5 000 à 9 999</option>
                    <option value="53">10 000 et plus</option>
                </select>
            </div>

            <div class="filter-section">
                <label class="filter-label" for="f-catjur">Catégorie juridique</label>
                <select id="f-catjur" name="categorie_juridique" class="filter-select">
                    <option value="">Toutes</option>
                    <option value="5710">SAS</option>
                    <option value="5720">SASU</option>
                    <option value="5499">SARL</option>
                    <option value="5498">EURL</option>
                    <option value="5410">SA</option>
                    <option value="6540">SCI</option>
                    <option value="6316">SNC</option>
                    <option value="1000">Entrepreneur individuel</option>
                    <option value="9220">Association</option>
                </select>
            </div>

            <button type="submit" class="filter-btn">Rechercher</button>
            <button type="button" class="filter-reset" id="btn-reset">Réinitialiser</button>

        </form>
    </aside>

    <!-- ===== COLONNE RÉSULTATS ===== -->
    <div class="result-col">
        <div class="result-col-head" id="result-meta">
            <span>Résultats</span>
        </div>
        <div class="result-col-list" id="results">
            <div class="empty-msg">Lancez une recherche.</div>
        </div>
        <div class="load-more-wrap hidden" id="load-more-wrap">
            <button type="button" class="load-more-btn" id="load-more-btn">Charger plus</button>
        </div>
    </div>

    <!-- ===== PANNEAU DÉTAIL ===== -->
    <main class="detail-col" id="detail">
        <div class="empty-centered">
            <svg viewBox="0 0 64 64" fill="none" class="empty-illus">
                <rect x="10" y="18" width="44" height="34" rx="3" stroke="currentColor" stroke-width="2"/>
                <path d="M20 18V14a4 4 0 0 1 4-4h16a4 4 0 0 1 4 4v4" stroke="currentColor" stroke-width="2"/>
                <line x1="32" y1="30" x2="32" y2="40" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <line x1="27" y1="35" x2="37" y2="35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <p>Sélectionnez une entreprise pour afficher la fiche.</p>
        </div>
    </main>

</div>

<!-- ===== OVERLAY : Sources ===== -->
<div class="overlay hidden" id="overlay-sources">
    <div class="sheet sheet-md">
        <div class="sheet-head">
            <h2>Sources — Entreprises</h2>
            <button type="button" class="sheet-close" data-close="overlay-sources">✕</button>
        </div>
        <div class="sheet-body">
            <table class="data-table">
                <thead><tr><th>Source</th><th>Usage</th></tr></thead>
                <tbody>
                    <tr><td>API Recherche d'Entreprises</td><td>Sociétés, établissements, dirigeants, finances partielles</td></tr>
                    <tr><td>BODACC Data DILA</td><td>Annonces commerciales par SIREN</td></tr>
                    <tr><td>Géoplateforme</td><td>Géocodage du siège social</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== OVERLAY : JSON ===== -->
<div class="overlay hidden" id="overlay-json">
    <div class="sheet sheet-lg">
        <div class="sheet-head">
            <h2>JSON Registers</h2>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="button" class="btn-sm" id="copy-json">Copier</button>
                <a class="btn-sm" id="download-json" href="#" download>Télécharger</a>
                <button type="button" class="sheet-close" data-close="overlay-json">✕</button>
            </div>
        </div>
        <div class="sheet-body p-0">
            <pre class="json-pre" id="json-output">{}</pre>
        </div>
    </div>
</div>

<script src="assets/js/entreprises.js?v=20260616-1"></script>
</body>
</html>
