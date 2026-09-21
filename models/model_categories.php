<?php
/**
 * Modèle pour la gestion des catégories
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../conn/conn.php';
require_once __DIR__ . '/../includes/db_helpers.php';

/**
 * Vide le cache des sous-catégories affichées sur categorie.php.
 */
function categories_flush_subcategories_cache(?int $parent_id = null): void
{
    if (!function_exists('cache_forget')) {
        require_once __DIR__ . '/../includes/simple_cache.php';
    }
    if ($parent_id !== null && $parent_id > 0) {
        cache_forget('subcats_parent_' . $parent_id);
        return;
    }
    $parents = get_parent_categories();
    foreach ($parents as $p) {
        $pid = (int) ($p['id'] ?? 0);
        if ($pid > 0) {
            cache_forget('subcats_parent_' . $pid);
        }
    }
}

/**
 * @return PDO|null
 */
function categories_db()
{
    return app_db();
}

/**
 * Indique si la table categories existe.
 */
function categories_table_exists(): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $db = categories_db();
    if (!$db) {
        $cached = false;
        return false;
    }

    try {
        $stmt = $db->query("SHOW TABLES LIKE 'categories'");
        $cached = (bool) $stmt->fetch(PDO::FETCH_NUM);
        return $cached;
    } catch (PDOException $e) {
        $cached = false;
        return false;
    }
}

/**
 * Indique si la colonne parent_id existe (sous-catégories).
 */
function categories_has_parent_id_column()
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $db = categories_db();
    if (!$db) {
        $cached = false;
        return false;
    }

    try {
        $stmt = $db->query("SHOW COLUMNS FROM categories LIKE 'parent_id'");
        $cached = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        return $cached;
    } catch (PDOException $e) {
        $cached = false;
        return false;
    }
}

/**
 * Catégories principales (sans parent) — pour rattacher une sous-catégorie.
 *
 * @return array<int, array>
 */
/**
 * Sous-catégories avec nom du parent et nombre de produits.
 *
 * @return array<int, array>
 */
