<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_admin_page();

$conn = db();
$adminId = (int) ($_SESSION['user_id'] ?? 0);
$adminStmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
$adminStmt->bind_param('i', $adminId);
$adminStmt->execute();
$adminName = $adminStmt->get_result()->fetch_assoc()['name'] ?? 'Administrator';
$summary = $conn->query("SELECT
  (SELECT COUNT(*) FROM products) AS products,
  (SELECT COUNT(*) FROM users WHERE role = 'user') AS customers,
  (SELECT COUNT(*) FROM orders WHERE status IN ('pending','preparing','ready')) AS active_orders,
  (SELECT COUNT(*) FROM reviews) AS reviews")->fetch_assoc();
$recentOrders = $conn->query("SELECT o.order_number, o.total, o.status, o.order_date, u.name
  FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.order_date DESC LIMIT 4");
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Singapore'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" href="assets/favicon.svg?v=2" type="image/svg+xml">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="GreenSprout Café administration dashboard">
  <title>Dashboard - GreenSprout Admin</title>
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/admin-theme.css?v=enhance11">
  <script src="assets/js/api-client.js?v=audit6"></script>
  <link rel="stylesheet" href="assets/css/responsive.css?v=audit7">
  <link id="shared-header-style" rel="stylesheet" href="assets/css/header.css?v=audit7">
  <link rel="stylesheet" href="assets/css/workspace.css?v=portfolio7">
</head>
<body class="admin-workspace admin-dashboard-body">
  <header><div class="header-inner">
    <a class="logo" href="admin_home.php" aria-label="GreenSprout admin home"><span>GreenSprout</span></a>
    <nav class="nav-links" aria-label="Admin navigation"><a href="admin_home.php" class="active" aria-current="page"><i class="fas fa-table-columns" aria-hidden="true"></i><span>Dashboard</span></a><a href="admin_overview.php"><i class="fas fa-chart-line" aria-hidden="true"></i><span>Insights</span></a><a href="admin_manage_menu.php"><i class="fas fa-utensils" aria-hidden="true"></i><span>Menu</span></a><a href="admin_panel.php"><i class="fas fa-list-check" aria-hidden="true"></i><span>Operations</span></a></nav>
    <div class="header-actions"><a href="admin_profile.php" class="icon-button" title="Admin account" aria-label="Admin account"><i class="fas fa-user-gear"></i></a><a href="logout.php" class="icon-button" title="Log out" aria-label="Log out"><i class="fas fa-right-from-bracket"></i></a></div>
  </div></header>

  <main class="dashboard-shell">
    <section class="dashboard-intro">
      <div><span class="dashboard-eyebrow"><?= htmlspecialchars($today->format('l, j F Y')) ?></span><h1>Good day, <?= htmlspecialchars($adminName) ?></h1><p>Here is what is happening at GreenSprout Café.</p></div>
      <a class="dashboard-primary-action" href="admin_edit_product.php?mode=add"><i class="fas fa-plus"></i> Add menu item</a>
    </section>

    <section class="dashboard-kpis" aria-label="Business summary">
      <article class="dashboard-kpi"><span class="kpi-icon blue"><i class="fas fa-utensils"></i></span><div><span>Menu items</span><strong><?= (int) $summary['products'] ?></strong></div></article>
      <article class="dashboard-kpi"><span class="kpi-icon violet"><i class="fas fa-users"></i></span><div><span>Customers</span><strong><?= (int) $summary['customers'] ?></strong></div></article>
      <article class="dashboard-kpi"><span class="kpi-icon amber"><i class="fas fa-bag-shopping"></i></span><div><span>Active orders</span><strong><?= (int) $summary['active_orders'] ?></strong></div></article>
      <article class="dashboard-kpi"><span class="kpi-icon teal"><i class="fas fa-star"></i></span><div><span>Reviews</span><strong><?= (int) $summary['reviews'] ?></strong></div></article>
    </section>

    <div class="dashboard-grid">
      <section class="dashboard-surface"><div class="surface-heading"><div><span class="dashboard-eyebrow">Workspace</span><h2>Quick actions</h2></div></div>
        <div class="quick-action-list">
          <a href="admin_overview.php" class="quick-action"><span class="quick-icon blue"><i class="fas fa-chart-line"></i></span><span><strong>Business insights</strong><small>Revenue, orders and customer ratings</small></span><i class="fas fa-arrow-right"></i></a>
          <a href="admin_manage_menu.php" class="quick-action"><span class="quick-icon teal"><i class="fas fa-bowl-food"></i></span><span><strong>Manage menu</strong><small>Products, pricing and nutrition details</small></span><i class="fas fa-arrow-right"></i></a>
          <a href="admin_panel.php" class="quick-action"><span class="quick-icon violet"><i class="fas fa-list-check"></i></span><span><strong>Daily operations</strong><small>Orders, customers and reviews</small></span><i class="fas fa-arrow-right"></i></a>
        </div>
      </section>

      <section class="dashboard-surface recent-orders"><div class="surface-heading"><div><span class="dashboard-eyebrow">Live data</span><h2>Recent orders</h2></div><a href="admin_panel.php">View all</a></div>
        <?php if ($recentOrders->num_rows === 0): ?>
          <div class="professional-empty-state"><span><i class="fas fa-receipt"></i></span><h3>No orders yet</h3><p>New customer orders will appear here automatically.</p></div>
        <?php else: ?><div class="recent-order-list"><?php while ($order = $recentOrders->fetch_assoc()): ?>
          <div class="recent-order-row"><span class="order-avatar"><i class="fas fa-receipt"></i></span><div><strong><?= htmlspecialchars($order['order_number']) ?></strong><small><?= htmlspecialchars($order['name']) ?> · <?= htmlspecialchars(date('j M, H:i', strtotime($order['order_date']))) ?></small></div><div class="order-meta"><strong>RM <?= number_format((float) $order['total'], 2) ?></strong><span class="order-status status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars(ucfirst($order['status'])) ?></span></div></div>
        <?php endwhile; ?></div><?php endif; ?>
      </section>
    </div>
  </main>
  <footer><div class="copyright"><p>&copy; 2026 GreenSprout Organic Café · Secure admin workspace</p><p class="portfolio-disclosure">Portfolio demonstration · No real payments or customer notifications.</p></div></footer>
</body>
</html>
