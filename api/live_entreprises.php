<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/live_sources.php';

/**
 * Recherche d'entreprises avec filtres — modele Registers/Pappers
 *
 * Parametres acceptes :
 *   q                          texte libre (nom, SIREN, SIRET)
 *   code_postal                ex: 75011
 *   departement                ex: 75
 *   region                     ex: 11
 *   code_naf                   ex: 6820A
 *   categorie_juridique        ex: 5710
 *   tranche_effectif_salarie   ex: 11 (code INSEE tranches effectifs)
 *   etat_administratif         A (active) | C (cessee)
 *   entreprise_cessee          true | false  (alias de etat_administratif)
 *   est_entrepreneur_individuel true | false
 *   est_association            true | false
 *   est_organisme_formation    true | false
 *   page                       numero de page (defaut 1)
 *   par_page                   resultats par page, max 25 (defaut 10)
 *
 * Reponse :
 *   { ok, mode, total, page, par_page, resultats: [...entreprises], source }
 */

try {
    $filters = [
        'q'                          => request_string('q'),
        'code_postal'                => request_string('code_postal'),
        'departement'                => request_string('departement'),
        'region'                     => request_string('region'),
        'code_naf'                   => request_string('code_naf'),
        'categorie_juridique'        => request_string('categorie_juridique'),
        'tranche_effectif_salarie'   => request_string('tranche_effectif_salarie'),
        'etat_administratif'         => request_string('etat_administratif'),
    ];

    // Booleens optionnels
    foreach (['entreprise_cessee', 'est_entrepreneur_individuel', 'est_association', 'est_organisme_formation'] as $key) {
        $val = request_string($key);
        if ($val !== '') {
            $filters[$key] = in_array(strtolower($val), ['true', '1', 'oui'], true);
        }
    }

    // Supprimer les chaines vides
    $filters = array_filter($filters, fn ($v) => $v !== null && $v !== '');

    $page    = max(1, (int) request_string('page', '1'));
    $perPage = max(1, min(25, (int) request_string('par_page', '10')));

    $result = live_search_entreprises($filters, $page, $perPage);

    json_response([
        'ok'       => true,
        'mode'     => 'live',
        'total'    => $result['total'],
        'page'     => $result['page'],
        'par_page' => $result['par_page'],
        'resultats'=> $result['resultats'],
        'source'   => $result['source'],
    ]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
