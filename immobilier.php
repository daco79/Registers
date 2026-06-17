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
    <title>Immobilier — Registers</title>
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
        <span class="breadcrumb-current re">Immobilier</span>
    </a>

    <div class="nav-end">
        <span class="db-pip <?php echo h($dbStatus); ?>"
              title="Base <?php echo h($dbStatus); ?>"></span>
        <a href="entreprises.php" class="nav-link">Entreprises</a>
        <button type="button" class="nav-link" id="btn-sources">Sources</button>
    </div>
</nav>

<div class="page-layout">

    <!-- ===== SIDEBAR FILTRES ===== -->
    <aside class="filter-sidebar">
        <div class="filter-body">

            <!-- Onglets DVF / DPE / RNIC -->
            <div class="source-tabs" id="source-tabs" style="margin:0 -16px;margin-top:-16px;flex-shrink:0">
                <button type="button" class="source-tab active" data-tab="dvf">DVF</button>
                <button type="button" class="source-tab" data-tab="dpe">DPE</button>
                <button type="button" class="source-tab" data-tab="rnic">RNIC</button>
            </div>

            <!-- ===== PANNEAU DVF ===== -->
            <form id="form-dvf" class="tab-panel active" autocomplete="off">
                <div class="filter-section">
                    <label class="filter-label" for="dvf-cp">Code postal ou commune</label>
                    <input id="dvf-cp" name="code_postal" type="text" class="filter-input re-focus"
                           placeholder="ex : 75011 ou 75056">
                </div>

                <div class="filter-section">
                    <label class="filter-label" for="dvf-type">Type de bien</label>
                    <select id="dvf-type" name="type_bien" class="filter-select re-focus">
                        <option value="">Tous</option>
                        <option value="Appartement">Appartement</option>
                        <option value="Maison">Maison</option>
                        <option value="Local">Local commercial</option>
                        <option value="Dependance">Dépendance</option>
                    </select>
                </div>

                <div class="filter-section">
                    <span class="filter-label">Surface bâtie (m²)</span>
                    <div class="filter-range">
                        <input name="surface_min" type="number" class="filter-input re-focus"
                               placeholder="min" min="0" step="1">
                        <span class="filter-range-sep">—</span>
                        <input name="surface_max" type="number" class="filter-input re-focus"
                               placeholder="max" min="0" step="1">
                    </div>
                </div>

                <div class="filter-section">
                    <span class="filter-label">Prix (€)</span>
                    <div class="filter-range">
                        <input name="prix_min" type="number" class="filter-input re-focus"
                               placeholder="min" min="0" step="1000">
                        <span class="filter-range-sep">—</span>
                        <input name="prix_max" type="number" class="filter-input re-focus"
                               placeholder="max" min="0" step="1000">
                    </div>
                </div>

                <div class="filter-section">
                    <label class="filter-label" for="dvf-annee">Année</label>
                    <select id="dvf-annee" name="annee" class="filter-select re-focus">
                        <option value="">Toutes</option>
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                        <option value="2022">2022</option>
                        <option value="2021">2021</option>
                        <option value="2020">2020</option>
                        <option value="2019">2019</option>
                    </select>
                </div>

                <button type="submit" class="filter-btn re-btn">Rechercher</button>
                <button type="button" class="filter-reset" data-reset="form-dvf">Réinitialiser</button>
            </form>

            <!-- ===== PANNEAU DPE ===== -->
            <form id="form-dpe" class="tab-panel" autocomplete="off">
                <div class="filter-section">
                    <label class="filter-label" for="dpe-cp">Code postal ou commune</label>
                    <input id="dpe-cp" name="code_postal" type="text" class="filter-input re-focus"
                           placeholder="ex : 75011 ou 75056">
                </div>

                <div class="filter-section">
                    <label class="filter-label" for="dpe-classe">Classe DPE</label>
                    <select id="dpe-classe" name="classe_dpe" class="filter-select re-focus">
                        <option value="">Toutes</option>
                        <option value="A">A — Très performant</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                        <option value="F">F</option>
                        <option value="G">G — Très énergivore</option>
                    </select>
                </div>

                <div class="filter-section">
                    <label class="filter-label" for="dpe-type">Type de bâtiment</label>
                    <select id="dpe-type" name="type_batiment" class="filter-select re-focus">
                        <option value="">Tous</option>
                        <option value="appartement">Appartement</option>
                        <option value="maison">Maison</option>
                        <option value="immeuble">Immeuble</option>
                    </select>
                </div>

                <div class="filter-section">
                    <span class="filter-label">Surface (m²)</span>
                    <div class="filter-range">
                        <input name="surface_min" type="number" class="filter-input re-focus"
                               placeholder="min" min="0" step="1">
                        <span class="filter-range-sep">—</span>
                        <input name="surface_max" type="number" class="filter-input re-focus"
                               placeholder="max" min="0" step="1">
                    </div>
                </div>

                <button type="submit" class="filter-btn re-btn">Rechercher</button>
                <button type="button" class="filter-reset" data-reset="form-dpe">Réinitialiser</button>
            </form>

            <!-- ===== PANNEAU RNIC ===== -->
            <form id="form-rnic" class="tab-panel" autocomplete="off">
                <div class="filter-section">
                    <label class="filter-label" for="rnic-cp">Code postal ou commune</label>
                    <input id="rnic-cp" name="code_postal" type="text" class="filter-input re-focus"
                           placeholder="ex : 75011 ou 75056">
                </div>

                <button type="submit" class="filter-btn re-btn">Rechercher</button>
                <button type="button" class="filter-reset" data-reset="form-rnic">Réinitialiser</button>
            </form>

        </div>
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
                <path d="M8 52 32 12l24 40H8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <line x1="32" y1="30" x2="32" y2="40" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="32" cy="46" r="1.5" fill="currentColor"/>
            </svg>
            <p>Sélectionnez un résultat pour afficher le détail.</p>
        </div>
    </main>

</div>

<!-- ===== OVERLAY : Sources ===== -->
<div class="overlay hidden" id="overlay-sources">
    <div class="sheet sheet-md">
        <div class="sheet-head">
            <h2>Sources — Immobilier</h2>
            <button type="button" class="sheet-close" data-close="overlay-sources">✕</button>
        </div>
        <div class="sheet-body">
            <table class="data-table">
                <thead><tr><th>Source</th><th>Données</th></tr></thead>
                <tbody>
                    <tr><td>DVF géolocalisé</td><td>Mutations immobilières, prix de vente, surfaces — streaming CSV départemental</td></tr>
                    <tr><td>ADEME Data Fair</td><td>DPE logements existants, classe énergétique, GES, surface</td></tr>
                    <tr><td>RNIC (ANAH)</td><td>Copropriétés : lots, syndic, période de construction</td></tr>
                    <tr><td>Géoplateforme</td><td>Parcelles cadastrales proches d'une adresse</td></tr>
                    <tr><td>RNB beta.gouv</td><td>Bâtiments par bbox GPS</td></tr>
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

<script src="assets/js/immobilier.js?v=20260616-1"></script>
</body>
</html>
