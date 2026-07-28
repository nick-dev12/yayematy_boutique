<?php
/**
 * Affichage des entrées admin — fonctionnalités masquées pour Yaye Maty
 * Programmation procédurale uniquement
 */

if (!function_exists('admin_ui_show_invoice')) {
    function admin_ui_show_invoice()
    {
        return false;
    }

    function admin_ui_show_livreurs_gps()
    {
        return true;
    }

    function admin_ui_show_livreurs_map()
    {
        return true;
    }

    function admin_ui_show_users_menu()
    {
        return false;
    }

    function admin_ui_show_param_videos()
    {
        return false;
    }

    function admin_ui_show_param_bulletin_paie()
    {
        return false;
    }

    function admin_ui_show_comptes_absences()
    {
        return false;
    }

    function admin_ui_show_comptes_employes()
    {
        return false;
    }

    function admin_ui_show_commandes_personnalisees()
    {
        return false;
    }
}
