# Rutas Seguras - CBTis 258 General Escobedo

Aplicación web responsiva para consultar el mapa, publicar reportes con ubicación y moderarlos desde una cuenta administrativa. Los reportes se guardan en MySQL.

## Estructura

```
index.php                 Página pública y mapa
api/reports.php           API JSON para reportes
api/locations.php         API de lectura y administración de ubicaciones
admin/login.php           Acceso administrativo
admin/index.php           Moderación (requiere iniciar sesión)
admin/locations.php       Edición del plantel y puntos seguros
config/database.php       Conexión PDO
config/admin_auth.php     Sesión y protección CSRF de administración
assets/css/style.css      Estilos responsivos y tema oscuro
assets/js/app.js          Mapa y funcionalidades interactivas
scripts/create_admin.php  Alta de administradores desde la consola
database.sql              Esquema sin reportes de ejemplo
```

## Instalación local (XAMPP en Windows)

1. Coloca el proyecto en `C:\xampp\htdocs\RUTA-SEGURA`.
2. Desde el panel de XAMPP inicia **Apache** y **MySQL**.
3. En `http://localhost/phpmyadmin`, abre la pestaña **SQL** e importa el contenido de `database.sql`.
4. Ejecuta la migración desde PowerShell o CMD para preparar las cuentas de usuario y asociar los reportes nuevos sin borrar los existentes:

   ```powershell
   cd C:\xampp\htdocs\RUTA-SEGURA
   C:\xampp\php\php.exe scripts\migrate_user_accounts.php
   ```

5. Verifica las credenciales de MySQL en `config/database.php`. El valor `root` sin contraseña es solo el predeterminado de XAMPP local; configura credenciales propias fuera de desarrollo.
6. Abre `http://localhost/RUTA-SEGURA/`. Inicia sesión con tu nombre de usuario para acceder al mapa y publicar reportes. Los administradores pueden ingresar desde ese mismo formulario con el correo usado al crear su cuenta y serán dirigidos al panel de administración.
7. Crea la cuenta administrativa desde PowerShell o CMD:

   ```powershell
   cd C:\xampp\htdocs\RUTA-SEGURA
   C:\xampp\php\php.exe scripts\create_admin.php
   ```

   Escribe un correo electrónico válido de hasta 80 caracteres y una contraseña de al menos 12 caracteres. La herramienta guarda solo el hash de contraseña. El correo puede aparecer en pantalla mientras se escribe; no uses la misma contraseña de otros servicios.
8. Las cuentas de usuario se crean desde **Regístrate aquí** en la página principal. Cada persona puede consultar solo su propio historial; su usuario no aparece en los reportes públicos. Los administradores se crean únicamente con el comando anterior y, al autenticarse, acceden a su panel separado.

No existe registro público de administradores. Para crear otro, vuelve a ejecutar el comando. Las cuentas se almacenan por separado en `users` y `admin_users`.

## Funcionalidades

- Mapa Leaflet/OpenStreetMap con ubicación del plantel y puntos seguros administrables; inicia sin marcadores precargados.
- En `admin/locations.php`, guardar el punto principal del plantel y agregar/eliminar puntos seguros; se pueden elegir en el mapa o ingresar coordenadas.
- En esa misma pantalla, trazar una ruta con clics sobre el mapa; al terminar se agrega el plantel como destino, y la ruta se guarda y publica con su color.
- Crear una cuenta de usuario, iniciar sesión y consultar el historial propio de reportes.
- Crear reportes con ubicación seleccionada en el mapa o GPS, tipo, nivel de riesgo y descripción; requiere iniciar sesión. El nombre del usuario no se publica.
- Guardar reportes y apoyos en MySQL; búsqueda, filtros, estadísticas y estado del reporte.
- Limitar la ubicación de cada reporte a 8 km del plantel.
- Marcar reportes como resueltos/reactivarlos únicamente desde el panel de administración protegido por contraseña.
- Inicio de sesión con contraseñas hash, cookie `HttpOnly`/`SameSite=Strict`, regeneración de sesión y token CSRF para acciones administrativas.
- Modo oscuro, botón SOS y contactos rápidos.

## API

Todas las respuestas están en JSON. Las acciones de escritura requieren `Content-Type: application/json`.

| Método | Endpoint | Acceso |
|---|---|---|
| GET | `api/reports.php?action=list&status=activo&type=...&q=...` | Público |
| GET | `api/reports.php?action=stats` | Público |
| POST | `api/reports.php?action=create` | Usuario autenticado; requiere sesión y CSRF; valida ubicación, campos y límites |
| POST | `api/reports.php?action=like` | Público |
| POST | `api/reports.php?action=resolve` | Solo administrador; requiere sesión y cabecera `X-CSRF-Token` |
| GET | `api/locations.php?action=list` | Público |
| POST | `api/locations.php?action=campus-save` | Solo administrador; requiere sesión y CSRF |
| POST | `api/locations.php?action=safe-point-create` | Solo administrador; requiere sesión y CSRF |
| POST | `api/locations.php?action=safe-point-delete` | Solo administrador; requiere sesión y CSRF |
| POST | `api/locations.php?action=route-create` | Solo administrador; requiere sesión y CSRF |
| POST | `api/locations.php?action=route-delete` | Solo administrador; requiere sesión y CSRF |

La aplicación necesita servir los archivos mediante Apache/PHP; abrir `index.php` directamente como archivo no activa el backend ni la conexión MySQL.
