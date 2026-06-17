<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

$dbStatus = 'connectee';
$dbError  = null;
$tableCount = 0;

try {
    $pdo = registers_db();
    $tableCount = count(fetch_all($pdo, "
        SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()
    "));
} catch (Throwable $e) {
    $dbStatus = 'indisponible';
    $dbError  = $e->getMessage();
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registers — Palette 2 : Parchemin & Espresso</title>
    <link rel="stylesheet" href="assets/css/app.css?v=20260615-4">
    <link rel="stylesheet" href="assets/css/palette2.css">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar" id="navbar">
    <a class="brand" href="#" id="brand-link" aria-label="Accueil">
        <span class="brand-icon">R</span>
        <span class="brand-name">Registers</span>
    </a>

    <!-- Recherche compacte — visible uniquement en mode workspace -->
    <form class="nav-search hidden" id="search-form" role="search">
        <div class="search-wrap">
            <svg class="search-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <circle cx="8.5" cy="8.5" r="5.75" stroke="currentColor" stroke-width="1.7"/>
                <path d="M13 13 17.5 17.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
            <input id="search-query" type="search"
                   placeholder="Société, SIREN, dirigeant, adresse, parcelle…"
                   autocomplete="off" spellcheck="false" aria-label="Recherche">
            <select id="search-type" aria-label="Type de recherche">
                <option value="all">Tout</option>
                <option value="company">Entreprise</option>
                <option value="person">Personne</option>
                <option value="address">Adresse</option>
                <option value="parcel">Parcelle</option>
            </select>
        </div>
        <button type="submit" class="btn-search">Rechercher</button>
    </form>

    <div class="nav-end">
        <span class="db-pip <?php echo h($dbStatus); ?>"
              title="Base <?php echo h($dbStatus); ?> — <?php echo (int)$tableCount; ?> objets"></span>
        <button type="button" class="nav-link" id="btn-sources">Sources</button>
        <button type="button" class="nav-link" id="btn-schema">Schéma</button>
    </div>
</nav>

<?php if ($dbError): ?>
<div class="alert-bar" role="alert"><?php echo h($dbError); ?></div>
<?php endif; ?>

<!-- ===== LANDING ===== -->
<section class="landing" id="landing">
    <div class="landing-inner">
        <h1 class="landing-title">Registers</h1>
        <p class="landing-sub">Sociétés, dirigeants, immobilier, parcelles —<br>toutes vos données croisées à la demande.</p>

        <!-- Barre de recherche principale -->
        <form class="hero-search" id="hero-form" role="search">
            <div class="hero-wrap">
                <svg class="hero-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <circle cx="8.5" cy="8.5" r="5.75" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M13 13 17.5 17.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                <input id="hero-query" type="search"
                       placeholder="Société, SIREN, dirigeant, adresse, parcelle…"
                       autocomplete="off" spellcheck="false" aria-label="Recherche">
                <select id="hero-type" aria-label="Type de recherche">
                    <option value="all">Tout</option>
                    <option value="company">Entreprise</option>
                    <option value="person">Personne</option>
                    <option value="address">Adresse</option>
                    <option value="parcel">Parcelle</option>
                </select>
            </div>
            <button type="submit" class="hero-btn">Rechercher</button>
        </form>

        <!-- Domaines -->
        <div class="domain-cards">
            <button type="button" class="domain-card co" data-domain="company">
                <span class="domain-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="15" rx="1.5"/>
                        <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                        <line x1="12" y1="12" x2="12" y2="17"/>
                        <line x1="9" y1="14.5" x2="15" y2="14.5"/>
                    </svg>
                </span>
                <strong>Entreprises</strong>
                <small>Sociétés · SIREN · Dirigeants · BODACC · Finances</small>
            </button>

            <button type="button" class="domain-card re" data-domain="address">
                <span class="domain-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V10.5Z"/>
                        <path d="M9 21V12h6v9"/>
                    </svg>
                </span>
                <strong>Immobilier</strong>
                <small>Adresses · Parcelles · DVF · DPE · Copropriétés</small>
            </button>

            <a class="domain-card est" href="https://www.estimatiz.fr/">
                <span class="domain-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2 2 7l10 5 10-5-10-5Z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </span>
                <strong>Estimatiz</strong>
                <small>Estimation · Prix au m² · Ventes DVF</small>
            </a>
        </div>

        <div class="landing-sources">
            <span class="src-chip">API Entreprises</span>
            <span class="src-chip">Géoplateforme</span>
            <span class="src-chip">DVF</span>
            <span class="src-chip">RNIC</span>
            <span class="src-chip">ADEME DPE</span>
            <span class="src-chip">BODACC</span>
        </div>
    </div>
</section>

<!-- ===== WORKSPACE ===== -->
<div class="workspace hidden" id="workspace">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-head">
            <div class="result-meta" id="result-meta"></div>
            <div class="type-filters" id="type-filters"></div>
        </div>
        <div class="results-list" id="results">
            <div class="empty-msg">Lance une recherche.</div>
        </div>
    </aside>

    <main class="detail-panel" id="detail">
        <div class="empty-centered">
            <svg viewBox="0 0 64 64" fill="none" class="empty-illus">
                <circle cx="28" cy="28" r="16" stroke="currentColor" stroke-width="2"/>
                <path d="M40 40 56 56" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <p>Sélectionne un résultat pour afficher la fiche.</p>
        </div>
    </main>
</div>

<!-- ===== OVERLAY : Sources ===== -->
<div class="overlay hidden" id="overlay-sources">
    <div class="sheet sheet-md">
        <div class="sheet-head">
            <h2>Sources branchées</h2>
            <button type="button" class="sheet-close" data-close="overlay-sources">✕</button>
        </div>
        <div class="sheet-body">
            <p class="text-muted mb-16">
                Registers interroge ces sources à la demande. Aucune copie nationale n'est réalisée.
            </p>
            <table class="data-table">
                <thead>
                    <tr><th>Domaine</th><th>Source</th><th>Usage</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge type-company">Entreprise</span></td>
                        <td>API Recherche d'Entreprises</td>
                        <td>Sociétés, établissements, dirigeants, finances partielles</td>
                    </tr>
                    <tr>
                        <td><span class="badge type-person">Personne</span></td>
                        <td>API Recherche d'Entreprises</td>
                        <td>Recherche par nom de dirigeant, sociétés associées</td>
                    </tr>
                    <tr>
                        <td><span class="badge type-address">Adresse</span></td>
                        <td>Géoplateforme (data.geopf.fr)</td>
                        <td>Adresses normalisées, coordonnées, code commune</td>
                    </tr>
                    <tr>
                        <td><span class="badge type-parcel">Cadastre</span></td>
                        <td>Géoplateforme / Parcellaire Express</td>
                        <td>Parcelles proches d'une adresse ou par identifiant</td>
                    </tr>
                    <tr>
                        <td><span class="badge type-parcel">Ventes</span></td>
                        <td>DVF géolocalisé</td>
                        <td>Mutations par parcelle, streaming CSV départemental</td>
                    </tr>
                    <tr>
                        <td><span class="badge type-copro">Copropriétés</span></td>
                        <td>RNIC via API tabulaire data.gouv</td>
                        <td>Copropriétés par adresse ou références cadastrales</td>
                    </tr>
                    <tr>
                        <td><span class="badge type-dpe">DPE</span></td>
                        <td>ADEME Data Fair</td>
                        <td>DPE logements existants par adresse</td>
                    </tr>
                    <tr>
                        <td><span class="badge type-company">Annonces</span></td>
                        <td>BODACC Data DILA</td>
                        <td>Annonces commerciales par SIREN</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== OVERLAY : Schéma ===== -->
<div class="overlay hidden" id="overlay-schema">
    <div class="sheet sheet-md">
        <div class="sheet-head">
            <h2>Schéma local — Registers</h2>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="button" class="btn-sm" id="refresh-schema">Rafraîchir</button>
                <button type="button" class="sheet-close" data-close="overlay-schema">✕</button>
            </div>
        </div>
        <div class="sheet-body">
            <div id="schema-table"><div class="empty-msg">Chargement…</div></div>
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

<script src="assets/js/app.js?v=20260615-4"></script>
</body>
</html>
