<div class="card">
    <h2>Planifier les matchs - <?= htmlspecialchars($tournoi['nom']) ?></h2>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <p><strong>Format :</strong> <?= (int) $tournoi['format'] ?> equipes</p>
    <p><strong>Equipes enregistrees :</strong> <?= count($teams) ?></p>
</div>

<div class="card">
    <h3>Equipes</h3>
    <?php if (empty($teams)): ?>
        <p>Aucune equipe inscrite. Ajoutez des equipes avant de planifier.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($teams as $team): ?>
                <li><?= htmlspecialchars($team['nom']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php if (!empty($teams)): ?>
    <div class="card">
        <h3>Parametres de planification</h3>
        <form method="post" action="/tournoi/<?= (int) $tournoi['id'] ?>/plan">
            <label for="date_match">Date par defaut</label>
            <input type="date" id="date_match" name="date_match">

            <label for="creneau">Creneau horaire</label>
            <input type="time" id="creneau" name="creneau">

            <label for="terrain_id">Terrain</label>
            <select id="terrain_id" name="terrain_id">
                <option value="">Aucun</option>
                <?php foreach ($terrains as $terrain): ?>
                    <option value="<?= (int) $terrain['id'] ?>">
                        <?= htmlspecialchars($terrain['nom']) ?> (<?= htmlspecialchars($terrain['taille']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Generer les matchs</button>
        </form>
    </div>
<?php endif; ?>
