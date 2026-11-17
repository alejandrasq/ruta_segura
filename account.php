<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/user_auth.php';

startUserSession();

function accountEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$error = '';
$mode = ($_GET['mode'] ?? '') === 'register' ? 'register' : 'login';
$nextInput = $_POST['next'] ?? $_GET['next'] ?? '';
$next = in_array($nextInput, ['report', 'home'], true) ? $nextInput : '';
$successUrl = $next === 'report' ? 'index.php?report=1' : ($next === 'home' ? 'index.php' : 'account.php');
$csrf = createUserCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $sentToken = $_POST['csrf_token'] ?? '';

    if (!is_string($sentToken) || !hash_equals($csrf, $sentToken)) {
        $error = 'La sesión expiró. Recarga la página e inténtalo de nuevo.';
    } elseif ($action === 'logout') {
        if (isUserAuthenticated()) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => 'Strict',
                ]);
            }
            session_destroy();
        }

        header('Location: index.php');
        exit;
    } elseif (!isUserAuthenticated() && in_array($action, ['login', 'register'], true)) {
        $usernameInput = $_POST['username'] ?? '';
        $passwordInput = $_POST['password'] ?? '';
        $username = is_string($usernameInput) ? trim($usernameInput) : '';
        $password = is_string($passwordInput) ? $passwordInput : '';
        $validUsername = preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $username) === 1;
        $validAdminEmail = strlen($username) <= 80 && filter_var($username, FILTER_VALIDATE_EMAIL) !== false;

        if ($action === 'register' && !$validUsername) {
            $error = 'El usuario debe tener de 3 a 80 caracteres: letras, números, punto, guion o guion bajo.';
        } elseif ($action === 'login' && !$validUsername && !$validAdminEmail) {
            $error = 'Ingresa un correo válido o un nombre de usuario.';
        } elseif ($action === 'register') {
            $confirmationInput = $_POST['password_confirmation'] ?? '';
            $confirmation = is_string($confirmationInput) ? $confirmationInput : '';
            if (strlen($password) < 12 || strlen($password) > 72) {
                $error = 'La contraseña debe tener entre 12 y 72 caracteres.';
            } elseif (!hash_equals($password, $confirmation)) {
                $error = 'Las contraseñas no coinciden.';
            } else {
                try {
                    $stmt = getDB()->prepare(
                        'INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)'
                    );
                    $stmt->execute([
                        ':username' => $username,
                        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ]);

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) getDB()->lastInsertId();
                    $_SESSION['user_username'] = $username;
                    unset($_SESSION['user_csrf'], $_SESSION['user_login_attempts'], $_SESSION['user_login_blocked_until']);
                    createUserCsrfToken();
                    header('Location: ' . $successUrl);
                    exit;
                } catch (PDOException $e) {
                    if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                        $error = 'Ese nombre de usuario ya está en uso. Elige otro.';
                    } else {
                        error_log('Rutas Seguras user registration: ' . $e->getMessage());
                        $error = 'No se pudo crear la cuenta. Inténtalo de nuevo más tarde.';
                    }
                }
            }
        } elseif (($_SESSION['user_login_blocked_until'] ?? 0) > time()) {
            $error = 'Demasiados intentos. Espera unos minutos antes de volver a intentarlo.';
        } else {
            $adminStmt = getDB()->prepare(
                'SELECT id, username, password_hash FROM admin_users WHERE username = :username LIMIT 1'
            );
            $adminStmt->execute([':username' => $username]);
            $admin = $adminStmt->fetch();

            if ($admin) {
                if (password_verify($password, $admin['password_hash'])) {
                    unset($_SESSION['user_login_attempts'], $_SESSION['user_login_blocked_until']);
                    session_write_close();

                    require_once __DIR__ . '/config/admin_auth.php';
                    startAdminSession();
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = (int) $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    unset($_SESSION['admin_csrf'], $_SESSION['admin_login_attempts'], $_SESSION['admin_login_blocked_until']);
                    createAdminCsrfToken();
                    header('Location: admin/index.php');
                    exit;
                }
            } else {
                $stmt = getDB()->prepare(
                    'SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1'
                );
                $stmt->execute([':username' => $username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['user_username'] = $user['username'];
                    unset($_SESSION['user_csrf'], $_SESSION['user_login_attempts'], $_SESSION['user_login_blocked_until']);
                    createUserCsrfToken();
                    header('Location: ' . $successUrl);
                    exit;
                }
            }

            $error = 'Correo, usuario o contraseña incorrectos.';
            $_SESSION['user_login_attempts'] = ($_SESSION['user_login_attempts'] ?? 0) + 1;
            if ($_SESSION['user_login_attempts'] >= 5) {
                $_SESSION['user_login_blocked_until'] = time() + 300;
                $_SESSION['user_login_attempts'] = 0;
            }
        }
    } else {
        $error = 'La acción solicitada no es válida.';
    }
}

