# Seguridad

## HTTPS

WebAuthn exige un **origen seguro**. En producción usá siempre TLS. El plugin valida HTTPS salvo entornos de desarrollo reconocidos por GLPI.

## RP ID y phishing

El **RP ID** ata las credenciales a tu dominio. Configuralo igual al hostname que ven los usuarios. Un RP ID incorrecto impide el registro o facilita confusión entre entornos distintos (prueba vs producción).

## Almacenamiento de credenciales

- Solo se guardan **claves públicas** y metadatos; nunca la clave privada del autenticador.
- `credential_id` y `user_handle` en binario en BD.
- Contador de firmas (`signCount`) para detectar posible **clonado** de credencial.

## CSRF

Las peticiones POST del plugin desde el navegador deben incluir el token CSRF de GLPI (cabecera `X-Glpi-Csrf-Token` y, donde aplique, cuerpo `_glpi_csrf_token`). GLPI 11 valida CSRF en controladores vía `CheckCsrfListener`.

## Rate limiting

Límite configurable de intentos por ventana de tiempo (por usuario en registro, por IP en autenticación anónima). Reduce fuerza bruta en endpoints de ceremonia.

## Attestation

Por defecto **`none`**: no se valida modelo de autenticador en registro. Modos `direct`/`indirect` solo si tu política de seguridad lo exige y tenés soporte de attestation en el servidor.

## Revocación

Ante robo o pérdida de dispositivo, revocá la passkey en GLPI de inmediato. La contraseña/LDAP/SSO primario sigue siendo un factor independiente salvo en modo passwordless.

## Permisos

Principio de mínimo privilegio: no otorgues derecho de revocación masiva sin necesidad. Los usuarios gestionan sus propias passkeys en Preferencias.

## Sesión y challenge

El challenge WebAuthn se guarda en la sesión PHP con expiración (~5 minutos). Las dos peticiones de cada ceremonia (`options` y `verify`) deben usar la misma sesión de navegador.

## Cumplimiento

Evaluá requisitos internos (ISO 27001, políticas de MFA corporativas). Este plugin complementa, no reemplaza, políticas de identidad centralizadas.
