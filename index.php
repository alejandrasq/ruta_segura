<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/**
 * Rutas Seguras - CBTis 258 General Escobedo
 * Página principal de la aplicación.
 */
require_once __DIR__ . '/config/user_auth.php';
startUserSession();
$userAuthenticated = isUserAuthenticated();
$userCsrfToken = createUserCsrfToken();
$entryNext = ($_GET['report'] ?? '') === '1' ? 'report' : 'home';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rutas Seguras - CBTis 258 General Escobedo</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <?php if ($userAuthenticated): ?>
    <!-- Leaflet + OpenStreetMap -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <?php endif; ?>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        cbtis: {
                            wine: '#6b1426',
                            gold: '#d4af37',
                            navy: '#1e293b',
                            safe: '#10b981',
                            warning: '#f59e0b',
                            danger: '#ef4444'
                        }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <link rel="stylesheet" href="assets/css/brand-theme.css?v=<?= filemtime(__DIR__ . '/assets/css/brand-theme.css') ?>">
</head>

<body class="brand-page bg-slate-100 dark:bg-slate-950 font-sans text-slate-800 dark:text-slate-200 antialiased flex flex-col min-h-screen transition-colors<?= $userAuthenticated ? '' : ' entry-page' ?>">
<?php if (!$userAuthenticated): ?>
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

    <main class="w-full flex-1 flex items-center justify-center px-4 py-12 sm:py-16">
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

            <section class="entry-login-card rounded-3xl border bg-white p-6 sm:p-9" aria-labelledby="login-title">
                <p class="entry-form-kicker text-xs font-bold uppercase tracking-[.18em]">Acceso a la comunidad</p>
                <h2 id="login-title" class="entry-form-title mt-2 text-4xl sm:text-5xl font-extrabold">Iniciar <em>sesión</em></h2>
                <p class="mt-3 mb-7 text-sm text-slate-500">Ingresa con tu cuenta para continuar.</p>

                <form action="account.php" method="post" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($userCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="next" value="<?= htmlspecialchars($entryNext, ENT_QUOTES, 'UTF-8') ?>">
                    <div>
                        <label for="entry-username" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-700">Correo electrónico o usuario</label>
                        <input id="entry-username" name="username" autocomplete="username" inputmode="email" required maxlength="80"
                            class="entry-input w-full rounded-xl border px-4 py-3 text-sm outline-none transition">
                        <p class="mt-1 text-[11px] text-slate-400">Administradores: correo electrónico. Usuarios: nombre de usuario.</p>
                    </div>
                    <div>
                        <label for="entry-password" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-700">Contraseña</label>
                        <input id="entry-password" name="password" type="password" autocomplete="current-password" required
                            class="entry-input w-full rounded-xl border px-4 py-3 text-sm outline-none transition">
                    </div>
                    <button type="submit" class="entry-submit flex w-full items-center justify-center gap-3 rounded-xl px-5 py-3.5 text-sm font-bold text-white transition">
                        Iniciar sesión <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    ¿No tienes cuenta?
                    <a class="entry-register font-bold underline underline-offset-4" href="account.php?mode=register&amp;next=<?= htmlspecialchars($entryNext, ENT_QUOTES, 'UTF-8') ?>">Regístrate aquí</a>
                </p>
            </section>
        </div>
    </main>
<?php else: ?>

    <!-- ======================= HEADER ======================= -->
    <header class="brand-header bg-white/90 dark:bg-slate-900/90 backdrop-blur sticky top-0 z-[600] shadow-sm border-b border-slate-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <img src="punto_seguro.png" alt="" class="brand-logo">
                    <div>
                        <h1 class="text-lg font-bold leading-none text-slate-900 dark:text-slate-100">Rutas Seguras</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">CBTis 258 • Gral. Escobedo, N.L.</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <a id="account-open-button" href="account.php" onclick="openAccountModal(event)" aria-haspopup="dialog" aria-expanded="false" aria-controls="account-modal"
                        class="brand-nav-link rounded-full px-3 py-2 text-xs font-bold transition">
                        <i class="fa-solid fa-user mr-1"></i><span><?= htmlspecialchars((string) $_SESSION['user_username'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                    <!-- Dark mode -->
                    <button onclick="toggleDarkMode()" title="Cambiar tema"
                        class="w-9 h-9 rounded-full flex items-center justify-center text-slate-500 dark:text-amber-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <i id="theme-icon" class="fa-solid fa-moon"></i>
                    </button>
                    <!-- SOS -->
                    <button onclick="toggleSOSModal()" class="sos-pulse bg-red-600 hover:bg-red-700 text-white px-3.5 py-1.5 rounded-full font-bold text-xs sm:text-sm flex items-center space-x-2 transition">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>ALERTA SOS</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- ======================= MAIN ======================= -->
    <main class="flex-1 flex flex-col md:flex-row relative max-w-7xl w-full mx-auto md:overflow-hidden">

        <!-- ============ SIDEBAR ============ -->
        <aside id="sidebar" class="w-full md:w-96 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col z-[550] md:h-[calc(100vh-64px)] overflow-y-auto custom-scrollbar order-2 md:order-1">

            <button id="sidebar-handle" onclick="toggleSidebar()" class="w-full py-3 items-center justify-center text-slate-400">
                <i class="fa-solid fa-chevron-up" id="sidebar-handle-icon"></i>
                <span class="ml-2 text-xs font-semibold">Panel de control</span>
            </button>

            <!-- Estadísticas -->
            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 grid grid-cols-3 gap-2 text-center" id="stats-bar">
                <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl py-2">
                    <p class="text-lg font-extrabold text-slate-800 dark:text-slate-100" id="stat-total">–</p>
                    <p class="text-[10px] text-slate-500 uppercase font-semibold">Reportes</p>
                </div>
                <div class="bg-amber-50 dark:bg-amber-500/10 rounded-xl py-2">
                    <p class="text-lg font-extrabold text-amber-600" id="stat-activos">–</p>
                    <p class="text-[10px] text-amber-600/80 uppercase font-semibold">Activos</p>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-500/10 rounded-xl py-2">
                    <p class="text-lg font-extrabold text-emerald-600" id="stat-resueltos">–</p>
                    <p class="text-[10px] text-emerald-600/80 uppercase font-semibold">Resueltos</p>
                </div>
            </div>

            <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Capa del mapa</h3>
                <div class="space-y-2">
                    <label class="flex items-center justify-between p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 cursor-pointer">
                        <span class="font-medium text-xs"><svg class="inline-block h-4 w-4 mr-2 align-middle text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="6" cy="19" r="2"/><circle cx="18" cy="5" r="2"/><path d="M8 19h2a4 4 0 0 0 4-4v-2a4 4 0 0 1 4-4"/></svg>Rutas seguras</span>
                        <input type="checkbox" id="layer-routes" checked onchange="toggleMapLayer('routes')" class="rounded text-emerald-700 focus:ring-emerald-600 h-4 w-4">
                    </label>
                    <label class="flex items-center justify-between p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 cursor-pointer">
                        <span class="font-medium text-xs"><svg class="inline-block h-4 w-4 mr-2 align-middle text-cbtis-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/><path d="M8 10v.01M8 13v.01M8 16v.01M16 14v.01M16 17v.01"/></svg>Plantel</span>
                        <input type="checkbox" id="layer-campus" checked onchange="toggleMapLayer('campus')" class="rounded text-cbtis-wine focus:ring-cbtis-wine h-4 w-4">
                    </label>
                    <label class="flex items-center justify-between p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 cursor-pointer">
                        <span class="font-medium text-xs"><svg class="inline-block h-4 w-4 mr-2 align-middle text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></svg>Puntos seguros</span>
                        <input type="checkbox" id="layer-safe-points" checked onchange="toggleMapLayer('safePoints')" class="rounded text-emerald-700 focus:ring-emerald-600 h-4 w-4">
                    </label>
                    <label class="flex items-center justify-between p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 cursor-pointer">
                        <span class="font-medium text-xs"><svg class="inline-block h-4 w-4 mr-2 align-middle text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 2 21h20L12 3Z"/><path d="M12 9v4m0 4h.01"/></svg>Reportes</span>
                        <input type="checkbox" id="layer-reports" checked onchange="toggleMapLayer('reports')" class="rounded text-amber-600 focus:ring-amber-500 h-4 w-4">
                    </label>
                </div>
            </div>

            <!-- Reportes -->
            <div class="p-4 flex-1 flex flex-col min-h-[300px]">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold flex items-center space-x-2">
                        <i class="fa-solid fa-bullhorn text-cbtis-wine"></i>
                        <span>Reportes de la Comunidad</span>
                    </h3>
                    <button onclick="openReportModal()" class="text-xs bg-cbtis-wine hover:bg-red-900 text-white px-2.5 py-1.5 rounded-lg font-semibold flex items-center space-x-1 shadow-sm transition">
                        <i class="fa-solid fa-plus"></i><span><?= $userAuthenticated ? 'Reportar' : 'Inicia sesión' ?></span>
                    </button>
                </div>

                <!-- Buscador y filtros -->
                <div class="space-y-2 mb-3">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="search-input" oninput="onSearchInput(this.value)" placeholder="Buscar reporte..."
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-8 pr-3 py-2 text-xs outline-none focus:ring-2 focus:ring-cbtis-wine">
                    </div>
                    <div class="flex space-x-1.5 overflow-x-auto pb-1" id="type-filters">
                        <button data-type="" onclick="setTypeFilter('')" class="type-filter-btn shrink-0 text-[10px] font-semibold px-2.5 py-1 rounded-full bg-cbtis-wine text-white">Todos</button>
                        <button data-type="Luz Fundida / Zona Oscura" onclick="setTypeFilter('Luz Fundida / Zona Oscura')" class="type-filter-btn shrink-0 text-[10px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"><svg class="inline-block h-3 w-3 mr-1 text-cbtis-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 10-13h-7l1-7Z"/></svg>Luz</button>
                        <button data-type="Presencia Escasa de Vía" onclick="setTypeFilter('Presencia Escasa de Vía')" class="type-filter-btn shrink-0 text-[10px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"><svg class="inline-block h-3 w-3 mr-1 text-cbtis-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 3-4 18M16 3l4 18M12 5v2m0 4v2m0 4v2"/></svg>Vial</button>
                        <button data-type="Zona Solitaria / Sospechosa" onclick="setTypeFilter('Zona Solitaria / Sospechosa')" class="type-filter-btn shrink-0 text-[10px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"><svg class="inline-block h-3 w-3 mr-1 text-cbtis-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>Solitaria</button>
                        <button data-type="Tráfico / Banqueta Obstruida" onclick="setTypeFilter('Tráfico / Banqueta Obstruida')" class="type-filter-btn shrink-0 text-[10px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"><svg class="inline-block h-3 w-3 mr-1 text-cbtis-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="10" width="18" height="7" rx="2"/><path d="m6 10 1.5-4h9L18 10M7 17v2m10-2v2"/></svg>Tráfico</button>
                        <button data-type="Acoso / Robo" onclick="setTypeFilter('Acoso / Robo')" class="type-filter-btn shrink-0 text-[10px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"><svg class="inline-block h-3 w-3 mr-1 text-cbtis-wine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></svg>Acoso/Robo</button>
                    </div>
                    <label class="flex items-center space-x-2 text-[11px] text-slate-500 dark:text-slate-400 cursor-pointer select-none">
                        <input type="checkbox" id="show-resolved" onchange="loadReports()" class="rounded text-cbtis-wine focus:ring-cbtis-wine h-3.5 w-3.5">
                        <span>Mostrar solo reportes resueltos</span>
                    </label>
                </div>

                <div id="reports-container" class="space-y-2.5 flex-1 overflow-y-auto pr-1">
                    <!-- Dinámico -->
                </div>
            </div>

            <!-- Contactos -->
            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-700">
                <p class="text-[10px] text-slate-500 uppercase font-bold mb-2 tracking-wider text-center">Contactos de Emergencia</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <a href="tel:8182869200" class="flex items-center justify-center space-x-1.5 py-2 px-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        <i class="fa-solid fa-shield-halved text-blue-600"></i><span>C4 Escobedo</span>
                    </a>
                    <a href="tel:8183971666" class="flex items-center justify-center space-x-1.5 py-2 px-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        <i class="fa-solid fa-school text-cbtis-wine"></i><span>CBTis 258</span>
                    </a>
                </div>
            </div>
        </aside>

        <section class="flex-1 flex flex-col relative order-1 md:order-2">
            <div id="map"></div>
            <button onclick="goToCampus()" title="Volver al plantel" aria-label="Volver al plantel"
                class="absolute bottom-4 right-4 z-[500] w-11 h-11 bg-white dark:bg-slate-800 rounded-full shadow-lg border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-200 hover:text-cbtis-wine transition">
                <i class="fa-solid fa-location-crosshairs text-lg"></i>
            </button>
        </section>
    </main>

    <!-- ======================= MODAL: REPORTAR ======================= -->
    <div id="report-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[1000] hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-slate-100 dark:border-slate-700 animate-fade-in-up max-h-[92vh] overflow-y-auto custom-scrollbar">
            <div class="brand-modal-header text-white p-4 flex items-center justify-between sticky top-0">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-amber-400"></i>
                    <h3 class="font-bold text-sm">Reportar Inseguridad o Riesgo</h3>
                </div>
                <button onclick="closeReportModal()" class="text-slate-400 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form id="report-form" onsubmit="handleReportSubmit(event)" class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1"><i class="fa-solid fa-clipboard-list mr-1" aria-hidden="true"></i>Tipo de Reporte</label>
                    <div class="relative">
                        <i id="report-type-icon" class="fa-solid fa-bolt absolute left-3 top-1/2 -translate-y-1/2 text-cbtis-wine" aria-hidden="true"></i>
                        <select id="report-type" onchange="updateReportTypeIcon(this.value)" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl py-2.5 pl-9 pr-3 text-xs focus:ring-2 focus:ring-cbtis-wine outline-none">
                            <option value="Luz Fundida / Zona Oscura">Luminaria Fundida / Falta de Luz</option>
                            <option value="Presencia Escasa de Vía">Necesidad de Presencia Vial / Policía</option>
                            <option value="Zona Solitaria / Sospechosa">Zona Solitaria o Terreno Baldío</option>
                            <option value="Tráfico / Banqueta Obstruida">Banqueta Obstruida / Tráfico Pesado</option>
                            <option value="Acoso / Robo">Acoso o Intento de Robo</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1">Nivel de Riesgo</label>
                    <div class="grid grid-cols-3 gap-2" id="severity-group">
                        <button type="button" data-severity="baja" aria-pressed="false" onclick="setSeverity('baja')" class="severity-btn py-2 rounded-xl text-xs font-bold border-2 transition">Baja</button>
                        <button type="button" data-severity="media" aria-pressed="true" onclick="setSeverity('media')" class="severity-btn is-selected py-2 rounded-xl text-xs font-bold border-2 transition">Media</button>
                        <button type="button" data-severity="alta" aria-pressed="false" onclick="setSeverity('alta')" class="severity-btn py-2 rounded-xl text-xs font-bold border-2 transition">Alta</button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1">Ubicación / Referencia</label>
                    <input type="text" id="report-location" placeholder="Describe el lugar" required minlength="3" maxlength="255"
                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-cbtis-wine outline-none">
                    <div class="flex items-center justify-between mt-2">
                        <p id="coords-label" class="text-[10px] text-slate-400"><i class="fa-solid fa-location-dot mr-1" aria-hidden="true"></i><span id="coords-value">Ubicación requerida: selecciona GPS o marca el mapa</span></p>
                        <div class="flex space-x-1.5">
                            <button type="button" onclick="useMyLocation()" class="text-[10px] font-bold px-2 py-1 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-600 hover:bg-blue-100 transition">
                                <i class="fa-solid fa-location-crosshairs mr-1"></i>GPS
                            </button>
                            <button type="button" onclick="startMapPick()" class="text-[10px] font-bold px-2 py-1 rounded-lg bg-cbtis-wine/10 text-cbtis-wine hover:bg-cbtis-wine/20 transition">
                                <i class="fa-solid fa-map-pin mr-1"></i>Marcar en mapa
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1">Descripción breve</label>
                    <textarea id="report-description" rows="3" minlength="5" maxlength="1000" placeholder="Describe brevemente la situación para alertar a otros compañeros..." required
                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-cbtis-wine outline-none"></textarea>
                </div>

                <div class="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 rounded-xl p-3 flex items-start space-x-2">
                    <i class="fa-solid fa-circle-info text-amber-600 text-xs mt-0.5"></i>
                    <p class="text-[11px] text-amber-800 dark:text-amber-300 leading-tight">Tu nombre de usuario no se muestra en el mapa ni en los reportes. No incluyas nombres ni datos personales en la descripción. Si hay peligro inmediato, llama al 911.</p>
                </div>

                <div class="flex space-x-2 pt-1">
                    <button type="button" onclick="closeReportModal()" class="brand-secondary-button w-1/2 py-2.5 rounded-xl font-bold text-xs transition">Cancelar</button>
                    <button type="submit" id="submit-btn" class="brand-primary-button w-1/2 py-2.5 rounded-xl font-bold text-xs shadow-md transition">Publicar Reporte</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================= MODAL: CUENTA ======================= -->
    <div id="account-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[1000] hidden flex items-center justify-center p-4"
        onclick="closeAccountModalFromBackdrop(event)">
        <section class="brand-panel w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 sm:p-8" role="dialog" aria-modal="true"
            aria-labelledby="account-modal-title" aria-describedby="account-modal-description" tabindex="-1">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wide font-bold text-rose-900">Cuenta de usuario</p>
                    <h2 id="account-modal-title" class="brand-heading text-2xl font-extrabold mt-1">
                        Hola, <?= htmlspecialchars((string) $_SESSION['user_username'], ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                    <p id="account-modal-description" class="text-sm text-slate-500 mt-1">Aquí puedes consultar el estado de tus reportes.</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <form method="post" action="account.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($userCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="logout">
                        <button class="brand-secondary-button rounded-xl px-3 py-2 text-sm font-bold" type="submit">
                            <i class="fa-solid fa-right-from-bracket mr-1"></i><span class="hidden sm:inline">Cerrar sesión</span><span class="sm:hidden">Salir</span>
                        </button>
                    </form>
                    <button id="account-close-button" type="button" onclick="closeAccountModal()" aria-label="Cerrar cuenta"
                        class="brand-secondary-button w-10 h-10 rounded-xl text-lg font-bold">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <button type="button" onclick="closeAccountModal(); openReportModal()"
                class="brand-primary-button inline-flex items-center gap-2 rounded-xl px-4 py-3 mt-6 text-sm font-bold">
                <i class="fa-solid fa-plus"></i>Crear un reporte
            </button>

            <h3 class="brand-heading mt-8 mb-3 text-lg font-extrabold">Mi historial</h3>
            <div id="account-empty-state" class="brand-empty-state p-6 text-center" role="status">
                <i class="fa-solid fa-circle-notch fa-spin text-xl"></i>
                <p class="mt-2">Cargando historial…</p>
            </div>
            <div id="account-report-list" class="space-y-3">
            </div>
            <p class="text-xs text-slate-500 mt-6">
                <i class="fa-solid fa-lock mr-1"></i>Este historial solo es visible desde tu cuenta. Tu nombre no aparece en los reportes públicos.
            </p>
        </section>
    </div>

    <!-- ======================= MODAL: SOS ======================= -->
    <div id="sos-modal" class="fixed inset-0 bg-red-950/80 backdrop-blur-md z-[1000] hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-3xl shadow-2xl p-6 text-center border-2 border-red-500 animate-fade-in-up">
            <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl border border-red-200">
                <i class="fa-solid fa-phone-flip animate-bounce"></i>
            </div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white mb-1">Centro de Emergencias</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Líneas de respuesta inmediata para General Escobedo:</p>

            <div class="space-y-3">
                <a href="tel:911" class="w-full bg-red-600 hover:bg-red-700 text-white py-3 px-4 rounded-xl font-bold text-sm flex items-center justify-between shadow-lg shadow-red-200 transition">
                    <span class="flex items-center space-x-2"><i class="fa-solid fa-shield-halved"></i><span>Emergencias Nacionales</span></span>
                    <span class="bg-red-800 px-2 py-0.5 rounded text-xs">911</span>
                </a>
                <a href="tel:8182869200" class="brand-c4-button w-full text-white py-3 px-4 rounded-xl font-bold text-sm flex items-center justify-between shadow-md transition">
                    <span class="flex items-center space-x-2"><i class="fa-solid fa-building-shield"></i><span>C4 Escobedo (Policía)</span></span>
                    <span class="text-xs text-slate-400">81 8286 9200</span>
                </a>
                <a href="tel:8183971666" class="brand-secondary-button w-full py-3 px-4 rounded-xl font-bold text-sm flex items-center justify-between transition">
                    <span class="flex items-center space-x-2"><i class="fa-solid fa-school"></i><span>Dirección CBTis 258</span></span>
                    <span class="text-xs text-slate-500">Plantel</span>
                </a>
            </div>

            <button onclick="toggleSOSModal()" class="mt-6 text-xs text-slate-400 font-semibold hover:text-slate-600">Cerrar ventana</button>
        </div>
    </div>

    <!-- Toasts -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-[1200] space-y-2 w-72"></div>

    <script>
        window.RS_USER_AUTHENTICATED = <?= $userAuthenticated ? 'true' : 'false' ?>;
        window.RS_USER_CSRF_TOKEN = <?= json_encode($userCsrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="assets/js/route-routing.js?v=<?= filemtime(__DIR__ . '/assets/js/route-routing.js') ?>"></script>
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
    <?php if (($_GET['account'] ?? '') === '1' && $userAuthenticated): ?>
    <script>
        openAccountModal();
        const accountUrl = new URL(window.location.href);
        accountUrl.searchParams.delete('account');
        history.replaceState(null, '', accountUrl.pathname + accountUrl.search + accountUrl.hash);
    </script>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
