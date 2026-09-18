# GreenSprout Café

A café ordering application built with **PHP, MySQL and vanilla JavaScript**. Originally developed as a group university project, then redesigned and enhanced by Khaw Kai Qing as a personal portfolio edition.

[![Project checks](https://github.com/kaiqingkhaw/greensprout-cafe/actions/workflows/checks.yml/badge.svg)](https://github.com/kaiqingkhaw/greensprout-cafe/actions/workflows/checks.yml)

## Features

**Customers:** create an account, browse and filter dishes, add special requests, place demo orders, retrieve invoices and check order status.

**Administrators:** manage products, customers, orders and reviews; view date-filtered analytics; export records as CSV.

- Server-calculated prices, saved order items and duplicate-request protection.
- Role-checked sessions, password hashing, prepared statements and CSRF protection.
- Validated profile image uploads and editable account details.
- Keyboard-accessible controls, reduced-motion support and a remembered video pause preference.
- Local starter food photos, with an illustration if a custom image fails.

## Project background

This repository is the revised portfolio edition of GreenSprout Café. The original café ordering system was a group assignment. My later work focuses on the customer and administrator interface redesign, functional improvements, validation, regression testing and project organisation.

The current application demonstrates account management, server-validated checkout, saved invoices and administrator operations. It is a learning project with simulated payments; the original group work is not presented as a solo project.

## Screenshots

### Sign in

![Sign-in screen](docs/screenshots/login.png)

### Customer menu

![Menu and category filters](docs/screenshots/menu.png)

### Admin workspace

![Administrator dashboard](docs/screenshots/admin.png)

### About

![About GreenSprout](docs/screenshots/about.png)

[Mobile product management](docs/screenshots/admin-mobile.png)

## Run locally

Requires PHP 8.1+, MySQL or compatible MariaDB, and the `mysqli`, `mbstring` and `fileinfo` PHP extensions. Tested with XAMPP PHP 8.2.4. Node.js 22+ is needed only for the checks.

1. Copy this folder to `C:/xampp/htdocs/GreenSproutCafe-Portfolio`.
2. Start **Apache** and **MySQL** in XAMPP.
3. For a new installation, import `database/schema.sql` using phpMyAdmin.
4. For an existing database, back it up and run `C:/xampp/php/php.exe scripts/migrate.php`. Do not re-import the starter schema over existing data.
5. Open [the local site](http://localhost/GreenSproutCafe-Portfolio/). The entry point redirects to `public/`.
6. Use Sign up for a customer account. Create an admin from the project directory:

```powershell
C:/xampp/php/php.exe scripts/create_admin.php your_admin admin@example.com "YOUR_UNIQUE_PASSWORD"
```

Use at least 12 characters for the admin password. No account passwords are bundled.

Database settings use server environment variables: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASSWORD`. `.env.example` lists the keys; the app does not automatically load `.env` files. Defaults target local XAMPP.

## Project structure

```text
greensprout-cafe/
├── app/                Shared PHP configuration and order/media helpers
├── public/             Website pages, endpoints and browser assets
│   ├── assets/
│   │   ├── css/        Stylesheets
│   │   ├── js/         Customer and admin behaviour
│   │   ├── images/     Food photos, logo and placeholders
│   │   ├── videos/     Café videos
│   │   └── vendor/     Font Awesome and its license
│   └── uploads/        Local profile photos (ignored by Git)
├── database/           Starter database schema
├── scripts/            CLI database migration and admin creation
├── tests/              Automated regression checks
├── docs/               Screenshots, architecture and review notes
└── .github/workflows/  Automated checks on GitHub
```

See [architecture and request flow](docs/ARCHITECTURE.md) for the main components.

## Checks

Frontend checks need no package installation:

```sh
npm test
php tests/product-media.php
```

The full localhost suite creates and removes its own temporary records:

```powershell
$env:ALLOW_TEST_WRITES = "1"
npm run test:http
npm run test:structure
```

Latest results: **106 HTTP checks**, **25 desktop/mobile page checks**, **15 structure checks**, and a complete customer-to-admin order journey. See the [verification report](docs/reviews/QA-2026-09-18.md) for coverage and limits.

GitHub Actions runs frontend checks, PHP syntax checks and the bundled-photo test on pushes and pull requests.

## Demo scope

- Card and wallet payments are simulated. No real payment details or money are collected.
- Order tracking shows saved café status; it is not live GPS.
- Contact messages are stored in MySQL. Email delivery and password-reset email are not configured.
- Cart drafts are stored in the current browser tab. Orders and invoices are saved in the database.
- Profile uploads accept JPG/PNG/GIF up to 2 MB and 6000 pixels per side. Previous photos are retained when replaced.

For a live deployment, set the web server document root to `public/` so `app/`, database scripts and development files stay outside the web root. A live deployment needs PHP and MySQL; GitHub Pages alone cannot run this backend. Configure HTTPS, restricted database credentials and equivalent upload protection when using a server other than Apache. Keep local credentials, customer uploads and database dumps out of the repository.

## Media credits

Food photographs are from Unsplash; [file sources and license](public/assets/images/CREDITS.md) are included. Font Awesome Free 6.4.0 is bundled with its [upstream license](public/assets/vendor/fontawesome/LICENSE.txt). Original café videos and the logo are retained from the university project.
