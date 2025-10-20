<?php
$isAuthPage = true;
$hasError = isset($error) && $error !== null && $error !== '';
$recaptchaOn = !empty($recaptchaEnabled);
?>
<div class="auth-card auth-card--standalone flow auth-card--register">
    <div class="auth-card__header auth-card__header--center">
        <img class="auth-card__logo" src="/assets/img/foot-fields-icon-1024.png" alt="Foot Fields" width="48" height="48">
        <h1 class="auth-card__title">Créer un compte</h1>
        <p class="auth-subtitle">Rejoignez Foot Fields et centralisez la gestion de vos terrains.</p>
    </div>

    <?php if ($hasError): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/register" class="auth-form flow" data-recaptcha-action="register">
        <div class="form-grid form-grid--two">
            <div>
                <label for="nom">Nom</label>
                <input type="text" name="nom" id="nom" class="form-control" required>
            </div>
            <div>
                <label for="prenom">Prénom</label>
                <input type="text" name="prenom" id="prenom" class="form-control" required>
            </div>
        </div>

        <div>
            <label for="email">E-mail</label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>

        <div class="form-grid form-grid--two">
            <div>
                <label for="telephone">Téléphone</label>
                <input type="text" name="telephone" id="telephone" class="form-control">
            </div>
            <div>
                <label for="adresse">Adresse</label>
                <input type="text" name="adresse" id="adresse" class="form-control">
            </div>
        </div>

        <div class="form-grid form-grid--two">
            <div>
                <label for="password">Mot de passe</label>
                <div class="input-with-action">
                    <input type="password" name="password" id="password" class="form-control" required>
                    <button type="button" class="icon-button" data-password-toggle="password" aria-label="Afficher le mot de passe">
                        <span class="icon-eye"></span>
                    </button>
                </div>
            </div>
            <div>
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <div class="input-with-action">
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                    <button type="button" class="icon-button" data-password-toggle="password_confirmation" aria-label="Afficher le mot de passe">
                        <span class="icon-eye"></span>
                    </button>
                </div>
            </div>
        </div>

        <?php if ($recaptchaOn): ?>
            <input type="hidden" name="g-recaptcha-response" value="">
            <p class="recaptcha-note">Ce formulaire est protégé par Google reCAPTCHA v3.</p>
        <?php else: ?>
            <p class="recaptcha-warning">reCAPTCHA non configuré. Ajoutez vos clés RECAPTCHA_SITE_KEY et RECAPTCHA_SECRET_KEY.</p>
        <?php endif; ?>

        <button type="submit" class="btn btn--primary auth-submit">Créer mon compte</button>
    </form>

    <p class="auth-footer">Vous avez déjà un compte ?
        <a class="auth-link" href="/login">Se connecter</a>
    </p>
</div>
