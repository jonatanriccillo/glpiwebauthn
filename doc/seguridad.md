# Seguridad

## HTTPS

WebAuthn requiere origen seguro. En producción use TLS. El plugin valida HTTPS salvo entornos de desarrollo que GLPI reconozca.

## RP ID

Las credenciales quedan ligadas al dominio (RP ID). Por defecto se infiere de la URL de GLPI. Un valor manual incorrecto impide registro o login, o mezcla entornos.

## Almacenamiento

Solo claves públicas y metadatos en base de datos. Contador de firmas para detectar posible clonado de credencial.

## CSRF

Las peticiones POST incluyen el token CSRF de GLPI.

## Rate limiting

Límite configurable de intentos por ventana (registro por usuario, autenticación por IP en login anónimo).

## Attestation

Por defecto `none`. `direct` e `indirect` solo si la política de la organización lo exige.

## Revocación

Revogue passkeys comprometidas de inmediato. El factor primario (contraseña, LDAP, SSO) sigue vigente salvo en modo passwordless.

## Permisos

Conceda derechos de revocación solo a perfiles que lo requieran.

## Sesión

El challenge vive en la sesión PHP unos minutos. Las peticiones `options` y `verify` deben compartir la misma sesión del navegador.
