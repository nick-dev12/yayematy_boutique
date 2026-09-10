<?php
/**
 * Modèle pour la gestion du slider
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../conn/conn.php';
require_once __DIR__ . '/../includes/db_helpers.php';

/**
 * Vide le cache des slides affichés sur la page d'accueil.
 */
function slider_bust_home_cache(): void
{
    if (!function_exists('cache_forget')) {
        require_once __DIR__ . '/../includes/simple_cache.php';
    }
    cache_forget('home_slides_actif');
}

/**
 * Récupère tous les slides actifs
 * @param string|null $statut Filtrer par statut ('actif', 'inactif' ou null pour tous)
 * @return array Tableau des slides (vide si aucun ou en cas d'erreur)
 */
function get_all_slides($statut = 'actif')
{
    $db = app_db();
    if (!$db) {
        return [];
    }

    try {
        if ($statut) {
            $stmt = $db->prepare("
                SELECT * FROM slider 
                WHERE statut = :statut 
                ORDER BY ordre ASC, date_creation DESC
            ");
            $stmt->execute(['statut' => $statut]);
        } else {
            $stmt = $db->prepare("
                SELECT * FROM slider 
                ORDER BY ordre ASC, date_creation DESC
            ");
            $stmt->execute();
        }

        $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $slides ? $slides : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Récupère un slide par son ID
 * @param int $id L'ID du slide
 * @return array|false Les données du slide ou False si non trouvé
 */
function get_slide_by_id($id)
{
    $db = app_db();
    if (!$db) {
        return false;
    }

    try {
        $stmt = $db->prepare('SELECT * FROM slider WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $slide = $stmt->fetch(PDO::FETCH_ASSOC);

        return $slide ? $slide : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Crée un nouveau slide
 */
function add_slide($titre, $paragraphe, $image, $bouton_texte = null, $bouton_lien = null, $ordre = 0, $statut = 'actif')
{
    $db = app_db();
    if (!$db) {
        return false;
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO slider (titre, paragraphe, image, bouton_texte, bouton_lien, ordre, statut, date_creation) 
            VALUES (:titre, :paragraphe, :image, :bouton_texte, :bouton_lien, :ordre, :statut, NOW())
        ");

        $result = $stmt->execute([
            'titre' => $titre,
            'paragraphe' => $paragraphe,
            'image' => $image,
            'bouton_texte' => $bouton_texte,
            'bouton_lien' => $bouton_lien,
            'ordre' => $ordre,
            'statut' => $statut,
        ]);

        if ($result) {
            slider_bust_home_cache();
            return $db->lastInsertId();
        }

        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Met à jour un slide
 */
function update_slide($id, $titre, $paragraphe, $image, $bouton_texte = null, $bouton_lien = null, $ordre = 0, $statut = 'actif')
{
    $db = app_db();
    if (!$db) {
        return false;
    }

    try {
        $stmt = $db->prepare("
            UPDATE slider SET
                titre = :titre,
                paragraphe = :paragraphe,
                image = :image,
                bouton_texte = :bouton_texte,
                bouton_lien = :bouton_lien,
                ordre = :ordre,
                statut = :statut,
                date_modification = NOW()
            WHERE id = :id
        ");

        $ok = $stmt->execute([
            'id' => $id,
            'titre' => $titre,
            'paragraphe' => $paragraphe,
            'image' => $image,
            'bouton_texte' => $bouton_texte,
            'bouton_lien' => $bouton_lien,
            'ordre' => $ordre,
            'statut' => $statut,
        ]);
        if ($ok) {
            slider_bust_home_cache();
        }
        return $ok;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Supprime un slide
 */
function delete_slide($id)
{
    $db = app_db();
    if (!$db) {
        return false;
    }

    try {
        $stmt = $db->prepare('DELETE FROM slider WHERE id = :id');
        $ok = $stmt->execute(['id' => $id]);
        if ($ok) {
            slider_bust_home_cache();
        }
        return $ok;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère le chemin de l'image d'un slide
 */
function get_slide_image_path($slide_id)
{
    $slide = get_slide_by_id($slide_id);
    return $slide ? $slide['image'] : false;
}
