<?php

use App\Core\Auth;

$authHelper = new Auth();
$isLoggedIn = $authHelper->check();
$reservationLink = $isLoggedIn ? '/reservation/create' : '/login?target=reservations';
$tournamentLink = $isLoggedIn ? '/tournoi/create' : '/login?target=tournaments';

$terrainPriceCount = isset($terrainPrices) && is_array($terrainPrices) ? count($terrainPrices) : 0;
$servicePriceCount = isset($servicePrices) && is_array($servicePrices) ? count($servicePrices) : 0;
$upcomingReservationCount = isset($upcomingReservations) && is_array($upcomingReservations) ? count($upcomingReservations) : 0;
$latestTournamentCount = isset($latestTournaments) && is_array($latestTournaments) ? count($latestTournaments) : 0;
$nextReservation = $upcomingReservationCount > 0 ? $upcomingReservations[0] : null;
$featuredTerrains = isset($featuredTerrains) && is_array($featuredTerrains) ? $featuredTerrains : [];
$placeholderImage = '/assets/img/foot-fields-icon-1024.png';

$categoryLabels = [
    'corporate' => 'Corporate',
    'association' => 'Association',
    'academie' => 'Academie',
    'jeunes' => 'Jeunes',
    'mixte' => 'Mixte',
];

$levelLabels = [
    'loisir' => 'Loisir',
    'intermediaire' => 'Intermediaire',
    'elite' => 'Elite',
];
?>
<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<section class="home-hero">
    <div class="home-hero__content">
        <span class="badge badge--info">Gestion centralisee</span>
        <h1>Reservez vos terrains en quelques clics</h1>
        <p>Planifiez vos creneaux, ajoutez des services et suivez vos tournois sur une interface moderne pensee pour les clubs de foot.</p>
        <div class="home-hero__actions">
            <a class="btn btn--primary" href="<?= htmlspecialchars($reservationLink) ?>">Reserver un terrain</a>
            <a class="btn btn--secondary" href="<?= htmlspecialchars($tournamentLink) ?>">Creer un tournoi</a>
        </div>
        <ul class="home-hero__stats">
            <li><strong><?= $terrainPriceCount ?></strong> formules terrains</li>
            <li><strong><?= $servicePriceCount ?></strong> services premium</li>
            <li><strong><?= $upcomingReservationCount ?></strong> reservations a venir</li>
        </ul>
    </div>
    <div class="home-hero__visual">
        <div class="card glass-card flow">
            <h3>Prochaine session</h3>
            <?php if ($nextReservation): ?>
                <p class="glass-card__highlight"><strong><?= htmlspecialchars($nextReservation['terrain_nom'] ?? 'Terrain') ?></strong></p>
                <p class="glass-card__meta">Le <?= htmlspecialchars($nextReservation['date_reservation']) ?> a <?= htmlspecialchars(substr((string) $nextReservation['creneau_horaire'], 0, 5)) ?></p>
                <p class="glass-card__meta">Statut : <span class="badge badge--success"><?= htmlspecialchars($nextReservation['statut']) ?></span></p>
            <?php else: ?>
                <p class="glass-card__meta">Aucune reservation programmee.</p>
                <p class="glass-card__meta">Profitez-en pour organiser votre prochain match.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (!empty($featuredTerrains)): ?>
