<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Administration - Terrains';
$activeAdminNav = $activeAdminNav ?? 'terrains';
$flash = isset($flash) && is_array($flash) ? $flash : null;
$flashData = is_array($flash['data'] ?? null) ? $flash['data'] : [];
$terrainSizes = isset($terrainSizes) && is_array($terrainSizes) ? $terrainSizes : ['mini', 'moyen', 'grand'];
$terrainTypes = isset($terrainTypes) && is_array($terrainTypes) ? $terrainTypes : ['gazon_naturel', 'gazon_artificiel', 'dur'];
$placeholderImage = '/assets/img/foot-fields-icon-1024.png';

$formatLabel = static function (string $value): string {
    $formatted = str_replace('_', ' ', $value);
    return ucwords($formatted);
};

$totalTerrains = count($terrains);
$availableTerrains = 0;
foreach ($terrains as $terrainItem) {
    if ((int) ($terrainItem['disponible'] ?? 0) === 1) {
        $availableTerrains++;
    }
}
$inactiveTerrains = max(0, $totalTerrains - $availableTerrains);
?>
<section class="admin-header">
    <div class="admin-header__inner">
        <div class="admin-header__content">
            <span class="admin-header__eyebrow">Administration</span>
            <h1 class="admin-header__title">Gestion des terrains</h1>
            <p class="admin-header__description">
                Activez ou mettez en pause vos terrains en temps reel pour garantir une experience fluide aux clients.
            </p>
        </div>
        <div class="admin-header__metrics">
            <div class="admin-metric">
                <span class="admin-metric__label">Terrains actifs</span>
                <span class="admin-metric__value"><?= (int) $availableTerrains ?></span>
                <span class="admin-metric__hint">Prets a etre reserves</span>
            </div>
            <div class="admin-metric">
                <span class="admin-metric__label">En pause</span>
                <span class="admin-metric__value"><?= (int) $inactiveTerrains ?></span>
                <span class="admin-metric__hint">Maintenance ou indisponibles</span>
            </div>
        </div>
    </div>
</section>

