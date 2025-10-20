<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Recaptcha;
use App\Core\View;
use App\Services\Mailer;

class ContactController
{
    private Mailer $mailer;

    public function __construct(?Mailer $mailer = null)
    {
        $this->mailer = $mailer ?? new Mailer();
    }

    public function show(): string
    {
        return $this->renderPage();
    }

    public function submit(): string
    {
        $payload = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'organization' => trim((string) ($_POST['organization'] ?? '')),
            'project_type' => trim((string) ($_POST['project_type'] ?? '')),
            'budget' => trim((string) ($_POST['budget'] ?? '')),
            'timeline' => trim((string) ($_POST['timeline'] ?? '')),
            'message' => trim((string) ($_POST['message'] ?? '')),
            'preferred_channel' => trim((string) ($_POST['preferred_channel'] ?? 'email')),
        ];

        $errors = $this->validate($payload);
        $recaptchaEnabled = $this->isRecaptchaEnabled();
        $token = isset($_POST['g-recaptcha-response']) ? (string) $_POST['g-recaptcha-response'] : null;

        if ($recaptchaEnabled) {
            $action = 'contact_form';
            $remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;
            if (!Recaptcha::verify($token, $action, 0.3, is_string($remoteIp) ? $remoteIp : null)) {
                $errors['recaptcha'] = 'La verification reCAPTCHA a echoue. Veuillez reessayer.';
            }
        }

        $status = null;
        $feedback = null;

        if (empty($errors)) {
            $subject = sprintf('[Foot Fields] Contact - %s', $payload['name']);
            $receiverEmail = Config::get('MAIL_CONTACT_TO', Config::get('MAIL_FROM', 'contact@footfields.com'));
            $receiverName = Config::get('MAIL_CONTACT_TO_NAME', Config::get('MAIL_FROM_NAME', 'Equipe Foot Fields'));

            $body = $this->buildMessageBody($payload);
            $sent = $this->mailer->send(
                is_string($receiverEmail) && $receiverEmail !== '' ? $receiverEmail : 'contact@footfields.com',
                is_string($receiverName) && $receiverName !== '' ? $receiverName : 'Equipe Foot Fields',
                $subject,
                $body
            );

            if ($sent) {
                $status = 'success';
                $feedback = 'Merci, votre message a bien ete envoye. Nous vous repondrons sous 24h.';
                $payload = $this->defaultFormState();
            } else {
                $status = 'error';
                $feedback = 'Une erreur est survenue lors de l envoi du message. Merci de reessayer ou de nous ecrire directement.';
            }
        }

