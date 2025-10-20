<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Administration - Utilisateurs';
$activeAdminNav = $activeAdminNav ?? 'users';
$searchTerm = $searchTerm ?? '';

$totalUsers = count($users);
$adminUsers = 0;
$recentUsers = 0;
$cutoffDate = new \DateTimeImmutable('-30 days');

foreach ($users as $user) {
    if (($user['role'] ?? 'client') === 'admin') {
        $adminUsers++;
    }
    $createdAt = $user['created_at'] ?? null;
    if (is_string($createdAt) && strtotime($createdAt) !== false) {
        $createdAtDate = new \DateTimeImmutable($createdAt);
        if ($createdAtDate >= $cutoffDate) {
            $recentUsers++;
        }
    }
}
?>
<section class="admin-header">
    <div class="admin-header__inner">
        <div class="admin-header__content">
            <span class="admin-header__eyebrow">Administration</span>
            <h1 class="admin-header__title">Gestion des utilisateurs</h1>
            <p class="admin-header__description">
                Surveillez vos clients et partenaires, attribuez des droits et gardez une trace des nouvelles inscriptions.
            </p>
        </div>
        <div class="admin-header__metrics">
            <div class="admin-metric">
                <span class="admin-metric__label">Total comptes</span>
                <span class="admin-metric__value"><?= (int) $totalUsers ?></span>
                <span class="admin-metric__hint">Tous les profils actifs</span>
            </div>
            <div class="admin-metric">
                <span class="admin-metric__label">Administrateurs</span>
                <span class="admin-metric__value"><?= (int) $adminUsers ?></span>
                <span class="admin-metric__hint">Equipe interne</span>
            </div>
            <div class="admin-metric">
                <span class="admin-metric__label">Nouveaux 30 jours</span>
                <span class="admin-metric__value"><?= (int) $recentUsers ?></span>
                <span class="admin-metric__hint">Derniers inscrits</span>
            </div>
        </div>
    </div>
</section>

<div class="admin-shell">
    <?php require __DIR__ . '/partials/nav.php'; ?>
    <div class="admin-main">
        <article class="admin-card card">
            <header class="admin-card__header admin-card__header--with-actions">
                <div>
                    <h2 class="admin-card__title">Liste des utilisateurs</h2>
                    <p class="admin-card__subtitle">
                        Modifiez les roles pour accorder l acces administrateur en toute securite.
                    </p>
                </div>
                <form method="get" class="admin-search">
                    <label for="admin-user-search" class="sr-only">Rechercher un utilisateur</label>
                    <input
                        id="admin-user-search"
                        class="admin-search__input"
                        type="search"
                        name="q"
                        value="<?= htmlspecialchars($searchTerm ?? '') ?>"
                        placeholder="Recherche par nom ou email"
                    >
                    <button type="submit" class="btn btn--secondary btn--sm">Rechercher</button>
                </form>
            </header>
            <div class="admin-card__body">
                <?php if (empty($users)): ?>
                    <p class="empty-state">Aucun utilisateur ne correspond a vos criteres.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="data-table admin-table admin-users-table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Profil</th>
                                <th>E-mail</th>
                                <th>Role</th>
                                <th>Inscription</th>
                                <th class="admin-table__actions">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= (int) $user['id'] ?></td>
                                    <td>
                                        <div class="admin-user">
                                            <?php if (!empty($user['avatar_path'])): ?>
                                                <img class="admin-user__avatar" src="/<?= htmlspecialchars($user['avatar_path']) ?>" alt="Avatar utilisateur">
                                            <?php else: ?>
                                                <?php
                                                $initialSource = '';
                                                if (!empty($user['prenom'])) {
                                                    $initialSource = $user['prenom'];
                                                } elseif (!empty($user['nom'])) {
                                                    $initialSource = $user['nom'];
                                                } else {
                                                    $initialSource = 'U';
                                                }
                                                ?>
                                                <span class="admin-user__initial"><?= htmlspecialchars(strtoupper(substr((string) $initialSource, 0, 1))) ?></span>
                                            <?php endif; ?>
                                            <div class="admin-user__identity">
                                                <?php
                                                $displayName = trim(
                                                    (isset($user['prenom']) ? (string) $user['prenom'] : '') . ' ' .
                                                    (isset($user['nom']) ? (string) $user['nom'] : '')
                                                );
                                                ?>
                                                <strong><?= htmlspecialchars($displayName !== '' ? $displayName : $user['email']) ?></strong>
                                                <span class="admin-user__meta"><?= htmlspecialchars($user['telephone'] ?? 'Telephone non renseigne') ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge <?= ($user['role'] ?? 'client') === 'admin' ? 'badge--info' : 'badge--muted' ?>">
                                            <?= htmlspecialchars($user['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                                    <td class="admin-table__actions">
                                        <form method="post" action="/admin/users/role" class="admin-inline-form">
                                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                            <label class="sr-only" for="role-<?= (int) $user['id'] ?>">Role</label>
                                            <select id="role-<?= (int) $user['id'] ?>" name="role" class="admin-select">
                                                <option value="client" <?= $user['role'] === 'client' ? 'selected' : '' ?>>Client</option>
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                            <button type="submit" class="btn btn--secondary btn--sm">Mettre a jour</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </div>
</div>
