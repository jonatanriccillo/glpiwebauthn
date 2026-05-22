# Guía de usuario

## Registrar una passkey

1. Inicie sesión en GLPI.
2. **Mis preferencias → Passkeys**.
3. Asigne un nombre (por ejemplo, dispositivo o llave).
4. **Agregar passkey** y complete la verificación del navegador o del sistema.

El límite de passkeys lo define el administrador.

## Segundo factor con passkey

1. Ingrese usuario y contraseña (o SSO).
2. Complete la verificación passkey o use TOTP según la configuración de su organización.

## Login solo con passkey

Si está habilitado y la passkey es residente: **Iniciar sesión con passkey** en la pantalla de login.

Si no es residente, escriba el usuario GLPI y luego use el botón de passkey.

## Revocar

**Mis preferencias → Passkeys → Revocar** en la credencial que ya no use.

## Navegadores

Chrome, Edge, Firefox y Safari en versiones recientes con soporte WebAuthn.

## Problemas

Consulte [faq.md](faq.md) o contacte al administrador con el mensaje de error exacto.
