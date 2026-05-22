# Guía del administrador

## Puesta en marcha

1. Instale el plugin ([instalacion.md](instalacion.md)).
2. Deje RP ID vacío salvo dominio distinto al de GLPI.
3. Active modo **Segundo factor (opcional)** para piloto.
4. Asigne derechos de lectura/actualización solo a perfiles que revoquen credenciales ajenas.
5. Registre una passkey de prueba y valide login y revocación.

## Revocación

**Administración → Usuarios** → usuario → pestaña **Passkeys** → **Revocar**.

Los usuarios pueden revocar las propias en Preferencias.

## Auditoría

- Registros en `glpi_plugin_webauthn_credentials` (nombre, fechas, estado).
- Logs del servidor web ante errores 4xx en la URL del plugin.

## Autenticación primaria

| Método | Efecto |
|--------|--------|
| Local GLPI | Passkey como segundo factor |
| LDAP / AD | Sin cambio en LDAP |
| CAS / SSO | Segundo factor en GLPI tras el IdP |
| OAuth | Igual que SSO |

Passwordless solo si el modo lo habilita explícitamente.

## Desactivación de emergencia

Desactive **Activado** en WebAuthn, o desinstale/desactive el plugin en **Configuración → Plugins**.

## Actualización

[instalacion.md](instalacion.md#actualización)
