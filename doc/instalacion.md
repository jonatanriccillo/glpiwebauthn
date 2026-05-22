# Instalación

## Requisitos previos

- **GLPI 11.0** o superior (probado con 11.0.7)
- **PHP 8.2+** con extensiones `openssl` y `json`
- **HTTPS** en la URL que usan los usuarios (WebAuthn no funciona en HTTP salvo `localhost` en desarrollo)
- **Composer** en el servidor o en la máquina desde la que empaquetás el despliegue

## Obtener el código

```bash
git clone https://github.com/jonatanriccillo/glpiwebauthn.git
cd glpiwebauthn
```

O descargá el ZIP desde GitHub y descomprimilo.

## Dependencias PHP

Desde la raíz del plugin:

```bash
composer install --no-dev --optimize-autoloader
```

Debe existir `vendor/autoload.php`. Sin eso, GLPI muestra el plugin como *no configurado*.

## Ubicación en GLPI 11

En GLPI 11 el directorio de plugins es:

```text
/var/glpi/plugins/webauthn/
```

En instalaciones clásicas (sin layout `/var/glpi`):

```text
/var/www/html/glpi/plugins/webauthn/
```

La URL pública es `/plugins/webauthn/...`. El plugin usa `Plugin::getWebDir('webauthn')` para generar rutas correctas.

## Pasos en la interfaz

1. Copiá la carpeta `webauthn` al directorio `plugins/` del servidor.
2. Asegurá permisos de lectura para el usuario del servidor web (`www-data` u otro).
3. Entrá a **Configuración → Plugins**.
4. Buscá **WebAuthn** e instalá / activá el plugin.
5. Abrí la configuración del plugin (**Configuración → WebAuthn** o enlace desde la ficha del plugin).
6. Activá el plugin, definí **RP ID** y modo de operación (ver [configuracion.md](configuracion.md)).
7. En **Administración → Perfiles**, asigná el derecho **WebAuthn** donde corresponda.

## RP ID (importante)

El **RP ID** debe ser el dominio que ve el navegador, **sin** `https://`, **sin** puerto ni ruta.

| URL de acceso | RP ID correcto |
|---------------|----------------|
| `https://glpi.empresa.com/` | `glpi.empresa.com` |
| `https://glpi.empresa.com:8443/` | `glpi.empresa.com` |

Si dejás RP ID vacío, se deriva del host configurado en **Configuración → General → URL de la API**.

## Comprobación

- En **Preferencias → Passkeys** debe aparecer la pestaña (con el plugin activo y permisos).
- En la pantalla de login debe verse *Iniciar sesión con passkey* si el modo lo permite.
- La consola del navegador no debe mostrar 404 al cargar `.../public/webauthn.js`.

## Siguiente paso

[configuracion.md](configuracion.md)
