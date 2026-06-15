<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

$dbStatus = 'connectee';
$dbError = null;
$tableCount = 0;

try {
    $pdo = registers_db();
    $tableCount = count(fetch_all($pdo, "
        SELECT TABLE_NAME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
    "));
} catch (Throwable $e) {
    $dbStatus = 'indisponible';
    $dbError = $e->getMessage();
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registers</title>
    <link rel="stylesheet" href="assets/css/app.css?v=20260614-3">
</head>
<body>
    <header class="topbar">
        <div>
            <p class="eyebrow">Recherche publique live</p>
            <h1>Registers</h1>
            <p class="tagline">Interroger les sources publiques, croiser les donnees entreprises et immobilier, puis produire une fiche JSON structuree sans importer massivement les bases nationales.</p>
        </div>
        <div class="status <?php echo h($dbStatus); ?>">
            <span></span>
            <strong><?php echo h($dbStatus); ?></strong>
            <small><?php echo (int) $tableCount; ?> objets</small>
        </div>
    </header>

    <?php if ($dbError): ?>
        <section class="alert" role="alert">
            <?php echo h($dbError); ?>
        </section>
    <?php endif; ?>

    <nav class="tabs" aria-label="Sections">
        <button class="tab active" type="button" data-tab="explorer">Explorer</button>
        <button class="tab" type="button" data-tab="connect">Sources</button>
        <button class="tab" type="button" data-tab="json">JSON</button>
        <button class="tab" type="button" data-tab="schema">Schema</button>
    </nav>

    <main>
        <section class="panel active" id="tab-explorer">
            <form class="querybar" id="search-form">
                <label>
                    <span>Type</span>
                    <select id="search-type">
                        <option value="all">Tout</option>
                        <option value="company">Entreprise</option>
                        <option value="person">Personne</option>
                        <option value="address">Adresse</option>
                        <option value="parcel">Parcelle</option>
                    </select>
                </label>
                <label class="grow">
                    <span>Recherche</span>
                    <input id="search-query" type="search" placeholder="Societe, SIREN, SIRET, dirigeant, adresse, parcelle..." autocomplete="off">
                </label>
                <button type="submit">Rechercher</button>
            </form>

            <div class="workspace">
                <aside class="results" id="results">
                    <div class="empty">Lance une recherche : societe, dirigeant, adresse ou parcelle. Registers interroge les sources publiques en direct.</div>
                </aside>
                <section class="detail" id="detail">
                    <div class="empty large">Selectionne un resultat pour obtenir une fiche lisible et le JSON complet au modele Registers.</div>
                </section>
            </div>
        </section>

        <section class="panel" id="tab-connect">
            <div class="connect-grid">
                <section class="tool-block">
                    <h2>Objectif</h2>
                    <p class="tool-copy">Registers agrege les donnees publiques disponibles a la demande. La base locale sert de modele et de cache eventuel, pas de copie nationale complete.</p>
                    <p class="tool-copy">Les proprietaires fonciers actuels nominatifs ne sont pas fournis sans acces Fichiers fonciers / MAJIC Cerema ; les mutations DVF et DVF+ restent des indices, pas une certification de propriete actuelle.</p>
                </section>

                <section class="tool-block">
                    <h2>Sources branchees</h2>
                    <table class="mini-table">
                        <thead><tr><th>Domaine</th><th>Source</th><th>Usage</th></tr></thead>
                        <tbody>
                            <tr><td>Entreprises</td><td>API Recherche d'Entreprises</td><td>Societes, etablissements, dirigeants, finances partielles</td></tr>
                            <tr><td>Personnes</td><td>API Recherche d'Entreprises</td><td>Recherche par nom de dirigeant et retour des societes associees</td></tr>
                            <tr><td>Adresse</td><td>Geocodage Geoplateforme</td><td>Adresses, coordonnees, code commune</td></tr>
                            <tr><td>Cadastre</td><td>Geocodage Geoplateforme / Parcellaire Express</td><td>Parcelles proches d'une adresse ou recherche par identifiant</td></tr>
                            <tr><td>Ventes</td><td>DVF geolocalise</td><td>Mutations par parcelle, streaming CSV departemental</td></tr>
                            <tr><td>Coproprietes</td><td>RNIC via API tabulaire data.gouv</td><td>Copro par adresse ou references cadastrales</td></tr>
                            <tr><td>DPE</td><td>ADEME Data Fair</td><td>DPE logements existants par adresse</td></tr>
                            <tr><td>Annonces</td><td>BODACC Data DILA</td><td>Annonces commerciales par SIREN</td></tr>
                        </tbody>
                    </table>
                </section>

                <section class="tool-block">
                    <h2>Test entreprise</h2>
                    <form class="querybar compact" id="company-import-form">
                        <label class="grow">
                            <span>SIREN ou SIRET</span>
                            <input id="company-identifier" type="text" inputmode="numeric" placeholder="Ex. 552100554">
                        </label>
                        <button type="submit">Interroger</button>
                    </form>
                    <pre class="connect-output" id="company-import-output">Pret.</pre>
                </section>

                <section class="tool-block">
                    <h2>Test adresse</h2>
                    <form class="querybar compact" id="geocode-form">
                        <label class="grow">
                            <span>Adresse</span>
                            <input id="geocode-query" type="text" placeholder="Ex. 10 rue Ordener 75018 Paris">
                        </label>
                        <button type="submit">Interroger</button>
                    </form>
                    <pre class="connect-output" id="geocode-output">Pret.</pre>
                </section>
            </div>
        </section>

        <section class="panel" id="tab-json">
            <form class="querybar" id="json-form">
                <label>
                    <span>Type</span>
                    <select id="json-type">
                        <option value="company">Entreprise</option>
                        <option value="address">Adresse</option>
                        <option value="parcel">Parcelle</option>
                    </select>
                </label>
                <label class="grow">
                    <span>Identifiant</span>
                    <input id="json-id" type="text" placeholder="SIREN/SIRET, adresse, numero de parcelle...">
                </label>
                <label>
                    <span>Mode</span>
                    <select id="json-mode">
                        <option value="live">Live</option>
                    </select>
                </label>
                <button type="submit">Generer</button>
            </form>

            <div class="json-actions">
                <button type="button" id="copy-json">Copier</button>
                <a class="button muted" id="download-json" href="#" aria-disabled="true">Telecharger</a>
            </div>
            <pre class="json-output" id="json-output">{}</pre>
        </section>

        <section class="panel" id="tab-schema">
            <div class="schema-head">
                <h2>Structure locale</h2>
                <button type="button" id="refresh-schema">Rafraichir</button>
            </div>
            <div class="schema-table" id="schema-table">
                <div class="empty">Chargement du schema...</div>
            </div>
        </section>
    </main>

    <script src="assets/js/app.js?v=20260614-3"></script>
</body>
</html>
