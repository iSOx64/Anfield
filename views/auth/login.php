<?php
$isAuthPage = true;

$hasError = isset($error) && $error !== null && $error !== '';
$recaptchaOn = !empty($recaptchaEnabled);
$statusMessage = isset($status) && $status !== null && $status !== '' ? $status : null;
$unverifiedFlag = !empty($unverified);
$rememberChecked = !empty($remember);
?>
<div class="auth-split">
    <div class="auth-showcase">
        <div class="auth-showcase__inner">
            <span class="badge badge--info">Plateforme club</span>
            <h1>Foot Fields, l'expérience football clé en main</h1>
            <p>Gérez vos terrains, services et tournois avec la même fluidité qu'une équipe de ligue pro. Tout est synchronisé et prêt pour vos joueurs.</p>
            <div class="auth-highlight-grid">
                <div class="auth-highlight">
                    <span class="auth-highlight__value">280+</span>
                    <span class="auth-highlight__label">clubs accompagnés</span>
                </div>
                <div class="auth-highlight">
                    <span class="auth-highlight__value">1 200</span>
                    <span class="auth-highlight__label">réservations mensuelles</span>
                </div>
                <div class="auth-highlight">
                    <span class="auth-highlight__value">98%</span>
                    <span class="auth-highlight__label">satisfaction</span>
                </div>
            </div>
        </div>
        <img class="auth-showcase__visual" src="/assets/img/foot-fields-banner-modern-neon-1920x1080.png" alt="Illustration terrain de football">
    </div>

    <div class="auth-card auth-card--standalone flow">
        <div class="auth-card__header auth-card__header--center">
            <img class="auth-card__logo" src="/assets/img/foot-fields-icon-1024.png" alt="Foot Fields" width="48" height="48">
            <h1 class="auth-card__title">Connexion à Foot Fields</h1>
            <p class="auth-subtitle">Accédez à votre espace club en toute sécurité.</p>
        </div>

        <?php if ($hasError): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($statusMessage): ?>
            <div class="alert alert-success"><?= htmlspecialchars($statusMessage) ?></div>
        <?php endif; ?>

        <?php if ($unverifiedFlag): ?>
            <div class="auth-info">
                Votre adresse e-mail n'est pas encore vérifiée. Consultez votre boîte de réception ou
                <a href="/verify">saisissez votre code</a>.
            </div>
        <?php endif; ?>

        <form method="post" action="/login" class="auth-form flow" data-recaptcha-action="login">
            <div>
                <label for="email">E-mail</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="vous@club.com" required>
            </div>

            <div>
                <label for="password">Mot de passe</label>
                <div class="input-with-action">
                    <input type="password" name="password" id="password" class="form-control" required>
                    <button type="button" class="icon-button" data-password-toggle="password" aria-label="Afficher le mot de passe">
                        <span class="icon-eye"></span>
                    </button>
                </div>
            </div>

            <?php if ($recaptchaOn): ?>
                <input type="hidden" name="g-recaptcha-response" value="">
                <p class="recaptcha-note">Ce formulaire est protégé par Google reCAPTCHA v3.</p>
            <?php else: ?>
                <p class="recaptcha-warning">reCAPTCHA non configuré. Ajoutez vos clés RECAPTCHA_SITE_KEY et RECAPTCHA_SECRET_KEY.</p>
            <?php endif; ?>

            <div class="auth-options">
                <label class="checkbox auth-options__remember">
                    <input type="checkbox" name="remember" value="1" <?= $rememberChecked ? 'checked' : '' ?>>
                    <span>Se souvenir de moi</span>
                </label>
                <a class="auth-link auth-options__link" href="#">Mot de passe oublié ?</a>
            </div>

            <button type="submit" class="btn btn--primary auth-submit">Se connecter</button>
        </form>

        <p class="auth-footer">
            Vous n'avez pas de compte ?
            <a class="auth-link" href="/register">Créer un compte</a>
        </p>
    </div>
</div>
