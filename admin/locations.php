<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/admin_auth.php';

startAdminSession();
if (!isAdminAuthenticated()) {
    header('Location: login.php');
    exit;
}

$csrf = createAdminCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ubicaciones | Rutas Seguras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/brand-theme.css?v=<?= filemtime(__DIR__ . '/../assets/css/brand-theme.css') ?>">
    <style>
        body { font-family: Inter, sans-serif; }
        #locations-map { height: min(68vh, 620px); min-height: 380px; }
        .location-marker { background: transparent; border: 0; }
    </style>
</head>
<body class="brand-page brand-admin-page min-h-screen bg-slate-100 text-slate-800">
    <header class="brand-header">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <img src="../punto_seguro.png" alt="" class="brand-logo">
                <div>
                    <h1 class="font-bold">Ubicaciones del mapa</h1>
                    <p class="text-xs">Plantel y puntos seguros</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="../index.php" class="brand-nav-link rounded-lg px-3 py-2 text-xs font-semibold">
                    <i class="fa-solid fa-map mr-1"></i><span class="hidden sm:inline">Ver mapa</span>
                </a>
                <a href="index.php" class="brand-nav-link rounded-lg px-3 py-2 text-xs font-semibold">
                    <i class="fa-solid fa-list-check mr-1"></i><span class="hidden sm:inline">Reportes</span>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(420px,1.1fr)]">
            <div class="space-y-5">
                <section class="brand-panel p-5">
                    <div class="mb-4">
                        <h2 class="brand-heading font-bold">Ubicación principal del plantel</h2>
                        <p class="text-xs text-slate-500 mt-1">Se muestra como marcador destacado en el mapa público.</p>
                    </div>
                    <form id="campus-form" class="space-y-3">
                        <label class="block text-xs font-semibold text-slate-600">Nombre
                            <input id="campus-name" required maxlength="120" value="CBTis 258" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </label>
                        <label class="block text-xs font-semibold text-slate-600">Dirección
                            <input id="campus-address" required minlength="3" maxlength="255" value="Calle Prof. Plinio Ordoñez 801, Hacienda del Topo, General Escobedo, Nuevo León, C.P. 66072" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </label>
                        <label class="block text-xs font-semibold text-slate-600">Descripción
                            <textarea id="campus-description" maxlength="1000" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-xs font-semibold text-slate-600">Latitud
                                <input id="campus-latitude" type="number" step="any" min="-90" max="90" required value="25.7877647" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </label>
                            <label class="block text-xs font-semibold text-slate-600">Longitud
                                <input id="campus-longitude" type="number" step="any" min="-180" max="180" required value="-100.3296582" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </label>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                            <button type="button" onclick="pickCoordinates('campus')" class="brand-secondary-button rounded-lg px-3 py-2 text-xs font-semibold">
                                <i class="fa-solid fa-map-pin mr-1"></i>Elegir en mapa
                            </button>
                            <button type="submit" class="brand-primary-button rounded-lg px-4 py-2 text-xs font-bold">
                                <i class="fa-solid fa-floppy-disk mr-1"></i>Guardar plantel
                            </button>
                        </div>
                    </form>
                </section>

                <section class="brand-panel p-5">
                    <div class="mb-4">
                        <h2 class="brand-heading font-bold">Agregar punto seguro</h2>
                        <p class="text-xs text-slate-500 mt-1">Los puntos guardados se marcarán en el mapa público.</p>
                    </div>
                    <form id="safe-point-form" class="space-y-3">
                        <label class="block text-xs font-semibold text-slate-600">Nombre del punto
                            <input id="safe-name" required minlength="2" maxlength="120" placeholder="Ej. Comercio o caseta" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </label>
                        <label class="block text-xs font-semibold text-slate-600">Dirección o referencia
                            <input id="safe-address" required minlength="3" maxlength="255" placeholder="Calle, cruce o referencia" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </label>
                        <label class="block text-xs font-semibold text-slate-600">Descripción
                            <textarea id="safe-description" maxlength="1000" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-xs font-semibold text-slate-600">Latitud
                                <input id="safe-latitude" type="number" step="any" min="-90" max="90" required placeholder="25.7877647" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </label>
                            <label class="block text-xs font-semibold text-slate-600">Longitud
                                <input id="safe-longitude" type="number" step="any" min="-180" max="180" required placeholder="-100.3296582" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </label>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                            <button type="button" onclick="pickCoordinates('safe')" class="brand-secondary-button rounded-lg px-3 py-2 text-xs font-semibold">
                                <i class="fa-solid fa-map-pin mr-1"></i>Elegir en mapa
                            </button>
                            <button type="submit" class="brand-primary-button rounded-lg px-4 py-2 text-xs font-bold">
                                <i class="fa-solid fa-plus mr-1"></i>Agregar punto
                            </button>
                        </div>
                    </form>
                </section>

                <section class="brand-panel p-5">
                    <div class="mb-4">
                        <h2 class="brand-heading font-bold">Trazar ruta segura al plantel</h2>
                        <p class="text-xs text-slate-500 mt-1">Marca puntos de referencia; la ruta se ajustará a las calles al terminar en el plantel.</p>
                    </div>
                    <form id="route-form" class="space-y-3">
                        <label class="block text-xs font-semibold text-slate-600">Nombre de la ruta
                            <input id="route-name" required minlength="2" maxlength="120" placeholder="Ej. Desde la parada principal" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </label>
                        <label class="block text-xs font-semibold text-slate-600">Descripción
                            <textarea id="route-description" maxlength="1000" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                        </label>
                        <label class="flex items-center justify-between gap-3 text-xs font-semibold text-slate-600">Color de la ruta
                            <input id="route-color" type="color" value="#059669" class="h-9 w-12 cursor-pointer rounded border border-slate-300 p-1">
                        </label>
                        <p id="route-draft-status" class="text-xs text-slate-500">Sin puntos trazados.</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="pickCoordinates('route')" class="brand-secondary-button rounded-lg px-3 py-2 text-xs font-semibold">
                                <i class="fa-solid fa-route mr-1"></i>Marcar puntos
                            </button>
                            <button id="finish-route-button" type="button" onclick="finishRouteAtCampus()" class="brand-secondary-button rounded-lg px-3 py-2 text-xs font-semibold">
                                <i class="fa-solid fa-school mr-1"></i>Ajustar y terminar
                            </button>
                            <button type="button" onclick="clearRouteDraft()" title="Borrar trazado actual" class="brand-secondary-button rounded-lg px-3 py-2 text-xs font-semibold">
                                <i class="fa-solid fa-eraser"></i>
                            </button>
                        </div>
                        <button type="submit" class="brand-primary-button w-full rounded-lg px-4 py-2.5 text-xs font-bold">
                            <i class="fa-solid fa-floppy-disk mr-1"></i>Guardar ruta
                        </button>
                    </form>
                </section>

                <section class="brand-panel p-5">
                    <h2 class="brand-heading font-bold mb-3">Puntos seguros guardados</h2>
                    <div id="safe-points-list" class="space-y-2">
                        <p class="text-sm text-slate-400">Cargando puntos...</p>
                    </div>
                </section>

                <section class="brand-panel p-5">
                    <h2 class="brand-heading font-bold mb-3">Rutas guardadas</h2>
                    <div id="safe-routes-list" class="space-y-2">
                        <p class="text-sm text-slate-400">Cargando rutas...</p>
                    </div>
                </section>
            </div>

            <section class="brand-panel p-4 h-fit xl:sticky xl:top-5">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div>
                        <h2 class="brand-heading font-bold">Mapa de selección</h2>
                        <p id="map-pick-status" class="text-xs text-slate-500 mt-1">Elige qué ubicación deseas fijar y haz clic en el mapa.</p>
                    </div>
                    <button type="button" onclick="cancelLocationPick()" title="Cancelar selección" class="brand-secondary-button w-9 h-9 rounded-lg">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div id="locations-map" class="rounded-lg overflow-hidden border border-slate-200"></div>
            </section>
        </div>
    </main>

    <div id="toast" role="status" class="fixed bottom-4 right-4 hidden max-w-sm rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-lg"></div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../assets/js/route-routing.js?v=<?= filemtime(__DIR__ . '/../assets/js/route-routing.js') ?>"></script>
    <script>
        const csrfToken = <?= json_encode($csrf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const apiUrl = '../api/locations.php';
        const fallbackCenter = [25.7877647, -100.3296582];
        let map;
        let savedMarkers;
        let savedRoutesLayer;
        let draftRouteLayer;
        let selectionMarker;
        let activeTarget = '';
        let campusLocation = null;
        let routeDraftPoints = [];
        let routeDraftAlignedPoints = [];
        let routeDraftFinished = false;
        let routeDraftUsedManualFallback = false;
        let routeAlignmentInProgress = false;

        document.addEventListener('DOMContentLoaded', () => {
            map = L.map('locations-map').setView(fallbackCenter, 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            savedMarkers = L.layerGroup().addTo(map);
            savedRoutesLayer = L.layerGroup().addTo(map);
            draftRouteLayer = L.layerGroup().addTo(map);
            map.on('click', event => {
                if (!activeTarget) return;
                if (activeTarget === 'route') {
                    addRouteDraftPoint(event.latlng);
                    return;
                }
                const target = activeTarget === 'campus' ? 'campus' : 'safe';
                document.getElementById(`${target}-latitude`).value = event.latlng.lat.toFixed(7);
                document.getElementById(`${target}-longitude`).value = event.latlng.lng.toFixed(7);
                if (selectionMarker) map.removeLayer(selectionMarker);
                selectionMarker = L.marker(event.latlng).addTo(map);
                cancelLocationPick();
            });

            document.getElementById('campus-form').addEventListener('submit', saveCampus);
            document.getElementById('safe-point-form').addEventListener('submit', createSafePoint);
            document.getElementById('route-form').addEventListener('submit', saveSafeRoute);
            document.getElementById('route-color').addEventListener('input', drawRouteDraft);
            loadLocations();
        });

        function pickCoordinates(target) {
            if (routeAlignmentInProgress) {
                showToast('Espera a que termine el ajuste de la ruta.', true);
                return;
            }
            activeTarget = target;
            const messages = {
                campus: 'Haz clic en el mapa para fijar el plantel.',
                safe: 'Haz clic en el mapa para fijar el punto seguro.',
                route: 'Haz clic en cada cruce o cambio de dirección para trazar la ruta.'
            };
            document.getElementById('map-pick-status').textContent = messages[target];
            document.getElementById('locations-map').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function cancelLocationPick() {
            activeTarget = '';
            document.getElementById('map-pick-status').textContent = 'Elige qué ubicación deseas fijar y haz clic en el mapa.';
        }

        async function loadLocations() {
            try {
                const response = await fetch(`${apiUrl}?action=list`);
                const result = await response.json();
                if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudieron cargar las ubicaciones');
                const { campus, safePoints } = result.data;
                campusLocation = campus;
                savedMarkers.clearLayers();
                savedRoutesLayer.clearLayers();
                savedRoutesLayer.clearLayers();
                if (campus) {
                    document.getElementById('campus-name').value = campus.name;
                    document.getElementById('campus-address').value = campus.address;
                    document.getElementById('campus-description').value = campus.description || '';
                    document.getElementById('campus-latitude').value = campus.latitude;
                    document.getElementById('campus-longitude').value = campus.longitude;
                    addLocationMarker(campus, 'fa-school', '#6b1426', 'Plantel');
                    map.setView([Number(campus.latitude), Number(campus.longitude)], 16);
                }
                safePoints.forEach(point => addLocationMarker(point, 'fa-shield-halved', '#15803d', 'Punto seguro'));
                renderSafePoints(safePoints);
                renderSafeRoutes(result.data.routes);
                const alignmentResults = await Promise.all(result.data.routes.map(async route => {
                    const line = L.polyline(route.points.map(point => [Number(point.latitude), Number(point.longitude)]), {
                        color: route.color, weight: 5, opacity: 0.8, lineCap: 'round', lineJoin: 'round'
                    }).addTo(savedRoutesLayer);
                    line.bindPopup(`<strong>${escapeHtml(route.name)}</strong><br>${escapeHtml(route.description)}`);
                    try {
                        const alignedPoints = await window.RSRouteRouting.align(route.points);
                        line.setLatLngs(alignedPoints.map(point => [point.latitude, point.longitude]));
                        return true;
                    } catch (error) {
                        console.warn(`No se pudo ajustar la ruta "${route.name}" a las calles:`, error);
                        return false;
                    }
                }));
                if (alignmentResults.some(aligned => !aligned)) {
                    showToast('No se pudieron ajustar todas las rutas a las calles; se muestran los trazados guardados.', true);
                }
            } catch (error) {
                showToast(error.message, true);
            }
        }

        function addLocationMarker(location, icon, color, label) {
            const marker = L.marker([Number(location.latitude), Number(location.longitude)], {
                icon: L.divIcon({
                    html: `<div style="background:${color}" class="w-9 h-9 rounded-full border-2 border-white shadow flex items-center justify-center text-white"><i class="fa-solid ${icon}"></i></div>`,
                    className: 'location-marker',
                    iconSize: [36, 36],
                    iconAnchor: [18, 18]
                })
            }).addTo(savedMarkers);
            marker.bindPopup(`<strong>${escapeHtml(label)}: ${escapeHtml(location.name)}</strong><br>${escapeHtml(location.address)}`);
        }

        function renderSafePoints(points) {
            const list = document.getElementById('safe-points-list');
            list.replaceChildren();
            if (!points.length) {
                list.innerHTML = '<p class="text-sm text-slate-400">Todavía no hay puntos seguros.</p>';
                return;
            }
            points.forEach(point => {
                const item = document.createElement('article');
                item.className = 'brand-list-item flex items-start justify-between gap-3 p-3';
                item.innerHTML = `<div class="min-w-0"><h3 class="text-sm font-bold">${escapeHtml(point.name)}</h3><p class="text-xs text-slate-500 mt-1">${escapeHtml(point.address)}</p><p class="text-xs text-slate-400 mt-1">${Number(point.latitude).toFixed(5)}, ${Number(point.longitude).toFixed(5)}</p></div>`;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'shrink-0 rounded-lg border border-red-200 px-2.5 py-2 text-xs font-semibold text-red-700 hover:bg-red-50';
                button.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
                button.title = 'Eliminar punto seguro';
                button.addEventListener('click', () => deleteSafePoint(point.id));
                item.appendChild(button);
                list.appendChild(item);
            });
        }

        function renderSafeRoutes(routes) {
            const list = document.getElementById('safe-routes-list');
            list.replaceChildren();
            if (!routes.length) {
                list.innerHTML = '<p class="text-sm text-slate-400">Todavía no hay rutas guardadas.</p>';
                return;
            }
            routes.forEach(route => {
                const item = document.createElement('article');
                item.className = 'brand-list-item flex items-start justify-between gap-3 p-3';
                item.innerHTML = `<div class="min-w-0"><h3 class="text-sm font-bold"><span class="mr-2 inline-block h-3 w-3 rounded-full align-middle" style="background:${escapeHtml(route.color)}"></span>${escapeHtml(route.name)}</h3><p class="text-xs text-slate-500 mt-1">${escapeHtml(route.description)}</p><p class="text-xs text-slate-400 mt-1">${route.points.length} puntos hasta el plantel</p></div>`;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'shrink-0 rounded-lg border border-red-200 px-2.5 py-2 text-xs font-semibold text-red-700 hover:bg-red-50';
                button.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
                button.title = 'Eliminar ruta';
                button.addEventListener('click', () => deleteSafeRoute(route.id));
                item.appendChild(button);
                list.appendChild(item);
            });
        }

        function addRouteDraftPoint(latlng) {
            routeDraftPoints.push({ latitude: latlng.lat, longitude: latlng.lng });
            routeDraftAlignedPoints = [];
            routeDraftFinished = false;
            routeDraftUsedManualFallback = false;
            drawRouteDraft();
            document.getElementById('route-draft-status').textContent = `${routeDraftPoints.length} puntos de referencia. Termina en el plantel para ajustar a las calles.`;
        }

        function drawRouteDraft() {
            draftRouteLayer.clearLayers();
            if (!routeDraftPoints.length) return;
            const displayPoints = routeDraftFinished && routeDraftAlignedPoints.length
                ? routeDraftAlignedPoints
                : routeDraftPoints;
            const coordinates = displayPoints.map(point => [point.latitude, point.longitude]);
            L.polyline(coordinates, {
                color: document.getElementById('route-color').value,
                weight: 5,
                opacity: 0.9,
                dashArray: routeDraftFinished ? null : '8, 8',
                lineCap: 'round',
                lineJoin: 'round'
            }).addTo(draftRouteLayer);
            if (!routeDraftFinished) {
                routeDraftPoints.forEach((point, index) => {
                    L.circleMarker([point.latitude, point.longitude], {
                        radius: index === 0 ? 6 : 4,
                        color: '#ffffff',
                        weight: 2,
                        fillColor: document.getElementById('route-color').value,
                        fillOpacity: 1
                    }).addTo(draftRouteLayer);
                });
            }
        }

        async function finishRouteAtCampus() {
            if (routeAlignmentInProgress) return;
            if (!campusLocation) {
                showToast('Primero guarda la ubicación principal del plantel', true);
                return;
            }
            if (!routeDraftPoints.length) {
                showToast('Marca al menos un punto inicial en el mapa', true);
                return;
            }
            const campusPoint = {
                latitude: Number(campusLocation.latitude),
                longitude: Number(campusLocation.longitude)
            };
            const lastPoint = routeDraftPoints[routeDraftPoints.length - 1];
            if (lastPoint.latitude !== campusPoint.latitude || lastPoint.longitude !== campusPoint.longitude) {
                routeDraftPoints.push(campusPoint);
            }
            routeDraftAlignedPoints = [];
            routeDraftFinished = false;
            routeDraftUsedManualFallback = false;
            activeTarget = '';
            drawRouteDraft();
            document.getElementById('route-draft-status').textContent = 'Ajustando el trazado a las calles...';
            document.getElementById('map-pick-status').textContent = 'Ajustando el trazado a las calles.';
            const finishButton = document.getElementById('finish-route-button');
            routeAlignmentInProgress = true;
            finishButton.disabled = true;
            try {
                routeDraftAlignedPoints = await window.RSRouteRouting.align(routeDraftPoints);
                routeDraftFinished = true;
                drawRouteDraft();
                document.getElementById('route-draft-status').textContent =
                    `Ruta ajustada a las calles: ${routeDraftAlignedPoints.length} puntos; el último es el plantel.`;
                document.getElementById('map-pick-status').textContent = 'Trazado ajustado y terminado en el plantel.';
            } catch (error) {
                routeDraftAlignedPoints = [];
                routeDraftFinished = true;
                routeDraftUsedManualFallback = true;
                drawRouteDraft();
                document.getElementById('route-draft-status').textContent = 'Se conserva el trazo manual; puedes reintentar o guardarlo con confirmación.';
                document.getElementById('map-pick-status').textContent = 'No se pudo ajustar; se conserva el trazo manual.';
                showToast(`No se pudo ajustar la ruta a las calles: ${error.message}`, true);
            } finally {
                routeAlignmentInProgress = false;
                finishButton.disabled = false;
            }
        }

        function clearRouteDraft() {
            if (routeAlignmentInProgress) {
                showToast('Espera a que termine el ajuste de la ruta.', true);
                return;
            }
            routeDraftPoints = [];
            routeDraftAlignedPoints = [];
            routeDraftFinished = false;
            routeDraftUsedManualFallback = false;
            activeTarget = '';
            draftRouteLayer.clearLayers();
            document.getElementById('route-draft-status').textContent = 'Sin puntos trazados.';
            document.getElementById('map-pick-status').textContent = 'Elige qué ubicación deseas fijar y haz clic en el mapa.';
        }

        async function saveCampus(event) {
            event.preventDefault();
            const form = event.currentTarget;
            const payload = {
                name: document.getElementById('campus-name').value.trim(),
                address: document.getElementById('campus-address').value.trim(),
                description: document.getElementById('campus-description').value.trim(),
                latitude: Number(document.getElementById('campus-latitude').value),
                longitude: Number(document.getElementById('campus-longitude').value)
            };
            await sendLocationAction('campus-save', payload, form, 'Ubicación principal guardada');
        }

        async function createSafePoint(event) {
            event.preventDefault();
            const form = event.currentTarget;
            const payload = {
                name: document.getElementById('safe-name').value.trim(),
                address: document.getElementById('safe-address').value.trim(),
                description: document.getElementById('safe-description').value.trim(),
                latitude: Number(document.getElementById('safe-latitude').value),
                longitude: Number(document.getElementById('safe-longitude').value)
            };
            await sendLocationAction('safe-point-create', payload, form, 'Punto seguro agregado');
        }

        async function deleteSafePoint(id) {
            if (!window.confirm('¿Eliminar este punto seguro?')) return;
            try {
                await postLocationAction('safe-point-delete', { id });
                await loadLocations();
                showToast('Punto seguro eliminado');
            } catch (error) {
                showToast(error.message, true);
            }
        }

        async function saveSafeRoute(event) {
            event.preventDefault();
            if (routeAlignmentInProgress) {
                showToast('Espera a que termine el ajuste de la ruta.', true);
                return;
            }
            if (!routeDraftFinished || routeDraftPoints.length < 2) {
                showToast('Traza la ruta y termínala en el plantel antes de guardar', true);
                return;
            }
            if (routeDraftUsedManualFallback &&
                !window.confirm('La ruta no se pudo ajustar a las calles. ¿Quieres guardar el trazado manual de todos modos?')) {
                return;
            }
            const form = event.currentTarget;
            const payload = {
                name: document.getElementById('route-name').value.trim(),
                description: document.getElementById('route-description').value.trim(),
                color: document.getElementById('route-color').value,
                points: routeDraftPoints.slice(0, -1)
            };
            const saved = await sendLocationAction('route-create', payload, form, 'Ruta segura guardada');
            if (saved) clearRouteDraft();
        }

        async function deleteSafeRoute(id) {
            if (!window.confirm('¿Eliminar esta ruta segura?')) return;
            try {
                await postLocationAction('route-delete', { id });
                await loadLocations();
                showToast('Ruta eliminada');
            } catch (error) {
                showToast(error.message, true);
            }
        }

        async function sendLocationAction(action, payload, form, message) {
            const submit = form.querySelector('[type="submit"]');
            submit.disabled = true;
            try {
                await postLocationAction(action, payload);
                form.reset();
                await loadLocations();
                showToast(message);
                return true;
            } catch (error) {
                showToast(error.message, true);
                return false;
            } finally {
                submit.disabled = false;
            }
        }

        async function postLocationAction(action, payload) {
            const response = await fetch(`${apiUrl}?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudo guardar');
            return result;
        }

        function showToast(message, error = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `fixed bottom-4 right-4 max-w-sm rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-lg ${error ? 'bg-red-600' : 'bg-emerald-700'}`;
            window.setTimeout(() => toast.classList.add('hidden'), 3200);
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, character => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            })[character]);
        }
    </script>
</body>
</html>
