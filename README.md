# Foot Fields

Lightweight MVC application in PHP 8.1 to manage football field reservations, optional services, and tournament planning.

## Installation

1. Install dependencies with `composer install`.
2. Configure environment:
   - Copy `.env` to `.env.local` if needed and update database/SMTP credentials.
   - Create Google reCAPTCHA v3 keys and set `RECAPTCHA_SITE_KEY` plus `RECAPTCHA_SECRET_KEY`.
   - Provide SMTP settings (`SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_ENCRYPTION`, `MAIL_FROM`, `MAIL_FROM_NAME`).
   - Create the MySQL database and run the SQL script in `database/schema.sql`.
3. Run the development server with `php -S localhost:8000 -t public`.

### Upgrading existing database

If your database was created before the email-verification feature, apply:

```sql
ALTER TABLE utilisateur
  ADD COLUMN avatar_path VARCHAR(255) NULL AFTER password_hash,
  ADD COLUMN verification_code VARCHAR(12) NULL AFTER avatar_path,
  ADD COLUMN verification_expires_at DATETIME NULL AFTER verification_code,
  ADD COLUMN email_verified_at DATETIME NULL AFTER verification_expires_at;
```

## Structure

- `public/` — front controller (`index.php`) and static assets.
- `app/Core` — configuration, database access, router, views, auth helpers.
- `app/Services` — business services (reservations, pricing, tournaments, mail).
- `app/Controllers` — HTTP controllers.
- `views/` — PHP templates grouped by feature.
- `storage/` — logs and generated documents.

## Email verification

Each new account receives a 6-digit code by email. Users can renvoyer the code and must confirm before accessing protected routes. Admins can update roles from the dashboard and users can upload avatars from the profile page.

## Administration

- Default admin credentials: `admin@footfields.com` / `Admin123!` (change them in production).
- Tableau de bord synthese avec revenu mensuel, reservations a venir, part des terrains et KPI quotidiens.
- Gestion des terrains avec ajout rapide, edition des disponibilites et historique des activations.
- Recherche et edition des roles utilisateurs avec statistiques mensuelles.
- Vue disponibilites avec filtres multi-criteres (periode, terrain, statut) et export CSV pour partager l agenda.

## Tests

No automated tests are bundled, but you can add PHPUnit if required.

## Licence

Released under the MIT Licence.


