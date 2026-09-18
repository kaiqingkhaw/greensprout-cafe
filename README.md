# GreenSprout Café

A café ordering website built with PHP, MySQL and JavaScript. Originally a group university project, later redesigned and extended by Khaw Kai Qing.

[![Project checks](https://github.com/kaiqingkhaw/greensprout-cafe/actions/workflows/checks.yml/badge.svg)](https://github.com/kaiqingkhaw/greensprout-cafe/actions/workflows/checks.yml)

## Features

- Customer registration, login and profile editing
- Menu search, category filters, cart and special requests
- Demo checkout, saved invoices and order status
- Admin product, customer, order and review management
- Sales summaries and CSV exports

## Source code

| Folder | Contents |
| --- | --- |
| [public/](public/) | HTML pages and PHP request handlers |
| [public/assets/css/](public/assets/css/) | Stylesheets |
| [public/assets/js/](public/assets/js/) | Frontend JavaScript |
| [app/](app/) | Database connection, session helpers and shared PHP code |
| [database/](database/) | MySQL schema and starter menu |
| [scripts/](scripts/) | Admin account creation and database migration |
| [tests/](tests/) | Automated tests |

Start with [the menu page](public/menu.html), [checkout handler](public/create_order.php) or [admin dashboard](public/admin_home.php). [Architecture notes](docs/ARCHITECTURE.md) explain how they fit together.

## Screenshots

![Customer menu](docs/screenshots/menu.png)

![Admin dashboard](docs/screenshots/admin.png)

[Login](docs/screenshots/login.png) · [About](docs/screenshots/about.png) · [Mobile admin](docs/screenshots/admin-mobile.png)

## Run locally

Requires PHP 8.1+, MySQL/MariaDB, and the PHP `mysqli`, `mbstring` and `fileinfo` extensions. Tested using XAMPP with PHP 8.2.4.

1. Copy the project into `C:/xampp/htdocs/GreenSproutCafe-Portfolio`.
2. Start Apache and MySQL in XAMPP.
3. For a new installation, import `database/schema.sql` through phpMyAdmin.
4. Open [localhost/GreenSproutCafe-Portfolio](http://localhost/GreenSproutCafe-Portfolio/).
5. Register a customer through the Sign up page.

To create an administrator, run this from the project folder:

```powershell
C:/xampp/php/php.exe scripts/create_admin.php your_admin admin@example.com "YOUR_UNIQUE_PASSWORD"
```

Use a password of at least 12 characters. Account passwords are not included in this repository.

Database defaults work with a standard local XAMPP setup. For other environments, configure the server variables listed in [.env.example](.env.example). The app does not automatically read `.env` files.

For an existing installation, back up the database and run `C:/xampp/php/php.exe scripts/migrate.php` instead of importing the starter schema again. For deployment, point the web server at `public/`. PHP and MySQL are required; GitHub Pages cannot run the backend.

## Tests

With Node.js 22+ and PHP available:

```sh
npm test
php tests/product-media.php
```

With the local Apache/MySQL installation running:

```powershell
$env:ALLOW_TEST_WRITES = "1"
npm run test:http
npm run test:structure
```

The HTTP suite creates temporary test records and removes them afterward. GitHub Actions runs frontend tests and PHP checks on every push.

## Limitations

Payments are simulated, tracking shows saved order status rather than GPS, and contact messages are stored without sending email. Password-reset email is not implemented. Profile photos and database credentials stay outside Git.

## Credits

The original application was developed as a group assignment. My later revision covers the interface redesign, feature improvements, validation, tests and repository organisation.

Food photos: [Unsplash sources](public/assets/images/CREDITS.md). Icons: [Font Awesome Free license](public/assets/vendor/fontawesome/LICENSE.txt). Café videos and the logo are from the original university project.
