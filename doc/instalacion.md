# Instalación

## Requisitos

- GLPI 11.0 o superior
- PHP 8.2 o superior (`openssl`, `json`)
- HTTPS en la URL de acceso (excepto `localhost` en desarrollo)

## Instalación

1. Coloque la carpeta `webauthn` en `plugins/` de la instancia GLPI, con `setup.php` y `hook.php` en `plugins/webauthn/`.
2. En GLPI: **Configuración → Plugins** → Instalar → Activar.
3. **Configuración → WebAuthn**: modo y parámetros ([configuracion.md](configuracion.md)).
4. **Administración → Perfiles**: asigne el derecho WebAuthn si corresponde.

Si GLPI reporta dependencias faltantes, ejecute `composer install --no-dev` dentro de `plugins/webauthn`.

## RP ID

Opcional. Vacío: el plugin toma el hostname de la URL de GLPI. Complételo solo si el dominio visible para el usuario difiere (proxy u otro hostname). Solo hostname, sin protocolo, puerto ni ruta.

## Comprobación

- Pestaña **Passkeys** en Preferencias.
- Botón de passkey en login, según el modo configurado.

## Actualización

1. Reemplace los archivos en `plugins/webauthn`.
2. **Configuración → Plugins → Actualizar** y reactive si GLPI lo solicita.

Ver [CHANGELOG.md](../CHANGELOG.md).
