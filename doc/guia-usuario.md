# Guía de usuario

## Registrar una passkey

1. Iniciá sesión en GLPI con tu usuario y contraseña (o SSO) habitual.
2. Andá a **Mis preferencias**.
3. Abrí la pestaña **Passkeys** (icono de huella).
4. Escribí un **nombre** reconocible (ej. *iPhone trabajo*, *YubiKey USB*).
5. Clic en **Agregar passkey**.
6. Seguí las instrucciones del navegador o del sistema (huella, PIN, llave de seguridad).

Podés registrar varias passkeys hasta el límite configurado por el administrador.

## Iniciar sesión con passkey (segundo factor)

1. Ingresá usuario y contraseña (o usá SSO) como siempre.
2. Si tu organización prioriza passkey, verás la pantalla de verificación con passkey.
3. Si no, podés ir a TOTP nativo desde el enlace alternativo.
4. Completá la verificación biométrica o con PIN/llave.

## Iniciar sesión solo con passkey (passwordless)

Si el administrador habilitó el modo adecuado y tu passkey es **residente** (discoverable):

1. En la pantalla de login, usá **Iniciar sesión con passkey**.
2. Elegí la credencial cuando el navegador lo pida.

Si tu passkey **no** es residente, primero escribí tu **usuario GLPI** en el campo de login y después el botón de passkey.

## Revocar una passkey

En **Mis preferencias → Passkeys**, usá **Revocar** junto a la credencial que ya no uses (dispositivo perdido, cambio de PC, etc.).

## Navegadores compatibles

- Chrome / Edge (Chromium)
- Firefox
- Safari (macOS, iOS)

En entornos de prueba podés usar autenticadores virtuales en las herramientas de desarrollo del navegador.

## Qué hacer si algo falla

Consultá [faq.md](faq.md) o contactá al administrador con el mensaje exacto que aparece en pantalla y la hora del intento.
