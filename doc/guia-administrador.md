# Guía del administrador

## Puesta en marcha recomendada

1. Instalá el plugin y dependencias ([instalacion.md](instalacion.md)).
2. Configurá **RP ID** con el dominio real de producción.
3. Activá el plugin en modo **Segundo factor (opcional)** para una prueba piloto.
4. Asigná **WebAuthn → Lectura/Actualizar** solo a perfiles de soporte si deben revocar credenciales ajenas.
5. Registrá una passkey de prueba con un usuario admin.
6. Validá login completo (contraseña + passkey) y revocación.

## Revocar passkeys de un usuario

1. **Administración → Usuarios** → elegí el usuario.
2. Pestaña **Passkeys**.
3. **Revocar** en la credencial comprometida o obsoleta.

Los usuarios también pueden revocar las propias desde Preferencias.

## Auditoría

- Tabla `glpi_plugin_webauthn_credentials`: nombre, fechas de creación y último uso, estado activo.
- Logs de GLPI y del servidor web para errores 4xx en rutas `/plugins/webauthn/`.

## Escenarios por tipo de autenticación primaria

| Método primario | Impacto del plugin |
|-----------------|-------------------|
| Local GLPI | Sin cambios; MFA passkey después de contraseña |
| LDAP / AD | Sin cambios en bind LDAP; MFA igual |
| CAS / SSO | Sin cambios en el IdP; MFA en GLPI tras retorno |
| OAuth | Igual que SSO |

No se sustituye la contraseña corporativa salvo en modo **passwordless** explícito.

## Desactivación de emergencia

1. Desactivá el interruptor **Activado** en configuración WebAuthn, **o**
2. Desactivá el plugin en **Configuración → Plugins**.

Los usuarios podrán seguir entrando con TOTP/contraseña según la configuración nativa de GLPI.

## Actualización de versión

Ver [despliegue.md](despliegue.md). Tras copiar archivos nuevos, ejecutá migraciones reinstalando o usando el botón de actualización del plugin si aplica.
