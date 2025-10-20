<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Administration - Reservations';
$activeAdminNav = $activeAdminNav ?? 'reservations';
$filters = isset($filters) && is_array($filters) ? $filters : [
    'start' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
    'end' => (new \DateTimeImmutable('today'))->modify('+14 days')->format('Y-m-d'),
    'terrain' => '',
    'status' => '',
    'focus' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
];
$terrains = isset($terrains) && is_array($terrains) ? $terrains : [];
$statuses = isset($statuses) && is_array($statuses) ? $statuses : [];
$summary = isset($summary) && is_array($summary) ? $summary : [
    'total' => count($reservations ?? []),
    'statusCounts' => [],
    'uniqueTerrains' => 0,
    'uniqueClients' => 0,
];
$nextReservation = $nextReservation ?? null;
$dailySchedule = isset($dailySchedule) && is_array($dailySchedule) ? $dailySchedule : null;

$startLabel = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($filters['start'] ?? ''));
$endLabel = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($filters['end'] ?? ''));
$periodLabel = '';
if ($startLabel instanceof \DateTimeImmutable && $endLabel instanceof \DateTimeImmutable) {
    $periodLabel = sprintf(
        'Du %s au %s',
        $startLabel->format('d/m/Y'),
        $endLabel->format('d/m/Y')
    );
}

$statusLabels = [
    'confirmee' => 'Confirmees',
    'annulee' => 'Annulees',
    'terminee' => 'Terminees',
];

$eventLabels = [
    'match_amical' => 'Match amical',
    'entrainement' => 'Entrainement dirige',
    'tournoi_corporate' => 'Tournoi corporate',
    'anniversaire' => 'Anniversaire sportif',
    'stage' => 'Stage intensif',
];
?>
<section class="admin-header">
    <div class="admin-header__inner">
        <div class="admin-header__content">
            <span class="admin-header__eyebrow">Administration</span>
            <h1 class="admin-header__title">Disponibilites et occupations</h1>
            <p class="admin-header__description">
                Analysez vos reservations, filtrez par terrain ou statut et exportez un suivi partageable.
            </p>
            <?php if ($periodLabel !== ''): ?>
                <p class="admin-header__description admin-header__description--secondary"><?= htmlspecialchars($periodLabel) ?></p>
            <?php endif; ?>
        </div>
        <div class="admin-header__metrics">
            <div class="admin-metric">
                <span class="admin-metric__label">Reservations couvertes</span>
                <span class="admin-metric__value"><?= (int) ($summary['total'] ?? 0) ?></span>
                <span class="admin-metric__hint">Dans la periode selectionnee</span>
            </div>
            <div class="admin-metric">
                <span class="admin-metric__label">Terrains concernes</span>
                <span class="admin-metric__value"><?= (int) ($summary['uniqueTerrains'] ?? 0) ?></span>
                <span class="admin-metric__hint">Activite repartie</span>
            </div>
            <div class="admin-metric">
                <span class="admin-metric__label">Clients touches</span>
                <span class="admin-metric__value"><?= (int) ($summary['uniqueClients'] ?? 0) ?></span>
                <span class="admin-metric__hint">Utilisateurs differents</span>
            </div>
        </div>
    </div>
</section>

