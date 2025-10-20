<?php

declare(strict_types=1);

$faqs = [
    [
        'question' => 'Comment reserver un terrain ?',
        'answer' => 'Connectez-vous a votre compte, choisissez un terrain disponible et validez la reservation en quelques clics.',
    ],
    [
        'question' => 'Puis-je annuler une reservation ?',
        'answer' => 'Oui, accedez a la section Mes reservations et utilisez le bouton Annuler. Les conditions d annulation peuvent varier selon la date.',
    ],
    [
        'question' => 'Comment contacter le support ?',
        'answer' => 'Ecrivez-nous a contact@footfields.com ou utilisez la page Contact pour planifier un echange.',
    ],
];
?>

<section class="section static-page">
    <div class="card flow">
        <h1>FAQs &amp; assistance</h1>
        <p>Retrouvez ici les reponses aux questions les plus frequentes ainsi que des repaires pour obtenir de l aide rapidement.</p>
        <dl class="faq-list">
            <?php foreach ($faqs as $item): ?>
                <div class="faq-list__item">
                    <dt><?= htmlspecialchars($item['question']) ?></dt>
                    <dd><?= htmlspecialchars($item['answer']) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
        <p>Besoin d une reponse personnalisee ? Notre equipe reste joignable par email et via le chat client.</p>
    </div>
</section>
