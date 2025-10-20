<?php
$userNom = isset($user['nom']) ? (string) $user['nom'] : '';
$userPrenom = isset($user['prenom']) ? (string) $user['prenom'] : '';
$userEmail = isset($user['email']) ? (string) $user['email'] : '';
$userTelephone = isset($user['telephone']) ? (string) $user['telephone'] : '';
$userAdresse = isset($user['adresse']) ? (string) $user['adresse'] : '';
$avatarPath = isset($user['avatar_path']) ? (string) $user['avatar_path'] : '';
$initial = strtoupper(substr($userPrenom !== '' ? $userPrenom : ($userNom !== '' ? $userNom : 'U'), 0, 1));
?>
<section class="section">
    <h1 class="section__title">Mon profil</h1>
    <p class="section__subtitle">Complétez vos informations pour faciliter la gestion de vos réservations et tournois.</p>
    <div class="feature-grid">
        <div class="card profile-card flow">
            <form method="post" action="/profile" enctype="multipart/form-data" class="profile-form flow">
                <div class="profile-avatar flow">
                    <?php if ($avatarPath !== ''): ?>
                        <img src="/<?= htmlspecialchars($avatarPath) ?>" alt="Avatar utilisateur">
                    <?php else: ?>
                        <div class="avatar-placeholder"><?= htmlspecialchars($initial) ?></div>
                    <?php endif; ?>
                    <label class="btn btn--secondary btn--sm">
                        Changer l'image
                        <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp">
                    </label>
                    <small>PNG, JPG, WebP (max. 2 Mo)</small>
                </div>

                <div class="form-grid form-grid--two">
                    <div>
                        <label>Nom</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($userNom) ?>" disabled>
                    </div>
                    <div>
                        <label>Prénom</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($userPrenom) ?>" disabled>
                    </div>
                </div>

                <div>
                    <label>E-mail</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($userEmail) ?>" disabled>
                </div>

                <div class="form-grid form-grid--two">
                    <div>
                        <label for="telephone">Téléphone</label>
                        <input type="text" id="telephone" name="telephone" class="form-control" value="<?= htmlspecialchars($userTelephone) ?>">
                    </div>
                    <div>
                        <label for="adresse">Adresse</label>
                        <textarea id="adresse" name="adresse" rows="3" class="form-control"><?= htmlspecialchars($userAdresse) ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </form>
        </div>
    </div>
</section>


