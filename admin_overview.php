<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_admin_page();

$connection = db();
$readDate = static function (mixed $value, string $fallback): string {
    $date = is_string($value) && $value !== '' ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;
    return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
};
$from = $readDate($_GET['from'] ?? null, date('Y-m-01', strtotime('-5 months')));
$to = $readDate($_GET['to'] ?? null, date('Y-m-d'));
if ($from > $to) [$from, $to] = [$to, $from];
$fromSql = $from . ' 00:00:00';
$toSql = $to . ' 23:59:59';
$metrics = ['revenue' => 0.0, 'customers' => 0, 'orders' => 0, 'rating' => 0.0];
$metricStatement = $connection->prepare("SELECT COUNT(*) orders, COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total ELSE 0 END), 0) revenue FROM orders WHERE order_date BETWEEN ? AND ?");
$metricStatement->bind_param('ss', $fromSql, $toSql); $metricStatement->execute();
$orderMetrics = $metricStatement->get_result()->fetch_assoc();
$metrics['revenue'] = (float) $orderMetrics['revenue'];
$metrics['customers'] = (int) ($connection->query("SELECT COUNT(*) value FROM users WHERE role = 'user'")->fetch_assoc()['value'] ?? 0);
$metrics['orders'] = (int) $orderMetrics['orders'];
$metrics['rating'] = (float) ($connection->query('SELECT COALESCE(AVG(rating), 0) value FROM reviews')->fetch_assoc()['value'] ?? 0);

$months = [];
$chartStart = new DateTimeImmutable(substr($from, 0, 7) . '-01');
$chartEnd = new DateTimeImmutable(substr($to, 0, 7) . '-01');
if ($chartStart < $chartEnd->modify('-11 months')) $chartStart = $chartEnd->modify('-11 months');
for ($month = $chartStart; $month <= $chartEnd; $month = $month->modify('+1 month')) {
    $key = $month->format('Y-m');
    $months[$key] = ['label' => $month->format('M y'), 'revenue' => 0.0, 'orders' => 0];
}
$monthlyStatement = $connection->prepare("SELECT DATE_FORMAT(order_date, '%Y-%m') month_key, SUM(CASE WHEN status <> 'cancelled' THEN total ELSE 0 END) revenue, COUNT(*) orders FROM orders WHERE order_date BETWEEN ? AND ? GROUP BY month_key ORDER BY month_key");
$monthlyStatement->bind_param('ss', $fromSql, $toSql); $monthlyStatement->execute();
$monthlyResult = $monthlyStatement->get_result();
while ($row = $monthlyResult->fetch_assoc()) {
    if (isset($months[$row['month_key']])) {
        $months[$row['month_key']]['revenue'] = (float) $row['revenue'];
        $months[$row['month_key']]['orders'] = (int) $row['orders'];
    }
}

$statusCounts = ['pending' => 0, 'preparing' => 0, 'ready' => 0, 'completed' => 0, 'cancelled' => 0];
$statusResult = $connection->query('SELECT status, COUNT(*) total FROM orders GROUP BY status');
while ($row = $statusResult->fetch_assoc()) if (isset($statusCounts[$row['status']])) $statusCounts[$row['status']] = (int) $row['total'];

$topDishes = [];
$dishStatement = $connection->prepare("SELECT oi.product_name, SUM(oi.quantity) quantity, SUM(oi.quantity * oi.unit_price) sales FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.status <> 'cancelled' AND o.order_date BETWEEN ? AND ? GROUP BY oi.product_name ORDER BY quantity DESC, sales DESC LIMIT 5");
$dishStatement->bind_param('ss', $fromSql, $toSql); $dishStatement->execute();
$dishResult = $dishStatement->get_result(); while ($row = $dishResult->fetch_assoc()) $topDishes[] = $row;
$paymentLabels = ['cash'=>'Cash on delivery','card_demo'=>'Card demo','ewallet_demo'=>'E-wallet demo'];
$payments = [];
$paymentStatement = $connection->prepare("SELECT payment_method, COUNT(*) total FROM orders WHERE order_date BETWEEN ? AND ? GROUP BY payment_method ORDER BY total DESC");
$paymentStatement->bind_param('ss', $fromSql, $toSql); $paymentStatement->execute();
$paymentResult = $paymentStatement->get_result(); while ($row = $paymentResult->fetch_assoc()) $payments[] = $row;

