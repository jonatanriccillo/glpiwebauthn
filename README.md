# WebAuthn para GLPI 11

Plugin de autenticación **FIDO2 / passkeys** para [GLPI](https://glpi-project.org) 11: segundo factor tras LDAP/CAS/SSO o login local, login sin contraseña opcional y gestión de credenciales desde Preferencias.

**Sitio del proyecto:** [https://github.com/jonatanriccillo/glpiwebauthn](https://github.com/jonatanriccillo/glpiwebauthn)

## Características

- Registro de passkeys por usuario (Preferencias → Passkeys)
- Segundo factor integrado con el flujo MFA nativo de GLPI (`mfa_pre_auth` / TOTP)
- Botón *Iniciar sesión con passkey* en la pantalla de login (modos configurables)
- Políticas por perfil (permitir / exigir passkeys)
- Detección de clonado por contador de firmas y límite de intentos
- Interfaz en español (`es_AR`, `es_ES`) e inglés (`en_GB`)

## Requisitos

| Componente | Versión |
|------------|---------|
| GLPI | 11.0.x |
| PHP | 8.2+ (`openssl`, `json`) |
| HTTPS | Obligatorio en producción |
| Composer | Para instalar `web-auth/webauthn-lib` |

## Instalación rápida

```bash
# Dentro del directorio del plugin
composer install --no-dev --optimize-autoloader
```

Copiá el plugin en `plugins/webauthn`, instalalo desde **Configuración → Plugins**, activá WebAuthn y configurá el **RP ID** (dominio sin puerto ni `https://`).

Documentación completa en español: **[doc/README.md](doc/README.md)**

## Licencia

[GPLv3](LICENSE) — Copyright Jonatan Riccillo