<section class="section">
    <h2 class="section__title">Terrains en vitrine</h2>
    <p class="section__subtitle">Des surfaces pretes a accueillir vos matchs et evenements speciales.</p>
    <div class="feature-grid">
        <?php foreach ($featuredTerrains as $terrain): ?>
            <?php $image = !empty($terrain['image_path']) ? '/' . ltrim((string) $terrain['image_path'], '/') : $placeholderImage; ?>
            <div class="feature-card flow terrain-card">
                <img src="<?= htmlspecialchars($image) ?>" alt="Photo du terrain <?= htmlspecialchars($terrain['nom'] ?? 'Terrain') ?>">
                <div>
                    <h4><?= htmlspecialchars($terrain['nom'] ?? 'Terrain') ?></h4>
                    <p class="terrain-card__meta">Taille : <?= htmlspecialchars(strtoupper((string) ($terrain['taille'] ?? ''))) ?> · Type : <?= htmlspecialchars(str_replace('_', ' ', (string) ($terrain['type'] ?? ''))) ?></p>
                    <span class="badge <?= ((int) ($terrain['disponible'] ?? 0) === 1) ? 'badge--success' : 'badge--warning' ?>">
                        <?= ((int) ($terrain['disponible'] ?? 0) === 1) ? 'Disponible' : 'Indisponible' ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <h2 class="section__title">Tout pour votre club</h2>
    <p class="section__subtitle">Des outils inspires des meilleures plateformes pour coordonner vos equipes.</p>
    <div class="feature-grid">
        <div class="feature-card flow">
            <h4>Tarifs terrains flexibles</h4>
            <ul class="pricing-list">
                <?php foreach ($terrainPrices as $price): ?>
                    <li>
                        <span><strong><?= htmlspecialchars(ucfirst($price['reference'])) ?></strong></span>
                        <span><?= number_format($price['prix'], 2, ',', ' ') ?> DH</span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn--secondary btn--sm" href="<?= htmlspecialchars($reservationLink) ?>">Voir les disponibilites</a>
        </div>
        <div class="feature-card flow">
            <h4>Services a la carte</h4>
            <ul class="service-list">
                <?php foreach ($servicePrices as $price): ?>
                    <li>
                        <span><strong><?= htmlspecialchars(ucfirst($price['reference'])) ?></strong></span>
                        <span><?= number_format($price['prix'], 2, ',', ' ') ?> DH</span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="feature-card__note">Ajoutez vestiaires, equipements et coaching en un clic.</p>
        </div>
        <div class="feature-card flow">
            <h4>Experience joueur premium</h4>
            <p>Notifications automatiques, suivi des tournois et messagerie integree pour garder vos equipes alignees.</p>
            <ul class="auth-highlights">
                <li>Confirmations instantanees</li>
                <li>Tableau de bord mobile</li>
                <li>Historique centralise</li>
            </ul>
        </div>
    </div>
</section>

<section class="section">
    <h2 class="section__title">Vos prochaines reservations</h2>
    <?php if (empty($upcomingReservations)): ?>
        <div class="card flow">
            <p>Aucune reservation programmee pour le moment.</p>
            <a class="btn btn--primary btn--sm" href="<?= htmlspecialchars($reservationLink) ?>">Planifier un creneau</a>
        </div>
    <?php else: ?>
        <div class="card">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Creneau</th>
                    <th>Terrain</th>
                    <th>Statut</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($upcomingReservations as $reservation): ?>
                    <tr>
                        <td><?= htmlspecialchars($reservation['date_reservation']) ?></td>
                        <td><?= htmlspecialchars(substr((string) $reservation['creneau_horaire'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars($reservation['terrain_nom'] ?? '') ?></td>
                        <td><span class="badge badge--info"><?= htmlspecialchars($reservation['statut'] ?? '') ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="section">
    <h2 class="section__title">Derniers tournois</h2>
    <?php if (empty($latestTournaments)): ?>
        <div class="card flow">
            <p>Aucun tournoi cree pour le moment.</p>
            <a class="btn btn--secondary btn--sm" href="<?= htmlspecialchars($tournamentLink) ?>">Lancer un tournoi</a>
        </div>
    <?php else: ?>
        <ul class="tournament-list">
            <?php foreach ($latestTournaments as $tournoi): ?>
                <?php
                $detailLink = $isLoggedIn ? '/tournoi/' . (int) $tournoi['id'] : '/login';
                $categorie = $tournoi['categorie'] ?? '';
                $niveau = $tournoi['niveau'] ?? '';
                ?>
                <li>
                    <div class="tournament-list__info">
                        <strong><?= htmlspecialchars($tournoi['nom']) ?></strong>
                        <span><?= (int) $tournoi['equipes'] ?> equipes</span>
                        <?php if (!empty($tournoi['date_debut'])): ?>
                            <span>Debut <?= htmlspecialchars($tournoi['date_debut']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($categorie) || !empty($niveau)): ?>
                            <span><?= htmlspecialchars($categoryLabels[$categorie] ?? 'Tout public') ?> · <?= htmlspecialchars($levelLabels[$niveau] ?? 'Tous niveaux') ?></span>
                        <?php endif; ?>
                    </div>
                    <a class="btn btn--ghost btn--sm" href="<?= htmlspecialchars($detailLink) ?>">Voir le detail</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="cta-banner">
    <h2>Donnez un avantage competitif a votre club</h2>
    <p>Synchronisez les reservations, offrez de nouveaux services et fidelisez vos equipes depuis une interface unique.</p>
    <div class="home-hero__actions">
        <a class="btn btn--primary" href="/register">Creer un compte club</a>
        <a class="btn btn--secondary" href="<?= htmlspecialchars($reservationLink) ?>">Reserver un terrain</a>
    </div>
</section>


