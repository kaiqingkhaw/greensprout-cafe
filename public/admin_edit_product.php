<?php
require_once dirname(__DIR__) . '/app/config.php';
require_admin_page();
$conn = db();
$isCreate = ($_GET['mode'] ?? '') === 'add';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    foreach (['name'=>255,'description'=>5000,'category'=>100,'image'=>500] as $field=>$limit) {
        $_POST[$field] = clean_text($_POST[$field] ?? '', $limit);
    }
    if (!in_array($_POST['category'], ['Breakfast','Mains','Drinks','Desserts'], true) ||
        !is_numeric($_POST['price'] ?? null) || (float) $_POST['price'] <= 0 || (float) $_POST['price'] > 99999 ||
        ($_POST['image'] !== '' && (!filter_var($_POST['image'], FILTER_VALIDATE_URL) || parse_url($_POST['image'], PHP_URL_SCHEME) !== 'https'))) {
        json_response(['error'=>'Use a valid category, price and HTTPS image URL.'], 422);
    }
    foreach (['discount_percent'=>100,'calories'=>65535,'protein'=>65535,'carbs'=>65535,'fats'=>65535,'fiber'=>65535] as $field=>$maximum) {
        $value = $_POST[$field] ?? '';
        if ($value === '') $value = '0';
        if (filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0,'max_range'=>$maximum]]) === false) json_response(['error'=>'Nutrition and discount values must be within valid ranges.'],422);
        $_POST[$field] = $value;
    }
    if (isset($_POST['name'])) {
        $id = intval($_POST['id'] ?? 0);
        $name = trim((string) $_POST['name']);
        $description = trim((string) $_POST['description']);
        $price = max(0, floatval($_POST['price']));
        $category = trim((string) $_POST['category']);
        $image = trim((string) ($_POST['image'] ?? ''));
        $featured = isset($_POST['featured']) ? 1 : 0;
        $discounted = isset($_POST['discounted']) ? 1 : 0;
        $discount_percent = max(0, min(100, intval($_POST['discount_percent'])));
        
        $calories = max(0, intval($_POST['calories']));
        $protein = max(0, intval($_POST['protein']));
        $carbs = max(0, intval($_POST['carbs']));
        $fats = max(0, intval($_POST['fats']));
        $fiber = max(0, intval($_POST['fiber']));

        if ($name === '' || $description === '' || $category === '' || $price <= 0) {
            $_SESSION['message'] = 'Please complete all required fields with a valid price.';
            header('Location: admin_edit_product.php' . ($id > 0 ? '?id=' . $id : '?mode=add'));
            exit;
        }

        if ($id > 0) {
            $existing = $conn->prepare('SELECT id FROM products WHERE id = ?');
            $existing->bind_param('i', $id); $existing->execute();
            if (!$existing->get_result()->fetch_assoc()) json_response(['error'=>'This product no longer exists. Return to Products and refresh the list.'], 404);
            $stmt = $conn->prepare('UPDATE products SET name=?, description=?, price=?, category=?, image=?, featured=?, discounted=?, discount_percent=?, calories=?, protein=?, carbs=?, fats=?, fiber=? WHERE id=?');
            $stmt->bind_param('ssdssiiiiiiiii', $name, $description, $price, $category, $image, $featured, $discounted, $discount_percent, $calories, $protein, $carbs, $fats, $fiber, $id);
            $saved = $stmt->execute();
            $_SESSION['message'] = $saved ? 'Product updated successfully!' : 'Unable to update the product.';
        } else {
            $stmt = $conn->prepare('INSERT INTO products (name, description, price, category, image, featured, discounted, discount_percent, calories, protein, carbs, fats, fiber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssdssiiiiiiii', $name, $description, $price, $category, $image, $featured, $discounted, $discount_percent, $calories, $protein, $carbs, $fats, $fiber);
            $saved = $stmt->execute();
            $_SESSION['message'] = $saved ? 'Product added successfully!' : 'Unable to add the product.';
        }
        
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) json_response(['success'=>true, 'redirect'=>'admin_manage_menu.php']);
        header("Location: admin_manage_menu.php");
        exit();
    }
}

// Fetch product to edit
$product = [
    'id' => 0, 'name' => '', 'description' => '', 'price' => '', 'category' => '',
    'image' => '', 'featured' => 0, 'discounted' => 0, 'discount_percent' => 0,
    'calories' => '', 'protein' => '', 'carbs' => '', 'fats' => '', 'fiber' => ''
];

