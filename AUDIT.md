# Audit and verification — 4 September 2026

## Findings and implementation

Security-sensitive: customer mutations lacked CSRF; profile replacement deleted old files before database success; admin user editing interpolated stored values into executable HTML; malformed inputs could expose raw errors.

Implemented: shared CSRF request handling, authenticated role rechecks, safer DOM editing, strict profile validation, bounded generated upload paths, non-executable upload configuration, generic errors and basic request limits.

High priority: checkout ignored order_items, discarded delivery/receipt data, had no retry deduplication, and the contact form simulated success.

Implemented: transactional relational line items, additive repeatable migration, persisted delivery/totals/requests, a unique per-user checkout key, restored invoices, order details for administrators and real contact persistence. Prices are server-authoritative. Historical JSON is retained as a compatibility snapshot.

Maintenance/UI: removed the team section, legacy admin HTML pages, fake newsletter/payment/favourite controls, old About chatbot logic and duplicate menu/review functions. Extracted customer CSS/menu JavaScript and removed two duplicate product-editor CSS copies. Preserved the existing green customer/blue admin identity. Restored mobile navigation, added modal keyboard handling and improved authentication error/field layout.

## Checks actually run

- PHP lint on every root PHP file; migration executed successfully more than once.
- JavaScript syntax parsing of all root JS files and inline HTML scripts.
- Live HTTP: valid/invalid signup, duplicates, password login, CSRF rejection, customer/admin role checks and logout.
- Live HTTP: delivery validation, server price enforcement, identical checkout retry returning the same order, receipt/line-item retrieval and another customer's access denial.
- Live HTTP: all six admin pages, product create/edit/delete, invalid product price and invalid status.
- Live HTTP upload: rejected a PHP file, accepted a tiny GIF, saved an apostrophe-containing name, rejected duplicate email while retaining the active image.
- Browser: customer login, product details, cart retained after refresh, delivery-to-demo-order checkout, saved invoice after navigation/reload and invoice-to-tracking.
- Browser: menu search; screenshots of the customer menu, About page and signup at desktop/narrow widths. These are sampled responsive checks, not exhaustive device coverage.

Temporary admin/product fixtures were removed. Temporary orders/contact/signup fixtures were cleaned up, and the existing QA profile fields were restored. No real payment was processed. Original customer orders were preserved.

## Limits

This is not a guarantee of zero bugs or a penetration-test certificate. Admin password-change submissions, every admin action, load/concurrency behaviour, all browser engines and complete assistive-technology behaviour were not exhaustively browser-tested.

No payment gateway, GPS, email verification/reset delivery or staff email dispatch is configured. Contact records are stored in MySQL. Legacy order delivery details cannot be reconstructed if never saved. Previous uploaded images are retained deliberately; production requires a controlled retention policy, HTTPS, restricted database credentials, backup/monitoring and stronger edge abuse controls.

The current project still contains some page-specific inline styling/handlers. The largest duplicated blocks were removed; a complete frontend rewrite was intentionally avoided.
# Guest checkout follow-up

## Customer header consistency

- Standardised customer header markup across Home, Menu, About and Profile: equal-width navigation slots, no repeated greeting, consistent account/cart/sign-out controls and active-page semantics. Headers now load shared styles statically before rendering; admin retains its separate navigation.
- Shared cart icon now opens menu.html?view=cart, which selects the cart pane. Reduced oversized customer hero typography while retaining imagery/colour identity.
- Browser verified signed-in controls on all four pages and Menu-link centre at 712.4px versus 712.5px screen centre at 1440px viewport. Verified profile cart-icon navigation opens visible cart pane. Checked 390px phone homepage without horizontal overflow. Asset references (43), JS syntax and profile PHP lint passed.

## Shared headers and asset organisation

- Moved root CSS and browser JS into assets/css and assets/js, updated page references and dynamic stylesheet loading. Kept public page/API/media URLs unchanged. README and syntax checker updated; check-assets.cjs validates local references.
- Centralised header layout in assets/css/header.css, supporting both direct and wrapped admin headers. Equal outer grid columns centre navigation independently of account text. Kept theme colours; removed redundant mobile hamburger where navigation is already visible.
- Browser measured centred navigation on Home, Menu, About, Profile and all six admin pages at desktop width. Inspected admin Operations at 390px; no page-level horizontal overflow. Temporary browser admin removed.
- All 35 local style/script references resolve; all organised assets return HTTP 200; JS syntax checks and admin route/product CRUD regression passed.
- Remaining legacy page-specific inline CSS and root media files are intentional follow-up candidates, not claimed fully refactored. No commits or pushes.

