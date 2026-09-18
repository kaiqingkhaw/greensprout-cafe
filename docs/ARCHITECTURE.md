# How the application works

## Pages and shared code

`public/` holds the HTML pages, PHP request handlers and browser assets. The `admin_*.php` pages check the signed-in user's role before displaying data or accepting changes.

`app/config.php` contains the database connection, session handling and shared validation functions. The other files in `app/` format saved orders and map starter products to local photos.

## Checkout

1. `public/get_products.php` supplies the menu.
2. JavaScript keeps the cart in the current browser tab.
3. `public/create_order.php` checks the login session and CSRF token, validates delivery details, and reads current prices from MySQL.
4. It saves the order and line items in one transaction. A unique checkout key prevents the same submission from creating a second order.
5. Customers retrieve their own invoices and status through the order endpoints. Administrators update status through `public/admin_panel.php`.

## Local setup and hosting

The root `index.php` redirects XAMPP users to `public/`. Apache redirect rules preserve the original page URLs. On a dedicated server, use `public/` as the document root.

The remaining folders hold database setup, command-line tools, tests and documentation. Their Apache rules block direct browser access when the whole project is inside `htdocs`. `public/uploads/` has separate rules to prevent uploaded files from executing as PHP.
