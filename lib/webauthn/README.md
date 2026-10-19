# lbuchs/WebAuthn 2.2.0 (bundled)

WebAuthn (passkeys) server library used for "sign in with fingerprint / Face ID" on the admin.

- Source: https://github.com/lbuchs/WebAuthn — tag `v2.2.0` (MIT license, see `LICENSE`).
- Archive: https://github.com/lbuchs/WebAuthn/archive/refs/tags/v2.2.0.tar.gz
  sha256 `33eb82b72a1c22034a8e7bf0faf0b7c30eb125705e2ce1095121b0440329d38a`
- Only `src/` and `LICENSE` are kept, unchanged.

It lives in the repository (not in `vendor/`) because the production server has no Composer: the deploy only
pulls the repository. The classes are loaded by `App\Providers\AppServiceProvider` (`lbuchs\WebAuthn\` →
`lib/webauthn/src/`). To update: download a newer tag, check its changes, replace `src/` and this file.
