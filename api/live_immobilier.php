<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/live_sources.php';

/**
 * Recherche immobilier multi-source — modele Registers
 *
 * Parametres acceptes :
 *   source          dvf (defaut) | rnic | dpe
 *   code_commune    code INSEE 5 chiffres (ex: 75111)
 *   code_postal     ex: 75011  — utilisé si code_commune absent
 *   departement     ex: 75     — déduit automatiquement si absent
 *
 *   [DVF uniquement]
 *   type_bien       Appartement | Maison | Local | Dependance
 *   surface_min / surface_max   surface bâtie en m²
 *   prix_min / prix_max         valeur foncière en €
 *   annee           année de mutation (defaut: année en cours - 1)
 *
 *   [DPE uniquement]
 *   classe_dpe      A | B | C | D | E | F | G
 *   type_batiment   appartement | maison | immeuble
 *   surface_min / surface_max
 *
 *   page            numero de page (defaut 1)
 *   par_page        resultats par page, max 50 (defaut 10)
 *
 * Reponse :
 *   { ok, mode, source_type, total, page, par_page, resultats: [...], source }
 */

try {
    $source = strtolower(request_string('source', 'dvf'));

    $filters = [
        'source'       => $source,
        'code_commune' => request_string('code_commune'),
        'code_postal'  => request_string('code_postal'),
        'departement'  => request_string('departement'),
    ];

    // Filtres DVF
    if ($source === 'dvf') {
        foreach (['type_bien', 'annee'] as $k) {
            $v = request_string($k);
            if ($v !== '') {
                $filters[$k] = $v;
            }
        }
        foreach (['surface_min', 'surface_max', 'prix_min', 'prix_max'] as $k) {
            $v = request_string($k);
            if ($v !== '' && is_numeric($v)) {
                $filters[$k] = (float) $v;
            }
        }
    }

    // Filtres DPE
    if ($source === 'dpe') {
        foreach (['classe_dpe', 'type_batiment'] as $k) {
            $v = request_string($k);
            if ($v !== '') {
                $filters[$k] = $v;
            }
        }
        foreach (['surface_min', 'surface_max'] as $k) {
            $v = request_string($k);
            if ($v !== '' && is_numeric($v)) {
                $filters[$k] = (float) $v;
            }
        }
    }

    // Supprimer les chaines vides
    $filters = array_filter($filters, fn ($v) => $v !== null && $v !== '');

    $page    = max(1, (int) request_string('page', '1'));
    $perPage = max(1, min(50, (int) request_string('par_page', '10')));

    $result = live_search_immobilier($filters, $page, $perPage);

    json_response([
        'ok'          => true,
        'mode'        => 'live',
        'source_type' => $source,
        'total'       => $result['total'],
        'page'        => $result['page'],
        'par_page'    => $result['par_page'],
        'resultats'   => $result['resultats'],
        'source'      => $result['source'],
    ]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
