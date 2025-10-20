<?php
$eventTypes = isset($eventTypes) && is_array($eventTypes) ? $eventTypes : [];
$skillLevels = isset($skillLevels) && is_array($skillLevels) ? $skillLevels : [];
$addonServices = isset($addonServices) && is_array($addonServices) ? $addonServices : [];
$selectedEventType = (string) ($reservation['type_evenement'] ?? (array_key_first($eventTypes) ?? ''));
$selectedSkillLevel = (string) ($reservation['niveau'] ?? (array_key_first($skillLevels) ?? ''));
$participantsValue = $reservation['participants'] ?? '';
$placeholderImage = '/assets/img/foot-fields-icon-1024.png';
?>
<section class="section">
    <h1 class="section__title">Modifier la reservation #<?= (int) $reservation['id'] ?></h1>
    <p class="section__subtitle">Ajustez les informations de votre creneau et les services associes.</p>
    <div class="feature-grid">
        <div class="card flow">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="/reservation/<?= (int) $reservation['id'] ?>/update" class="flow" data-recaptcha-action="reservation-update">
                <div>
                    <label for="terrain_id">Terrain</label>
                    <select id="terrain_id" name="terrain_id" class="form-control" required>
                        <?php foreach ($terrains as $terrain): ?>
                            <?php
                            $value = (string) ($terrain['id'] ?? '');
                            $imagePath = !empty($terrain['image_path'])
                                ? '/' . ltrim((string) $terrain['image_path'], '/')
                                : $placeholderImage;
                            ?>
                            <option
                                value="<?= htmlspecialchars($value) ?>"
                                data-image="<?= htmlspecialchars($imagePath) ?>"
                                <?= ((int) $reservation['terrain_id'] === (int) $terrain['id']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars(($terrain['nom'] ?? 'Terrain') . ' (' . ($terrain['taille'] ?? '') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="terrain-preview" aria-live="polite">
                    <?php
                    $currentImage = !empty($reservation['terrain_image_path'])
                        ? '/' . ltrim((string) $reservation['terrain_image_path'], '/')
                        : $placeholderImage;
                    ?>
                    <img id="terrain-preview-image"
                         src="<?= htmlspecialchars($currentImage) ?>"
                         data-placeholder="<?= htmlspecialchars($placeholderImage) ?>"
                         alt="Apercu du terrain selectionne">
                </div>

                <div class="form-grid form-grid--two">
                    <div>
                        <label for="date_reservation">Date</label>
                        <input
                            type="date"
                            id="date_reservation"
                            name="date_reservation"
                            class="form-control"
                            value="<?= htmlspecialchars($reservation['date_reservation']) ?>"
                            required
                        >
                    </div>
                    <div>
                        <label for="creneau_horaire">Creneau horaire</label>
                        <input
                            type="time"
                            id="creneau_horaire"
                            name="creneau_horaire"
                            class="form-control"
                            value="<?= htmlspecialchars(substr((string) $reservation['creneau_horaire'], 0, 5)) ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-grid form-grid--two">
                    <div>
                        <label for="type_evenement">Type d'evenement</label>
                        <select id="type_evenement" name="type_evenement" class="form-control">
                            <?php foreach ($eventTypes as $value => $label): ?>
                                <option value="<?= htmlspecialchars((string) $value) ?>"
                                    <?= $selectedEventType === (string) $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="niveau">Niveau de jeu</label>
                        <select id="niveau" name="niveau" class="form-control">
                            <?php foreach ($skillLevels as $value => $label): ?>
                                <option value="<?= htmlspecialchars((string) $value) ?>"
                                    <?= $selectedSkillLevel === (string) $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="participants">Nombre de participants</label>
                    <input
                        type="number"
                        id="participants"
                        name="participants"
                        class="form-control"
                        min="2"
                        max="40"
                        value="<?= htmlspecialchars((string) $participantsValue) ?>"
                    >
                </div>

                <div>
                    <label for="demande">Demande specifique</label>
                    <textarea
                        id="demande"
                        name="demande"
                        rows="3"
                        class="form-control"
                    ><?= htmlspecialchars((string) $reservation['demande']) ?></textarea>
                </div>

                <fieldset class="flow reservation-services">
                    <legend>Services supplementaires</legend>
                    <?php foreach ($servicePrices as $reference => $service): ?>
                        <?php $checked = (int) ($reservation[$reference] ?? 0) === 1; ?>
                        <label class="checkbox">
                            <input
                                type="checkbox"
                                name="<?= htmlspecialchars($reference) ?>"
                                value="1"
                                <?= $checked ? 'checked' : '' ?>
                            >
                            <span>
                                <?= htmlspecialchars($service['description'] ?? ucfirst($reference)) ?>
                                (<?= number_format($service['prix'], 2, ',', ' ') ?> DH)
                            </span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <button type="submit" class="btn btn--primary">Enregistrer les modifications</button>
            </form>
        </div>
        <div class="card flow">
            <h3>Resume de la reservation</h3>
            <p><strong>Terrain :</strong> <?= htmlspecialchars($reservation['terrain_nom'] ?? '') ?></p>
            <p><strong>Date :</strong> <?= htmlspecialchars($reservation['date_reservation']) ?></p>
            <p><strong>Creneau :</strong> <?= htmlspecialchars(substr((string) $reservation['creneau_horaire'], 0, 5)) ?></p>
            <p><strong>Type :</strong> <?= htmlspecialchars($eventTypes[$selectedEventType] ?? ucfirst($selectedEventType)) ?></p>
            <p><strong>Niveau :</strong> <?= htmlspecialchars($skillLevels[$selectedSkillLevel] ?? ucfirst($selectedSkillLevel)) ?></p>
            <?php if (!empty($participantsValue)): ?>
                <p><strong>Participants :</strong> <?= (int) $participantsValue ?></p>
            <?php endif; ?>
            <p><strong>Statut :</strong> <span class="badge badge--info"><?= htmlspecialchars($reservation['statut']) ?></span></p>
        </div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var terrainSelect = document.getElementById('terrain_id');
    var previewImage = document.getElementById('terrain-preview-image');
    if (!terrainSelect || !previewImage) {
        return;
    }
    var placeholder = previewImage.dataset.placeholder || '';
    function updatePreview() {
        var option = terrainSelect.options[terrainSelect.selectedIndex];
        if (option && option.dataset.image) {
            previewImage.src = option.dataset.image;
        } else if (placeholder !== '') {
            previewImage.src = placeholder;
        }
    }
    terrainSelect.addEventListener('change', updatePreview);
});
</script>