<div class="admin-shell">
    <?php require __DIR__ . '/partials/nav.php'; ?>
    <div class="admin-main">
        <?php if ($flash): ?>
            <?php
            $flashStatus = $flash['status'] ?? 'info';
            $flashMessage = is_string($flash['message'] ?? null) ? $flash['message'] : '';
            ?>
            <div class="admin-flash admin-flash--<?= htmlspecialchars($flashStatus) ?>">
                <strong><?= $flashStatus === 'success' ? 'Succes' : 'Attention' ?>:</strong>
                <?= htmlspecialchars($flashMessage) ?>
            </div>
        <?php endif; ?>

        <article class="admin-card card admin-card--compact">
            <header class="admin-card__header">
                <div>
                    <h2 class="admin-card__title">Ajouter un terrain</h2>
                    <p class="admin-card__subtitle">
                        Anticipez les demandes en enregistrant vos nouveaux terrains ou espaces partenaires.
                    </p>
                </div>
            </header>
            <div class="admin-card__body">
                <form method="post" action="/admin/terrains/new" class="admin-form" enctype="multipart/form-data">
                    <div class="admin-form__grid">
                        <div class="admin-form__field">
                            <label for="terrain-nom">Nom du terrain</label>
                            <input
                                id="terrain-nom"
                                name="nom"
                                type="text"
                                required
                                value="<?= htmlspecialchars($flashData['nom'] ?? '') ?>"
                                placeholder="Terrain central 1"
                            >
                        </div>
                        <div class="admin-form__field">
                            <label for="terrain-taille">Taille</label>
                            <select id="terrain-taille" name="taille" required>
                                <option value="">Choisir</option>
                                <?php foreach ($terrainSizes as $size): ?>
                                    <option value="<?= htmlspecialchars($size) ?>" <?= ($flashData['taille'] ?? '') === $size ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(strtoupper($size)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-form__field">
                            <label for="terrain-type">Type de surface</label>
                            <select id="terrain-type" name="type" required>
                                <option value="">Choisir</option>
                                <?php foreach ($terrainTypes as $type): ?>
                                    <option value="<?= htmlspecialchars($type) ?>" <?= ($flashData['type'] ?? '') === $type ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($formatLabel($type)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-form__field">
                            <label for="terrain-image">Photo du terrain</label>
                            <input
                                type="file"
                                id="terrain-image"
                                name="terrain_image"
                                accept="image/*"
                            >
                            <small class="form-hint">Formats autorises : jpg, png, webp (2 MB max).</small>
                        </div>
                    </div>
                    <div class="admin-form__actions">
                        <label class="switch">
                            <input type="checkbox" name="disponible" value="1" <?= ($flashData['disponible'] ?? '1') === '1' ? 'checked' : '' ?>>
                            <span class="switch__slider" aria-hidden="true"></span>
                            <span class="switch__label">Disponible des maintenant</span>
                        </label>
                        <button type="submit" class="btn btn--primary">Ajouter le terrain</button>
                    </div>
                </form>
            </div>
        </article>

        <article class="admin-card card">
            <header class="admin-card__header">
                <div>
                    <h2 class="admin-card__title">Disponibilite par terrain</h2>
                    <p class="admin-card__subtitle">
                        Ajustez l etat de chaque terrain sans quitter votre bureau.
                    </p>
                </div>
            </header>
            <div class="admin-card__body">
                <?php if (empty($terrains)): ?>
                    <p class="empty-state">Aucun terrain enregistre pour le moment.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="data-table admin-table">
                            <thead>
                            <tr>
                                <th>Visuel</th>
                                <th>Nom</th>
                                <th>Taille</th>
                                <th>Type</th>
                                <th>Etat</th>
                                <th class="admin-table__actions">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($terrains as $terrain): ?>
                                <?php $isDisponible = (int) ($terrain['disponible'] ?? 0) === 1; ?>
                                <tr>
                                    <td>
                                        <?php
                                        $imagePath = !empty($terrain['image_path'])
                                            ? '/' . ltrim((string) $terrain['image_path'], '/')
                                            : $placeholderImage;
                                        ?>
                                        <img class="admin-terrain-thumb" src="<?= htmlspecialchars($imagePath) ?>" alt="Illustration du terrain <?= htmlspecialchars($terrain['nom']) ?>">
                                    </td>
                                    <td>
                                        <div class="admin-row-title">
                                            <strong><?= htmlspecialchars($terrain['nom']) ?></strong>
                                            <span class="admin-row-hint">
                                                <?= htmlspecialchars(strtoupper((string) $terrain['taille'])) ?> -
                                                <?= htmlspecialchars($formatLabel((string) $terrain['type'])) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($terrain['taille']) ?></td>
                                    <td><?= htmlspecialchars($terrain['type']) ?></td>
                                    <td>
                                        <span class="badge <?= $isDisponible ? 'badge--success' : 'badge--warning' ?>">
                                            <?= $isDisponible ? 'Disponible' : 'Indisponible' ?>
                                        </span>
                                    </td>
                                    <td class="admin-table__actions">
                                        <form method="post" action="/admin/terrains" class="admin-inline-form" enctype="multipart/form-data">
                                            <input type="hidden" name="terrain_id" value="<?= (int) $terrain['id'] ?>">
                                            <label class="switch">
                                                <input type="checkbox" name="disponible" value="1" <?= $isDisponible ? 'checked' : '' ?>>
                                                <span class="switch__slider" aria-hidden="true"></span>
                                                <span class="switch__label"><?= $isDisponible ? 'Actif' : 'Inactif' ?></span>
                                            </label>
                                            <label class="admin-inline-upload">
                                                <input type="file" name="terrain_image" accept="image/*">
                                                <span>Mettre a jour la photo</span>
                                            </label>
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
