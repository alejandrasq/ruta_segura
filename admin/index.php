<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin_auth.php';

startAdminSession();
if (!isAdminAuthenticated()) {
    header('Location: login.php');
    exit;
}

$csrf = createAdminCsrfToken();
$stmt = getDB()->query(
    'SELECT id, type, location, description, severity, status, likes, created_at
     FROM reports ORDER BY created_at DESC LIMIT 100'
);
$reports = $stmt->fetchAll();
$stats = getDB()->query(
    "SELECT COUNT(*) AS total,
        SUM(status = 'activo') AS activos,
        SUM(status = 'resuelto') AS resueltos
     FROM reports"
)->fetch();

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración | Rutas Seguras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/brand-theme.css?v=<?= filemtime(__DIR__ . '/../assets/css/brand-theme.css') ?>">
    <style>body{font-family:Inter,sans-serif}</style>
</head>
<body class="brand-page brand-admin-page min-h-screen bg-slate-100 text-slate-800">
    <header class="brand-header">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-1 flex items-center justify-between gap-4 min-h-16">
            <div class="flex items-center gap-3">
                <img src="../punto_seguro.png" alt="" class="brand-logo">
                <div>
                    <h1 class="font-bold">Rutas Seguras</h1>
                    <p class="text-xs">Moderación de reportes · CBTis 258</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="admin-session-pill hidden sm:inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold">
                    <i class="fa-solid fa-user"></i><?= h((string) ($_SESSION['admin_username'] ?? 'Administrador')) ?>
                </span>
                <form method="post" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                    <button class="brand-secondary-button rounded-full px-3 py-2 text-xs font-semibold" type="submit">
                        <i class="fa-solid fa-right-from-bracket mr-1"></i><span class="hidden sm:inline">Salir</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="admin-dashboard-layout max-w-7xl mx-auto">
        <aside class="admin-dashboard-sidebar">
            <section aria-labelledby="admin-summary-title">
                <h2 id="admin-summary-title" class="admin-section-label">
                    <i class="fa-solid fa-chart-simple mr-2"></i>Resumen
                </h2>
                <div class="grid grid-cols-3 gap-2">
                    <div class="brand-stat-card p-3">
                        <p class="text-[10px] uppercase font-bold text-slate-500">Todos</p>
                        <p class="text-xl font-extrabold mt-1"><?= (int) $stats['total'] ?></p>
                    </div>
                    <div class="brand-stat-card brand-stat-active p-3">
                        <p class="text-[10px] uppercase font-bold text-amber-700">Activos</p>
                        <p class="text-xl font-extrabold text-amber-700 mt-1"><?= (int) $stats['activos'] ?></p>
                    </div>
                    <div class="brand-stat-card brand-stat-resolved p-3">
                        <p class="text-[10px] uppercase font-bold text-emerald-700">Resueltos</p>
                        <p class="text-xl font-extrabold text-emerald-700 mt-1"><?= (int) $stats['resueltos'] ?></p>
                    </div>
                </div>
            </section>

            <section class="admin-dashboard-help">
                <h2 class="admin-section-label">
                    <i class="fa-solid fa-list-check mr-2"></i>Moderación
                </h2>
                <p class="text-sm text-slate-600 leading-6">
                    Revisa cada reporte y actualiza su estado cuando la situación haya sido atendida.
                </p>
            </section>

            <nav class="admin-dashboard-shortcuts" aria-label="Accesos rápidos">
                <a href="locations.php" class="brand-secondary-button flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold">
                    <i class="fa-solid fa-location-dot"></i>Administrar ubicaciones
                </a>
                <a href="../index.php" class="brand-secondary-button flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold">
                    <i class="fa-solid fa-map"></i>Ver mapa público
                </a>
            </nav>
        </aside>

        <section class="admin-dashboard-content" aria-labelledby="reports-title">
            <div class="admin-dashboard-content-header">
                <div>
                    <h2 id="reports-title" class="brand-heading text-lg font-extrabold">Últimos reportes</h2>
                    <p class="text-xs text-slate-500 mt-1">Revisa la información y cambia su estado cuando corresponda.</p>
                </div>
                <button onclick="location.reload()" class="brand-secondary-button shrink-0 rounded-full px-3 py-2 text-xs font-bold">
                    <i class="fa-solid fa-rotate mr-1"></i>Actualizar
                </button>
            </div>

            <div class="admin-dashboard-report-list custom-scrollbar">
            <?php if (!$reports): ?>
                <div class="brand-empty-state flex min-h-52 items-center justify-center p-8 text-center text-sm">
                    <div>
                        <i class="fa-solid fa-shield-heart text-2xl text-rose-900/70"></i>
                        <p class="mt-3">Todavía no hay reportes.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($reports as $report): ?>
                <?php
                    $resolved = $report['status'] === 'resuelto';
                    $severityColor = ['baja' => 'emerald', 'media' => 'amber', 'alta' => 'red'][$report['severity']] ?? 'slate';
                ?>
                <article class="brand-panel brand-report-card p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold bg-<?= $severityColor ?>-50 text-<?= $severityColor ?>-700 border border-<?= $severityColor ?>-200">
                                    Riesgo <?= h(ucfirst($report['severity'])) ?>
                                </span>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold <?= $resolved ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
                                    <?= $resolved ? 'Resuelto' : 'Activo' ?>
                                </span>
                                <span class="text-[10px] text-slate-400"><?= h($report['created_at']) ?></span>
                            </div>
                            <h3 class="font-bold text-sm"><?= h($report['type']) ?></h3>
                            <p class="text-xs font-semibold text-slate-600 mt-1"><?= h($report['location']) ?></p>
                            <p class="text-sm text-slate-500 mt-2 whitespace-pre-line"><?= h($report['description']) ?></p>
                            <p class="text-[11px] text-slate-400 mt-2"><i class="fa-regular fa-thumbs-up mr-1"></i><?= (int) $report['likes'] ?> apoyos</p>
                        </div>
                        <button type="button" data-report-id="<?= (int) $report['id'] ?>" data-next-status="<?= $resolved ? 'activo' : 'resuelto' ?>"
                            class="resolve-btn shrink-0 rounded-xl px-3 py-2 text-xs font-bold transition <?= $resolved ? 'brand-secondary-button' : 'brand-primary-button' ?>">
                            <i class="fa-solid <?= $resolved ? 'fa-rotate-left' : 'fa-check' ?> mr-1"></i>
                            <?= $resolved ? 'Reactivar' : 'Marcar resuelto' ?>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
            </div>
        </section>
    </main>

    <div id="toast" role="status" class="fixed bottom-4 right-4 hidden max-w-sm rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-lg"></div>
    <script>
        const csrfToken = <?= json_encode($csrf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        document.querySelectorAll('.resolve-btn').forEach((button) => {
            button.addEventListener('click', async () => {
                button.disabled = true;
                try {
                    const response = await fetch('../api/reports.php?action=resolve', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify({ id: Number(button.dataset.reportId) })
                    });
                    const result = await response.json();
                    if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudo actualizar');
                    location.reload();
                } catch (error) {
                    const toast = document.getElementById('toast');
                    toast.textContent = error.message;
                    toast.className = 'fixed bottom-4 right-4 max-w-sm rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-lg bg-red-600';
                } finally {
                    button.disabled = false;
                }
            });
        });
    </script>
</body>
</html>
