# Configuración

## Panel global

**Configuración → WebAuthn** (o `plugins/webauthn/front/config.form.php`).

| Parámetro | Descripción |
|-----------|-------------|
| **Activado** | Interruptor maestro del plugin |
| **Modo** | Ver tabla siguiente |
| **Prioridad del prompt** | Tras login con contraseña/LDAP: mostrar passkey antes que TOTP o al revés |
| **Lógica MFA** | `OR`: basta passkey **o** TOTP. `AND`: ambos obligatorios si el usuario tiene TOTP |
| **RP ID** | Dominio WebAuthn (ver [instalacion.md](instalacion.md)) |
| **RP Name** | Nombre mostrado al registrar la passkey |
| **Attestation** | `none` (recomendado), `indirect` o `direct` |
| **Exigir clave residente** | Passkey almacenada en el autenticador (permite selector en login sin usuario) |
| **Máx. passkeys por usuario** | Límite de credenciales activas |
| **Rate limit** | Intentos máximos y ventana en segundos (registro y login) |

### Modos de operación

| Modo | Comportamiento |
|------|----------------|
| **Desactivado** | El plugin está instalado pero no participa en login ni MFA |
| **Segundo factor (opcional)** | Tras autenticación primaria, se puede usar passkey o TOTP; botón passwordless en login si hay passkeys compatibles |
| **Segundo factor (obligatorio)** | Si el usuario tiene passkeys registradas, debe completar WebAuthn en el flujo MFA |
| **Passwordless** | Permite login solo con passkey (y políticas de perfil); suele requerir claves residentes |

## Políticas por perfil

En cada **Perfil → pestaña WebAuthn**:

- **Permitir passkeys**: los usuarios de ese perfil pueden registrar passkeys.
- **Exigir registro de passkey**: refuerzo de política (según implementación de perfil).

Combiná con derechos GLPI del plugin.

## Derecho `plugin_webauthn`

| Nivel | Uso |
|-------|-----|
| **Lectura** | Ver passkeys de usuarios (pestaña en ficha Usuario) |
| **Actualizar** | Revocar passkeys de otros usuarios |
| **Configuración** | Acceso al panel global (suele reservarse a administradores) |

Los usuarios finales registran sus propias passkeys en **Preferencias** sin necesidad de este derecho de administración.

## Coexistencia con TOTP nativo de GLPI

El plugin **no reemplaza** LDAP, CAS, OAuth ni la contraseña local. Solo interviene en la fase MFA:

1. El usuario se autentica con el método primario habitual.
2. GLPI deja la sesión en `mfa_pre_auth`.
3. Según prioridad, redirige a passkey (`/plugins/webauthn/auth/prompt`) o al TOTP nativo (`/MFA/Prompt`).
4. Tras éxito, se marca `mfa_success` y se completa el login en `front/login.php`.

Con **lógica OR**, un solo factor satisfactorio (passkey **o** TOTP) alcanza. Con **AND**, si el usuario tiene TOTP habilitado, debe completar ambos.

## HTTPS

Obligatorio en producción. Detrás de proxy inverso, verificá que PHP detecte HTTPS (`X-Forwarded-Proto: https`) o WebAuthn rechazará la operación.
