# Foot Fields

Lightweight MVC application in PHP 8.1 to manage football field reservations, optional services, and tournament planning.

## Installation

1. **Install dependencies**
   `ash
   composer install
   `
2. **Configure environment**
   - Copy .env to .env.local if you want local overrides and update database/SMTP credentials.
   - Provide Google reCAPTCHA v3 keys in RECAPTCHA_SITE_KEY and RECAPTCHA_SECRET_KEY.
   - Create the MySQL database and run the SQL script in database/schema.sql.
3. **Run the development server**
   `ash
   php -S localhost:8000 -t public
   `
4. **contact me for .env file** ww

### Upgrading an existing database

If your database was created before the email-verification/remember-me features, run:

`sql
ALTER TABLE utilisateur
  ADD COLUMN avatar_path VARCHAR(255) NULL AFTER password_hash,
  ADD COLUMN verification_code VARCHAR(12) NULL AFTER avatar_path,
  ADD COLUMN verification_expires_at DATETIME NULL AFTER verification_code,
  ADD COLUMN email_verified_at DATETIME NULL AFTER verification_expires_at,
  ADD COLUMN remember_token VARCHAR(255) NULL AFTER email_verified_at,
  ADD COLUMN remember_expires_at DATETIME NULL AFTER remember_token;
`

## Structure

- public/ � front controller (index.php) and static assets.
- pp/Core � configuration, database access, router, views, auth helpers.
- pp/Services � business services (reservations, pricing, tournaments, mail).
- pp/Controllers � HTTP controllers.
- iews/ � PHP templates grouped by feature.
- storage/ � logs and generated documents.

## Features

- Email verification with 6-digit code and resend option.
- Persistent login (�remember me�) cookie with secure, expiring tokens.
- Profile management (avatar upload, contact details).
- Admin dashboard to manage users, roles, and facilities.

## Licence


