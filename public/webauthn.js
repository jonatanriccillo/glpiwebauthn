/**
 * GLPI WebAuthn plugin — client helpers (vanilla JS).
 */
(function (global) {
    'use strict';

    const ERROR_MAP = {
        NotAllowedError: 'Operación cancelada o no permitida.',
        SecurityError: 'Error de seguridad — revisá HTTPS y el dominio.',
        NotSupportedError: 'Este navegador no soporta WebAuthn.',
        InvalidStateError: 'Esta passkey ya está registrada.',
        UnknownError: 'Error desconocido de WebAuthn.',
    };

    function supportsWebAuthn() {
        return !!(global.PublicKeyCredential && navigator.credentials);
    }

    function b64ToBuffer(b64url) {
        const pad = '='.repeat((4 - (b64url.length % 4)) % 4);
        const b64 = (b64url + pad).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(b64);
        const buf = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i++) {
            buf[i] = raw.charCodeAt(i);
        }
        return buf.buffer;
    }

    function bufferToB64(buffer) {
        const bytes = new Uint8Array(buffer);
        let str = '';
        bytes.forEach((b) => { str += String.fromCharCode(b); });
        return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function decodeOptions(options) {
        const o = { ...options };
        if (o.challenge) {
            o.challenge = b64ToBuffer(o.challenge);
        }
        if (o.user && o.user.id) {
            o.user = { ...o.user, id: b64ToBuffer(o.user.id) };
        }
        if (Array.isArray(o.allowCredentials)) {
            if (o.allowCredentials.length === 0) {
                delete o.allowCredentials;
            } else {
                o.allowCredentials = o.allowCredentials.map((c) => ({
                    ...c,
                    id: b64ToBuffer(c.id),
                }));
            }
        }
        if (Array.isArray(o.excludeCredentials)) {
            o.excludeCredentials = o.excludeCredentials.map((c) => ({
                ...c,
                id: b64ToBuffer(c.id),
            }));
        }
        return o;
    }

    function credentialToJSON(cred) {
        const response = cred.response;
        const json = {
            id: cred.id,
            type: cred.type,
            rawId: bufferToB64(cred.rawId),
            response: {
                clientDataJSON: bufferToB64(response.clientDataJSON),
            },
        };
        if (response.attestationObject) {
            json.response.attestationObject = bufferToB64(response.attestationObject);
        }
        if (response.authenticatorData) {
            json.response.authenticatorData = bufferToB64(response.authenticatorData);
        }
        if (response.signature) {
            json.response.signature = bufferToB64(response.signature);
        }
        if (response.userHandle) {
            json.response.userHandle = bufferToB64(response.userHandle);
        }
        return json;
    }

    function resolveCsrfToken(opts) {
        if (opts?.csrf) {
            return opts.csrf;
        }
        if (typeof global.getAjaxCsrfToken === 'function') {
            const t = global.getAjaxCsrfToken();
            if (t) {
                return t;
            }
        }
        const meta = document.querySelector('meta[property="glpi:csrf_token"]');
        return meta ? meta.getAttribute('content') : null;
    }

    function apiError(data, fallback) {
        if (data === null || data === undefined) {
            return fallback;
        }
        if (typeof data === 'string') {
            return data;
        }
        if (typeof data === 'boolean') {
            return fallback;
        }
        const err = data.error;
        if (typeof err === 'string' && err !== '') {
            return err;
        }
        if (typeof err === 'boolean') {
            return fallback;
        }
        return fallback;
    }

    function showError(el, err) {
        const msg = ERROR_MAP[err?.name] || err?.message || (typeof err === 'string' ? err : String(err));
        if (el) {
            el.textContent = msg;
            el.classList.remove('d-none');
        } else {
            alert(msg);
        }
    }

    function clearError(el) {
        if (el) {
            el.textContent = '';
            el.classList.add('d-none');
        }
    }

    function setBusy(btn, busy) {
        if (!btn) {
            return;
        }
        btn.disabled = busy;
        btn.dataset.webauthnBusy = busy ? '1' : '';
    }

    function glpiFetchHeaders(csrf, extra) {
        const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...extra };
        if (csrf) {
            headers['X-Glpi-Csrf-Token'] = csrf;
        }
        return headers;
    }

    async function postJson(url, body, csrf) {
        const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: glpiFetchHeaders(csrf, { 'Content-Type': 'application/json' }),
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(apiError(data, res.statusText || 'Error de servidor'));
        }
        return data;
    }

    async function register(opts) {
        if (!supportsWebAuthn()) {
            throw new Error(ERROR_MAP.NotSupportedError);
        }
        const base = opts.pluginBase;
        const csrf = resolveCsrfToken(opts);
        const name = opts.name || document.getElementById('webauthn_cred_name')?.value?.trim();
        if (!name) {
            throw new Error('El nombre de la passkey es obligatorio.');
        }
        if (!csrf) {
            throw new Error('Token CSRF no disponible. Recargá la página.');
        }

        const fd = new FormData();
        fd.append('name', name);
        fd.append('_glpi_csrf_token', csrf);
        const optRes = await fetch(`${base}/register/options`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: glpiFetchHeaders(csrf),
            body: fd,
        });
        const optData = await optRes.json().catch(() => ({}));
        if (!optRes.ok) {
            throw new Error(optData.error || 'No se pudieron obtener las opciones de registro');
        }

        const pubKey = decodeOptions(optData.publicKey);
        const cred = await navigator.credentials.create({ publicKey: pubKey });
        const verify = await postJson(
            `${base}/register/verify`,
            { credential: credentialToJSON(cred), _glpi_csrf_token: csrf },
            csrf
        );
        if (verify.success) {
            global.location.reload();
        }
    }

    async function authenticate(opts) {
        if (!supportsWebAuthn()) {
            throw new Error(ERROR_MAP.NotSupportedError);
        }
        const base = opts.pluginBase;
        const csrf = resolveCsrfToken(opts);
        const body = {};
        if (opts.loginName) {
            body.login_name = opts.loginName;
        }

        const optRes = await fetch(`${base}/auth/options`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: glpiFetchHeaders(csrf, { 'Content-Type': 'application/json' }),
            body: JSON.stringify(body),
        });
        const optData = await optRes.json().catch(() => null);
        if (!optRes.ok) {
            throw new Error(apiError(optData, 'No se pudieron obtener las opciones de autenticación'));
        }
        if (!optData?.publicKey || typeof optData.publicKey !== 'object') {
            throw new Error(apiError(optData, 'Respuesta inválida del servidor (publicKey)'));
        }

        const pubKey = decodeOptions(optData.publicKey);
        const cred = await navigator.credentials.get({ publicKey: pubKey });
        if (!cred) {
            throw new Error('No se seleccionó ninguna passkey.');
        }

        const verify = await postJson(
            `${base}/auth/verify`,
            { credential: credentialToJSON(cred), _glpi_csrf_token: csrf },
            csrf
        );

        if (verify?.next) {
            global.location.href = verify.next;
        } else if (verify?.success) {
            global.location.reload();
        }
    }

    async function revoke(btn, opts) {
        const id = btn.dataset.id;
        const base = opts.pluginBase;
        const csrf = resolveCsrfToken(opts);
        if (!csrf) {
            throw new Error('Token CSRF no disponible. Recargá la página.');
        }
        const fd = new FormData();
        fd.append('_glpi_csrf_token', csrf);
        const res = await fetch(`${base}/credentials/${id}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: glpiFetchHeaders(csrf),
            body: fd,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.error || 'No se pudo revocar la passkey');
        }
        global.location.reload();
    }

    function prefsRoot(fromNode) {
        if (!fromNode) {
            return document.getElementById('webauthn-prefs');
        }
        return fromNode.closest
            ? fromNode.closest('#webauthn-prefs')
            : document.getElementById('webauthn-prefs');
    }

    function prefsOpts(root) {
        if (!root) {
            return null;
        }
        return {
            pluginBase: root.dataset.pluginBase,
        };
    }

    function loginErrorEl() {
        return document.getElementById('webauthn_login_error');
    }

    /** Clicks on login + preference UI (works after ajax tab load). */
    function bindClickDelegation() {
        document.addEventListener('click', (ev) => {
            const loginBtn = ev.target.closest('#webauthn_passwordless_btn');
            if (loginBtn) {
                ev.preventDefault();
                const base = loginBtn.dataset.pluginBase;
                if (!base) {
                    showError(loginErrorEl(), new Error('Configuración del plugin incompleta.'));
                    return;
                }
                if (loginBtn.dataset.webauthnBusy === '1') {
                    return;
                }
                const errEl = loginErrorEl();
                clearError(errEl);
                setBusy(loginBtn, true);
                authenticate({
                    pluginBase: base,
                    loginName: document.querySelector('[name="login_name"]')?.value?.trim() || '',
                })
                    .catch((e) => showError(errEl, e))
                    .finally(() => setBusy(loginBtn, false));
                return;
            }

            const regBtn = ev.target.closest('#webauthn_register_btn');
            if (regBtn) {
                ev.preventDefault();
                const root = prefsRoot(regBtn);
                const opts = prefsOpts(root);
                const errEl = document.getElementById('webauthn_error');
                if (!opts?.pluginBase) {
                    showError(errEl, new Error('Configuración del plugin incompleta.'));
                    return;
                }
                if (regBtn.dataset.webauthnBusy === '1') {
                    return;
                }
                clearError(errEl);
                setBusy(regBtn, true);
                register(opts)
                    .catch((e) => showError(errEl, e))
                    .finally(() => setBusy(regBtn, false));
                return;
            }

            const revokeBtn = ev.target.closest('.webauthn-revoke');
            if (revokeBtn) {
                ev.preventDefault();
                const root = prefsRoot(revokeBtn);
                const opts = prefsOpts(root);
                if (!opts?.pluginBase) {
                    alert('Configuración del plugin incompleta.');
                    return;
                }
                setBusy(revokeBtn, true);
                revoke(revokeBtn, opts)
                    .catch((e) => alert(e.message))
                    .finally(() => setBusy(revokeBtn, false));
            }
        });
    }

    bindClickDelegation();

    global.GlpiWebauthn = {
        supportsWebAuthn,
        register,
        authenticate,
        revoke,
    };
})(window);
