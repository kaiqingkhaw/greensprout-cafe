<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/order-data.php';
require_admin_page();
$conn = db();

// Dashboard statistics
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$totalOrders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$totalReviews = $conn->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    verify_csrf($_POST['csrf_token'] ?? null);
    
    try {
    switch ($_POST['action']) {
        case 'delete_user':
            $user_id = (int)$_POST['user_id'];
            $history = $conn->prepare('SELECT id FROM orders WHERE user_id = ? LIMIT 1');
            $history->bind_param('i', $user_id); $history->execute();
            if ($history->get_result()->fetch_assoc()) json_response(['error'=>'This customer has order history and cannot be deleted.'], 409);
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            if (!$stmt->affected_rows) json_response(['error'=>'Customer not found. Refresh the page.'], 404);
            echo json_encode(['success' => true]);
            exit;
            
        case 'update_order_status':
            $order_id = (int)$_POST['order_id'];
            $status = $_POST['status'];
            if (!in_array($status, ['pending', 'preparing', 'ready', 'completed', 'cancelled'], true)) {
                json_response(['error' => 'Invalid order status.'], 422);
            }
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $order_id);
            $check = $conn->prepare('SELECT id FROM orders WHERE id = ?');
            $check->bind_param('i', $order_id); $check->execute();
            if (!$check->get_result()->fetch_assoc()) json_response(['error'=>'Order not found. Refresh the page.'], 404);
            echo json_encode(['success' => $stmt->execute()]);
            exit;
            
        case 'delete_order':
            $order_id = (int)$_POST['order_id'];
            $conn->begin_transaction();
            // Delete order items first (foreign key constraint)
            $stmt1 = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt1->bind_param("i", $order_id);
            $stmt1->execute();
            
            // Delete order
            $stmt2 = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $stmt2->bind_param("i", $order_id);
            $stmt2->execute();
            $conn->commit();
            echo json_encode(['success' => $stmt2->affected_rows > 0]);
            exit;
            
        case 'delete_review':
            $review_id = (int)$_POST['review_id'];
            $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->bind_param("i", $review_id);
            $stmt->execute();
            echo json_encode(['success' => $stmt->affected_rows > 0]);
            exit;
            
        case 'update_user':
            foreach (['name'=>100,'email'=>190,'phone'=>30,'username'=>50] as $field=>$limit) $_POST[$field] = clean_text($_POST[$field] ?? '', $limit);
            if ($_POST['name'] === '' || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) || !valid_phone($_POST['phone']) || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $_POST['username'])) json_response(['error'=>'Enter a valid name, email, phone and username.'],422);
            $user_id = (int)$_POST['user_id'];
            $name = $_POST['name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $username = $_POST['username'];
            $check = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'user'");
            $check->bind_param('i', $user_id); $check->execute();
            if (!$check->get_result()->fetch_assoc()) json_response(['error'=>'Customer not found. Refresh the page.'], 404);
            
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, username = ? WHERE id = ? AND role = 'user'");
            $stmt->bind_param("ssssi", $name, $email, $phone, $username, $user_id);
            echo json_encode(['success' => $stmt->execute()]);
            exit;
        default:
            json_response(['error'=>'Unknown admin action.'], 422);
    }
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log($exception->getMessage());
        json_response(['error'=>($exception instanceof mysqli_sql_exception && $exception->getCode() === 1062) ? 'That username or email is already in use.' : 'Unable to save this change. Refresh and try again.'], 409);
    }
}

