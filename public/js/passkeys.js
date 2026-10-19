// Fingerprint / Face ID (WebAuthn passkeys) in the browser: the server's options (binary values in base64url) turned
// into what the browser asks the device for, and the device's answer sent back in base64url. Used by the login page,
// the profile (registering this device) and the app's lock.
(function (global) {
    const toBuffer = (value) => {
        const base64 = value.replace(/-/g, '+').replace(/_/g, '/');
        const binary = atob(base64 + '='.repeat((4 - (base64.length % 4)) % 4));
        return Uint8Array.from(binary, (char) => char.charCodeAt(0)).buffer;
    };
    const toBase64Url = (buffer) => {
        let binary = '';
        new Uint8Array(buffer).forEach((byte) => { binary += String.fromCharCode(byte); });
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    };

    // POST as JSON; an error carries the server's message and the HTTP status.
    async function post(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(body || {}),
        });
        const json = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(json.errors ? Object.values(json.errors).flat()[0] : (json.message || ''));
            error.status = response.status;
            throw error;
        }
        return json;
    }

    const supported = () => !!(global.PublicKeyCredential && navigator.credentials && global.isSecureContext);

    // This device registered for the signed-in user (the current password first).
    async function register(optionsUrl, storeUrl, password, name) {
        const { publicKey } = await post(optionsUrl, { password });
        publicKey.challenge = toBuffer(publicKey.challenge);
        publicKey.user.id = toBuffer(publicKey.user.id);
        (publicKey.excludeCredentials || []).forEach((item) => { item.id = toBuffer(item.id); });
        const credential = await navigator.credentials.create({ publicKey });
        return post(storeUrl, {
            name,
            credential: {
                id: toBase64Url(credential.rawId),
                clientDataJSON: toBase64Url(credential.response.clientDataJSON),
                attestationObject: toBase64Url(credential.response.attestationObject),
            },
        });
    }

    // Signing in (or unlocking) with a passkey of this device.
    async function signIn(optionsUrl, signInUrl) {
        const { publicKey } = await post(optionsUrl);
        publicKey.challenge = toBuffer(publicKey.challenge);
        (publicKey.allowCredentials || []).forEach((item) => { item.id = toBuffer(item.id); });
        const credential = await navigator.credentials.get({ publicKey });
        return post(signInUrl, {
            credential: {
                id: toBase64Url(credential.rawId),
                clientDataJSON: toBase64Url(credential.response.clientDataJSON),
                authenticatorData: toBase64Url(credential.response.authenticatorData),
                signature: toBase64Url(credential.response.signature),
                userHandle: credential.response.userHandle ? toBase64Url(credential.response.userHandle) : null,
            },
        });
    }

    // A short message for what went wrong (cancelled on the device, not allowed, the server's own message).
    function message(error, texts) {
        if (error && (error.name === 'NotAllowedError' || error.name === 'AbortError')) return texts.cancelled;
        if (error && error.name === 'InvalidStateError') return texts.already;
        return (error && error.message) || texts.failed;
    }

    global.Passkeys = { supported, register, signIn, post, message };
})(window);
