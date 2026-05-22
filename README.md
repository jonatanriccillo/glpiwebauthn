# WebAuthn para GLPI 11

Autenticación FIDO2 (passkeys) para GLPI 11: segundo factor con passkey o TOTP, login sin contraseña opcional y gestión de credenciales en Preferencias.

## Características

- Registro de passkeys por usuario
- Segundo factor integrado con GLPI
- Políticas por perfil
- Detección de clonado y límite de intentos
- Idiomas: español (Argentina, España) e inglés (Reino Unido)

## Requisitos

- GLPI 11.0.x
- PHP 8.2+ (`openssl`, `json`)
- HTTPS en producción

## Instalación

Copie la carpeta `webauthn` en `plugins/`, luego **Configuración → Plugins** → Instalar → Activar.

Documentación: [doc/README.md](doc/README.md)

## Licencia

GPLv3 — Copyright Jonatan Riccillo
