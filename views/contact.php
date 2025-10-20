<?php

declare(strict_types=1);

$profile = isset($profile) && is_array($profile) ? $profile : [];
$metrics = isset($metrics) && is_array($metrics) ? $metrics : [];
$commitments = isset($commitments) && is_array($commitments) ? $commitments : [];
$collaborationSteps = isset($collaborationSteps) && is_array($collaborationSteps) ? $collaborationSteps : [];
$availability = isset($availability) && is_array($availability) ? $availability : [];
$availabilitySlots = isset($availability['slots']) && is_array($availability['slots']) ? $availability['slots'] : [];
$availabilityNext = is_string($availability['nextWindow'] ?? null) ? $availability['nextWindow'] : null;
$skills = isset($profile['skills']) && is_array($profile['skills']) ? $profile['skills'] : [];
$formData = isset($formData) && is_array($formData) ? $formData : [];
$formErrors = isset($formErrors) && is_array($formErrors) ? $formErrors : [];
$formStatus = isset($formStatus) && is_string($formStatus) ? $formStatus : null;
$formFeedback = isset($formFeedback) && is_string($formFeedback) ? $formFeedback : null;
$projectTypes = isset($projectTypes) && is_array($projectTypes) ? $projectTypes : [];
$contactChannels = isset($contactChannels) && is_array($contactChannels) ? $contactChannels : [];
$recaptchaOn = !empty($recaptchaEnabled);
?>
<section class="section contact-hero" id="contact">
    <div class="contact-layout">
        <div class="card flow contact-profile">
            <div class="contact-profile__header">
                <?php if (!empty($profile['photo'])): ?>
                    <img class="contact-profile__photo" src="<?= htmlspecialchars((string) $profile['photo']) ?>" alt="Portrait de <?= htmlspecialchars((string) ($profile['name'] ?? '')) ?>">
                <?php endif; ?>
                <div class="contact-profile__identity">
                    <h1 class="section__title"><?= htmlspecialchars((string) ($profile['name'] ?? '')) ?></h1>
                    <?php if (!empty($profile['title'])): ?>
                        <p class="section__subtitle"><?= htmlspecialchars((string) $profile['title']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($profile['location'])): ?>
                        <p class="contact-profile__location"><?= htmlspecialchars((string) $profile['location']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($profile['description'])): ?>
                <p><?= htmlspecialchars((string) $profile['description']) ?></p>
            <?php endif; ?>
            <?php if ($skills): ?>
                <div class="contact-skills">
                    <?php foreach ($skills as $skill): ?>
                        <span class="contact-skill"><?= htmlspecialchars((string) $skill) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="contact-meta">
                <div>
                    <span class="contact-meta__label">E-mail</span>
                    <a class="contact-meta__value" href="mailto:<?= htmlspecialchars((string) ($profile['email'] ?? '')) ?>"><?= htmlspecialchars((string) ($profile['email'] ?? '')) ?></a>
                </div>
                <div>
                    <span class="contact-meta__label">Telephone</span>
                    <a class="contact-meta__value" href="tel:+212766361603"><?= htmlspecialchars((string) ($profile['phone'] ?? '')) ?></a>
                </div>
            </div>
            <?php if ($availabilitySlots): ?>
                <div class="availability">
                    <h2>Disponibilites</h2>
                    <ul>
                        <?php foreach ($availabilitySlots as $slot): ?>
                            <li>
                                <strong><?= htmlspecialchars((string) ($slot['jour'] ?? '')) ?>:</strong>
                                <span><?= htmlspecialchars((string) ($slot['horaire'] ?? '')) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($availabilityNext): ?>
                        <p class="availability__hint"><?= htmlspecialchars($availabilityNext) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="contact-actions">
                <?php if (!empty($profile['email'])): ?>
                    <a class="btn btn--secondary" href="mailto:<?= htmlspecialchars((string) $profile['email']) ?>">Ecrire un email</a>
                <?php endif; ?>
                <?php if (!empty($profile['github'])): ?>
                    <a class="btn btn--ghost" target="_blank" rel="noopener" href="<?= htmlspecialchars((string) $profile['github']) ?>">GitHub</a>
                <?php endif; ?>
                <?php if (!empty($profile['linkedin'])): ?>
                    <a class="btn btn--ghost" target="_blank" rel="noopener" href="<?= htmlspecialchars((string) $profile['linkedin']) ?>">LinkedIn</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card flow contact-form-card">
            <h2>Parlez-moi de votre projet</h2>
            <p>Decrivez votre besoin et obtenez une reponse personnalisee, un plan d action et un chiffrage transparent.</p>
            <?php if ($formFeedback): ?>
                <?php $statusClass = $formStatus === 'success' ? 'contact-alert--success' : 'contact-alert--error'; ?>
                <div class="contact-alert <?= $statusClass ?>">
                    <?= htmlspecialchars($formFeedback) ?>
                </div>
            <?php endif; ?>
            <form method="post" action="/contact" class="contact-form flow" data-recaptcha-action="contact_form">
                <div class="form-grid">
                    <div>
                        <label for="name">Nom complet</label>
                        <input id="name" name="name" type="text" value="<?= htmlspecialchars((string) ($formData['name'] ?? '')) ?>" placeholder="Ex: Sarah Benali" required>
                        <?php if (isset($formErrors['name'])): ?>
                            <p class="form-error"><?= htmlspecialchars($formErrors['name']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="email">Adresse e-mail</label>
                        <input id="email" name="email" type="email" value="<?= htmlspecialchars((string) ($formData['email'] ?? '')) ?>" placeholder="vous@example.com" required>
                        <?php if (isset($formErrors['email'])): ?>
                            <p class="form-error"><?= htmlspecialchars($formErrors['email']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="organization">Organisation</label>
                        <input id="organization" name="organization" type="text" value="<?= htmlspecialchars((string) ($formData['organization'] ?? '')) ?>" placeholder="Nom du club ou de l association">
                    </div>
                    <div>
                        <label for="project_type">Type de projet</label>
                        <select id="project_type" name="project_type">
                            <?php foreach ($projectTypes as $value => $label): ?>
                                <option value="<?= htmlspecialchars((string) $value) ?>" <?= (($formData['project_type'] ?? '') === (string) $value) ? 'selected' : '' ?> <?= $value === '' ? 'disabled hidden' : '' ?>>
                                    <?= htmlspecialchars((string) $label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($formErrors['project_type'])): ?>
                            <p class="form-error"><?= htmlspecialchars($formErrors['project_type']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="budget">Budget cible</label>
                        <input id="budget" name="budget" type="text" value="<?= htmlspecialchars((string) ($formData['budget'] ?? '')) ?>" placeholder="Ex: 15 000 MAD">
                    </div>
                    <div>
                        <label for="timeline">Delai souhaite</label>
                        <input id="timeline" name="timeline" type="text" value="<?= htmlspecialchars((string) ($formData['timeline'] ?? '')) ?>" placeholder="Ex: Lancement d ici 2 mois">
                    </div>
                </div>
                <div>
                    <label for="message">Votre message</label>
                    <textarea id="message" name="message" rows="5" placeholder="Parlez-nous des objectifs, des utilisateurs vises et des fonctionnalites attendues." required><?= htmlspecialchars((string) ($formData['message'] ?? '')) ?></textarea>
                    <?php if (isset($formErrors['message'])): ?>
                        <p class="form-error"><?= htmlspecialchars($formErrors['message']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="preferred_channel">Canal de reponse prefere</label>
                    <select id="preferred_channel" name="preferred_channel">
                        <?php foreach ($contactChannels as $value => $label): ?>
                            <option value="<?= htmlspecialchars((string) $value) ?>" <?= (($formData['preferred_channel'] ?? 'email') === (string) $value) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($formErrors['preferred_channel'])): ?>
                        <p class="form-error"><?= htmlspecialchars($formErrors['preferred_channel']) ?></p>
                    <?php endif; ?>
                </div>
                <?php if (isset($formErrors['recaptcha'])): ?>
                    <p class="form-error"><?= htmlspecialchars($formErrors['recaptcha']) ?></p>
                <?php endif; ?>
                <?php if ($recaptchaOn): ?>
                    <input type="hidden" name="g-recaptcha-response" value="">
                    <p class="recaptcha-note">Ce formulaire est protege par Google reCAPTCHA v3.</p>
                <?php else: ?>
                    <p class="recaptcha-warning">reCAPTCHA non configure. Ajoutez vos cles RECAPTCHA_SITE_KEY et RECAPTCHA_SECRET_KEY.</p>
                <?php endif; ?>
                <button type="submit" class="btn btn--primary btn--full">Envoyer ma demande</button>
            </form>
        </div>
    </div>
</section>

<?php if ($metrics): ?>
    <section class="section contact-metrics">
        <div class="metrics-grid">
            <?php foreach ($metrics as $metric): ?>
                <div class="metric-card">
                    <span class="metric-card__value"><?= htmlspecialchars((string) ($metric['value'] ?? '')) ?></span>
                    <span class="metric-card__label"><?= htmlspecialchars((string) ($metric['label'] ?? '')) ?></span>
                    <?php if (!empty($metric['description'])): ?>
                        <p class="metric-card__description"><?= htmlspecialchars((string) $metric['description']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($commitments): ?>
    <section class="section contact-commitments">
        <h2 class="section__title">Ce que Foot Fields vous garantit</h2>
        <div class="commitment-grid">
            <?php foreach ($commitments as $commitment): ?>
                <div class="commitment-card card flow">
                    <h3><?= htmlspecialchars((string) ($commitment['title'] ?? '')) ?></h3>
                    <?php if (!empty($commitment['details']) && is_array($commitment['details'])): ?>
                        <ul>
                            <?php foreach ($commitment['details'] as $detail): ?>
                                <li><?= htmlspecialchars((string) $detail) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($collaborationSteps): ?>
    <section class="section contact-journey">
        <div class="card flow">
            <h2 class="section__title">Un parcours de collaboration clair</h2>
            <p>Du premier echange au deploiement, chaque etape est cadree pour vous apporter de la lisibilite et des livrables concrets.</p>
            <ol class="journey-steps">
                <?php foreach ($collaborationSteps as $step): ?>
                    <li>
                        <h3><?= htmlspecialchars((string) ($step['title'] ?? '')) ?></h3>
                        <p><?= htmlspecialchars((string) ($step['text'] ?? '')) ?></p>
                    </li>
                <?php endforeach; ?>
            </ol>
            <div class="journey-cta">
                <a class="btn btn--primary" href="#contact">Planifier un point de depart</a>
                <a class="btn btn--ghost" href="/ressources/faq">Consulter la FAQ</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="section contact-final">
    <div class="card flow contact-cta">
        <h2>Pret a faire evoluer votre experience terrain ?</h2>
        <p>Foot Fields vous aide a reserver, automatiser et piloter vos infrastructures sportives. Partagez votre contexte et vous recevrez un plan d action personnalise ainsi qu un prototype rapide.</p>
        <div class="home-hero__actions">
            <a class="btn btn--primary" href="#contact">Envoyer une demande</a>
            <a class="btn btn--secondary" href="mailto:<?= htmlspecialchars((string) ($profile['email'] ?? 'contact@footfields.com')) ?>">Demander une presentation</a>
        </div>
    </div>
</section>
