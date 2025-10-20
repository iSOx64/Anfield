<?php

declare(strict_types=1);

$currentAdminNav = $activeAdminNav ?? '';

$adminNavItems = [
    [
        'slug' => 'dashboard',
        'label' => 'Tableau de bord',
        'description' => 'Vue generale des activites',
        'href' => '/admin',
    ],
    [
        'slug' => 'terrains',
        'label' => 'Terrains',
        'description' => 'Gestion des disponibilites',
        'href' => '/admin/terrains',
    ],
    [
        'slug' => 'reservations',
        'label' => 'Reservations',
        'description' => 'Suivi des occupations',
        'href' => '/admin/dispo',
    ],
    [
        'slug' => 'users',
        'label' => 'Utilisateurs',
        'description' => 'Roles et clients',
        'href' => '/admin/users',
    ],
];
?>
<nav class="admin-nav" aria-label="Navigation administration">
    <div class="admin-nav__header">
        <span class="admin-nav__eyebrow">Espace admin</span>
        <h2 class="admin-nav__title">Commandes</h2>
    </div>
    <ul class="admin-nav__list">
        <?php foreach ($adminNavItems as $item): ?>
            <?php
            $isActive = $currentAdminNav === $item['slug'];
            $classes = 'admin-nav__link';
            if ($isActive) {
                $classes .= ' is-active';
            }
            ?>
            <li class="admin-nav__item">
                <a class="<?= htmlspecialchars($classes) ?>" href="<?= htmlspecialchars($item['href']) ?>">
                    <span class="admin-nav__label"><?= htmlspecialchars($item['label']) ?></span>
                    <span class="admin-nav__description"><?= htmlspecialchars($item['description']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
