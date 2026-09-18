# Profile, payment and admin follow-up

Implemented photo removal on Save Changes (old files are retained, not erased), card-demo/e-wallet-demo selections persisted to orders and invoices, payment labels in admin order details, and clearer admin validation/database-conflict responses. Admin customer deletion is blocked for customers with order history; order deletion now uses a transaction.

Fresh localhost regression checks passed: GIF upload, photo removal and reload; cash/card-demo/e-wallet-demo saved methods; repeated checkout deduplication; changed payment method conflict; unsupported payment rejection; five admin landing/list pages; missing order, invalid status and unknown-action errors. Temporary QA accounts, their orders and uploaded test image were removed after testing. Existing customer records were not changed.

All root PHP files passed syntax checking; JavaScript parsing and local asset-reference checks passed. These are targeted checks, not an exhaustive admin/UI/browser audit. No real payment gateway is integrated and no charge or payment credential collection occurs.