if ($isCreate) {
    // Keep the empty defaults for a new product.
} elseif (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $product = $res->fetch_assoc();
    } else {
        header("Location: admin_manage_menu.php");
        exit();
    }
} else {
    header("Location: admin_manage_menu.php");
    exit();
}

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
    <title><?= $isCreate ? 'Add' : 'Edit' ?> Product - GreenSprout Admin</title>
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/admin-product-form.css?v=organized1">
    <link rel="stylesheet" href="assets/css/admin-theme.css?v=enhance11">
  <script src="assets/js/api-client.js?v=audit6"></script>
  <link rel="stylesheet" href="assets/css/responsive.css?v=audit7">
  <link id="shared-header-style" rel="stylesheet" href="assets/css/header.css?v=audit7">
  <link rel="stylesheet" href="assets/css/workspace.css?v=portfolio7">
</head>
<body class="admin-workspace admin-product-editor">
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
                <h2><?= $isCreate ? 'Add Menu Item' : 'Edit Product' ?></h2>
                <p><?= $isCreate ? 'Create a new item for the customer menu' : 'Update the product details below' ?></p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?= strpos($message, 'successfully') !== false ? 'success' : 'info' ?>">
                    <i class="fas <?= strpos($message, 'deleted') !== false ? 'fa-trash-alt' : (strpos($message, 'updated') !== false ? 'fa-save' : 'fa-info-circle') ?>"></i>
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            
            <a href="admin_manage_menu.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
            
            <div class="form-container">
                <form method="POST" id="productForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-tag" aria-hidden="true"></i> Product Name</label>
                            <input type="text" id="name" name="name" required value="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="price"><i class="fas fa-money-bill-wave" aria-hidden="true"></i> Price (RM)</label>
                            <input type="number" step="0.01" min="0.01" max="99999" id="price" name="price" required value="<?= $product['price'] ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="category"><i class="fas fa-folder" aria-hidden="true"></i> Category</label>
                            <select id="category" name="category" required><option value="">Choose a category</option><?php foreach (['Breakfast','Mains','Drinks','Desserts'] as $category): ?><option value="<?= $category ?>" <?= $product['category'] === $category ? 'selected' : '' ?>><?= $category ?></option><?php endforeach; ?></select>
                        </div>
                        
                        <div class="form-group">
                            <label for="image"><i class="fas fa-image" aria-hidden="true"></i> Image URL</label>
                            <input type="url" placeholder="https://… (optional)" aria-describedby="image-help" id="image" name="image" value="<?= htmlspecialchars($product['image']) ?>">
                            <small id="image-help">Use an HTTPS photo URL. Leave blank to use the café illustration.</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description"><i class="fas fa-align-left" aria-hidden="true"></i> Description</label>
                        <textarea id="description" name="description" rows="3" required><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Nutritional Information</label>
                        <div class="nutrition-grid">
                            <div class="nutrition-item">
                                <label for="calories">Calories</label>
                                <input type="number" id="calories" name="calories" min="0" value="<?= $product['calories'] ?>">
                            </div>
                            <div class="nutrition-item">
                                <label for="protein">Protein (g)</label>
                                <input type="number" id="protein" name="protein" min="0" value="<?= $product['protein'] ?>">
                            </div>
                            <div class="nutrition-item">
                                <label for="carbs">Carbs (g)</label>
                                <input type="number" id="carbs" name="carbs" min="0" value="<?= $product['carbs'] ?>">
                            </div>
                            <div class="nutrition-item">
                                <label for="fats">Fats (g)</label>
                                <input type="number" id="fats" name="fats" min="0" value="<?= $product['fats'] ?>">
                            </div>
                            <div class="nutrition-item">
                                <label for="fiber">Fiber (g)</label>
                                <input type="number" id="fiber" name="fiber" min="0" value="<?= $product['fiber'] ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="discount_percent"><i class="fas fa-percent" aria-hidden="true"></i> Discount %</label>
                            <input type="number" id="discount_percent" name="discount_percent" min="0" max="100" value="<?= $product['discount_percent'] ?>">
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="featured" <?= $product['featured'] ? 'checked' : '' ?>>
                            Featured Item
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="discounted" <?= $product['discounted'] ? 'checked' : '' ?>>
                            Discounted
                        </label>
                    </div>
                    
                    <div class="form-footer">
                        <button type="submit">
                            <i class="fas fa-save"></i>
                            <?= $isCreate ? 'Add Menu Item' : 'Update Product' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <!-- Footer content from original file -->
    </footer>

    <script src="assets/js/admin-product-editor.js?v=1"></script>
</body>
</html>
