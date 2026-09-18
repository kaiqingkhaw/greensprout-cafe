<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/product-media.php';
require_admin_page();
$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_product') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $productId = (int) ($_POST['product_id'] ?? 0);
    if ($productId > 0) {
        $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $_SESSION['message'] = $stmt->affected_rows > 0 ? 'Product deleted successfully!' : 'Product not found. The list has been refreshed.';
    }
    header('Location: admin_manage_menu.php');
    exit;
}

// Schema changes are owned by database/schema.sql and scripts/migrate.php.


// Fetch all products AFTER processing POST actions
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");

// Get message if exists
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" href="assets/favicon.svg?v=2" type="image/svg+xml">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Green Sprout Café</title>
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <style>
        /* All CSS styles remain unchanged from your original code */
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
            --border-radius: 8px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--secondary);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            line-height: 1.6;
            color: var(--text);
            overflow-x: hidden;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--dark), var(--primary), var(--primary-light));
            padding: 15px 5%;
            color: var(--light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            flex: 1;
        }

        .nav-links {
            display: flex;
            justify-content: center;
            flex: 2;
        }

        .nav-links a {
            color: var(--light);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 25px;
            transition: all 0.3s ease;
            margin: 0 10px;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-links a.active {
            background: rgba(255, 255, 255, 0.3);
        }

        .header-spacer {
            flex: 1;
            display: flex;
            justify-content: flex-end;
        }

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

        /* Main Content Styles */
        .admin-content {
            padding: 40px 5%;
            flex: 1;
        }

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-title h2 {
            font-size: 2.2rem;
            color: var(--primary);
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary-lighter);
            border-radius: 2px;
        }

        /* Form and Table Styles */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--box-shadow);
            margin: 60px 0 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
            color: var(--text);
        }

        input, textarea, select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input:focus, textarea:focus, select:focus {
            border-color: var(--primary);
            outline: none;
        }

        .checkbox-group {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
        }

        button {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 20px;
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.4);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0 50px;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        th, td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .action-links {
            display: flex;
            gap: 15px;
        }

        .edit-link {
            color: var(--success);
            text-decoration: none;
            transition: color 0.3s;
        }

        .edit-link:hover {
            color: #388e3c;
            text-decoration: underline;
        }

        .delete-link {
            color: var(--danger);
            text-decoration: none;
            transition: color 0.3s;
        }

        .delete-link:hover {
            color: #d32f2f;
            text-decoration: underline;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #555;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 30px;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #333;
            transform: translateX(-3px);
        }
        
        /* New table column styles */
        .thumbnail {
            max-width: 70px;
            max-height: 70px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .nutrition-info {
            font-size: 0.85rem;
            line-height: 1.4;
        }
        
        .nutrition-info span {
            display: block;
        }
        
        /* Nutrition Tooltip */
        .nutrition-tooltip {
            position: relative;
            cursor: pointer;
            display: inline-block;
        }
        
        .nutrition-tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 10px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .nutrition-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        .nutrition-tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
        
        /* Nutrition Form Layout */
        .nutrition-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .nutrition-item {
            display: flex;
            flex-direction: column;
        }
        
        .nutrition-item label {
            font-weight: normal;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        /* Success and Error Messages */
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .alert .close {
            position: absolute;
            right: 15px;
            cursor: pointer;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }

            .form-container {
                padding: 20px;
            }

            table {
                display: block;
                overflow-x: auto;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .footer-column {
                text-align: center;
            }
            
            .footer-links {
                align-items: center;
            }
            
            .footer-column h3::after {
                left: 50%;
                transform: translateX(-50%);
            }
        }

        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            background: transparent;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Mobile menu */
        .mobile-nav {
            display: none;
            background: linear-gradient(135deg, var(--dark), var(--primary));
            padding: 15px;
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            z-index: 99;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .mobile-nav.active {
            display: block;
        }
        
        .mobile-nav a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .mobile-nav a:hover, .mobile-nav a.active {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* New styles for product management */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .stat-label-large {
            font-size: 1.1rem;
            color: var(--text);
            font-weight: 600;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .no-image {
            display: inline-flex;
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            color: var(--primary-light);
            font-size: 0.9rem;
            text-align: center;
            padding: 10px;
            font-weight: 600;
            border: 2px dashed #90caf9;
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            min-width: 90px;
        }
        
        .edit-btn {
            background-color: var(--warning);
            color: white;
        }
        
        .edit-btn:hover {
            background-color: #f57c00;
            transform: translateY(-3px);
        }
        
        .featured-badge {
            display: inline-block;
            background: linear-gradient(135deg, #ffd700, #ff9800);
            color: #333;
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            margin-top: 8px;
        }
        
        .discount-badge {
            display: inline-block;
            background: linear-gradient(135deg, #4caf50, #2e7d32);
            color: white;
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            margin-top: 8px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-top: 10px;
        }
        
        .stat-item {
            background-color: var(--secondary);
            padding: 8px;
            border-radius: 6px;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
            font-weight: 500;
        }
        
        .form-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 25px;
        }
        
        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            font-weight: 600;
            display: flex;
            align-items: center;
            animation: fadeIn 0.5s, fadeOut 0.5s 2.5s;
            position: relative;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: all 0.5s ease;
        }
        
        .message.success {
            background: linear-gradient(135deg, #4caf50, #2e7d32);
            color: white;
        }
        
        .message.info {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }
        
        .message i {
            margin-right: 10px;
            font-size: 1.4rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        .message::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: rgba(255,255,255,0.5);
            animation: progress 3s linear;
        }
        
        @keyframes progress {
            from { width: 100%; }
            to { width: 0; }
        }
        
        .nutrition-highlight {
            font-weight: 700;
            color: var(--dark);
        }
        
        .search-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-bar input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #d1e3ff;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .search-bar button {
            padding: 12px 20px;
        }
        
        /* Added spacing between sections */
        .form-section-title {
            text-align: center;
            margin: 30px 0 20px;
            color: var(--primary);
            position: relative;
        }
        
        .form-section-title h3 {
            font-size: 1.8rem;
            display: inline-block;
            padding-bottom: 10px;
        }
        
        .form-section-title h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--primary-lighter);
            border-radius: 2px;
        }
        
        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            margin: 30px 0;
        }
        .message.hidden {
            opacity: 0;
            height: 0;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }
    </style>
    <link rel="stylesheet" href="assets/css/admin-theme.css?v=enhance11">
  <script src="assets/js/api-client.js?v=audit6"></script>
  <link rel="stylesheet" href="assets/css/responsive.css?v=audit7">
  <link id="shared-header-style" rel="stylesheet" href="assets/css/header.css?v=audit7">
  <link rel="stylesheet" href="assets/css/workspace.css?v=portfolio7">
</head>
<body class="admin-workspace admin-menu-management">
    
    <header>
        <a class="logo" href="admin_home.php" aria-label="GreenSprout admin home" style="color:inherit;text-decoration:none;"><span>GreenSprout</span></a>
        <div class="nav-links">
            <a href="admin_home.php"><i class="fas fa-table-columns" aria-hidden="true"></i><span>Dashboard</span></a>
            <a href="admin_overview.php"><i class="fas fa-chart-line" aria-hidden="true"></i><span>Insights</span></a>
            <a href="admin_manage_menu.php" class="active" aria-current="page"><i class="fas fa-utensils" aria-hidden="true"></i><span>Menu</span></a>
            <a href="admin_panel.php"><i class="fas fa-list-check" aria-hidden="true"></i><span>Operations</span></a>
        </div>
        <div class="header-spacer header-actions">
            <a href="admin_profile.php" class="icon-button" title="Admin account" aria-label="Admin account"><i class="fas fa-user-gear"></i></a>
            <a href="logout.php" class="icon-button" title="Logout" aria-label="Logout"><i class="fas fa-sign-out-alt"></i></a>
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <main class="admin-content">
        <div class="content-container">
            <div class="section-title">
                <h2>Manage Products</h2>
                <p>Create and maintain the items shown on the customer menu</p>
                <a href="admin_edit_product.php?mode=add" class="dashboard-primary-action menu-add-action"><i class="fas fa-plus"></i> Add menu item</a>
            </div>

           <?php if (!empty($message)): ?>
                <div class="message <?= strpos($message, 'successfully') !== false ? 'success' : 'info' ?>" id="statusMessage">
                    <i class="fas <?= strpos($message, 'deleted') !== false ? 'fa-trash-alt' : (strpos($message, 'updated') !== false ? 'fa-save' : 'fa-info-circle') ?>"></i>
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    <span class="close" onclick="closeMessage()" style="position: absolute; right: 15px; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </span>
                </div>
            <?php endif; ?>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-label-large">Total Products</div>
                    <div class="stat-value"><?= $products->num_rows ?></div>
                    <i class="fas fa-utensils fa-2x" style="color: var(--primary);"></i>
                </div>
                <div class="stat-card">
                    <div class="stat-label-large">Featured Items</div>
                    <div class="stat-value">
                        <?php 
                        $featured = $conn->query("SELECT COUNT(*) as count FROM products WHERE featured=1");
                        echo $featured->fetch_assoc()['count'];
                        ?>
                    </div>
                    <i class="fas fa-star fa-2x" style="color: var(--warning);"></i>
                </div>
                <div class="stat-card">
                    <div class="stat-label-large">Discounted</div>
                    <div class="stat-value">
                        <?php 
                        $discounted = $conn->query("SELECT COUNT(*) as count FROM products WHERE discounted=1");
                        echo $discounted->fetch_assoc()['count'];
                        ?>
                    </div>
                    <i class="fas fa-tag fa-2x" style="color: var(--success);"></i>
                </div>
                <div class="stat-card">
                    <div class="stat-label-large">Categories</div>
                    <div class="stat-value">
                        <?php 
                        $categories = $conn->query("SELECT COUNT(DISTINCT category) as count FROM products");
                        echo $categories->fetch_assoc()['count'];
                        ?>
                    </div>
                    <i class="fas fa-layer-group fa-2x" style="color: var(--primary-light);"></i>
                </div>
            </div>

            <div class="section-title">
                <h3>All Products</h3>
            </div>
            
            <div class="search-bar">
                <input type="text" id="searchInput" aria-label="Search menu products" placeholder="Search products..." oninput="searchProducts()">
                <button onclick="searchProducts()"><i class="fas fa-search"></i> Search</button>
            </div>
            
            <?php if ($products->num_rows > 0): ?>
            <div class="products-table-container" role="region" aria-label="Menu products" tabindex="0">
            <table id="productsTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Details</th>
                        <th>Price</th>
                        <th>Category</th>
                        <th>Nutrition</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $products->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if ($row['image']): ?>
                                <img src="<?= htmlspecialchars(product_image_url($row['image'])) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="thumbnail" onerror="this.onerror=null;this.src='assets/images/dish-placeholder.svg';">
                            <?php else: ?>
                                <img src="assets/images/dish-placeholder.svg" alt="" class="thumbnail">
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="nutrition-highlight"><?= htmlspecialchars($row['name']) ?></div>
                            <?php if ($row['featured']): ?>
                                <div class="featured-badge"><i class="fas fa-star"></i> Featured</div>
                            <?php endif; ?>
                            <?php if ($row['discounted']): ?>
                                <div class="discount-badge"><i class="fas fa-tag"></i> <?= $row['discount_percent'] ?>% OFF</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-size: 0.9rem; margin-top: 8px;"><?= htmlspecialchars(mb_strimwidth($row['description'], 0, 63, '...'), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td>
                            <div class="nutrition-highlight"><span class="mobile-field-label">Price</span>RM <?= number_format($row['price'], 2) ?></div>
                        </td>
                        <td>
                            <div class="product-category-label">
                                <?= htmlspecialchars($row['category']) ?>
                            </div>
                        </td>
                        <td>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div><?= $row['calories'] ? $row['calories'] : '-' ?></div>
                                    <div class="stat-label">Calories</div>
                                </div>
                                <div class="stat-item">
                                    <div><?= $row['protein'] ? $row['protein'] . 'g' : '-' ?></div>
                                    <div class="stat-label">Protein</div>
                                </div>
                                <div class="stat-item">
                                    <div><?= $row['carbs'] ? $row['carbs'] . 'g' : '-' ?></div>
                                    <div class="stat-label">Carbs</div>
                                </div>
                                <div class="stat-item">
                                    <div><?= $row['fats'] ? $row['fats'] . 'g' : '-' ?></div>
                                    <div class="stat-label">Fats</div>
                                </div>
                            </div>
                        </td>
                        <td class="actions">
                            <a href="admin_edit_product.php?id=<?= $row['id'] ?>" class="action-btn edit-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form method="post" class="inline-delete-form" onsubmit="return confirm('Delete this menu item? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="action-btn delete-btn"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
                <div class="message info" style="text-align: center; padding: 20px;">
                    <i class="fas fa-info-circle"></i> No products found
                </div>
            <?php endif; ?>
        </div>
    </main>

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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Auto-hide message after 3 seconds
            const message = document.getElementById('statusMessage');
            if (message) {
                setTimeout(() => {
                    message.style.display = 'none';
                }, 3000);
            }
            
            // Initialize search on input
            document.getElementById('searchInput').addEventListener('input', searchProducts);
            document.getElementById('searchInput').setAttribute('aria-label', 'Search products');
        });

        function searchProducts() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('productsTable');
            if (!table) return;
            const tr = table.getElementsByTagName('tr');
            
            // Start from 1 to skip header row
            for (let i = 1; i < tr.length; i++) {
                const tds = tr[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < tds.length; j++) {
                    if (tds[j]) {
                        const txtValue = tds[j].textContent || tds[j].innerText;
                        if (txtValue.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
            let summary = document.getElementById('products-filter-summary');
            if (!summary) { summary = document.createElement('p'); summary.id = 'products-filter-summary'; summary.setAttribute('role','status'); table.parentElement.after(summary); }
            const visible = [...tr].slice(1).filter(row => row.style.display !== 'none').length;
            summary.textContent = visible ? visible + ' matching products' : 'No matching products. Try a different search.';
        }

        function closeMessage() {
            const message = document.getElementById('statusMessage');
            if (message) {
                message.style.display = 'none';
            }
        }
    </script>
</body>
</html>
