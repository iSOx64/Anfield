<?php
$eventLabels = [
    'match_amical' => 'Match amical',
    'entrainement' => 'Entrainement dirige',
    'tournoi_corporate' => 'Tournoi corporate',
    'anniversaire' => 'Anniversaire sportif',
    'stage' => 'Stage intensif',
];
?>
<section class="section">
    <h1 class="section__title">Mes reservations</h1>
    <p class="section__subtitle">Retrouvez vos creneaux passes et a venir pour garder un coup d'avance.</p>
    <?php if (empty($reservations)): ?>
        <div class="card flow">
            <p>Vous n'avez pas encore de reservation.</p>
            <a class="btn btn--primary btn--sm" href="/reservation/create">Reserver un creneau</a>
        </div>
    <?php else: ?>
        <div class="card">
            <table class="data-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Terrain</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Evenement</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($reservations as $reservation): ?>
                    <?php $eventType = $eventLabels[$reservation['type_evenement'] ?? ''] ?? ucfirst((string) ($reservation['type_evenement'] ?? '')); ?>
                    <tr>
                        <td><?= (int) $reservation['id'] ?></td>
                        <td><?= htmlspecialchars($reservation['terrain_nom'] ?? '') ?></td>
                        <td><?= htmlspecialchars($reservation['date_reservation']) ?></td>
                        <td><?= htmlspecialchars(substr((string) $reservation['creneau_horaire'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars($eventType) ?></td>
                        <td><span class="badge badge--info"><?= htmlspecialchars($reservation['statut']) ?></span></td>
                        <td><a class="btn btn--ghost btn--sm" href="/reservation/<?= (int) $reservation['id'] ?>">Voir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
