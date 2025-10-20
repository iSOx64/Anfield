<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Administration - Tableau de bord';
$activeAdminNav = $activeAdminNav ?? 'dashboard';

$snapshot = isset($snapshot) && is_array($snapshot) ? $snapshot : [];
$revenueTrend = isset($revenueTrend) && is_array($revenueTrend) ? $revenueTrend : [];
$topTerrains = isset($topTerrains) && is_array($topTerrains) ? $topTerrains : [];
$upcomingReservations = isset($upcomingReservations) && is_array($upcomingReservations) ? $upcomingReservations : [];

$totalUsers = (int) ($snapshot['total_users'] ?? 0);
$adminUsers = (int) ($snapshot['admin_users'] ?? 0);
$activeTerrains = (int) ($snapshot['active_terrains'] ?? 0);
$reservationsToday = (int) ($snapshot['reservations_today'] ?? 0);
$reservationsWeek = (int) ($snapshot['reservations_week'] ?? 0);
$newUsersMonth = (int) ($snapshot['new_users_month'] ?? 0);
$revenueMonth = (float) ($snapshot['revenue_month'] ?? 0.0);
$revenueChange = (float) ($snapshot['revenue_change'] ?? 0.0);
$terrainUtilization = isset($terrainUtilization) && is_array($terrainUtilization) ? $terrainUtilization : [];
$utilizationRange = isset($utilizationRange) && is_array($utilizationRange) ? $utilizationRange : null;
$utilizationRangeStart = $utilizationRange ? \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($utilizationRange['start'] ?? '')) : null;
$utilizationRangeEnd = $utilizationRange ? \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($utilizationRange['end'] ?? '')) : null;

$maxTrend = 0.0;
foreach ($revenueTrend as $point) {
    $maxTrend = max($maxTrend, (float) ($point['total'] ?? 0.0));
}
?>
<section class="admin-header">
    <div class="admin-header__inner">
        <div class="admin-header__content">
            <span class="admin-header__eyebrow">Administration</span>
            <h1 class="admin-header__title">Bienvenue dans votre cockpit</h1>
            <p class="admin-header__description">
                Prenez la temperature de votre complexe en un clin d oeil et accedez aux actions critiques.
            </p>
        </div>
        <div class="admin-header__metrics">
            <div class="admin-metric">
                <span class="admin-metric__label">Clients actifs</span>
                <span class="admin-metric__value"><?= (int) $totalUsers ?></span>
                <span class="admin-metric__hint"><?= (int) $adminUsers ?> membres dans l equipe</span>
            </div>
            <div class="admin-metric">
                <span class="admin-metric__label">Terrains disponibles</span>
                <span class="admin-metric__value"><?= (int) $activeTerrains ?></span>
                <span class="admin-metric__hint">Pret a etre reserves</span>
            </div>
            <div class="admin-metric">
                <span class="admin-metric__label">Sessions aujourd hui</span>
                <span class="admin-metric__value"><?= (int) $reservationsToday ?></span>
                <span class="admin-metric__hint">Clients attendus</span>
            </div>
            <div class="admin-metric">
                <span class="admin-metric__label">7 prochains jours</span>
                <span class="admin-metric__value"><?= (int) $reservationsWeek ?></span>
                <span class="admin-metric__hint">Reservations planifiees</span>
            </div>
            <div class="admin-metric">
                <span class="admin-metric__label">Nouveaux clients</span>
                <span class="admin-metric__value"><?= (int) $newUsersMonth ?></span>
                <span class="admin-metric__hint">Inscriptions ce mois</span>
            </div>
        </div>
    </div>
</section>

