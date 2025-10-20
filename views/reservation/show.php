<?php
$serviceLabels = [
    'ballon' => 'Ballon',
    'arbitre' => 'Arbitre',
    'maillot' => 'Maillots',
    'douche' => 'Douche',
    'coach' => 'Coach dedie',
    'photographe' => 'Photographe',
    'traiteur' => 'Service traiteur',
];
$eventLabels = [
    'match_amical' => 'Match amical',
    'entrainement' => 'Entrainement dirige',
    'tournoi_corporate' => 'Tournoi corporate',
    'anniversaire' => 'Anniversaire sportif',
    'stage' => 'Stage intensif',
];
$levelLabels = [
    'loisir' => 'Loisir',
    'intermediaire' => 'Intermediaire',
    'competitif' => 'Competitif',
];
$eventType = (string) ($reservation['type_evenement'] ?? '');
$skillLevel = (string) ($reservation['niveau'] ?? '');
$participants = $reservation['participants'] ?? null;
?>
<section class="section">
    <h1 class="section__title">Reservation #<?= (int) $reservation['id'] ?></h1>
    <p class="section__subtitle">Tous les details de votre creneau et des services associes.</p>
    <div class="feature-grid">
        <div class="card flow">
            <p><strong>Terrain :</strong> <?= htmlspecialchars($reservation['terrain_nom'] ?? '') ?></p>
            <p><strong>Date :</strong> <?= htmlspecialchars($reservation['date_reservation']) ?></p>
            <p><strong>Creneau :</strong> <?= htmlspecialchars(substr((string) $reservation['creneau_horaire'], 0, 5)) ?></p>
            <p><strong>Statut :</strong> <span class="badge badge--info"><?= htmlspecialchars($reservation['statut']) ?></span></p>
            <p><strong>Type d'evenement :</strong> <?= htmlspecialchars($eventLabels[$eventType] ?? ucfirst($eventType)) ?></p>
            <p><strong>Niveau :</strong> <?= htmlspecialchars($levelLabels[$skillLevel] ?? ucfirst($skillLevel)) ?></p>
            <?php if (!empty($participants)): ?>
                <p><strong>Participants :</strong> <?= (int) $participants ?></p>
            <?php endif; ?>
            <?php if (!empty($reservation['demande'])): ?>
                <div>
                    <strong>Demande :</strong>
                    <p><?= nl2br(htmlspecialchars((string) $reservation['demande'])) ?></p>
                </div>
            <?php endif; ?>
        </div>
        <div class="card flow">
            <h3>Services complementaires</h3>
            <ul class="auth-highlights">
                <?php foreach ($serviceLabels as $key => $label): ?>
                    <?php $isActive = (int) ($reservation[$key] ?? 0) === 1; ?>
                    <li><?= htmlspecialchars($label) ?> : <?= $isActive ? 'Oui' : 'Non' ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="card flow">
            <h3>Facturation</h3>
            <ul class="pricing-list">
                <li><span>Terrain</span><span><?= number_format($facture['terrain'], 2, ',', ' ') ?> DH</span></li>
                <li><span>Services</span><span><?= number_format($facture['service'], 2, ',', ' ') ?> DH</span></li>
                <li><span>Total</span><span><strong><?= number_format($facture['total'], 2, ',', ' ') ?> DH</strong></span></li>
            </ul>
        </div>
    </div>

    <div class="home-hero__actions actions-inline">
        <a class="btn btn--primary" href="/reservation/<?= (int) $reservation['id'] ?>/invoice">Telecharger la facture (PDF)</a>
        <?php if ($reservation['statut'] !== 'annulee'): ?>
            <a class="btn btn--secondary" href="/reservation/<?= (int) $reservation['id'] ?>/edit">Modifier</a>
            <form method="post" action="/reservation/<?= (int) $reservation['id'] ?>/cancel">
                <button type="submit" class="btn btn--ghost">Annuler la reservation</button>
            </form>
        <?php endif; ?>
    </div>
</section>
