# Changelog

## 1.0.1

### Añadido

- Dependencias PHP incluidas en el paquete (`vendor/`)
- Migración al actualizar sin borrar credenciales ni configuración
- Mensaje en GLPI cuando faltan dependencias del plugin

### Corregido

- Coherencia entre modo desactivado e interruptor Activado
- Segundo factor con passkey sin TOTP configurado en el usuario
- Carga de JavaScript solo en login, verificación passkey y pestaña Passkeys
- Carga de `webauthn.js` en la pantalla de verificación passkey