<div class="admin-shell">
    <?php require __DIR__ . '/partials/nav.php'; ?>
    <div class="admin-main">
        <?php if ($nextReservation !== null): ?>
            <?php
            $nextDateTime = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                sprintf(
                    '%s %s',
                    (string) ($nextReservation['date_reservation'] ?? ''),
                    isset($nextReservation['creneau_horaire']) ? (string) $nextReservation['creneau_horaire'] : '00:00:00'
                )
            );
            ?>
            <aside class="admin-highlight card">
                <div class="admin-highlight__content">
                    <span class="admin-highlight__eyebrow">Prochaine reservation</span>
                    <h2 class="admin-highlight__title"><?= htmlspecialchars($nextReservation['terrain_nom'] ?? 'Terrain') ?></h2>
                    <p class="admin-highlight__description">
                        <?= htmlspecialchars($nextReservation['statut'] ?? 'confirmee') ?> - <?= htmlspecialchars($nextReservation['date_reservation'] ?? '') ?>
                        a <?= htmlspecialchars(substr((string) ($nextReservation['creneau_horaire'] ?? ''), 0, 5)) ?>
                    </p>
                </div>
                <div class="admin-highlight__meta">
                    <?php if ($nextDateTime instanceof \DateTimeImmutable): ?>
                        <span class="admin-highlight__meta-item">
                            Dans
                            <strong><?= $nextDateTime->diff(new \DateTimeImmutable('now'))->format('%a j %h h %i min') ?></strong>
                        </span>
                    <?php endif; ?>
                    <span class="admin-highlight__meta-item">
                        Client #<?= (int) ($nextReservation['utilisateur_id'] ?? 0) ?>
                    </span>
                </div>
            </aside>
        <?php endif; ?>

        <article class="admin-card card">
            <header class="admin-card__header admin-card__header--with-actions">
                <div>
                    <h2 class="admin-card__title">Agenda des reservations</h2>
                    <p class="admin-card__subtitle">
                        Filtrez par periode et exportez les resultats pour votre equipe.
                    </p>
                </div>
                <div class="admin-card__actions">
                    <a class="btn btn--ghost btn--sm" href="/admin/dispo/export?<?= htmlspecialchars(http_build_query($filters), ENT_QUOTES, 'UTF-8') ?>">Exporter CSV</a>
                </div>
            </header>
            <div class="admin-card__body">
                <form method="get" action="/admin/dispo" class="admin-form admin-form--filters">
                    <div class="admin-form__grid admin-form__grid--filters">
                        <div class="admin-form__field">
                            <label for="filter-start">Date debut</label>
                            <input type="date" id="filter-start" name="start" value="<?= htmlspecialchars($filters['start'] ?? '') ?>">
                        </div>
                        <div class="admin-form__field">
                            <label for="filter-end">Date fin</label>
                            <input type="date" id="filter-end" name="end" value="<?= htmlspecialchars($filters['end'] ?? '') ?>">
                        </div>
                        <div class="admin-form__field">
                            <label for="filter-terrain">Terrain</label>
                            <select id="filter-terrain" name="terrain">
                                <option value="">Tous les terrains</option>
                                <?php foreach ($terrains as $terrain): ?>
                                    <option value="<?= (int) ($terrain['id'] ?? 0) ?>" <?= ((string) ($filters['terrain'] ?? '') === (string) ($terrain['id'] ?? '')) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($terrain['nom'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-form__field">
                            <label for="filter-status">Statut</label>
                            <select id="filter-status" name="status">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= htmlspecialchars($status) ?>" <?= ((string) ($filters['status'] ?? '') === $status) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($statusLabels[$status] ?? ucfirst($status)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-form__field">
                            <label for="filter-focus">Jour detaille</label>
                            <input type="date" id="filter-focus" name="focus" value="<?= htmlspecialchars($filters['focus'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="admin-form__actions">
                        <button type="submit" class="btn btn--primary">Actualiser</button>
                        <a class="btn btn--ghost" href="/admin/dispo">Reinitialiser</a>
                    </div>
                </form>

                <div class="admin-summary">
                    <?php foreach ($summary['statusCounts'] ?? [] as $statusKey => $count): ?>
                        <div class="admin-summary__item">
                            <span class="admin-summary__label"><?= htmlspecialchars($statusLabels[$statusKey] ?? ucfirst((string) $statusKey)) ?></span>
                            <strong class="admin-summary__value"><?= (int) $count ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($reservations)): ?>
                    <p class="empty-state">Aucune reservation correspondant a vos filtres. Ajustez la periode ou le statut.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="data-table admin-table">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Terrain</th>
                                <th>Client</th>
                                <th>Statut</th>
                                <th>Demandes</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td><?= htmlspecialchars($reservation['date_reservation']) ?></td>
                                    <td><?= htmlspecialchars(substr((string) ($reservation['creneau_horaire'] ?? ''), 0, 5)) ?></td>
                                    <td><?= htmlspecialchars($reservation['terrain_nom'] ?? '') ?></td>
                                    <td>#<?= (int) ($reservation['utilisateur_id'] ?? 0) ?></td>
                                    <td>
                                        <span class="badge badge--info"><?= htmlspecialchars($reservation['statut'] ?? 'confirmee') ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($reservation['demande'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </article>

        <?php if ($dailySchedule !== null && !empty($dailySchedule['terrains'])): ?>
            <?php $detailDateObj = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($dailySchedule['date'] ?? '')); ?>
            <article class="admin-card card">
                <header class="admin-card__header">
                    <div>
                        <h2 class="admin-card__title">Vue horaire detaillee</h2>
                        <p class="admin-card__subtitle">
                            Disponibilite par terrain pour le
                            <?= $detailDateObj instanceof \DateTimeImmutable
                                ? htmlspecialchars($detailDateObj->format('d/m/Y'))
                                : htmlspecialchars((string) ($dailySchedule['date'] ?? '')) ?>
                        </p>
                    </div>
                </header>
                <div class="admin-card__body">
                    <div class="admin-availability">
                        <div class="admin-availability__header">
                            <span class="admin-availability__cell admin-availability__cell--time">Heure</span>
                            <?php foreach ($dailySchedule['terrains'] as $terrain): ?>
                                <span class="admin-availability__cell"><?= htmlspecialchars($terrain['nom'] ?? '') ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="admin-availability__body">
                            <?php foreach ($dailySchedule['slots'] as $slot): ?>
                                <div class="admin-availability__row">
                                    <span class="admin-availability__cell admin-availability__cell--time"><?= htmlspecialchars($slot['time'] ?? '') ?></span>
                                    <?php foreach ($dailySchedule['terrains'] as $terrain): ?>
                                        <?php
                                        $terrainId = (int) ($terrain['id'] ?? 0);
                                        $slotData = $slot['terrains'][$terrainId] ?? ['reserved' => false, 'reservation' => null];
                                        $reserved = !empty($slotData['reserved']);
                                        $reservation = $slotData['reservation'] ?? null;
                                        $eventType = is_array($reservation) ? ($reservation['type_evenement'] ?? '') : '';
                                        $eventLabel = $eventLabels[$eventType] ?? '';
                                        ?>
                                        <span class="admin-availability__cell <?= $reserved ? 'is-reserved' : 'is-free' ?>">
                                            <?php if ($reserved && is_array($reservation)): ?>
                                                <strong>Reserve</strong>
                                                <small>#<?= (int) ($reservation['utilisateur_id'] ?? 0) ?></small>
                                                <?php if ($eventLabel !== ''): ?>
                                                    <small><?= htmlspecialchars($eventLabel) ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <strong>Libre</strong>
                                            <?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </article>
        <?php endif; ?>
    </div>
</div>