<div class="admin-shell">
    <?php require __DIR__ . '/partials/nav.php'; ?>
    <div class="admin-main">
        <article class="admin-card card admin-card--highlight">
            <header class="admin-card__header admin-card__header--with-actions">
                <div>
                    <h2 class="admin-card__title">Revenu mensuel</h2>
                    <p class="admin-card__subtitle">
                        Somme des factures generees sur le mois en cours.
                    </p>
                </div>
                <div class="admin-kpi">
                    <span class="admin-kpi__value"><?= number_format($revenueMonth, 2, '.', ' ') ?> MAD</span>
                    <span class="admin-kpi__hint <?= $revenueChange >= 0 ? 'is-up' : 'is-down' ?>">
                        <?= $revenueChange >= 0 ? '+' : '' ?><?= number_format($revenueChange, 1, '.', ' ') ?>%
                        vs mois precedent
                    </span>
                </div>
            </header>
        </article>

        <div class="admin-grid">
            <article class="admin-card card">
                <header class="admin-card__header">
                    <div>
                        <h2 class="admin-card__title">Performance sur 6 mois</h2>
                        <p class="admin-card__subtitle">
                            Evolution du chiffre d affaires mensuel.
                        </p>
                    </div>
                </header>
                <div class="admin-card__body">
                    <?php if (empty($revenueTrend)): ?>
                        <p class="empty-state">Pas encore de factures pour etablir une tendance.</p>
                    <?php else: ?>
                        <div class="admin-trend">
                            <?php foreach ($revenueTrend as $point): ?>
                                <?php
                                $total = (float) ($point['total'] ?? 0.0);
                                $percent = $maxTrend > 0.0 ? max(8, (int) round(($total / $maxTrend) * 100)) : 8;
                                ?>
                                <div class="admin-trend__item">
                                    <span class="admin-trend__bar" style="height: <?= $percent ?>%;"></span>
                                    <span class="admin-trend__value"><?= number_format($total, 0, '.', ' ') ?> MAD</span>
                                    <span class="admin-trend__label"><?= htmlspecialchars((string) ($point['period'] ?? '')) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <article class="admin-card card">
                <header class="admin-card__header">
                    <div>
                        <h2 class="admin-card__title">Terrains les plus demandes</h2>
                        <p class="admin-card__subtitle">
                            Classement sur les 90 derniers jours.
                        </p>
                    </div>
                </header>
                <div class="admin-card__body">
                    <?php if (empty($topTerrains)): ?>
                        <p class="empty-state">Aucune reservation enregistree pour le moment.</p>
                    <?php else: ?>
                        <ul class="admin-top-list">
                            <?php foreach ($topTerrains as $terrain): ?>
                                <li class="admin-top-list__item">
                                    <div>
                                        <strong><?= htmlspecialchars((string) ($terrain['nom'] ?? '')) ?></strong>
                                        <span class="admin-top-list__hint">
                                            <?= (int) ($terrain['reservations'] ?? 0) ?> reservations
                                        </span>
                                    </div>
                                    <span class="badge <?= ($terrain['disponible'] ?? false) ? 'badge--success' : 'badge--warning' ?>">
                                        <?= ($terrain['disponible'] ?? false) ? 'Disponible' : 'Indisponible' ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </article>

            <article class="admin-card card">
                <header class="admin-card__header">
                    <div>
                        <h2 class="admin-card__title">Repartition des reservations</h2>
                        <p class="admin-card__subtitle">
                            <?php if ($utilizationRangeStart instanceof \DateTimeImmutable && $utilizationRangeEnd instanceof \DateTimeImmutable): ?>
                                Part des reservations entre le <?= htmlspecialchars($utilizationRangeStart->format('d/m')) ?>
                                et le <?= htmlspecialchars($utilizationRangeEnd->format('d/m')) ?>
                            <?php else: ?>
                                Part des reservations sur 30 jours
                            <?php endif; ?>
                        </p>
                    </div>
                </header>
                <div class="admin-card__body">
                    <?php if (empty($terrainUtilization)): ?>
                        <p class="empty-state">Aucune reservation n a encore ete enregistree pour cette periode.</p>
                    <?php else: ?>
                        <ul class="admin-utilization">
                            <?php foreach ($terrainUtilization as $row): ?>
                                <li class="admin-utilization__item">
                                    <div class="admin-utilization__label">
                                        <strong><?= htmlspecialchars($row['nom'] ?? '') ?></strong>
                                        <span><?= (int) ($row['reservations'] ?? 0) ?> sessions</span>
                                    </div>
                                    <div class="admin-utilization__bar">
                                        <span style="width: <?= min(100, max(0, (float) ($row['share'] ?? 0.0))) ?>%;"></span>
                                    </div>
                                    <span class="admin-utilization__value"><?= number_format((float) ($row['share'] ?? 0.0), 1, '.', ' ') ?>%</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </article>
        </div>

        <div class="admin-grid admin-grid--two">
            <article class="admin-card card">
                <header class="admin-card__header">
                    <div>
                        <h2 class="admin-card__title">Reservations a venir</h2>
                        <p class="admin-card__subtitle">
                            Les 5 prochains creneaux planifies.
                        </p>
                    </div>
                </header>
                <div class="admin-card__body">
                    <?php if (empty($upcomingReservations)): ?>
                        <p class="empty-state">Aucune reservation prochaine. Lancez une campagne !</p>
                    <?php else: ?>
                        <ul class="admin-upcoming">
                            <?php foreach ($upcomingReservations as $reservation): ?>
                                <li class="admin-upcoming__item">
                                    <div>
                                        <strong><?= htmlspecialchars($reservation['terrain_nom'] ?? 'Terrain') ?></strong>
                                        <span class="admin-upcoming__meta">
                                            <?= htmlspecialchars($reservation['date_reservation'] ?? '') ?> a
                                            <?= htmlspecialchars(substr((string) ($reservation['creneau_horaire'] ?? ''), 0, 5)) ?>
                                        </span>
                                    </div>
                                    <span class="badge badge--info"><?= htmlspecialchars($reservation['statut'] ?? 'confirmee') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </article>

            <article class="admin-card card">
                <header class="admin-card__header">
                    <div>
                        <h2 class="admin-card__title">Actions rapides</h2>
                        <p class="admin-card__subtitle">
                            Acces direct aux sections frequentes pour votre equipe.
                        </p>
                    </div>
                </header>
                <div class="admin-card__body">
                    <div class="admin-actions">
                        <a class="admin-action" href="/admin/terrains">
                            <span class="admin-action__title">Ajouter un terrain</span>
                            <span class="admin-action__hint">Un nouveau court ou un partenaire</span>
                        </a>
                        <a class="admin-action" href="/reservation/create">
                            <span class="admin-action__title">Creer une reservation</span>
                            <span class="admin-action__hint">Reserver pour un client VIP</span>
                        </a>
                        <a class="admin-action" href="/admin/users">
                            <span class="admin-action__title">Gerer les roles</span>
                            <span class="admin-action__hint">Activer un nouveau collaborateur</span>
                        </a>
                        <a class="admin-action" href="/tournoi/create">
                            <span class="admin-action__title">Planifier un tournoi</span>
                            <span class="admin-action__hint">Lancer un evenement special</span>
                        </a>
                    </div>
                </div>
            </article>
        </div>
    </div>
</div>
