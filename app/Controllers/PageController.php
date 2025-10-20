<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

class PageController
{
    public function faq(): string
    {
        return View::render('pages/faq', [
            'pageTitle' => 'FAQs & assistance',
        ]);
    }

    public function privacy(): string
    {
        return View::render('pages/privacy', [
            'pageTitle' => 'Politique de confidentialite',
        ]);
    }

    public function terms(): string
    {
        return View::render('pages/terms', [
            'pageTitle' => "Conditions d'utilisation",
        ]);
    }

    public function legal(): string
    {
        return View::render('pages/legal', [
            'pageTitle' => 'Mentions legales',
        ]);
    }

    public function cookies(): string
    {
        return View::render('pages/cookies', [
            'pageTitle' => 'Politique cookies',
        ]);
    }
}
