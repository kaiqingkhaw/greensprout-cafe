<?php
require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/app/order-data.php';
date_default_timezone_set('Asia/Kuala_Lumpur');
start_app_session();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Database connection
$conn = db();
$currentUserStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$currentUserStmt->bind_param("i", $_SESSION['user_id']);
$currentUserStmt->execute();
$user = $currentUserStmt->get_result()->fetch_assoc();

// Handle profile updates without deleting the previous image before a successful save.
if (!$user) { $_SESSION = []; header('Location: login.html'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    verify_csrf($_POST['csrf_token'] ?? null);
    $newPath = null;
    try {
        $name = clean_text($_POST['name'] ?? '', 100);
        $email = clean_text($_POST['email'] ?? '', 190);
        $phone = clean_text($_POST['phone'] ?? '', 30);
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !valid_phone($phone)) throw new DomainException('Enter a valid name, email and phone number.');
        $image = $user['profile_image'];
        if (($_POST['remove_photo'] ?? '') === '1') $image = null;
        $file = $_FILES['profile_image'] ?? null;
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (!is_int($file['error']) || $file['error'] !== UPLOAD_ERR_OK) throw new DomainException('The photo could not upload. Choose a file under 2 MB.');
            if ($file['size'] > 2 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) throw new DomainException('Choose a photo under 2 MB.');
            $type = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $extensions = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/gif'=>'gif'];
            $size = @getimagesize($file['tmp_name']);
            if (!isset($extensions[$type]) || !$size || $size[0] > 6000 || $size[1] > 6000) throw new DomainException('Choose a valid JPG, PNG or GIF image up to 6000 pixels per side.');
            $image = 'uploads/profile_' . (int) $_SESSION['user_id'] . '_' . bin2hex(random_bytes(16)) . '.' . $extensions[$type];
            $newPath = __DIR__ . '/' . $image;
            if (!move_uploaded_file($file['tmp_name'], $newPath)) throw new RuntimeException('Unable to store upload.');
        }
        $stmt = $conn->prepare('UPDATE users SET name=?, email=?, phone=?, profile_image=? WHERE id=?');
        $stmt->bind_param('ssssi', $name, $email, $phone, $image, $_SESSION['user_id']); $stmt->execute();
        $_SESSION['name'] = $name; $_SESSION['email'] = $email; $_SESSION['phone'] = $phone;
        // Old images are intentionally retained; no database path is ever used for deletion.
        $success_message = 'Profile updated successfully.';
    } catch (Throwable $e) {
        if ($newPath && is_file($newPath)) unlink($newPath); // Only the new, generated path from this request.
        $error_message = $e instanceof DomainException ? $e->getMessage() :
            ($e instanceof mysqli_sql_exception && $e->getCode() === 1062 ? 'This email is already registered.' : 'Unable to update your profile. Please try again.');
        error_log($e->getMessage());
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" href="assets/favicon.svg?v=2" type="image/svg+xml">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Green Sprout Café</title>
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #2e7d32;
            --primary-light: #4caf50;
            --primary-lighter: #8bc34a;
            --secondary: #f9f5eb;
            --accent: #ff9800;
            --dark: #1b5e20;
            --light: #ffffff;
            --text: #333333;
            --text-light: #666666;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--secondary);
            color: var(--text);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* Header Styles - Matching MainMenu.html */
        header .nav-links {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        header .nav-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 10px 16px;
            border-radius: 24px;
            color: #fff;
            text-decoration: none;
            white-space: nowrap;
        }
        header .nav-links a:hover { background: rgba(255,255,255,.12); }
        header .login-status { max-width: 220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        header .icon-button[aria-current="page"] { outline:2px solid #b9e2c1; outline-offset:2px; }
        @media (min-width:761px) and (max-width:1100px) {
            header .login-status { display:none; }
            header .nav-links { gap:8px; }
        }
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
        
        .header-icons {
            display: flex;
            gap: 15px;
            align-items: center;
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
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .login-status {
            color: var(--light);
            margin-right: 10px;
            font-size: 0.9rem;
        }
        
        /* Profile Container */
        .profile-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 5%;
            animation: fadeIn 0.5s ease;
            flex: 1;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-lighter));
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--light);
            box-shadow: var(--shadow);
            border: 5px solid var(--light);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .profile-avatar:hover {
            transform: scale(1.05);
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--accent);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .avatar-upload:hover {
            background: #e68a00;
            transform: scale(1.1);
        }
        
        .avatar-upload i {
            color: var(--light);
            font-size: 1.2rem;
        }
        
        #profile_image {
            display: none;
        }
        
        .profile-header h1 {
            font-size: 2.2rem;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .profile-header p {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        /* Profile Form - Matching MainMenu.html style */
        .profile-form {
            background: var(--light);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
            max-width: 600px;
            margin: 0 auto;
            transition: var(--transition);
        }
        
        .profile-form:hover {
            box-shadow: var(--shadow-hover);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary);
            font-size: 0.95rem;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }
        
        /* Button Styles - Matching MainMenu.html but slightly smaller */
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-block;
            cursor: pointer;
            border: none;
            text-align: center;
            flex: 1;
        }
        
        .btn-back {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary-light);
        }
        
        .btn-back:hover {
            background: rgba(76, 175, 80, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-light), var(--primary-lighter));
            color: var(--light);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.6);
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
        }
        
        /* Alerts */
        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            color: #d32f2f;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
        
        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--dark), var(--primary));
            color: var(--light);
            padding: 40px 5% 20px;
            text-align: center;
            margin-top: auto;
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
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-avatar {
                width: 120px;
                height: 120px;
                font-size: 3rem;
            }
            
            .profile-header h1 {
                font-size: 1.8rem;
            }
            
            .profile-form {
                padding: 30px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
            
            .avatar-upload {
                width: 30px;
                height: 30px;
            }
            
            .avatar-upload i {
                font-size: 1rem;
            }
            
            .profile-form {
                padding: 25px 20px;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/css/customer-theme.css?v=consistent2">
  <script src="assets/js/api-client.js?v=audit6"></script>
  <link rel="stylesheet" href="assets/css/responsive.css?v=consistent2">
  <link id="shared-header-style" rel="stylesheet" href="assets/css/header.css?v=audit6">
  <link rel="stylesheet" href="assets/css/account-checkout.css?v=polish9">
  <link rel="stylesheet" href="assets/css/workspace.css?v=portfolio7">
</head>
<body class="account-page">
<header class="customer-header">
        <a class="logo" href="MainMenu.html" aria-label="GreenSprout home"><span>GreenSprout</span></a>
        <nav class="nav-links" aria-label="Main navigation">
            <a href="MainMenu.html">Home</a>
            <a href="menu.html">Menu</a>
            <a href="About.us.html">About Us</a>
        </nav>
        <div class="header-icons">
            <a href="profile.php" class="icon-button" title="My account" aria-label="My account" aria-current="page"><i class="fas fa-user" aria-hidden="true"></i></a>
            <a href="menu.html?view=cart" class="icon-button" title="Cart" aria-label="Cart"><i class="fas fa-shopping-cart" aria-hidden="true"></i></a>
            <a href="logout.php" class="logout-btn" title="Sign out" aria-label="Sign out"><i class="fas fa-sign-out-alt" aria-hidden="true"></i></a>
        </div>
    </header>

    <div class="profile-container">
        <div class="account-heading"><span class="eyebrow">YOUR ACCOUNT</span><h1>My profile</h1><p>Manage your personal details and keep track of your orders.</p></div>
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="account-layout">
        <aside class="profile-header">
            <div class="profile-avatar">
                <?php if (!empty($user['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <h2><?= htmlspecialchars($user['name']) ?></h2>
            <p>Member since <?php echo isset($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : 'June 2025'; ?></p>
            <div class="photo-actions"><button type="button" id="choose-photo" class="photo-button"><i class="fas fa-camera" aria-hidden="true"></i> Change photo</button><button type="button" id="remove-photo-button" class="photo-remove" <?= empty($user['profile_image']) ? 'hidden' : '' ?>>Remove photo</button></div>
            <p class="photo-hint">JPG, PNG or GIF · up to 2 MB</p><p id="photo-feedback" class="photo-feedback" role="status">Changes are saved with your profile.</p>
            <div class="account-note"><i class="fas fa-leaf" aria-hidden="true"></i><strong>Good food. Your way.</strong><p>Keep your details up to date for a smoother next order.</p><a href="menu.html">Explore the menu →</a></div>
        </aside>

        <form class="profile-form" method="POST" action="profile.php" enctype="multipart/form-data">
            <div class="section-heading"><span class="eyebrow">ACCOUNT SETTINGS</span><h2>Personal information</h2><p>Keep your details up to date for your next order.</p></div>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif">
            <input type="hidden" name="remove_photo" value="0" id="remove_photo">
            
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>
            
            <div class="button-group">
                <a href="MainMenu.html" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to home
                </a>
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
        </div>

        <?php
        // Order history query
        $conn = db();
        $orders = [];
        if (!$conn->connect_error) {
            $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $orders[] = order_payload($row);
            }
            $stmt->close();
            $conn->close();
        }
        ?>
        <div class="order-history-section" style="margin:40px auto 0;max-width:900px;">
            <h2 style="color:var(--primary);margin-bottom:25px;text-align:center;font-size:2rem;">Order History</h2>
            <?php if (empty($orders)): ?>
                <div style="text-align:center;color:#888;font-size:1.1rem;padding:40px 0;">You have not placed any orders yet.</div>
            <?php else: ?>
                <div class="order-history-list">
                    <?php foreach ($orders as $order): ?>
                        <?php 
                            $current_status = $order['status'];
                            $is_ongoing = !in_array($current_status, ['completed', 'cancelled', 'delivered'], true);
                            $card_tag = 'a';
                            $card_href = 'href="menu.html?track_order=' . htmlspecialchars($order['order_number']) . '&amp;view=invoice"';
                            $card_style = 'background:var(--light);border-radius:16px;box-shadow:var(--shadow);margin-bottom:30px;padding:30px 25px;display:block;text-decoration:none;color:inherit;';
                            if ($is_ongoing) $card_style .= 'cursor:pointer;transition:all 0.3s ease;';
                        ?>
                        <<?php echo $card_tag; ?> <?php echo $card_href; ?> class="order-card" style="<?php echo $card_style; ?>" onmouseover="if(this.tagName==='A')this.style.boxShadow='var(--shadow-hover)';" onmouseout="this.style.boxShadow='var(--shadow)';">
                            <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;margin-bottom:18px;gap:10px;">
                                <div style="font-weight:600;color:var(--primary);font-size:1.1rem;">
                                    <i class="fas fa-receipt"></i> Order #: <?php echo htmlspecialchars($order['order_number']); ?>
                                </div>
                                <div style="color:var(--text-light);font-size:0.98rem;">
                                    <i class="fas fa-calendar-alt"></i> <?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?>
                                </div>
                                <div style="color:var(--primary-light);font-weight:600;">
                                    <i class="fas fa-coins"></i> RM<?php echo number_format($order['total'],2); ?>
                                </div>
                                <div style="color:#fff;background:<?php echo $is_ongoing ? 'var(--accent)' : 'var(--primary-light)'; ?>;border-radius:20px;padding:4px 18px;font-size:0.98rem;display:inline-block;">
                                    <i class="fas fa-info-circle"></i> <?php echo ucfirst($current_status); ?>
                                </div>
                            </div>
                            <div style="margin-top:10px;">
                                <strong>Items:</strong>
                                <ul style="margin:10px 0 0 18px;padding:0;list-style:disc;color:var(--text);">
                                    <?php 
                                    $items = json_decode($order['items'], true);
                                    if (is_array($items)) {
                                        foreach ($items as $item) {
                                            echo '<li>' . htmlspecialchars($item['name']) . ' x ' . intval($item['quantity']) . ' <span style="color:#888;font-size:0.97em;">(RM' . number_format($item['price'],2) . ' each)</span></li>';
                                        }
                                    } else {
                                        echo '<li>No item details</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                        </<?php echo $card_tag; ?>>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
<footer>
    <div class="footer-content">
        <div class="footer-column">
            <h3>Green Sprout Café</h3>
            <p style="color: rgba(255, 255, 255, 0.8); margin-bottom: 20px;">Breakfast, bowls, drinks and desserts.</p>
            <p class="portfolio-disclosure">Demo application · No real orders or payments.</p>
        </div>
        
        <div class="footer-column">
            <h3>Quick Links</h3>
            <div class="footer-links">
                <a href="MainMenu.html">Home</a>
                <a href="menu.html">Menu</a>
                <a href="About.us.html">About Us</a>
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
        
        <div class="footer-column newsletter">
<h3>Visit the café</h3>
<p>Monday–Sunday<br>8:00 AM–9:00 PM</p>
<a href="About.us.html">Location &amp; contact details →</a>
        </div>
    </div>
    
    <div class="copyright">
        <p>&copy; 2026 GreenSprout Café. University project.</p>
    </div>
</footer>

    <script src="assets/js/account-photo.js?v=1"></script>
</body>
</html>
