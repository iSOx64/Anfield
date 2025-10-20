<?php

use App\Core\Auth;
use App\Core\Config;

$auth = new Auth();
$currentUser = $auth->user();
$pageTitle = isset($pageTitle) ? $pageTitle : 'Foot Fields';
$recaptchaSiteKey = Config::get('RECAPTCHA_SITE_KEY');
$recaptchaSiteKey = is_string($recaptchaSiteKey) ? trim($recaptchaSiteKey) : '';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH);
$currentPath = $parsedPath !== false && $parsedPath !== null ? $parsedPath : '/';

$menuItems = [
    [
        'label' => 'Accueil',
        'href' => '/',
        'guest_href' => '/',
        'visible' => true,
    ],
    [
        'label' => 'Reservations',
        'href' => '/reservation/my',
        'guest_href' => '/login?target=reservations',
        'visible' => true,
    ],
    [
        'label' => 'Tournois',
        'href' => '/tournoi/create',
        'guest_href' => '/login?target=tournaments',
        'visible' => true,
    ],
    [
        'label' => 'Administration',
        'href' => '/admin/terrains',
        'guest_href' => '/admin/terrains',
        'visible' => $auth->isAdmin(),
    ],
];

$currentQuery = [];
if (!empty($_GET)) {
    $currentQuery = $_GET;
}

$renderRecaptchaScripts = function () use ($recaptchaSiteKey): void {
    if ($recaptchaSiteKey === '') {
        return;
    }
    ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($recaptchaSiteKey) ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var siteKey = <?= json_encode($recaptchaSiteKey) ?>;
            var forms = document.querySelectorAll('[data-recaptcha-action]');
            forms.forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.recaptchaSkip === '1') {
                        return;
                    }
                    event.preventDefault();
                    if (typeof grecaptcha === 'undefined') {
                        form.dataset.recaptchaSkip = '1';
                        form.submit();
                        return;
                    }
                    var action = form.getAttribute('data-recaptcha-action') || 'submit';
                    grecaptcha.ready(function () {
                        grecaptcha.execute(siteKey, { action: action }).then(function (token) {
                            var input = form.querySelector('input[name="g-recaptcha-response"]');
                            if (!input) {
                                input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'g-recaptcha-response';
                                form.appendChild(input);
                            }
                            input.value = token;
                            form.dataset.recaptchaSkip = '1';
                            form.submit();
                        }).catch(function () {
                            form.dataset.recaptchaSkip = '1';
                            form.submit();
                        });
                    });
                });
            });
        });
    </script>
    <?php
};

$renderSiteHeader = function () use ($menuItems, $currentPath, $currentQuery, $auth, $currentUser): void {
    ?>
    <header class="site-header">
        <div class="site-header__top">
            <div class="site-header__top-inner">
                <div class="site-header__contact">
                    <a class="site-header__info-link site-header__info-link--phone" href="tel:+212766361603">+212 7 66 36 16 03</a>
                    <a class="site-header__info-link site-header__info-link--mail" href="mailto:contact@footfields.com">contact@footfields.com</a>
                </div>
                <div class="site-header__badges">
                    <span class="site-header__badge">Support 7j/7</span>
                    <span class="site-header__badge">Paiements securises</span>
                </div>
            </div>
        </div>
        <div class="site-navbar">
            <a class="brand" href="/">
                <img src="/assets/img/foot-fields-icon-1024.png" alt="Foot Fields">
                Foot Fields
            </a>
            <nav class="site-nav" aria-label="Navigation principale">
                <?php foreach ($menuItems as $item) :
                    if (!$item['visible']) {
                        continue;
                    }
                    $link = $auth->check() ? $item['href'] : ($item['guest_href'] ?? $item['href']);
                    $linkPath = parse_url($link, PHP_URL_PATH) ?? $link;
                    parse_str(parse_url($link, PHP_URL_QUERY) ?? '', $linkQuery);
                    if ($auth->check()) {
                        $active = $linkPath !== '/' ? str_starts_with($currentPath, $linkPath) : $currentPath === '/';
                    } else {
                        $active = $currentPath === $linkPath;
                        if ($active && !empty($linkQuery)) {
                            $active = true;
                            foreach ($linkQuery as $key => $value) {
                                $active = $active && (($currentQuery[$key] ?? null) === $value);
                            }
                        }
                    }
                    ?>
                    <a class="site-nav__link <?= $active ? 'site-nav__link--active' : '' ?>" href="<?= htmlspecialchars($link) ?>">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="site-actions">
                <?php if ($auth->check()) : ?>
                    <a class="avatar" href="/profile" aria-label="Profil">
                        <?php
                        if (!empty($currentUser['avatar_path'])) {
                            echo '<img src="/' . htmlspecialchars($currentUser['avatar_path']) . '" alt="Avatar">';
                        } else {
                            $initial = 'U';
                            if (!empty($currentUser['prenom'])) {
                                $initial = strtoupper(substr((string) $currentUser['prenom'], 0, 1));
                            } elseif (!empty($currentUser['nom'])) {
                                $initial = strtoupper(substr((string) $currentUser['nom'], 0, 1));
                            }
                            echo htmlspecialchars($initial);
                        }
                        ?>
                    </a>
                    <form method="post" action="/logout" class="logout-form">
                        <button type="submit" class="btn btn--secondary btn--sm">Déconnexion</button>
                    </form>
                <?php else : ?>
                    <a class="site-nav__link" href="/login">Connexion</a>
                    <a class="btn btn--primary" href="/register">Créer un compte</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <?php
};

