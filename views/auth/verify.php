<?php
$emailSafe = isset($email) ? htmlspecialchars((string) $email) : '';
$errorSafe = isset($error) ? $error : null;
$statusSafe = isset($status) ? $status : null;
?>
<div class="auth-layout">
    <aside class="auth-sidebar">
        <div class="auth-brand">
            <img src="/assets/img/foot-fields-icon-transparent-1024.png" alt="Foot Fields">
            <span>Foot Fields</span>
        </div>
        <nav class="auth-menu">
            <a class="active" href="/verify"><span class="auth-menu-icon icon-user"></span>Vérification</a>
            <a href="/"><span class="auth-menu-icon icon-home"></span>Accueil</a>
        </nav>
        <ul class="auth-highlights">
            <li>✔ Sécurité renforcée</li>
            <li>✔ Support prioritaire</li>
        </ul>
    </aside>
    <section class="auth-content">
        <div class="auth-card flow">
            <h2>Confirmez votre e-mail</h2>
            <p class="auth-subtitle">Entrez le code à six chiffres envoyé à <?= $emailSafe ?></p>
            <?php if (!empty($errorSafe)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errorSafe) ?></div>
            <?php endif; ?>
            <?php if (!empty($statusSafe)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($statusSafe) ?></div>
            <?php endif; ?>
            <form method="post" action="/verify" class="auth-form flow">
                <div>
                    <label for="code">Code de vérification</label>
                    <input type="text" name="code" id="code" class="form-control" maxlength="6" pattern="[0-9]{6}" placeholder="123456" required>
                </div>
                <button type="submit" class="btn btn--primary auth-submit">Valider</button>
            </form>
            <form method="post" action="/verify/resend" class="auth-form flow">
                <button type="submit" class="btn btn--secondary btn--sm">Renvoyer le code</button>
            </form>
            <p class="auth-footer">Adresse incorrecte ? <a class="auth-link" href="/register">Créer un nouveau compte</a></p>
        </div>
    </section>
</div>