// Fetch data
$users = $conn->query("SELECT id, name, email, phone, username, member_since FROM users WHERE role = 'user'");
$orders = $conn->query("SELECT orders.*, users.name FROM orders JOIN users ON orders.user_id = users.id ORDER BY order_date DESC");
$reviews = $conn->query("SELECT r.id, u.name, r.rating, r.review, r.created_at FROM reviews r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" href="assets/favicon.svg?v=2" type="image/svg+xml">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Green Sprout Café</title>
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <style>
        /* ========== HEADER & FOOTER STYLES ========== */
        :root {
            --primary: #1976d2;
            --primary-light: #2196f3;
            --primary-lighter: #64b5f6;
            --secondary: #e3f2fd;
            --accent: #ff9800;
            --dark: #0d47a1;
            --light: #ffffff;
            --text: #333333;
            --text-light: #666666;
            --success: #4CAF50;
            --warning: #FFC107;
            --danger: #F44336;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--dark), var(--primary), var(--primary-light));
            padding: 15px 5%;
            color: var(--light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 10px;
            color: var(--light);
        }

        .nav-links {
            display: flex;
            gap: 25px;
        }

        .nav-links a {
            color: var(--light);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-links a.active {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Consistent button style for all icons */
        .icon-button {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--light);
            text-decoration: none;
            position: relative;
            border: none;
        }

        .icon-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .header-icons {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        /* Footer Styles */
        footer {
            background: linear-gradient(135deg, var(--dark), var(--primary));
            color: var(--light);
            padding: 40px 5% 20px;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            text-align: left;
            margin-bottom: 30px;
        }

        .footer-column h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-lighter);
            border-radius: 2px;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--light);
            transform: translateX(5px);
        }

        .newsletter p {
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.8);
        }

        .newsletter-form {
            display: flex;
            gap: 10px;
        }

        .newsletter-form input {
            flex: 1;
            padding: 12px 15px;
            border: none;
            border-radius: 5px;
            outline: none;
        }

        .newsletter-form button {
            background: var(--primary-lighter);
            color: var(--light);
            border: none;
            border-radius: 5px;
            padding: 0 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .newsletter-form button:hover {
            background: var(--accent);
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links .icon-button {
            font-size: 18px;
        }

        .copyright {
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        /* ========== ADMIN PANEL STYLES ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
        }

        /* Header Styles */
        .admin-header {
            background: #ffffff;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid #2196F3;
        }

        .admin-header h1 {
            color: #0d47a1;
            font-size: 2.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #2196F3;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .admin-details h3 {
            font-size: 1.1rem;
            color: #333;
        }

        .admin-details p {
            font-size: 0.9rem;
            color: #9e9e9e;
        }

        /* Hero Section */
        .hero {
            position: relative;
            height: 400px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }

        .hero-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 15px;
        }

        .hero-content h1 {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .hero-content p {
            font-size: 1.2rem;
        }

        /* Dashboard Styles */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: #2196F3;
        }

        .dashboard-card.users::before { background: #2196F3; }
        .dashboard-card.orders::before { background: #03A9F4; }
        .dashboard-card.reviews::before { background: #00BCD4; }

        .dashboard-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #2196F3;
        }

        .dashboard-card.users i { color: #2196F3; }
        .dashboard-card.orders i { color: #03A9F4; }
        .dashboard-card.reviews i { color: #00BCD4; }

        .dashboard-card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #333;
        }

        .dashboard-card p {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2196F3;
        }

        .dashboard-card.users p { color: #2196F3; }
        .dashboard-card.orders p { color: #03A9F4; }
        .dashboard-card.reviews p { color: #00BCD4; }

        /* Section Styles */
        .section {
            background: #ffffff;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .section-header h2 {
            color: #0d47a1;
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-header h2 i {
            color: #2196F3;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }

        th {
            background: #2196F3;
            color: white;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 15px;
        }

        td {
            padding: 14px 20px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        /* Button Styles */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit {
            background: #2196F3;
            color: white;
        }

        .btn-edit:hover {
            background: #0b7dda;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #f44336;
            color: white;
        }

        .btn-delete:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }

        .btn-save {
            background: #4CAF50;
            color: white;
        }

        .btn-save:hover {
            background: #3e8e41;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #9e9e9e;
            color: white;
        }

        .btn-cancel:hover {
            background: #757575;
            transform: translateY(-2px);
        }

        /* Form Elements */
        .status-select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            font-size: 12px;
            color: #333;
        }

        .edit-input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }

        /* Message Styles */
        .message {
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-weight: 600;
            display: none;
        }

        .success {
            background: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .error {
            background: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #d1ecf1; color: #0c5460; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-completed { background: #e2e3e5; color: #383d41; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        /* Rating Stars */
        .rating-stars {
            color: #FFC107;
            font-size: 16px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .dashboard {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .admin-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .admin-info {
                justify-content: center;
            }
            
            .admin-header h1 {
                font-size: 1.8rem;
            }
            
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .section {
                padding: 20px;
            }
            
            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            table {
                font-size: 13px;
            }
            
            th, td {
                padding: 12px 15px;
            }
            
            .hero-content h1 {
                font-size: 2rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section {
            animation: fadeIn 0.5s ease-out;
        }

        .dashboard-card {
            animation: fadeIn 0.6s ease-out;
        }

        .header-inner {
  position: relative;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.nav-links {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 25px;
}

    </style>
    <link rel="stylesheet" href="assets/css/admin-theme.css?v=enhance11">
  <script src="assets/js/api-client.js?v=audit6"></script>
  <link rel="stylesheet" href="assets/css/responsive.css?v=audit7">
  <link id="shared-header-style" rel="stylesheet" href="assets/css/header.css?v=audit7">
  <link rel="stylesheet" href="assets/css/workspace.css?v=portfolio7">
</head>
<body class="admin-workspace admin-operations">
    <!-- Header -->
   <header>
  <div class="header-inner">
    <a class="logo" href="admin_home.php" aria-label="GreenSprout admin home" style="color:inherit;text-decoration:none;"><span>GreenSprout</span></a>
    <div class="nav-links">
      <a href="admin_home.php"><i class="fas fa-table-columns" aria-hidden="true"></i><span>Dashboard</span></a>
      <a href="admin_overview.php"><i class="fas fa-chart-line" aria-hidden="true"></i><span>Insights</span></a>
      <a href="admin_manage_menu.php"><i class="fas fa-utensils" aria-hidden="true"></i><span>Menu</span></a>
      <a href="admin_panel.php" class="active" aria-current="page"><i class="fas fa-list-check" aria-hidden="true"></i><span>Operations</span></a>
    </div>
    <div class="header-actions">
      <a href="admin_profile.php" class="icon-button" title="Admin account" aria-label="Admin account"><i class="fas fa-user-gear"></i></a>
      <a href="logout.php" class="icon-button" title="Logout" aria-label="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </div>
</header>

    <section class="admin-page-banner">
      <span class="banner-eyebrow">Operations</span>
      <h1>Daily operations</h1>
      <p>Manage customers, review incoming orders, and moderate feedback from one workspace.</p>
    </section>
    
    <div class="container">
        <!-- Dashboard Section -->
        <div class="dashboard">
            <div class="dashboard-card users">
                <i class="fas fa-users"></i>
                <h3>Total Users</h3>
                <p><?= $totalUsers ?></p>
            </div>
            
            <div class="dashboard-card orders">
                <i class="fas fa-shopping-cart"></i>
                <h3>Total Orders</h3>
                <p><?= $totalOrders ?></p>
            </div>
            
            <div class="dashboard-card reviews">
                <i class="fas fa-star"></i>
                <h3>Customer Reviews</h3>
                <p><?= $totalReviews ?></p>
            </div>
        </div>
        
        <div id="message" class="message" role="status" aria-live="polite"></div>

        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-user"></i> Registered Users</h2>
            </div>
            <div class="table-container">
                <table id="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Username</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users->num_rows === 0): ?>
                        <tr class="empty-table-row"><td colspan="7"><i class="fas fa-user-plus"></i><strong>No customers yet</strong><span>Registered customer accounts will appear here.</span></td></tr>
                        <?php endif; ?>
                        <?php while ($u = $users->fetch_assoc()): ?>
                        <tr data-id="<?= $u['id'] ?>">
                            <td><?= $u['id'] ?></td>
                            <td class="editable" data-field="name"><?= htmlspecialchars($u['name']) ?></td>
                            <td class="editable" data-field="email"><?= htmlspecialchars($u['email']) ?></td>
                            <td class="editable" data-field="phone"><?= htmlspecialchars($u['phone']) ?></td>
                            <td class="editable" data-field="username"><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= date('M j, Y', strtotime($u['member_since'])) ?></td>
                            <td class="actions">
                                <button class="btn btn-edit" onclick="editUser(<?= $u['id'] ?>)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-delete" onclick="deleteUser(<?= $u['id'] ?>)"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-shopping-bag"></i> Orders Management</h2>
            </div>
            <div class="table-container">
                <table id="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Order Number</th>
                            <th>Date</th>
                            <th>Total (RM)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders->num_rows === 0): ?>
                        <tr class="empty-table-row"><td colspan="7"><i class="fas fa-receipt"></i><strong>No orders yet</strong><span>Customer orders will appear here when they are placed.</span></td></tr>
                        <?php endif; ?>
                        <?php while ($o = $orders->fetch_assoc()): ?>
                        <tr data-id="<?= $o['id'] ?>">
                            <td><?= $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['name']) ?></td>
                            <td><strong><?= htmlspecialchars($o['order_number']) ?></strong>
                                <?php $details = order_payload($o); ?>
                                <details><summary>Items &amp; delivery</summary>
                                    <p>Payment: <?= htmlspecialchars(['cash'=>'Cash on delivery', 'card_demo'=>'Card demo — no charge processed', 'ewallet_demo'=>'E-wallet demo — no charge processed'][$o['payment_method']] ?? 'Unknown method') ?></p>
                                    <?php foreach (json_decode($details['items'], true) as $line): ?>
                                        <p><?= htmlspecialchars($line['name']) ?> × <?= (int) $line['quantity'] ?>
                                        <?php if ($line['specialRequest']): ?><br><?= htmlspecialchars($line['specialRequest']) ?><?php endif; ?></p>
                                    <?php endforeach; ?>
                                    <?php if ($details['deliveryInfo']): ?>
                                        <p><?= htmlspecialchars($details['deliveryInfo']['address']) ?><br><?= htmlspecialchars($details['deliveryInfo']['phone']) ?></p>
                                        <p><?= htmlspecialchars($details['deliveryInfo']['specialInstructions']) ?></p>
                                    <?php else: ?><p>No delivery details saved for this older order.</p><?php endif; ?>
                                </details>
                            </td>
                            <td><?= date('M j, Y H:i', strtotime($o['order_date'])) ?></td>
                            <td><strong>RM <?= number_format($o['total'], 2) ?></strong></td>
                            <td>
                                <select class="status-select" aria-label="Status for order <?= htmlspecialchars($o['order_number']) ?>" data-saved-status="<?= htmlspecialchars($o['status']) ?>" onchange="updateOrderStatus(<?= $o['id'] ?>, this.value)">
                                    <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="preparing" <?= $o['status'] === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                    <option value="ready" <?= $o['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                                    <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn btn-delete" onclick="deleteOrder(<?= $o['id'] ?>)"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-star"></i> Customer Reviews</h2>
            </div>
            <div class="table-container">
                <table id="reviews-table">
                    <thead>
                        <tr>
                            <th>Review ID</th>
                            <th>Customer</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($reviews->num_rows === 0): ?>
                        <tr class="empty-table-row"><td colspan="6"><i class="fas fa-star"></i><strong>No reviews yet</strong><span>Published customer feedback will appear here.</span></td></tr>
                        <?php endif; ?>
                        <?php while ($r = $reviews->fetch_assoc()): ?>
                        <tr data-id="<?= $r['id'] ?>">
                            <td><?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td>
                                <div class="rating-stars">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?= $i <= $r['rating'] ? '★' : '☆' ?>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($r['review']) ?></td>
                            <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-delete" onclick="deleteReview(<?= $r['id'] ?>)"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>GreenSprout Cafe</h3>
                <p style="color: rgba(255, 255, 255, 0.8); margin-bottom: 20px;">Breakfast, bowls, drinks and desserts.</p>
                <p class="portfolio-disclosure">Portfolio demonstration · Social channels are intentionally not connected.</p>
            </div>
            
            <div class="footer-column">
                <h3>Quick Links</h3>
                <div class="footer-links">
                    <a href="admin_home.php">Home</a>
                    <a href="admin_manage_menu.php"><i class="fas fa-utensils" aria-hidden="true"></i><span>Menu</span></a>
                    <a href="admin_panel.php"><i class="fas fa-list-check" aria-hidden="true"></i><span>Operations</span></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Contact Us</h3>
                <div class="footer-links">
                    <a href="tel:+60123456789"><i class="fas fa-phone"></i> +60 12-345 6789</a>
                    <a href="mailto:info@greensprout.com"><i class="fas fa-envelope"></i> info@greensprout.com</a>
                    <a href="https://www.google.com/maps/search/?api=1&amp;query=Bukit+Bintang+Kuala+Lumpur" target="_blank" rel="noopener noreferrer"><i class="fas fa-map-marker-alt"></i> Lot NO. 251, Jalan bukit Bintang, 55100 Kuala Lumpur, Malaysia</a>
                </div>
            </div>
            
<div class="footer-column"><h3>GreenSprout Café</h3><p>Monday–Sunday · 8:00 AM–9:00 PM</p></div>
        </div>
        
        <div class="copyright">
            <p>&copy; 2026 GreenSprout Organic Cafe. Admin workspace.</p>
        </div>
    </footer>

    <script src="assets/js/admin-tables.js?v=enhance10"></script>
    <script>
        const csrfToken = <?= json_encode(csrf_token()) ?>;
        // Check login status on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkLoginStatus();
        });

        function checkLoginStatus() {
            // In a real app, this would check the user's session
            const loginStatus = document.getElementById('login-status');
            if (loginStatus) {
                loginStatus.textContent = "Admin User";
            }
        }

        function showMessage(message, type = 'success') {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = message;
            messageDiv.className = 'message ' + type;
            messageDiv.style.display = 'block';
            
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 3000);
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                GS.fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({action: 'delete_user', user_id: userId, csrf_token: csrfToken})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('#users-table tr[data-id="' + userId + '"]').remove();
                        showMessage('User deleted successfully');
                    } else {
                        showMessage(data.error || 'Error deleting user', 'error');
                    }
                })
                .catch(error => {
                    showMessage('Error occurred', 'error');
                });
            }
        }

        function editUser(userId) {
            const row = document.querySelector('#users-table tr[data-id="' + userId + '"]');
            const editableCells = row.querySelectorAll('.editable');
            const actionsCell = row.querySelector('.actions');
            
            // Store original values
            const originalValues = {};
            editableCells.forEach(cell => {
                const field = cell.getAttribute('data-field');
                originalValues[field] = cell.textContent;
                const input = document.createElement('input');
                input.type = 'text'; input.className = 'edit-input'; input.value = cell.textContent;
                input.dataset.field = field; input.setAttribute('aria-label', field);
                cell.replaceChildren(input);
            });
            
            // Change action buttons
            const save = document.createElement('button');
            save.className = 'btn btn-save'; save.textContent = 'Save';
            save.addEventListener('click', () => saveUser(userId));
            const cancel = document.createElement('button');
            cancel.className = 'btn btn-cancel'; cancel.textContent = 'Cancel';
            cancel.addEventListener('click', () => cancelEdit(userId, originalValues));
            actionsCell.replaceChildren(save, cancel);
        }

        function saveUser(userId) {
            const row = document.querySelector('#users-table tr[data-id="' + userId + '"]');
            const inputs = row.querySelectorAll('.edit-input');
            
            const formData = new FormData();
            formData.append('action', 'update_user');
            formData.append('csrf_token', csrfToken);
            formData.append('user_id', userId);
            
            inputs.forEach(input => {
                formData.append(input.getAttribute('data-field'), input.value);
            });
            
            GS.fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    inputs.forEach(input => {
                        const cell = input.parentElement;
                        cell.textContent = input.value;
                    });
                    
                    const actionsCell = row.querySelector('.actions');
                    actionsCell.innerHTML = `
                        <button class="btn btn-edit" onclick="editUser(${userId})"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-delete" onclick="deleteUser(${userId})"><i class="fas fa-trash"></i> Delete</button>
                    `;
                    
                    showMessage('User updated successfully');
                } else {
                    showMessage(data.error || 'Error updating user', 'error');
                }
            })
            .catch(error => {
                showMessage('Error occurred', 'error');
            });
        }

        function cancelEdit(userId, originalValues) {
            const row = document.querySelector('#users-table tr[data-id="' + userId + '"]');
            const editableCells = row.querySelectorAll('.editable');
            const actionsCell = row.querySelector('.actions');
            
            editableCells.forEach(cell => {
                const field = cell.getAttribute('data-field');
                cell.textContent = originalValues[field];
            });
            
            actionsCell.innerHTML = `
                <button class="btn btn-edit" onclick="editUser(${userId})"><i class="fas fa-edit"></i> Edit</button>
                <button class="btn btn-delete" onclick="deleteUser(${userId})"><i class="fas fa-trash"></i> Delete</button>
            `;
        }

        function updateOrderStatus(orderId, status) {
            const select = document.querySelector('#orders-table tr[data-id="' + orderId + '"] .status-select');
            if (select.disabled) return;
            const previous = select.dataset.savedStatus;
            select.disabled = true;
            GS.fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({action: 'update_order_status', order_id: orderId, status, csrf_token: csrfToken})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    select.dataset.savedStatus = status;
                    showMessage('Order status updated successfully');
                } else {
                    select.value = previous;
                    showMessage(data.error || 'Error updating order status', 'error');
                }
            })
            .catch(error => {
                select.value = previous;
                showMessage('Unable to save the status. The previous selection has been restored.', 'error');
            }).finally(() => { select.disabled = false; });
        }

        function deleteOrder(orderId) {
            if (confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
                GS.fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({action: 'delete_order', order_id: orderId, csrf_token: csrfToken})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('#orders-table tr[data-id="' + orderId + '"]').remove();
                        showMessage('Order deleted successfully');
                    } else {
                        showMessage('Error deleting order', 'error');
                    }
                })
                .catch(error => {
                    showMessage('Error occurred', 'error');
                });
            }
        }

        function deleteReview(reviewId) {
            if (confirm('Are you sure you want to delete this review?')) {
                GS.fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({action: 'delete_review', review_id: reviewId, csrf_token: csrfToken})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('#reviews-table tr[data-id="' + reviewId + '"]').remove();
                        showMessage('Review deleted successfully');
                    } else {
                        showMessage('Error deleting review', 'error');
                    }
                })
                .catch(error => {
                    showMessage('Error occurred', 'error');
                });
            }
        }
    </script>
</body>
</html>
