<?php
/**
 * Données du tableau de bord admin — chargement tolérant aux erreurs BDD.
 */

if (!function_exists('admin_dashboard_empty_stats')) {
    function admin_dashboard_empty_stats(): array
    {
        return [
            'montant_total' => 0.0,
            'nb_commandes' => 0,
            'montant_livrees' => 0.0,
            'montant_non_traitees' => 0.0,
            'nb_livrees' => 0,
            'nb_non_traitees' => 0,
        ];
    }
}

if (!function_exists('admin_dashboard_load_data')) {
    /**
     * @return array<string, mixed>
     */
    function admin_dashboard_load_data(): array
    {
        $defaults = [
            'categories' => [],
            'produits_all' => [],
            'nb_produits_total' => 0,
            'total_commandes' => 0,
            'commandes_perso_en_attente' => 0,
            'en_attente' => 0,
            'prise_en_charge' => 0,
            'stats_mois' => admin_dashboard_empty_stats(),
            'stats_jour' => admin_dashboard_empty_stats(),
            'nb_rupture' => 0,
            'nb_promo' => 0,
            'nb_categories' => 0,
            'produits_plus_vendus' => [],
            'produits_aleatoires' => [],
            'data_error' => false,
        ];

        try {
            require_once __DIR__ . '/../../models/model_commandes_admin.php';
            require_once __DIR__ . '/../../models/model_commandes_personnalisees.php';
            require_once __DIR__ . '/../../models/model_produits.php';
            require_once __DIR__ . '/../../models/model_categories.php';

            $categories = get_all_categories();
            $categories = is_array($categories) ? $categories : [];

            $produits_all = get_all_produits();
            $produits_all = is_array($produits_all) ? $produits_all : [];

            $commandes_mois = get_commandes_by_periode('plage', null, null, date('Y-m-01'), date('Y-m-t'));
            $commandes_mois = is_array($commandes_mois) ? $commandes_mois : [];

            $commandes_jour = get_commandes_by_periode('jour');
            $commandes_jour = is_array($commandes_jour) ? $commandes_jour : [];

            $produits_plus_vendus = get_produits_plus_vendus(8);
            if (!is_array($produits_plus_vendus) || empty($produits_plus_vendus)) {
                $fallback = get_all_produits('actif');
                $produits_plus_vendus = is_array($fallback) ? array_slice($fallback, 0, 8) : [];
            }

            $ids_top = array_map(function ($produit) {
                return (int) ($produit['id'] ?? 0);
            }, $produits_plus_vendus);

            $produits_aleatoires = get_produits_aleatoires(10, $ids_top);
            $produits_aleatoires = is_array($produits_aleatoires) ? $produits_aleatoires : [];

            $nb_rupture = count(array_filter($produits_all, function ($produit) {
                return ($produit['statut'] ?? '') === 'rupture_stock' || (int) ($produit['stock'] ?? 0) <= 0;
            }));

            return [
                'categories' => $categories,
                'produits_all' => $produits_all,
                'nb_produits_total' => count($produits_all),
                'total_commandes' => count_commandes_by_statut(),
                'commandes_perso_en_attente' => count_commandes_personnalisees_by_statut('en_attente'),
                'en_attente' => count_commandes_by_statut('en_attente'),
                'prise_en_charge' => count_commandes_by_statut('prise_en_charge'),
                'stats_mois' => get_stats_comptabilite_periode($commandes_mois),
                'stats_jour' => get_stats_comptabilite_periode($commandes_jour),
                'nb_rupture' => $nb_rupture,
                'nb_promo' => function_exists('count_produits_en_promo') ? count_produits_en_promo() : 0,
                'nb_categories' => count($categories),
                'produits_plus_vendus' => $produits_plus_vendus,
                'produits_aleatoires' => $produits_aleatoires,
                'data_error' => false,
            ];
        } catch (Throwable $e) {
            $defaults['data_error'] = true;
            return $defaults;
        }
    }
}
