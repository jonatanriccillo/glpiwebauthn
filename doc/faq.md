# Preguntas frecuentes y solución de problemas

## El botón de passkey en login no hace nada

- Forzá recarga (**Ctrl+F5**).
- Verificá en F12 → **Red** que se cargue `webauthn.js` sin 404.
- Comprobá que el plugin esté **activado** y el modo permita passwordless u opcional.
- En modo no-passwordless, ingresá **usuario GLPI** antes del botón si la passkey no es residente.

## Aparece el texto `true` como error

Suele ser una respuesta API mal formada. Revisá en F12 → **Red** el cuerpo de `/auth/options` o `/register/verify` y actualizá el plugin a la última versión publicada.

## `Authentication challenge expired` o `Registration challenge expired`

- Pasó el tiempo de expiración (~5 minutos) entre el inicio y la confirmación.
- La sesión no se mantuvo entre las dos peticiones (otra pestaña, cookies bloqueadas, proxy que rompe sesión).
- Solución: recargar la página, usar HTTPS y el mismo dominio que al registrar la passkey.

## Error 403 / Token CSRF inválido

- Recargá la página para obtener un token nuevo.
- No abras el flujo en varias pestañas a la vez.
- Comprobá que no haya extensiones del navegador que bloqueen cookies de sesión.

## No pide huella al iniciar sesión

- En modo con contraseña, ingresá **usuario GLPI** antes del botón de passkey si la credencial no es residente.
- Activá modo **passwordless** o **clave residente** si querés elegir la passkey sin escribir usuario.
- Verificá que el usuario tenga passkeys registradas.

## Passkey registrada pero login falla

- RP ID distinto al dominio actual.
- La passkey se registró en otro hostname (prueba vs producción).
- Reloj del servidor muy desincronizado (afecta TOTP; menos común en WebAuthn).

## Plugin “no configurado”

Ejecutá `composer install` dentro del plugin y comprobá `vendor/autoload.php`.

## Solo funciona en localhost

En producción hace falta **HTTPS** válido y RP ID correcto.

## LDAP / SSO deja de funcionar

El plugin no debe tocar el login primario. Si ocurre, desactivá el plugin y reportá el caso con logs; revisá hooks en `setup.php`.

## Dónde pedir ayuda

- Issues: [github.com/jonatanriccillo/glpiwebauthn](https://github.com/jonatanriccillo/glpiwebauthn/issues)
- Incluí versión GLPI, versión del plugin, navegador, modo configurado y captura de la petición fallida (sin datos personales).
