<?php
/**
 * Icône + teinte pour pastilles sous-catégories (page catégorie).
 *
 * @return array{icon: string, tone: string}
 */
function categorie_subcat_visual(string $nom): array
{
    static $map = null;
    if ($map === null) {
        $map = [
            'riz' => ['fa-solid fa-bowl-food', 'tone-peach'],
            'mil' => ['fa-solid fa-wheat-awn', 'tone-sand'],
            'maïs' => ['fa-solid fa-seedling', 'tone-mint'],
            'mais' => ['fa-solid fa-seedling', 'tone-mint'],
            'blé' => ['fa-solid fa-bread-slice', 'tone-sand'],
            'ble' => ['fa-solid fa-bread-slice', 'tone-sand'],
            'fonio' => ['fa-solid fa-leaf', 'tone-mint'],
        ];
    }

    $n = function_exists('mb_strtolower') ? mb_strtolower(trim($nom)) : strtolower(trim($nom));
    if (isset($map[$n])) {
        return ['icon' => $map[$n][0], 'tone' => $map[$n][1]];
    }

    foreach ($map as $needle => $visual) {
        if (strpos($n, $needle) !== false) {
            return ['icon' => $visual[0], 'tone' => $visual[1]];
        }
    }

    return ['icon' => 'fa-solid fa-tag', 'tone' => 'tone-grey'];
}