$renderSiteFooter = function () use ($auth): void {
    ?>
    <footer class="site-footer">
        <div class="site-footer__inner">
            <div class="site-footer__grid site-footer__grid--enhanced">
                <div class="site-footer__brand">
                    <img src="/assets/img/foot-fields-icon-1024.png" alt="Foot Fields">
                    <p>Foot Fields accompagne les clubs et complexes sportifs avec une plateforme moderne pour reserver, animer et analyser l activite des terrains.</p>
                    <div class="footer-actions">
                        <a class="btn btn--footer btn--ghost" href="mailto:contact@footfields.com">
                            <span class="footer-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false"><path d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zm2 1v.35l7 4.67 7-4.67V6zm14 12V9.28l-6.42 4.28a1 1 0 0 1-1.16 0L5 9.28V18z"/></svg>
                            </span>
                            <span>Ecrire</span>
                        </a>
                    </div>
                    <ul class="footer-social">
                        <li><a class="footer-social__link" href="https://github.com/iSOx64" target="_blank" rel="noopener" aria-label="GitHub de Foot Fields">
                            <svg viewBox="0 0 24 24" focusable="false"><path d="M12 .5a11.5 11.5 0 0 0-3.63 22.42c.57.1.78-.25.78-.55v-1.94c-3.18.69-3.85-1.53-3.85-1.53-.52-1.31-1.28-1.66-1.28-1.66-1.05-.72.08-.71.08-.71 1.16.08 1.77 1.2 1.77 1.2 1.04 1.78 2.73 1.27 3.4.97.1-.76.41-1.27.75-1.56-2.54-.29-5.21-1.27-5.21-5.64 0-1.25.45-2.27 1.2-3.07-.12-.29-.52-1.45.11-3.02 0 0 .97-.31 3.18 1.18a11 11 0 0 1 5.8 0c2.21-1.49 3.18-1.18 3.18-1.18.63 1.57.23 2.73.11 3.02.75.8 1.2 1.82 1.2 3.07 0 4.39-2.68 5.34-5.23 5.62.42.36.8 1.07.8 2.17v3.22c0 .31.21.66.79.55A11.5 11.5 0 0 0 12 .5z"/></svg>
                        </a></li>
                        <li><a class="footer-social__link" href="https://www.linkedin.com/in/abderrahim-sadiki-4b5722231/" target="_blank" rel="noopener" aria-label="LinkedIn de Foot Fields">
                            <svg viewBox="0 0 24 24" focusable="false"><path d="M20.45 20.45h-3.55v-5.4c0-1.29-.02-2.95-1.8-2.95-1.8 0-2.08 1.4-2.08 2.85v5.5H9.47V9h3.4v1.56h.05c.47-.9 1.63-1.85 3.35-1.85 3.58 0 4.24 2.36 4.24 5.42zM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zM7.12 20.45H3.56V9H7.12zM22 0H2A2 2 0 0 0 0 2v20a2 2 0 0 0 2 2h20a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/></svg>
                        </a></li>
                    </ul>
                </div>
                <div class="site-footer__col">
                    <h4>Navigation</h4>
                    <ul class="site-footer__list">
                        <li><a href="/">Accueil</a></li>
                        <li><a href="<?= $auth->check() ? '/reservation/create' : '/login' ?>">Reserver un terrain</a></li>
                        <li><a href="<?= $auth->check() ? '/tournoi/create' : '/login' ?>">Organiser un tournoi</a></li>
                        <li><a href="/contact">Contact</a></li>
                        <?php if ($auth->isAdmin()) : ?>
                            <li><a href="/admin/terrains">Espace administration</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="site-footer__col">
                    <h4>Coordonnees</h4>
                    <ul class="site-footer__list site-footer__list--contact">
                        <li><a href="tel:+212766361603">+212 7 66 36 16 03</a></li>
                        <li><a href="mailto:contact@footfields.com">contact@footfields.com</a></li>
                        <li>45 Rue des Jardins, Casablanca</li>
                        <li>Support client 7j/7</li>
                    </ul>
                </div>
                <div class="site-footer__col">
                    <h4>Ressources</h4>
                    <ul class="site-footer__list">
                        <li><a href="/ressources/faq">FAQs &amp; assistance</a></li>
                        <li><a href="/ressources/politique-confidentialite">Politique de confidentialite</a></li>
                        <li><a href="/ressources/conditions-utilisation">Conditions d'utilisation</a></li>
                    </ul>
                </div>
            </div>
            <div class="site-footer__bottom">
                <span>&copy; <?= date('Y') ?> Foot Fields. Tous droits reserves.</span>
                <div class="site-footer__legal">
                    <a href="/mentions-legales">Mentions legales</a>
                    <a href="/politique-cookies">Politique cookies</a>
                </div>
            </div>
        </div>
    </footer>
    <?php
};

if (!empty($isAuthPage)) :
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" href="/assets/img/foot-fields-icon-transparent-1024.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-body">
<?php $renderSiteHeader(); ?>
<main id="main-content" class="site-main site-main--auth">
    <?= $content ?>
</main>
<?php $renderSiteFooter(); ?>
<?php $renderRecaptchaScripts(); ?>
<script src="/assets/js/app.js"></script>
</body>
</html>
<?php
return;
endif;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" href="/assets/img/foot-fields-icon-transparent-1024.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<a class="skip-link" href="#main-content">Aller au contenu principal</a>
<?php $renderSiteHeader(); ?>
<main id="main-content" class="site-main">
    <?= $content ?>
</main>
<?php $renderSiteFooter(); ?>
<?php $renderRecaptchaScripts(); ?>
<script src="/assets/js/app.js"></script>
</body>
</html>




















