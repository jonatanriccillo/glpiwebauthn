# Despliegue

## Estructura en el servidor

```text
webauthn/
├── inc/              # Clases GLPI (config, credenciales, perfiles)
├── src/              # Servicios y controladores Symfony
├── public/           # webauthn.js
├── front/            # Configuración legacy
├── templates/        # Twig (prompt MFA)
├── locales/          # Traducciones
├── sql/              # Esquema inicial
├── vendor/           # Composer (no commitear si publicás sin vendor)
└── doc/              # Esta documentación
```

## GLPI 11 en Docker (imagen oficial)

Ruta típica en el contenedor:

```text
/var/glpi/plugins/webauthn/
```

Ejemplo de despliegue (reemplazá `<contenedor>` por el nombre de tu contenedor GLPI):

```bash
# Desde tu PC
scp -r webauthn/ usuario@servidor:/tmp/webauthn_upload/

# En el servidor
docker cp /tmp/webauthn_upload/webauthn <contenedor>:/var/glpi/plugins/webauthn
docker exec -u root <contenedor> chown -R www-data:www-data /var/glpi/plugins/webauthn
docker exec <contenedor> bash -c 'cd /var/glpi/plugins/webauthn && composer install --no-dev --optimize-autoloader'
```

## Proxy inverso (Caddy, nginx, Apache)

- Terminación TLS en el proxy.
- Cabecera `X-Forwarded-Proto: https` hacia GLPI.
- El **RP ID** debe coincidir con el hostname público, no con el nombre interno del contenedor.

## Compilar traducciones

Si editás archivos `.po` (requiere `gettext` / `msgfmt`):

```bash
cd locales
for f in *.po; do msgfmt -o "${f%.po}.mo" "$f"; done
```

## Publicar en GitHub

El repositorio oficial es [github.com/jonatanriccillo/glpiwebauthn](https://github.com/jonatanriccillo/glpiwebauthn).

Recomendación para el repositorio:

- No subir `vendor/` (usar Composer en cada despliegue).
- Incluir `composer.lock` para builds reproducibles.
- Etiquetar releases `v1.0.0` alineadas con `PLUGIN_WEBAUTHN_VERSION` en `setup.php`.

## Actualización

1. Respaldo de tablas `glpi_plugin_webauthn_*`.
2. Reemplazo de archivos del plugin (excepto `vendor` si preferís reinstalar dependencias).
3. `composer install` si cambió `composer.json`.
4. En GLPI: actualizar plugin desde la interfaz o reinstalar para ejecutar migraciones en `hook.php`.
