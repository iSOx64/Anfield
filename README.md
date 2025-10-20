# Foot Fields

Lightweight MVC application in PHP 8.1 to manage football field reservations, optional services, and tournament planning.

## Installation

1. **Install dependencies**
   `hash
   composer install
   `
2. **Configure environment**
   - Copy .env to .env.local if you want local overrides and update database/SMTP credentials.
   - Provide Google reCAPTCHA v3 keys in RECAPTCHA_SITE_KEY and RECAPTCHA_SECRET_KEY.
   - Create the MySQL database and run the SQL script in database/schema.sql.
3. **Run the development server**
   `hash
   php -S localhost:8000 -t public
   `
4. **contact me for .env file** ww
check this:
https://footfields.kesug.com/

