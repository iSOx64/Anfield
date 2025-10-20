<?php
$organisateurPrenom = isset($tournoi['organisateur_prenom']) ? (string) $tournoi['organisateur_prenom'] : '';
$organisateurNom = isset($tournoi['organisateur_nom']) ? (string) $tournoi['organisateur_nom'] : '';
$displayName = htmlspecialchars(trim($organisateurPrenom . ' ' . $organisateurNom));

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

$categorie = $tournoi['categorie'] ?? '';
$niveau = $tournoi['niveau'] ?? '';
$frais = $tournoi['frais_inscription'] ?? null;
?>
<section class="section">
    <div class="card flow">
        <h1 class="section__title"><?= htmlspecialchars($tournoi['nom']) ?></h1>
        <p><strong>Format :</strong> <?= (int) $tournoi['format'] ?> equipes</p>
        <p><strong>Organisateur :</strong> <?= $displayName ?></p>
        <div class="tournoi-meta">
            <?php if (!empty($categorie)): ?>
                <span class="badge badge--info">Categorie : <?= htmlspecialchars($categoryLabels[$categorie] ?? ucfirst((string) $categorie)) ?></span>
            <?php endif; ?>
            <?php if (!empty($niveau)): ?>
                <span class="badge badge--info">Niveau : <?= htmlspecialchars($levelLabels[$niveau] ?? ucfirst((string) $niveau)) ?></span>
            <?php endif; ?>
        </div>
        <div class="tournoi-details">
            <?php if (!empty($tournoi['date_debut'])): ?>
                <p><strong>Debut :</strong> <?= htmlspecialchars($tournoi['date_debut']) ?></p>
            <?php endif; ?>
            <?php if (!empty($tournoi['date_fin'])): ?>
                <p><strong>Fin :</strong> <?= htmlspecialchars($tournoi['date_fin']) ?></p>
            <?php endif; ?>
            <?php if (!empty($tournoi['lieu'])): ?>
                <p><strong>Lieu :</strong> <?= htmlspecialchars($tournoi['lieu']) ?></p>
            <?php endif; ?>
            <?php if ($frais !== null): ?>
                <p><strong>Frais d'inscription :</strong> <?= number_format((float) $frais, 2, ',', ' ') ?> DH</p>
            <?php endif; ?>
            <?php if (!empty($tournoi['recompense'])): ?>
                <p><strong>Recompense :</strong> <?= htmlspecialchars($tournoi['recompense']) ?></p>
            <?php endif; ?>
            <?php if (!empty($tournoi['description'])): ?>
                <div>
                    <strong>Description :</strong>
                    <p><?= nl2br(htmlspecialchars((string) $tournoi['description'])) ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($tournoi['contact_email']) || !empty($tournoi['contact_phone'])): ?>
                <p><strong>Contact :</strong>
                    <?php if (!empty($tournoi['contact_email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($tournoi['contact_email']) ?>"><?= htmlspecialchars($tournoi['contact_email']) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($tournoi['contact_phone'])): ?>
                        <span><?= htmlspecialchars($tournoi['contact_phone']) ?></span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    </div>

    <div class="feature-grid">
        <div class="card flow">
            <h3>Equipes</h3>
            <?php if (empty($teams)): ?>
                <p>Aucune equipe enregistree.</p>
            <?php else: ?>
                <ul class="auth-highlights">
                    <?php foreach ($teams as $team): ?>
                        <li><?= htmlspecialchars($team['nom']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($isOrganisateur)): ?>
                <form method="post" action="/tournoi/<?= (int) $tournoi['id'] ?>/team" class="flow">
                    <label for="team_name">Ajouter une equipe</label>
                    <input type="text" id="team_name" name="team_name" class="form-control" placeholder="Nom de l equipe" required>
                    <button type="submit" class="btn btn--secondary">Ajouter</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card flow">
            <h3>Matches</h3>
            <?php if (empty($matches)): ?>
                <p>Aucun match planifie.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Tour</th>
                        <th>Equipe A</th>
                        <th>Equipe B</th>
                        <th>Date</th>
                        <th>Creneau</th>
                        <th>Terrain</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($matches as $match): ?>
                        <?php
                        $teamA = isset($match['equipe_a']) && $match['equipe_a'] !== null && $match['equipe_a'] !== '' ? $match['equipe_a'] : 'TBD';
                        $teamB = isset($match['equipe_b']) && $match['equipe_b'] !== null && $match['equipe_b'] !== '' ? $match['equipe_b'] : 'TBD';
                        $dateMatch = isset($match['date_match']) ? (string) $match['date_match'] : '';
                        $creneau = isset($match['creneau_horaire']) ? substr((string) $match['creneau_horaire'], 0, 5) : '';
                        $terrainNom = isset($match['terrain_nom']) ? (string) $match['terrain_nom'] : '';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $match['round']) ?></td>
                            <td><?= htmlspecialchars($teamA) ?></td>
                            <td><?= htmlspecialchars($teamB) ?></td>
                            <td><?= htmlspecialchars($dateMatch) ?></td>
                            <td><?= htmlspecialchars($creneau) ?></td>
                            <td><?= htmlspecialchars($terrainNom) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($isOrganisateur)): ?>
                <a class="btn btn--primary" href="/tournoi/<?= (int) $tournoi['id'] ?>/planner">Planifier les matchs</a>
            <?php endif; ?>
        </div>
    </div>
</section>

