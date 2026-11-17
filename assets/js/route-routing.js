'use strict';

(() => {
    const ROUTER_URL = 'https://routing.openstreetmap.de/routed-foot/route/v1/driving';
    const MAX_COORDINATES = 100;
    const REQUEST_TIMEOUT_MS = 15000;

    async function routeSegment(points) {
        const coordinates = points.map(point => `${point.longitude},${point.latitude}`).join(';');
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

        try {
            const response = await fetch(
                `${ROUTER_URL}/${coordinates}?overview=full&geometries=geojson&steps=false`,
                {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal
                }
            );
            const result = await response.json();
            const route = result && Array.isArray(result.routes) ? result.routes[0] : null;
            if (!response.ok || !result || result.code !== 'Ok' || !route || !route.geometry || !Array.isArray(route.geometry.coordinates)) {
                throw new Error(result && result.message ? result.message : 'El servicio no pudo encontrar una ruta por calles.');
            }

            const routedPoints = route.geometry.coordinates.map(coordinate => {
                const longitude = Number(coordinate[0]);
                const latitude = Number(coordinate[1]);
                if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                    throw new Error('El servicio devolvió coordenadas no válidas.');
                }
                return { latitude, longitude };
            });
            if (routedPoints.length < 2) throw new Error('El servicio devolvió una ruta incompleta.');
            return routedPoints;
        } catch (error) {
            if (error && error.name === 'AbortError') {
                throw new Error('El servicio de rutas tardó demasiado en responder.');
            }
            throw error;
        } finally {
            clearTimeout(timeout);
        }
    }

    async function align(points) {
        if (!Array.isArray(points) || points.length < 2) {
            throw new Error('Se requieren al menos dos puntos para ajustar una ruta.');
        }

        const waypoints = points.map(point => {
            const latitude = Number(point.latitude);
            const longitude = Number(point.longitude);
            if (!Number.isFinite(latitude) || latitude < -90 || latitude > 90 ||
                !Number.isFinite(longitude) || longitude < -180 || longitude > 180) {
                throw new Error('La ruta contiene coordenadas no válidas.');
            }
            return { latitude, longitude };
        });

        const routedPoints = [];
        for (let start = 0; start < waypoints.length - 1; start += MAX_COORDINATES - 1) {
            const end = Math.min(start + MAX_COORDINATES - 1, waypoints.length - 1);
            const segment = await routeSegment(waypoints.slice(start, end + 1));
            routedPoints.push(...(start === 0 ? segment : segment.slice(1)));
        }

        return routedPoints;
    }

    window.RSRouteRouting = Object.freeze({ align });
})();
