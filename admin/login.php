<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_auth.php';

startAdminSession();

if (isAdminAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
$csrf = createAdminCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sentToken = $_POST['csrf_token'] ?? '';
    if (!is_string($sentToken) || !hash_equals($csrf, $sentToken)) {
        $error = 'La sesión expiró. Recarga la página e inténtalo de nuevo.';
    } elseif (($_SESSION['admin_login_blocked_until'] ?? 0) > time()) {
        $error = 'Demasiados intentos. Espera unos minutos antes de volver a intentarlo.';
    } else {
        $emailInput = $_POST['email'] ?? '';
        $passwordInput = $_POST['password'] ?? '';
        $email = is_string($emailInput) ? trim($emailInput) : '';
        $password = is_string($passwordInput) ? $passwordInput : '';

        $admin = null;
        if (strlen($email) <= 80 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            $stmt = getDB()->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $admin = $stmt->fetch();
        }

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            unset($_SESSION['admin_csrf']);
            unset($_SESSION['admin_login_attempts'], $_SESSION['admin_login_blocked_until']);
            createAdminCsrfToken();
            header('Location: index.php');
            exit;
        }

        $error = 'Correo o contraseña incorrectos.';
        $_SESSION['admin_login_attempts'] = ($_SESSION['admin_login_attempts'] ?? 0) + 1;
        if ($_SESSION['admin_login_attempts'] >= 5) {
            $_SESSION['admin_login_blocked_until'] = time() + 300;
            $_SESSION['admin_login_attempts'] = 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso administrativo | Rutas Seguras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/brand-theme.css?v=<?= filemtime(__DIR__ . '/../assets/css/brand-theme.css') ?>">
    <style>body{font-family:Inter,sans-serif}</style>
</head>
<body class="brand-page min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <main class="brand-panel w-full max-w-md p-7">
        <a href="../index.php" class="brand-link inline-flex items-center gap-2 text-sm mb-8">
            <i class="fa-solid fa-arrow-left"></i> Volver al mapa
        </a>
        <img src="../punto_seguro.png" alt="Logo Rutas Seguras CBTis 258" class="admin-login-logo mb-5">
        <h1 class="brand-heading text-2xl font-extrabold">Panel de administración</h1>
        <p class="text-sm text-slate-500 mt-1 mb-6">Inicia sesión para moderar los reportes.</p>
        <p class="text-xs text-slate-500 -mt-3 mb-5">Este acceso es exclusivo para administradores. Las cuentas de usuario se crean por separado.</p>

        <?php if ($error !== ''): ?>
            <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div>
                <label for="email" class="block text-xs font-bold uppercase text-slate-600 mb-1">Correo electrónico</label>
                <input id="email" name="email" type="email" autocomplete="email" required maxlength="80"
                    class="brand-input w-full rounded-xl border px-3 py-3 text-sm outline-none">
            </div>
            <div>
                <label for="password" class="block text-xs font-bold uppercase text-slate-600 mb-1">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                    class="brand-input w-full rounded-xl border px-3 py-3 text-sm outline-none">
            </div>
            <button type="submit" class="brand-primary-button w-full rounded-xl py-3 font-bold text-sm">
                Iniciar sesión
            </button>
        </form>
    </main>
</body>
</html>
