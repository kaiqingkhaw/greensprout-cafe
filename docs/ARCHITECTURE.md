# Architecture

GreenSprout is a small PHP application with MySQL storage and vanilla JavaScript. It keeps the original page-based design of the university project.

## Boundaries

- **public/** contains the browser entry points. Customer pages include `MainMenu.html`, `menu.html`, authentication and profile pages. The `admin_*.php` pages require an administrator session.
- **app/** contains PHP code loaded by the entry points: environment-backed database configuration, session and CSRF helpers, order payloads and product photo mapping. It is not a browser destination.
- **database/** and **scripts/** own fresh installation and upgrades. Database changes are not performed by ordinary page requests.
- **tests/** contains the executable checks. Historical review notes live in `docs/reviews/`.

## Order flow

1. The menu loads products from `public/get_products.php`.
2. A customer signs in, chooses products and enters delivery details.
3. `public/create_order.php` validates the session, CSRF token and submitted fields. It reads product prices from MySQL and calculates the final amount on the server.
4. The order and its line items are saved together. A unique checkout key protects repeated submissions.
5. Invoice and tracking endpoints only return orders belonging to the signed-in customer. Administrators update saved order status from the operations page.

## Serving the application

For XAMPP, copy the entire repository into `htdocs/GreenSproutCafe-Portfolio` and visit its root URL. The root `index.php` redirects to `public/`. Apache redirects preserve the original root-level page bookmarks. For a dedicated host, serve `public/` directly; the application loads `app/` from its parent directory.

The `app`, `database`, `scripts`, `tests`, `docs` and `.github` folders also deny direct Apache requests when the repository sits beneath XAMPP's document root. Profile uploads retain their own executable-file restrictions. PHP setup scripts additionally require CLI execution.

## Deliberate limits

Payments, delivery illustrations and contact submissions are demonstrations. They do not process money, provide GPS tracking or send email. This project does not use a frontend build system or a PHP framework; adding folders does not change those architectural choices.
