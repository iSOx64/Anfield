<?php
$old = isset($old) && is_array($old) ? $old : [];
$eventTypes = isset($eventTypes) && is_array($eventTypes) ? $eventTypes : [];
$skillLevels = isset($skillLevels) && is_array($skillLevels) ? $skillLevels : [];
$addonServices = isset($addonServices) && is_array($addonServices) ? $addonServices : [];
$selectedTerrain = $old['terrain_id'] ?? '';
$selectedEventType = $old['type_evenement'] ?? (array_key_first($eventTypes) ?? '');
$selectedSkillLevel = $old['niveau'] ?? (array_key_first($skillLevels) ?? '');
$participantsValue = $old['participants'] ?? '';
$placeholderImage = '/assets/img/foot-fields-icon-1024.png';
?>
<section class="section">
    <h1 class="section__title">Planifier une reservation</h1>
    <p class="section__subtitle">Choisissez un terrain, un creneau et personnalisez votre experience selon vos joueurs.</p>
    <div class="feature-grid">
        <div class="card flow">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="/reservation" class="flow" data-recaptcha-action="reservation-create">
                <div>
                    <label for="terrain_id">Terrain</label>
                    <select id="terrain_id" name="terrain_id" class="form-control" required>
                        <option value="">Selectionnez un terrain</option>
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
                                <?= $selectedTerrain !== '' && (string) $selectedTerrain === $value ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars(($terrain['nom'] ?? 'Terrain') . ' (' . ($terrain['taille'] ?? '') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="terrain-preview" aria-live="polite">
                    <img id="terrain-preview-image"
                         src="<?= htmlspecialchars($placeholderImage) ?>"
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
                            value="<?= htmlspecialchars($old['date_reservation'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($old['creneau_horaire'] ?? '') ?>"
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
                        placeholder="Indiquez le nombre de joueurs prevu"
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
                        placeholder="Ajoutez des precisions sur votre evenement (niveau, format, animateur, etc.)"
                    ><?= htmlspecialchars((string) ($old['demande'] ?? '')) ?></textarea>
                </div>

                <fieldset class="flow reservation-services">
                    <legend>Services supplementaires</legend>
                    <?php foreach ($servicePrices as $reference => $service): ?>
                        <?php $checked = !empty($old[$reference]); ?>
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

                <button type="submit" class="btn btn--primary">Confirmer la reservation</button>
            </form>
        </div>
        <div class="card flow">
            <h3>Tarifs terrains</h3>
            <ul class="pricing-list">
                <?php foreach ($terrainPrices as $price): ?>
                    <li>
                        <span><?= htmlspecialchars(ucfirst($price['reference'])) ?></span>
                        <span><?= number_format($price['prix'], 2, ',', ' ') ?> DH</span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <h3>Services disponibles</h3>
            <ul class="service-list">
                <?php foreach ($servicePrices as $price): ?>
                    <li>
                        <span><?= htmlspecialchars(ucfirst($price['reference'])) ?></span>
                        <span><?= number_format($price['prix'], 2, ',', ' ') ?> DH</span>
                    </li>
                <?php endforeach; ?>
            </ul>
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
    updatePreview();
});
</script>