        return $this->renderPage([
            'formData' => $payload,
            'formErrors' => $errors,
            'formStatus' => $status,
            'formFeedback' => $feedback,
        ]);
    }

    /**
     * @param array<string, string> $payload
     * @return array<string, string>
     */
    private function validate(array $payload): array
    {
        $errors = [];

        if ($payload['name'] === '') {
            $errors['name'] = 'Veuillez indiquer votre nom complet.';
        }

        if ($payload['email'] === '') {
            $errors['email'] = 'Veuillez fournir une adresse e-mail.';
        } elseif (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L adresse e-mail semble invalide.';
        }

        if ($payload['message'] === '') {
            $errors['message'] = 'Merci de detailler votre besoin.';
        } elseif (mb_strlen($payload['message']) < 20) {
            $errors['message'] = 'Le message doit contenir au moins 20 caracteres pour que nous puissions repondre efficacement.';
        }

        if ($payload['project_type'] !== '' && !in_array($payload['project_type'], array_keys($this->projectTypes()), true)) {
            $errors['project_type'] = 'Le type de projet selectionne est invalide.';
        }

        if ($payload['preferred_channel'] !== '' && !in_array($payload['preferred_channel'], array_keys($this->contactChannels()), true)) {
            $errors['preferred_channel'] = 'Le canal de reponse selectionne est invalide.';
        }

        return $errors;
    }

    /**
     * @param array<string, string> $payload
     */
    private function buildMessageBody(array $payload): string
    {
        $rows = [
            ['Nom', $payload['name']],
            ['Email', $payload['email']],
            ['Organisation', $payload['organization'] !== '' ? $payload['organization'] : 'Non precise'],
            ['Type de projet', $this->projectTypes()[$payload['project_type']] ?? 'Non precise'],
            ['Budget cible', $payload['budget'] !== '' ? $payload['budget'] : 'Non precise'],
            ['Delai souhaite', $payload['timeline'] !== '' ? $payload['timeline'] : 'Non precise'],
            ['Canal prefere', $this->contactChannels()[$payload['preferred_channel']] ?? 'Email'],
        ];

        $body = '<h2>Nouveau message via la page Contact</h2>';
        $body .= '<table style="width:100%;border-collapse:collapse;">';
        foreach ($rows as [$label, $value]) {
            $body .= sprintf(
                '<tr><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">%s</th><td style="padding:8px;border-bottom:1px solid #eee;">%s</td></tr>',
                htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
                nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'))
            );
        }
        $body .= '</table>';
        $body .= sprintf(
            '<h3 style="margin-top:24px;">Message</h3><p style="white-space:pre-line;">%s</p>',
            htmlspecialchars($payload['message'], ENT_QUOTES, 'UTF-8')
        );

        return $body;
    }

    private function renderPage(array $overrides = []): string
    {
        $shared = [
            'pageTitle' => 'Contact',
            'profile' => $this->profile(),
            'formData' => $overrides['formData'] ?? $this->defaultFormState(),
            'formErrors' => $overrides['formErrors'] ?? [],
            'formStatus' => $overrides['formStatus'] ?? null,
            'formFeedback' => $overrides['formFeedback'] ?? null,
            'recaptchaEnabled' => $this->isRecaptchaEnabled(),
            'projectTypes' => $this->projectTypes(),
            'contactChannels' => $this->contactChannels(),
            'metrics' => $this->metrics(),
            'commitments' => $this->commitments(),
            'collaborationSteps' => $this->collaborationSteps(),
            'availability' => $this->availability(),
        ];

        return View::render('contact', $shared);
    }

    private function profile(): array
    {
        return [
            'name' => 'Abderrahim Sadiki',
            'title' => 'Ingenieur logiciel - ENSA Tetouan',
            'description' => 'J accompagne les complexes sportifs et associations dans la conception de plateformes fiables, evolutives et orientees resultats.',
            'skills' => ['PHP 8', 'Architecture logiciel', 'Laravel', 'React', 'DevOps', 'Data Analytics'],
            'photo' => '/assets/img/iSOx64.jpg',
            'github' => 'https://github.com/iSOx64',
            'linkedin' => 'https://www.linkedin.com/in/abderrahim-sadiki-4b5722231/',
            'email' => 'contact@footfields.com',
            'phone' => '+212 7 66 36 16 03',
            'location' => 'ENSA Tetouan - Maroc',
        ];
    }

    private function defaultFormState(): array
    {
        return [
            'name' => '',
            'email' => '',
            'organization' => '',
            'project_type' => '',
            'budget' => '',
            'timeline' => '',
            'message' => '',
            'preferred_channel' => 'email',
        ];
    }

    private function isRecaptchaEnabled(): bool
    {
        return (bool) Config::get('RECAPTCHA_SITE_KEY') && (bool) Config::get('RECAPTCHA_SECRET_KEY');
    }

    private function projectTypes(): array
    {
        return [
            '' => 'Selectionnez un type de projet',
            'reservation' => 'Reservation & gestion des terrains',
            'tournament' => 'Organisation de tournois & brackets',
            'analytics' => 'Tableaux de bord et statistiques',
            'automation' => 'Automatisation et integrations',
            'other' => 'Autre initiative digitale',
        ];
    }

    private function contactChannels(): array
    {
        return [
            'email' => 'E-mail',
            'video' => 'Visio (Google Meet, Zoom)',
            'whatsapp' => 'Message WhatsApp',
        ];
    }

    private function metrics(): array
    {
        return [
            [
                'label' => 'Instances Foot Fields deployees',
                'value' => '12+',
                'description' => 'Complexes sportifs accompagnes avec des solutions sur-mesure.',
            ],
            [
                'label' => 'Delai moyen de reponse',
                'value' => '-24h',
                'description' => 'Engagement a repondre a chaque sollicitation sous 24 heures ouvrables.',
            ],
            [
                'label' => 'Satisfaction clients',
                'value' => '4.9/5',
                'description' => 'Moyenne des retours sur les derniers projets pilotes.',
            ],
        ];
    }

    private function commitments(): array
    {
        return [
            [
                'title' => 'Accompagnement personnalise',
                'details' => [
                    'Diagnostic gratuit de votre organisation actuelle.',
                    'Prototype interactif livre en moins de 10 jours.',
                    'Plan de deploiement detaille et support a la conduite du changement.',
                ],
            ],
            [
                'title' => 'Fiabilite technique',
                'details' => [
                    'Stack moderne (PHP 8, Laravel, React) et automatisation CI/CD.',
                    'Tests fonctionnels et revues de code systematiques.',
                    'Supervision et alerting pour maintenir la disponibilite.',
                ],
            ],
            [
                'title' => 'Transparence',
                'details' => [
                    'Reporting hebdomadaire des avancements.',
                    'Facturation claire et forfaits adaptes au niveau d ambition.',
                    'Acces partage aux documents et outils projet.',
                ],
            ],
        ];
    }

    private function collaborationSteps(): array
    {
        return [
            [
                'title' => 'Exploration (30 min)',
                'text' => 'Entretien pour comprendre vos enjeux, vos utilisateurs et vos objectifs business.',
            ],
            [
                'title' => 'Proposition sur-mesure',
                'text' => 'Remise d un plan d action (fonctionnalites, planning, investissement) dans les 72h.',
            ],
            [
                'title' => 'Prototype & validation',
                'text' => 'Proof-of-concept interactif pour valider l experience sur un panel cible.',
            ],
            [
                'title' => 'Deploiement accompagne',
                'text' => 'Industrialisation, transfert de competences et suivi post-lancement.',
            ],
        ];
    }

    private function availability(): array
    {
        return [
            'slots' => [
                ['jour' => 'Lundi - Vendredi', 'horaire' => '09h00 - 18h30 GMT+1'],
                ['jour' => 'Samedi', 'horaire' => '10h00 - 14h00 GMT+1'],
            ],
            'nextWindow' => 'Nouvelles disponibilites pour ateliers decouverte: semaine du 27 octobre.',
        ];
    }
}
