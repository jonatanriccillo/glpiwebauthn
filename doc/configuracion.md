# Configuración

Panel: **Configuración → WebAuthn**.

| Parámetro | Descripción |
|-----------|-------------|
| Activado | Habilita o deshabilita el plugin |
| Modo | Ver modos más abajo |
| Orden del segundo factor | Tras el login primario: passkey antes que TOTP, o al revés |
| Reglas con TOTP y passkey | Passkey o TOTP alcanza, o ambos obligatorios si el usuario tiene TOTP |
| RP ID | Opcional; vacío usa el hostname de la URL de GLPI |
| RP Name | Nombre mostrado al registrar la passkey |
| Attestation | `none` (recomendado), `indirect` o `direct` |
| Exigir clave residente | Passkey discoverable; permite login sin escribir usuario |
| Máx. passkeys por usuario | Límite de credenciales activas |
| Rate limit | Intentos máximos y ventana en segundos |

## Modos

| Modo | Comportamiento |
|------|----------------|
| Desactivado | Instalado pero sin participar en login ni segundo factor |
| Segundo factor (opcional) | Passkey o TOTP tras el login primario; passwordless en login si aplica |
| Segundo factor (obligatorio) | Passkey obligatoria si el usuario tiene credenciales registradas |
| Passwordless | Login solo con passkey según perfil; suele requerir clave residente |

## Perfiles

En **Administración → Perfiles → WebAuthn**:

- Permitir passkeys
- Exigir registro de passkey

## Derechos del plugin

| Derecho | Uso |
|---------|-----|
| Lectura | Ver passkeys en la ficha de usuario |
| Actualizar | Revocar passkeys de otros usuarios |
| Configuración | Panel global WebAuthn |

Los usuarios registran las propias passkeys en **Preferencias** sin estos derechos.

## TOTP de GLPI

El plugin no sustituye LDAP, CAS, OAuth ni contraseña local. Interviene solo en el segundo factor: tras el login primario, GLPI solicita passkey o TOTP según el orden y las reglas configuradas.

## HTTPS

Obligatorio en producción. Con proxy inverso, el servidor debe exponer HTTPS a PHP (por ejemplo cabecera `X-Forwarded-Proto: https`).