$authenticated = isUserAuthenticated();
$entryRegistration = !$authenticated && $mode === 'register';
$accountPopupRequest = ($_GET['modal_data'] ?? '') === '1';
if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'GET' && !$accountPopupRequest) {
    header('Location: index.php?account=1');
    exit;
}

$username = $authenticated ? (string) $_SESSION['user_username'] : '';
$reports = [];
if ($authenticated) {
    $stmt = getDB()->prepare(
        'SELECT type, location, description, severity, status, created_at
         FROM reports WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 100'
    );
    $stmt->execute([':user_id' => (int) $_SESSION['user_id']]);
    $reports = $stmt->fetchAll();
}

if (($_GET['modal_data'] ?? '') === '1' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if (!$authenticated) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Inicia sesión para consultar tu historial.']);
    } else {
        echo json_encode(['ok' => true, 'reports' => $reports], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuenta de usuario | Rutas Seguras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php if ($entryRegistration): ?>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/brand-theme.css?v=<?= filemtime(__DIR__ . '/assets/css/brand-theme.css') ?>">
    <style>body{font-family:Inter,sans-serif}</style>
</head>
<body class="brand-page min-h-screen bg-slate-100 text-slate-800 flex flex-col<?= $entryRegistration ? ' entry-page' : '' ?>">
    <?php if ($entryRegistration): ?>
    <header class="entry-header brand-header bg-white/90 backdrop-blur border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
            <a href="index.php" class="flex items-center gap-3" aria-label="Rutas Seguras, inicio">
                <img src="punto_seguro.png" alt="" class="entry-mini-logo">
                <span>
                    <span class="block font-extrabold leading-tight text-slate-900">Rutas Seguras</span>
                    <span class="block text-xs text-slate-500">CBTis 258 · General Escobedo</span>
                </span>
            </a>
            <span class="hidden sm:inline-flex items-center gap-2 text-xs font-semibold text-slate-500">
                <i class="fa-solid fa-lock text-emerald-700"></i> Acceso seguro
            </span>
        </div>
    </header>
    <?php else: ?>
    <header class="brand-header bg-white border-b border-slate-200">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between gap-3">
            <a href="index.php" class="flex items-center gap-2 font-extrabold text-slate-900">
                <img src="punto_seguro.png" alt="" class="brand-logo">Rutas Seguras
            </a>
            <a href="index.php" class="brand-link text-sm font-semibold"><i class="fa-solid fa-map mr-1"></i>Volver al mapa</a>
        </div>
    </header>
    <?php endif; ?>

    <main class="<?= $entryRegistration ? 'w-full flex-1 flex items-center justify-center px-4 py-12 sm:py-16' : 'w-full max-w-2xl mx-auto px-4 py-8 flex-1' ?>">
        <?php if ($entryRegistration): ?>
            <div class="entry-layout w-full max-w-6xl mx-auto grid items-center gap-10 md:grid-cols-2 lg:gap-16">
                <section class="entry-identity" aria-labelledby="entry-title">
                    <img src="punto_seguro.png" alt="Logotipo de Rutas Seguras, CBTis 258" class="entry-logo-image">
                    <p class="entry-kicker mt-7 inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wider">
                        <i class="fa-solid fa-location-dot"></i> Comunidad CBTis 258
                    </p>
                    <h1 id="entry-title" class="entry-title mt-5 text-4xl sm:text-5xl font-extrabold tracking-tight">
                        Muévete seguro.<br><em>Regresa tranquilo.</em>
                    </h1>
                    <p class="mt-5 max-w-lg text-sm sm:text-base leading-7 text-slate-600">
                        Consulta rutas seguras, ubica puntos de apoyo y comparte alertas para cuidar a quienes recorren el camino contigo.
                    </p>
                    <div class="entry-features mt-7 grid gap-3 sm:grid-cols-3 md:grid-cols-1 lg:grid-cols-3">
                        <div><i class="fa-solid fa-route"></i><span>Rutas seguras</span></div>
                        <div><i class="fa-solid fa-shield-heart"></i><span>Puntos de apoyo</span></div>
                        <div><i class="fa-solid fa-bullhorn"></i><span>Alertas vecinales</span></div>
                    </div>
                    <p class="entry-emergency mt-8 text-xs text-slate-500">
                        <i class="fa-solid fa-phone-volume mr-1"></i> En una emergencia inmediata, llama al <strong>911</strong>.
                    </p>
                </section>

                <section class="entry-login-card rounded-3xl border bg-white p-6 sm:p-9" aria-labelledby="register-title">
                    <p class="entry-form-kicker text-xs font-bold uppercase tracking-[.18em]">Únete a la comunidad</p>
                    <h2 id="register-title" class="entry-form-title mt-2 text-4xl sm:text-5xl font-extrabold">Crear <em>cuenta</em></h2>
                    <p class="mt-3 mb-7 text-sm text-slate-500">Completa tus datos para publicar reportes y consultar su estado.</p>

                    <?php if ($next === 'report'): ?>
                        <div class="brand-notice mb-5 rounded-xl px-4 py-3 text-sm">
                            Para publicar un reporte, primero crea tu cuenta o inicia sesión.
                        </div>
                    <?php endif; ?>
                    <?php if ($error !== ''): ?>
                        <div role="alert" class="entry-error mb-5 rounded-xl border px-4 py-3 text-sm"><?= accountEscape($error) ?></div>
                    <?php endif; ?>

                    <form method="post" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= accountEscape($csrf) ?>">
                        <input type="hidden" name="action" value="register">
                        <input type="hidden" name="next" value="<?= accountEscape($next) ?>">
                        <div>
                            <label for="username" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-700">Nombre de usuario</label>
                            <input id="username" name="username" autocomplete="username" required minlength="3" maxlength="80"
                                pattern="[a-zA-Z0-9._-]{3,80}" value="<?= accountEscape((string) ($_POST['username'] ?? '')) ?>"
                                class="entry-input w-full rounded-xl border px-4 py-3 text-sm outline-none transition">
                            <p class="mt-1 text-[11px] text-slate-400">3 a 80 caracteres: letras, números, punto, guion o guion bajo.</p>
                        </div>
                        <div>
                            <label for="password" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-700">Contraseña</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" required minlength="12" maxlength="72"
                                class="entry-input w-full rounded-xl border px-4 py-3 text-sm outline-none transition">
                            <p class="mt-1 text-[11px] text-slate-400">Usa entre 12 y 72 caracteres.</p>
                        </div>
                        <div>
                            <label for="password_confirmation" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-700">Confirmar contraseña</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="12" maxlength="72"
                                class="entry-input w-full rounded-xl border px-4 py-3 text-sm outline-none transition">
                        </div>
                        <button type="submit" class="entry-submit flex w-full items-center justify-center gap-3 rounded-xl px-5 py-3.5 text-sm font-bold text-white transition">
                            Crear cuenta <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-500">
                        ¿Ya tienes cuenta?
                        <a class="entry-register font-bold underline underline-offset-4" href="index.php<?= $next === 'report' ? '?report=1' : '' ?>">Inicia sesión</a>
                    </p>
                </section>
            </div>
        <?php else: ?>
        <?php if ($authenticated): ?>
            <section class="brand-panel p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7">
                    <div>
                        <p class="text-xs uppercase tracking-wide font-bold text-rose-900">Cuenta de usuario</p>
                        <h1 class="brand-heading text-2xl font-extrabold mt-1">Hola, <?= accountEscape($username) ?></h1>
                        <p class="text-sm text-slate-500 mt-1">Aquí puedes consultar el estado de tus reportes.</p>
                    </div>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= accountEscape($csrf) ?>">
                        <input type="hidden" name="action" value="logout">
                        <button class="brand-secondary-button rounded-xl px-4 py-2 text-sm font-bold" type="submit">
                            <i class="fa-solid fa-right-from-bracket mr-1"></i>Cerrar sesión
                        </button>
                    </form>
                </div>

                <a href="index.php?report=1" class="brand-primary-button inline-flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-bold">
                    <i class="fa-solid fa-plus"></i>Crear un reporte
                </a>

                <h2 class="brand-heading mt-8 mb-3 text-lg font-extrabold">Mi historial</h2>
                <?php if (!$reports): ?>
                    <div class="brand-empty-state p-6 text-center">
                        <i class="fa-solid fa-clipboard-list text-2xl text-slate-300"></i>
                        <p class="font-bold mt-2">Aún no tienes reportes</p>
                        <p class="text-sm text-slate-500 mt-1">Tus reportes aparecerán aquí después de publicarlos.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($reports as $report): ?>
                            <article class="brand-panel brand-report-card p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-bold"><?= accountEscape($report['type']) ?></h3>
                                        <p class="text-sm text-slate-600 mt-1"><?= accountEscape($report['location']) ?></p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold <?= $report['status'] === 'resuelto' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                        <?= $report['status'] === 'resuelto' ? 'Resuelto' : 'En revisión' ?>
                                    </span>
                                </div>
                                <p class="text-sm text-slate-500 mt-2"><?= accountEscape($report['description']) ?></p>
                                <p class="text-xs text-slate-400 mt-3">Riesgo <?= accountEscape(ucfirst($report['severity'])) ?> · <?= accountEscape($report['created_at']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <p class="text-xs text-slate-500 mt-6"><i class="fa-solid fa-lock mr-1"></i>Este historial solo es visible desde tu cuenta. Tu nombre no aparece en los reportes públicos.</p>
            </section>
        <?php else: ?>
            <section class="brand-panel p-6 sm:p-8">
                <a href="index.php" class="brand-link inline-flex items-center gap-2 text-sm mb-7">
                    <i class="fa-solid fa-arrow-left"></i>Volver al mapa
                </a>
                <img src="punto_seguro.png" alt="Logo Rutas Seguras CBTis 258" class="account-login-logo mb-5">
                <h1 class="brand-heading text-2xl font-extrabold"><?= $mode === 'register' ? 'Crear cuenta de usuario' : 'Iniciar sesión' ?></h1>
                <p class="text-sm text-slate-500 mt-1 mb-6">
                    <?= $mode === 'register' ? 'Regístrate para publicar reportes y consultar su estado.' : 'Inicia sesión para publicar reportes y ver tu historial.' ?>
                </p>

                <?php if ($next === 'report'): ?>
                    <div class="brand-notice mb-5 rounded-xl px-4 py-3 text-sm">
                        Para publicar un reporte, primero inicia sesión o crea una cuenta.
                    </div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= accountEscape($error) ?></div>
                <?php endif; ?>

                <form method="post" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= accountEscape($csrf) ?>">
                    <input type="hidden" name="action" value="<?= $mode === 'register' ? 'register' : 'login' ?>">
                    <input type="hidden" name="next" value="<?= accountEscape($next) ?>">
                    <div>
                        <label for="username" class="block text-xs font-bold uppercase text-slate-600 mb-1"><?= $mode === 'register' ? 'Nombre de usuario' : 'Correo de administrador o nombre de usuario' ?></label>
                        <input id="username" name="username" autocomplete="username" required maxlength="80"
                            <?= $mode === 'register' ? 'minlength="3" pattern="[a-zA-Z0-9._-]{3,80}"' : '' ?>
                            value="<?= accountEscape((string) ($_POST['username'] ?? '')) ?>"
                            class="brand-input w-full rounded-xl border px-3 py-3 text-sm outline-none">
                        <?php if ($mode === 'register'): ?>
                            <p class="text-xs text-slate-400 mt-1">3 a 80 caracteres: letras, números, punto, guion o guion bajo.</p>
                        <?php else: ?>
                            <p class="text-xs text-slate-400 mt-1">Administradores: correo electrónico. Usuarios: nombre de usuario.</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase text-slate-600 mb-1">Contraseña</label>
                        <input id="password" name="password" type="password" autocomplete="<?= $mode === 'register' ? 'new-password' : 'current-password' ?>" required
                            <?= $mode === 'register' ? 'minlength="12" maxlength="72"' : '' ?>
                            class="brand-input w-full rounded-xl border px-3 py-3 text-sm outline-none">
                        <?php if ($mode === 'register'): ?><p class="text-xs text-slate-400 mt-1">Usa entre 12 y 72 caracteres.</p><?php endif; ?>
                    </div>
                    <?php if ($mode === 'register'): ?>
                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold uppercase text-slate-600 mb-1">Confirmar contraseña</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="12" maxlength="72"
                                class="brand-input w-full rounded-xl border px-3 py-3 text-sm outline-none">
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="brand-primary-button w-full rounded-xl py-3 font-bold text-sm">
                        <?= $mode === 'register' ? 'Crear cuenta' : 'Iniciar sesión' ?>
                    </button>
                </form>

                <p class="text-sm text-center text-slate-600 mt-5">
                    <?php if ($mode === 'register'): ?>
                        ¿Ya tienes cuenta? <a class="brand-link font-bold hover:underline" href="account.php<?= $next !== '' ? '?next=' . rawurlencode($next) : '' ?>">Inicia sesión</a>
                    <?php else: ?>
                        ¿Aún no tienes cuenta? <a class="brand-link font-bold hover:underline" href="account.php?mode=register<?= $next !== '' ? '&next=' . rawurlencode($next) : '' ?>">Crear cuenta</a>
                    <?php endif; ?>
                </p>
            </section>
        <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