## Customer regression run — fresh results

- Passed local HTTP tests: invalid/valid/duplicate signup; invalid/valid login; CSRF and role checks; invalid quantities/delivery; saved relational invoice and server pricing; identical retry deduplication and changed retry conflict; preparing/ready/completed status reads; completed invoice reload; cross-user order denial; review and contact submission; executable upload rejection; valid profile image/name update; duplicate profile email; logout/private API denial.
- Tracking statuses were set only on the temporary test account by a CLI fixture; this tests customer status reads, not the admin status-update UI.
- Browser passed: smoothie search, product details, add to cart, quantity increment, discount application, removal confirmation and recalculated totals. Mobile authenticated profile measured no page overflow at 390px; found/fixed unreadable visited navigation links and excess top spacing.
- Temporary customer and its orders/review/contact were removed. Windows denied deletion of one unreferenced 1px GIF: uploads/profile_13_638df1e954d6a49b3bc0042258e8c9cd.gif. It remains excluded from Git.
- Coverage is not exhaustive: no fresh complete browser payment submission, real payment/GPS/email integration, or all-device certification. Tests do not guarantee absence of other bugs.

## Responsive layout follow-up

- Added shared responsive.css: readable sign-in button, larger menu tabs, explicit cart container width, aligned checkout-step labels, wrapping discount input and narrow cart item layout. Removed mobile back-to-top overlay that covered totals.
- Tablet filters move above products instead of squeezing them alongside a wide sidebar. Added narrow invoice/profile handling and keyboard-focus help-launcher suppression.
- Browser checked phone menu/cart at 390px and tablet menu at 768px; measured cart overflow before fixing (452px) and after (375px content in a 390px viewport). JavaScript syntax checks passed.
- This follow-up is not a complete visual certification of authenticated profile, admin, payment or every browser/device.

## Contact feedback regression pass

- Replaced misleading green error block with field-specific, associated validation messages. Separate compact success/error feedback preserves entries on failures. Server validates optional phone and returns field errors; message minimum is explicit.
- Fixed contact form's cramped two-column mobile layout; desktop retains two columns.
- Reran PHP lint and JS parsing; all passed. HTTP checks passed for public pages, products/reviews, guest profile/admin denial, private order APIs and invalid contact fields (422, no saved submission).
- Reran admin six-page checks plus invalid price, temporary product creation/edit/deletion and invalid status. Temporary admin removed. Browser tested empty contact validation and inspected narrow layout.
- This pass did not rerun full checkout, upload replacement, every admin operation, or every device/browser. Earlier results above remain historical, not fresh tests.

## Café help refinement

- Replaced the emoji-style launcher with a restrained icon and `Café help` label.
- Kept the assistant transparent: it is an automated, local FAQ and not a live employee or generative-AI service.
- Added distinct replies for ordering, tracking, delivery fees, payment, dietary needs and staff/contact requests, with relevant internal links and cautious claims.
- Added a persistent contact-form route and removed the simulated reply delay.
- Browser checked the opened widget at narrow width and verified the order-tracking response/link. JavaScript syntax checks passed.

## Authentication presentation and customer feedback

- Guest browsing now uses a secondary outlined button below each authentication form.
- Login/signup validation displays accessible form messages; checkout, review and logout errors use a dismissible on-page notification rather than native alert dialogs. Admin destructive confirmations remain unchanged.
- Browser verified empty login, incorrect credentials, checkout validation banner and dismissal; visually checked login at narrow viewport. JavaScript syntax checks passed. No new order created.
- Versioned changed frontend assets to avoid stale browser CSS/scripts.

- Added explicit guest browsing links to login and signup; placing orders still requires authentication.
- Fixed authentication clearing the guest cart. Checkout now checks the server session before delivery and resumes after customer login. Signup preserves the checkout destination.
- Return navigation uses fixed local destinations, not an arbitrary redirect URL.
- Browser verified: logout, guest browsing, add one dish, checkout redirects to login, customer login returns to delivery with Cart 1. No order submitted in this follow-up.
- JavaScript syntax checks passed. This follow-up does not constitute an exhaustive test of every website feature.