function progress(float $value, float $target): int { return (int) min(100, round(($value / max(1, $target)) * 100)); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" href="assets/favicon.svg?v=2" type="image/svg+xml">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Business Insights - GreenSprout Admin</title>
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    header { position: sticky; top: 0; z-index: 20; display: flex; align-items: center; justify-content: space-between; padding: 15px 5%; }
    .logo { display: flex; align-items: center; font-size: 1.5rem; font-weight: 800; }
    .nav-links, .header-actions { display: flex; align-items: center; gap: 12px; }
    .nav-links a { padding: 8px 14px; border-radius: 999px; text-decoration: none; font-weight: 600; }
    .icon-button { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 50%; text-decoration: none; }
    main { width: min(1180px, 92%); margin: 34px auto 70px; }
    .metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin: 24px 0; }
    .metric { padding: 22px; }
    .metric-top { display: flex; justify-content: space-between; align-items: center; color: #607067; font-weight: 700; }
    .metric-top i { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 12px; background: #e5f2e8; color: #2f7d4a; }
    .metric-value { margin-top: 18px; color: #174c31; font-size: clamp(1.7rem, 3vw, 2.35rem); font-weight: 800; letter-spacing: -.04em; }
    .analytics-grid { display: grid; grid-template-columns: 1.55fr 1fr; gap: 24px; }
    .chart-card, .goals-card { padding: 26px; }
    .card-title { margin: 0 0 4px; color: #174c31; font-size: 1.3rem; }
    .card-copy { margin: 0 0 24px; color: #607067; }
    .chart-wrap { height: 320px; }
    .goal { margin-top: 23px; }
    .goal-row { display: flex; justify-content: space-between; gap: 16px; margin-bottom: 8px; color: #294b3a; font-weight: 700; }
    .progress { height: 10px; overflow: hidden; border-radius: 999px; background: #e4ebe5; }
    .progress span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #2f7d4a, #79b98a); }
    .empty-note { margin-top: 24px; padding: 16px; border-radius: 12px; background: #f5f8f5; color: #607067; font-size: .9rem; }
    .report-filter { display:flex; align-items:end; gap:12px; flex-wrap:wrap; padding:18px; }
    .report-filter label { display:grid; gap:6px; color:#52635a; font-size:.78rem; font-weight:700; }
    .report-filter input { min-height:42px; padding:8px 11px; border:1px solid #d7e2d4; border-radius:8px; }
    .report-filter button,.export-link { min-height:42px; padding:10px 15px; border:0; border-radius:8px; background:#315b43; color:#fff; font:700 .85rem inherit; text-decoration:none; cursor:pointer; }
    .report-range { margin-left:auto; color:#69766d; font-size:.8rem; }
    .insight-lists { display:grid; grid-template-columns:1.4fr 1fr; gap:24px; margin-top:24px; }
    .insight-list { padding:24px; }
    .rank-row,.payment-row { display:grid; grid-template-columns:34px minmax(0,1fr) auto; gap:12px; align-items:center; padding:13px 0; border-bottom:1px solid #e6ebe3; }
    .rank-row:last-child,.payment-row:last-child { border-bottom:0; }
    .rank { display:grid; width:28px; height:28px; place-items:center; border-radius:50%; background:#edf3e7; color:#315b43; font-size:.75rem; font-weight:800; }
    .rank-row strong,.payment-row strong { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#294536; }
    .rank-row small,.payment-row small { color:#6b796f; }
    @media(max-width:900px){.metrics{grid-template-columns:repeat(2,1fr)}.analytics-grid,.insight-lists{grid-template-columns:1fr}}
    @media(max-width:600px){.metrics{grid-template-columns:1fr}.report-filter>*{width:100%}.report-range{margin:0}}
  </style>
  <link rel="stylesheet" href="assets/css/admin-theme.css?v=enhance12">
  <script src="assets/js/api-client.js?v=audit6"></script>
  <link rel="stylesheet" href="assets/css/responsive.css?v=audit7">
  <link id="shared-header-style" rel="stylesheet" href="assets/css/header.css?v=audit7">
  <link rel="stylesheet" href="assets/css/workspace.css?v=portfolio7">
</head>
<body class="admin-workspace admin-insights-body">
  <header>
    <a class="logo" href="admin_home.php" aria-label="GreenSprout admin home"><span>GreenSprout</span></a>
    <nav class="nav-links" aria-label="Admin navigation"><a href="admin_home.php"><i class="fas fa-table-columns" aria-hidden="true"></i><span>Dashboard</span></a><a href="admin_overview.php" class="active" aria-current="page"><i class="fas fa-chart-line" aria-hidden="true"></i><span>Insights</span></a><a href="admin_manage_menu.php"><i class="fas fa-utensils" aria-hidden="true"></i><span>Menu</span></a><a href="admin_panel.php"><i class="fas fa-list-check" aria-hidden="true"></i><span>Operations</span></a></nav>
    <div class="header-actions"><a href="admin_profile.php" class="icon-button" aria-label="Admin account"><i class="fas fa-user-gear"></i></a><a href="logout.php" class="icon-button" aria-label="Logout"><i class="fas fa-sign-out-alt"></i></a></div>
  </header>
  <main>
    <section class="admin-page-banner"><span class="banner-eyebrow">Live database insights</span><h1>Business performance</h1><p>Understand café activity using real customer, order, revenue, and review data.</p></section>
    <form class="report-filter content-card" method="get">
      <label>From<input type="date" name="from" value="<?= htmlspecialchars($from) ?>" max="<?= htmlspecialchars($to) ?>"></label>
      <label>To<input type="date" name="to" value="<?= htmlspecialchars($to) ?>" min="<?= htmlspecialchars($from) ?>" max="<?= date('Y-m-d') ?>"></label>
      <button type="submit"><i class="fas fa-filter"></i> Apply dates</button>
      <a class="export-link" href="admin_export.php?type=orders&amp;from=<?= urlencode($from) ?>&amp;to=<?= urlencode($to) ?>"><i class="fas fa-download"></i> Export CSV</a>
      <span class="report-range">Showing <?= date('j M Y', strtotime($from)) ?> – <?= date('j M Y', strtotime($to)) ?></span>
    </form>
    <section class="metrics">
      <article class="metric content-card"><div class="metric-top"><span>Revenue in range</span><i class="fas fa-coins"></i></div><div class="metric-value">RM <?= number_format($metrics['revenue'], 2) ?></div></article>
      <article class="metric content-card"><div class="metric-top"><span>Customers</span><i class="fas fa-users"></i></div><div class="metric-value"><?= number_format($metrics['customers']) ?></div></article>
      <article class="metric content-card"><div class="metric-top"><span>Orders in range</span><i class="fas fa-receipt"></i></div><div class="metric-value"><?= number_format($metrics['orders']) ?></div></article>
      <article class="metric content-card"><div class="metric-top"><span>Average rating</span><i class="fas fa-star"></i></div><div class="metric-value"><?= $metrics['rating'] > 0 ? number_format($metrics['rating'], 1) . '/5' : '—' ?></div></article>
    </section>
    <section class="insight-lists">
      <article class="insight-list content-card"><h2 class="card-title">Best-selling dishes</h2><p class="card-copy">Ranked by quantity sold in the selected period.</p>
        <?php if (!$topDishes): ?><div class="empty-note">No sales data in this range.</div><?php else: foreach ($topDishes as $index => $dish): ?>
          <div class="rank-row"><span class="rank"><?= $index + 1 ?></span><strong><?= htmlspecialchars($dish['product_name']) ?></strong><small><?= (int) $dish['quantity'] ?> sold · RM <?= number_format((float) $dish['sales'], 2) ?></small></div>
        <?php endforeach; endif; ?>
      </article>
      <article class="insight-list content-card"><h2 class="card-title">Payment mix</h2><p class="card-copy">Methods selected during checkout.</p>
        <?php if (!$payments): ?><div class="empty-note">No payment data in this range.</div><?php else: foreach ($payments as $payment): ?>
          <div class="payment-row"><span class="rank"><i class="fas fa-wallet"></i></span><strong><?= htmlspecialchars($paymentLabels[$payment['payment_method']] ?? ucfirst($payment['payment_method'])) ?></strong><small><?= (int) $payment['total'] ?> order<?= (int) $payment['total'] === 1 ? '' : 's' ?></small></div>
        <?php endforeach; endif; ?>
      </article>
    </section>
    <section class="analytics-grid">
      <article class="chart-card content-card"><h2 class="card-title">Performance trend</h2><p class="card-copy">Up to the latest 12 months within the selected range.</p><div class="chart-wrap"><canvas id="performanceChart" aria-label="Monthly revenue and order chart"></canvas></div></article>
      <article class="goals-card content-card"><h2 class="card-title">Operational targets</h2><p class="card-copy">Progress updates automatically as activity grows.</p>
        <div class="goal"><div class="goal-row"><span>Revenue target</span><span><?= progress($metrics['revenue'], 50000) ?>%</span></div><div class="progress"><span style="width:<?= progress($metrics['revenue'], 50000) ?>%"></span></div></div>
        <div class="goal"><div class="goal-row"><span>300 orders</span><span><?= progress($metrics['orders'], 300) ?>%</span></div><div class="progress"><span style="width:<?= progress($metrics['orders'], 300) ?>%"></span></div></div>
        <div class="goal"><div class="goal-row"><span>4.5 customer rating</span><span><?= progress($metrics['rating'], 4.5) ?>%</span></div><div class="progress"><span style="width:<?= progress($metrics['rating'], 4.5) ?>%"></span></div></div>
        <?php if ($metrics['orders'] === 0): ?><div class="empty-note"><i class="fas fa-circle-info"></i> Analytics will populate after customers begin placing orders.</div><?php endif; ?>
      </article>
    </section>
  </main>
  <script>
    const chartElement = document.getElementById('performanceChart');
    const chartData = {labels: <?= json_encode(array_column($months, 'label')) ?>, revenue: <?= json_encode(array_column($months, 'revenue')) ?>, orders: <?= json_encode(array_column($months, 'orders')) ?>};
    if (typeof Chart !== 'function') {
      const fallback = document.createElement('div'); fallback.className = 'empty-note'; fallback.setAttribute('role','status');
      fallback.textContent = chartData.labels.map((label,index) => `${label}: RM${Number(chartData.revenue[index]).toFixed(2)}, ${chartData.orders[index]} orders`).join(' · ') || 'No chart data in this period.';
      chartElement.replaceWith(fallback);
    } else new Chart(chartElement, {
      type: 'bar',
      data: { labels: chartData.labels, datasets: [
        { label: 'Revenue (RM)', data: chartData.revenue, backgroundColor: 'rgba(49,91,67,.78)', borderRadius: 7, yAxisID: 'y' },
        { label: 'Orders', data: chartData.orders, type: 'line', borderColor: '#789768', backgroundColor: '#789768', tension: .35, yAxisID: 'y1' }
      ]},
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(15,47,85,.08)' } }, y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { precision: 0 } } } }
    });
  </script>
</body>
</html>
