# Preguntas frecuentes

## El botón de passkey en login no responde

- Recargue la página sin caché.
- Confirme que `webauthn.js` carga sin error 404 (herramientas de desarrollo del navegador, pestaña Red).
- Plugin activado y modo que permita passkey en login.
- Si la passkey no es residente, ingrese el usuario GLPI antes del botón.

## Aparece el texto `true` como error

Respuesta API incorrecta. Revise la petición fallida en la pestaña Red y actualice el plugin.

## Challenge expirado (registro o autenticación)

- Más de unos minutos entre inicio y confirmación.
- Sesión interrumpida (otra pestaña, cookies bloqueadas, proxy).
- Use HTTPS y el mismo dominio que al registrar la passkey.

## Error 403 / CSRF inválido

- Recargue la página.
- No abra el flujo en varias pestañas.
- Revise extensiones que bloqueen cookies.

## No solicita verificación biométrica

- Ingrese usuario GLPI antes del botón si la passkey no es residente.
- Modo passwordless o clave residente según política.
- Usuario con passkeys registradas.

## Passkey registrada pero el login falla

- RP ID o dominio distinto al de registro.
- Entorno de prueba vs producción con hostname distinto.
- Reloj del servidor desincronizado (afecta TOTP).

## Plugin no configurado

Ejecute `composer install` en el directorio del plugin si falta `vendor/autoload.php`.

## Solo funciona en localhost

En producción se requiere HTTPS. RP ID suele resolverse automáticamente.

## LDAP o SSO deja de funcionar

El login primario no debe verse afectado. Desactive el plugin y revise logs de GLPI y del servidor web.

## Reporte de incidencias

Indique versión de GLPI, versión del plugin, navegador, modo configurado y el mensaje de error (sin datos personales).