function get_all_subcategories_with_count()
{
    $db = categories_db();
    if (!$db || !categories_has_parent_id_column()) {
        return [];
    }

    try {
        $stmt = $db->prepare("
            SELECT c.*, parent.nom AS parent_nom, COUNT(p.id) AS nb_produits
            FROM categories c
            INNER JOIN categories parent ON parent.id = c.parent_id
            LEFT JOIN produits p ON p.categorie_id = c.id AND p.statut = 'actif'
            WHERE c.parent_id IS NOT NULL AND c.parent_id > 0
            GROUP BY c.id
            ORDER BY parent.nom ASC, c.nom ASC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function get_parent_categories()
{
    $db = categories_db();
    if (!$db) {
        return [];
    }

    try {
        if (categories_has_parent_id_column()) {
            $stmt = $db->prepare('
                SELECT * FROM categories
                WHERE parent_id IS NULL OR parent_id = 0
                ORDER BY nom ASC
            ');
        } else {
            $stmt = $db->prepare('SELECT * FROM categories ORDER BY nom ASC');
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Sous-catégories d'une catégorie parente.
 *
 * @return array<int, array<string, mixed>>
 */
function get_subcategories_by_parent_id($parent_id)
{
    $parent_id = (int) $parent_id;
    if ($parent_id <= 0 || !categories_has_parent_id_column()) {
        return [];
    }

    $db = categories_db();
    if (!$db) {
        return [];
    }

    try {
        $stmt = $db->prepare('
            SELECT id, nom, image, parent_id
            FROM categories
            WHERE parent_id = :parent_id
            ORDER BY nom ASC
        ');
        $stmt->execute(['parent_id' => $parent_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Récupère toutes les catégories actives
 * @return array Tableau des catégories ou [] si indisponible
 */
function get_all_categories()
{
    $db = categories_db();
    if (!$db) {
        return [];
    }

    try {
        $stmt = $db->prepare('SELECT * FROM categories ORDER BY nom ASC');
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $categories ? $categories : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Récupère une catégorie par son ID
 * @param int $id L'ID de la catégorie
 * @return array|false Les données de la catégorie ou False si non trouvée
 */
function get_categorie_by_id($id)
{
    $db = categories_db();
    if (!$db) {
        return false;
    }

    try {
        $stmt = $db->prepare('SELECT * FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $categorie = $stmt->fetch(PDO::FETCH_ASSOC);

        return $categorie ? $categorie : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère une catégorie par son nom
 * @param string $nom Le nom de la catégorie
 * @return array|false Les données de la catégorie ou False si non trouvée
 */
function get_categorie_by_nom($nom)
{
    $db = categories_db();
    if (!$db) {
        return false;
    }

    try {
        $stmt = $db->prepare('SELECT * FROM categories WHERE nom = :nom');
        $stmt->execute(['nom' => $nom]);
        $categorie = $stmt->fetch(PDO::FETCH_ASSOC);

        return $categorie ? $categorie : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Crée une nouvelle catégorie
 * @param string $nom Le nom de la catégorie
 * @param string $description La description
 * @param string|null $image Le chemin de l'image
 * @return int|false L'ID de la catégorie créée ou False en cas d'erreur
 */
function create_categorie($nom, $description = null, $image = null, $parent_id = null)
{
    $db = categories_db();
    if (!$db) {
        return false;
    }

    $parent_id = $parent_id !== null && $parent_id !== '' ? (int) $parent_id : null;
    if ($parent_id !== null && $parent_id <= 0) {
        $parent_id = null;
    }

    try {
        if (categories_has_parent_id_column()) {
            if ($parent_id !== null) {
                $check = $db->prepare('SELECT id FROM categories WHERE id = :id LIMIT 1');
                $check->execute(['id' => $parent_id]);
                if (!$check->fetch(PDO::FETCH_ASSOC)) {
                    return false;
                }
            }
            $stmt = $db->prepare('
                INSERT INTO categories (parent_id, nom, description, image, date_creation)
                VALUES (:parent_id, :nom, :description, :image, NOW())
            ');
            $result = $stmt->execute([
                'parent_id' => $parent_id,
                'nom' => $nom,
                'description' => $description,
                'image' => $image,
            ]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO categories (nom, description, image, date_creation) 
                VALUES (:nom, :description, :image, NOW())
            ");
            $result = $stmt->execute([
                'nom' => $nom,
                'description' => $description,
                'image' => $image,
            ]);
        }

        if ($result) {
            $new_id = (int) $db->lastInsertId();
            categories_flush_subcategories_cache($parent_id);
            return $new_id;
        }

        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Met à jour une catégorie
 * @param int $id L'ID de la catégorie
 * @param string $nom Le nom de la catégorie
 * @param string $description La description
 * @param string|null $image Le chemin de l'image
 * @return bool True en cas de succès, False sinon
 */
function update_categorie($id, $nom, $description = null, $image = null)
{
    $db = categories_db();
    if (!$db) {
        return false;
    }

    try {
        $stmt = $db->prepare("
            UPDATE categories SET
                nom = :nom,
                description = :description,
                image = :image
            WHERE id = :id
        ");

        $ok = $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'description' => $description,
            'image' => $image,
        ]);
        if ($ok) {
            $cat = get_categorie_by_id((int) $id);
            $pid = (int) ($cat['parent_id'] ?? 0);
            categories_flush_subcategories_cache($pid > 0 ? $pid : (int) $id);
        }
        return $ok;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Supprime une catégorie
 * @param int $id L'ID de la catégorie
 * @return bool True en cas de succès, False sinon
 */
function delete_categorie($id)
{
    $db = categories_db();
    if (!$db) {
        return false;
    }

    try {
        $cat = get_categorie_by_id((int) $id);
        $stmt = $db->prepare('DELETE FROM categories WHERE id = :id');
        $ok = $stmt->execute(['id' => $id]);
        if ($ok) {
            $pid = (int) ($cat['parent_id'] ?? 0);
            categories_flush_subcategories_cache($pid > 0 ? $pid : null);
        }
        return $ok;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Vérifie si une catégorie a des produits associés
 * @param int $categorie_id L'ID de la catégorie
 * @return bool True si la catégorie a des produits, False sinon
 */
function categorie_has_produits($categorie_id)
{
    $db = categories_db();
    if (!$db) {
        return false;
    }

    try {
        $stmt = $db->prepare('SELECT COUNT(*) FROM produits WHERE categorie_id = :id');
        $stmt->execute(['id' => $categorie_id]);
        $count = $stmt->fetchColumn();

        return $count > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère toutes les catégories avec le nombre de produits
 * @return array Tableau des catégories avec le nombre de produits
 */
function get_all_categories_with_count()
{
    $db = categories_db();
    if (!$db) {
        return [];
    }

    try {
        if (categories_has_parent_id_column()) {
            $stmt = $db->prepare("
                SELECT c.*, COUNT(p.id) as nb_produits, parent.nom AS parent_nom
                FROM categories c
                LEFT JOIN categories parent ON parent.id = c.parent_id
                LEFT JOIN produits p ON c.id = p.categorie_id AND p.statut = 'actif'
                GROUP BY c.id
                ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.nom ASC
            ");
        } else {
            $stmt = $db->prepare("
                SELECT c.*, COUNT(p.id) as nb_produits
                FROM categories c
                LEFT JOIN produits p ON c.id = p.categorie_id AND p.statut = 'actif'
                GROUP BY c.id
                ORDER BY c.nom ASC
            ");
        }
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $categories ? $categories : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Récupère les catégories les plus populaires (basées sur les visites et commandes)
 * @param int $limit Nombre maximum de catégories à retourner (par défaut 5)
 * @return array Tableau des catégories les plus populaires mélangées aléatoirement
 */
function get_top_categories($limit = 5)
{
    $db = categories_db();
    if (!$db) {
        return [];
    }

    try {
        $stmt = $db->prepare("
            SELECT 
                c.*,
                COALESCE(visites_stats.nb_visites, 0) as nb_visites,
                COALESCE(commandes_stats.nb_commandes, 0) as nb_commandes,
                (COALESCE(visites_stats.nb_visites, 0) + COALESCE(commandes_stats.nb_commandes, 0)) as score_popularite
            FROM categories c
            LEFT JOIN (
                SELECT p.categorie_id, COUNT(pv.id) as nb_visites
                FROM produits_visites pv
                INNER JOIN produits p ON pv.produit_id = p.id
                WHERE p.statut = 'actif'
                GROUP BY p.categorie_id
            ) visites_stats ON c.id = visites_stats.categorie_id
            LEFT JOIN (
                SELECT p.categorie_id, COUNT(cp.id) as nb_commandes
                FROM commande_produits cp
                INNER JOIN produits p ON cp.produit_id = p.id
                WHERE p.statut = 'actif'
                GROUP BY p.categorie_id
            ) commandes_stats ON c.id = commandes_stats.categorie_id
            HAVING score_popularite > 0
            ORDER BY score_popularite DESC, c.nom ASC
            LIMIT :limit
        ");

        $stmt->bindValue(':limit', $limit * 2, PDO::PARAM_INT);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($categories)) {
            $categories = get_all_categories();
        }

        if (!empty($categories)) {
            mt_srand(time() + (int) (microtime(true) * 1000000));
            shuffle($categories);
            $categories = array_slice($categories, 0, $limit);
        }

        return $categories ? $categories : [];
    } catch (PDOException $e) {
        $categories = get_all_categories();
        if (!empty($categories)) {
            mt_srand(time() + (int) (microtime(true) * 1000000));
            shuffle($categories);
            $categories = array_slice($categories, 0, $limit);
        }
        return $categories ? $categories : [];
    }
}
