<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config.php';
require_admin_page();

$connection = db();
$adminId = (int) $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_account') {
            $name = clean_text($_POST['name'] ?? '', 100);
            $username = clean_text($_POST['username'] ?? '', 50);
            $email = filter_var(clean_text($_POST['email'] ?? '', 190), FILTER_VALIDATE_EMAIL);

            if ($name === '' || !$email || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
                throw new DomainException('Enter a valid name, email, and username.');
            }

            $stmt = $connection->prepare("UPDATE users SET name = ?, username = ?, email = ? WHERE id = ? AND role = 'admin'");
            $stmt->bind_param('sssi', $name, $username, $email, $adminId);
            $stmt->execute();
            $_SESSION['name'] = $name;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $success = 'Account details updated successfully.';
        } elseif ($action === 'change_password') {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            $stmt = $connection->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
            $stmt->bind_param('i', $adminId);
            $stmt->execute();
            $record = $stmt->get_result()->fetch_assoc();

            if (!$record || !password_verify($currentPassword, $record['password'])) {
                throw new DomainException('The current password is incorrect.');
            }
            if (strlen($newPassword) < 12 || strlen($newPassword) > 72 || trim($newPassword) === '' || str_contains($newPassword, "\0")) {
                throw new DomainException('The new password must be 12-72 bytes and cannot contain only spaces or null characters.');
            }
            if ($newPassword !== $confirmPassword) {
                throw new DomainException('The new passwords do not match.');
            }

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $connection->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'admin'");
            $update->bind_param('si', $hash, $adminId);
            $update->execute();
            $success = 'Password changed successfully.';
        }
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    } catch (mysqli_sql_exception $exception) {
        if ((int) $exception->getCode() === 1062) {
            $error = 'That username or email is already in use.';
        } else {
            error_log($exception->getMessage());
            $error = 'Unable to update the account right now.';
        }
    }
}

$stmt = $connection->prepare("SELECT name, username, email, created_at FROM users WHERE id = ? AND role = 'admin'");
$stmt->bind_param('i', $adminId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
if (!$admin) {
    header('Location: logout.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" href="assets/favicon.svg?v=2" type="image/svg+xml">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Account - Green Sprout Café</title>
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    header { position: sticky; top: 0; z-index: 20; display: flex; align-items: center; justify-content: space-between; padding: 15px 5%; }
    .logo { display: flex; align-items: center; font-size: 1.5rem; font-weight: 800; }
    .nav-links { display: flex; gap: 12px; }
    .nav-links a { padding: 8px 14px; border-radius: 999px; text-decoration: none; font-weight: 600; }
    .header-actions { display: flex; gap: 10px; }
    .icon-button { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 50%; text-decoration: none; }
    main { width: min(1120px, 92%); margin: 34px auto 70px; }
    .account-banner { display: flex; align-items: center; gap: 22px; padding: 30px; border-radius: 22px; background: linear-gradient(120deg, #174c31, #2f7d4a); color: #fff; box-shadow: 0 14px 38px rgba(24,48,37,.12); }
    .avatar { width: 76px; height: 76px; display: grid; place-items: center; flex: 0 0 76px; border: 2px solid rgba(255,255,255,.24); border-radius: 22px; background: rgba(255,255,255,.12); font-size: 2rem; }
    .account-banner h1 { margin: 0 0 4px; font-size: clamp(1.8rem, 4vw, 2.6rem); letter-spacing: -.04em; }
    .account-banner p { margin: 0; color: rgba(255,255,255,.82); }
    .account-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px; }
    .account-card { padding: 28px; }
    .account-card h2 { margin: 0 0 6px; color: #174c31; font-size: 1.35rem; }
    .account-card > p { margin: 0 0 24px; color: #607067; }
    .field { margin-bottom: 18px; }
    label { display: block; margin-bottom: 7px; color: #294b3a; font-size: .9rem; font-weight: 700; }
    input { width: 100%; padding: 12px 14px; border: 1px solid #cedbd1; border-radius: 10px; background: #fbfdfb; color: #183025; font: inherit; }
    .form-note { margin: -8px 0 18px; color: #708078; font-size: .84rem; }
    .submit-btn { width: 100%; padding: 12px 18px; border-radius: 10px; font: inherit; font-weight: 700; cursor: pointer; }
    .alert { display: flex; align-items: center; gap: 10px; margin: 20px 0 0; padding: 14px 16px; border-radius: 12px; font-weight: 600; }
    .alert-success { background: #e5f3e8; color: #216640; }
    .alert-error { background: #faeaea; color: #a93838; }
    @media (max-width: 820px) { .account-grid { grid-template-columns: 1fr; } .nav-links { display: none; } }
  </style>
  <link rel="stylesheet" href="assets/css/admin-theme.css?v=enhance11">
  <script src="assets/js/api-client.js?v=audit6"></script>
  <link rel="stylesheet" href="assets/css/responsive.css?v=audit7">
  <link id="shared-header-style" rel="stylesheet" href="assets/css/header.css?v=audit7">
  <link rel="stylesheet" href="assets/css/workspace.css?v=portfolio7">
</head>
<body class="admin-workspace admin-account">
  <header>
    <a class="logo" href="admin_home.php" aria-label="GreenSprout admin home"><span>GreenSprout</span></a>
    <nav class="nav-links" aria-label="Admin navigation">
      <a href="admin_home.php"><i class="fas fa-table-columns" aria-hidden="true"></i><span>Dashboard</span></a>
      <a href="admin_overview.php"><i class="fas fa-chart-line" aria-hidden="true"></i><span>Insights</span></a>
      <a href="admin_manage_menu.php"><i class="fas fa-utensils" aria-hidden="true"></i><span>Menu</span></a>
      <a href="admin_panel.php"><i class="fas fa-list-check" aria-hidden="true"></i><span>Operations</span></a>
    </nav>
    <div class="header-actions">
      <a href="admin_profile.php" class="icon-button" title="Admin account" aria-label="Admin account"><i class="fas fa-user-gear"></i></a>
      <a href="logout.php" class="icon-button" title="Logout" aria-label="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </header>

  <main>
    <section class="account-banner">
      <div class="avatar" aria-hidden="true"><i class="fas fa-user-shield"></i></div>
      <div>
        <h1><?= e($admin['name']) ?></h1>
        <p>Administrator account · Member since <?= date('F Y', strtotime($admin['created_at'])) ?></p>
      </div>
    </section>

    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><?= e($error) ?></div><?php endif; ?>

    <div class="account-grid">
      <section class="account-card content-card">
        <h2>Account details</h2>
        <p>Keep the administrator identity and contact email up to date.</p>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="update_account">
          <div class="field"><label for="name">Display name</label><input id="name" name="name" value="<?= e($admin['name']) ?>" maxlength="100" required></div>
          <div class="field"><label for="username">Username</label><input id="username" name="username" value="<?= e($admin['username']) ?>" minlength="3" maxlength="50" required></div>
          <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?= e($admin['email']) ?>" maxlength="190" required></div>
          <button class="submit-btn" type="submit">Save account details</button>
        </form>
      </section>

      <section class="account-card content-card">
        <h2>Change password</h2>
        <p>Use a unique password that is not shared with customer accounts.</p>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="change_password">
          <div class="field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
          <div class="field"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" minlength="12" autocomplete="new-password" required></div>
          <p class="form-note">Minimum 12 characters.</p>
          <div class="field"><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="confirm_password" type="password" minlength="12" autocomplete="new-password" required></div>
          <button class="submit-btn" type="submit">Change password</button>
        </form>
      </section>
    </div>
  </main>
</body>
</html>
