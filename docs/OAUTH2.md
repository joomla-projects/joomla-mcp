# com_mcp Authentication (OAuth2 + legacy Bearer token)

`com_mcp`'s API endpoint authenticates MCP clients (Claude Code, Claude Desktop, etc.) using **either**:

1. **OAuth2** — signed JWT Bearer tokens issued by the **jCrafts OAuth2 Server**
   (`com_jcraftsoauth2server`), a league/oauth2-server-based OAuth2/OIDC authorization server already
   installed on this site. This is the preferred method going forward.
2. **Legacy static Bearer token** — the original pre-shared token stored per-client in `#__mcp.client_token`,
   still supported so existing MCP client configurations (and any workflow that can't do a browser-based
   OAuth2 authorization-code flow) keep working unchanged.

Both are tried, in that order, for every request — see [Architecture](#architecture).

## Why

The original implementation (`DemoAuthService`) checked a static, admin-generated pre-shared token
stored in `#__mcp.client_token`. That's a shared secret, not OAuth2 — no expiry enforcement beyond a
manual "Regenerate Token" button, no scopes, no standard authorization-code flow, and no way to revoke a
single client without regenerating the token for everyone using it. Since jCrafts OAuth2 Server was
already available on the site, OAuth2 support was added as the preferred method — but the static-token
path was kept in place (rather than removed) so it keeps working as a fallback for clients that were
already configured with a `client_token`, or that can't perform a browser-based OAuth2 flow.

## Architecture

```
MCP client (Claude Code, Claude Desktop, ...)
    │  Authorization: Bearer <token>   (JWT, or legacy static client_token)
    ▼
api/components/com_mcp/src/Core/McpEndpoint.php   (extracts the Bearer token, unchanged)
    │
    ▼
AuthServiceInterface::validateToken()
    │
    ▼
ChainAuthService   (api/components/com_mcp/src/Auth/ChainAuthService.php)
    │
    ├─▶ 1. JCraftsOAuth2AuthService   (api/components/com_mcp/src/Auth/JCraftsOAuth2AuthService.php)
    │        - Reads the RS256 public key from com_jcraftsoauth2server's component params
    │        - Builds a League ResourceServer via OAuth2ServerFactory::createResourceServer()
    │        - Validates the JWT (signature + expiry) — throws OAuthServerException if invalid
    │        - Reads oauth_user_id from the validated token claims
    │        - Loads the Joomla user, rejects if blocked / pending activation / requires password reset
    │        - ACL check: user must have the "mcp.access" permission on com_mcp
    │        - Returns TokenInfo::fromOAuth2($userId, $clientId), or null if any step above failed
    │
    └─▶ 2. DemoAuthService   (api/components/com_mcp/src/Auth/DemoAuthService.php) — tried only if (1) returned null
             - Looks up the raw token in #__mcp.client_token via McpModel::getByToken()
             - Returns TokenInfo::fromArray($row), or null if not found / not published
    │
    ▼
McpEndpoint sets the current user from TokenInfo->userid and serves the MCP request
(if both services return null, McpEndpoint responds 401 Unauthorized)
```

`McpController` and `McpEndpoint` required **no changes** — they already consumed authentication
generically via `AuthServiceInterface`, resolved from the DI container as `mcp.authService`.

## Files

| File | Change |
|---|---|
| `api/components/com_mcp/src/Auth/JCraftsOAuth2AuthService.php` | **New.** Implements `AuthServiceInterface` against jCrafts OAuth2 Server. |
| `api/components/com_mcp/src/Auth/ChainAuthService.php` | **New.** Tries a list of `AuthServiceInterface` implementations in order; returns the first success. |
| `api/components/com_mcp/src/Auth/TokenInfo.php` | Added `TokenInfo::fromOAuth2()` factory. |
| `administrator/components/com_mcp/access.xml` | Added the `mcp.access` ACL action. |
| `administrator/components/com_mcp/language/en-GB/com_mcp.ini` | Added `COM_MCP_ACTION_ACCESS` / `_DESC` strings. |
| `administrator/components/com_mcp/services/provider.php` | Binds `mcp.authService` to `ChainAuthService(JCraftsOAuth2AuthService, DemoAuthService)`. |

`DemoAuthService`, the `#__mcp` table, `McpTable`, `McpController::regenerateToken()`, and the admin
"MCP Clients" list/edit screens (`forms/mcp.xml`, `McpconfigField`) are **still actively used** — they
back the legacy static-token fallback path, tried second in the chain above.

The pre-existing `OAuthService`, `AccessTokenModel`, `OAuthCodeModel`, `OAuthClientModel` scaffolding
under `com_mcp` (an earlier, never-wired attempt at a bespoke OAuth2 server, unrelated to jCrafts) is
still unused dead code and was not touched.

## Setup

### OAuth2 path (recommended) — one-time, in jCrafts OAuth2 Server admin

1. **Key pair**: ensure com_jcraftsoauth2server has an RSA key pair generated and its `public_key`
   component param is populated (Console: `php cli/joomla.php jcraftsoauth2server:generate-keypair`, or
   the equivalent admin UI action).
2. **OAuth2 client**: create a client for the MCP integration (e.g. "Claude Code MCP") with grants
   `authorization_code` + `refresh_token`, and the redirect URI(s) your MCP client uses. Use PKCE for
   public clients (no client secret), or mark it confidential if the client can hold a secret.
3. **Permission**: in Joomla admin, go to **Components → MCP Server → Options → Permissions** and grant
   **Access MCP** (`mcp.access`) to the user group(s) that should be allowed to use MCP. This defaults to
   denied for everyone except Super Users.
4. **Authorize**: have the MCP client run the standard OAuth2 authorization-code (+ PKCE) flow against
   jCrafts' existing `/authorize` and `/token` endpoints (`com_jcraftsoauth2server`) to obtain an access
   token.

### Legacy Bearer token path — unchanged, no extra setup

Create/edit an MCP client under **Components → MCP Server → MCP Clients** as before; the generated
`client_token` continues to work exactly as it did previously — `ChainAuthService` falls back to it
whenever the token isn't a valid jCrafts JWT. No `mcp.access` permission check applies to this path (the
existing `#__mcp` row's `user_id`/`state` are the only gates, same as before this change).

## Verification

```bash
# Valid OAuth2 token → expect a normal MCP JSON-RPC response (e.g. tools/list), not 401
curl -H "Authorization: Bearer <jwt>" "https://<site>/api/index.php/v1/mcp" \
  -X POST -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}'

# Valid legacy client_token (from an MCP Clients row) → expect the same success response
curl -H "Authorization: Bearer <client_token>" "https://<site>/api/index.php/v1/mcp" ...

# Invalid/expired/unrecognised token (fails both services in the chain) → expect 401
curl -H "Authorization: Bearer garbage" "https://<site>/api/index.php/v1/mcp" ...

# Valid OAuth2 token for a user WITHOUT the mcp.access permission → expect 401
```

**End-to-end tested (2026-07-15)** on a local dev instance: created a public OAuth2 client via
`oauth2-server:client:create --public` in jCrafts, ran the browser authorization-code + PKCE flow against
its `/authorize` and `/token` endpoints (`v1/oauth2/authorize`, `v1/oauth2/token`), and confirmed the
issued JWT authenticates against `POST /api/index.php/v1/mcp` (HTTP 200, valid `initialize` response),
while a garbage token still gets HTTP 401. Two bugs were found and fixed/worked around during this test:

1. **Fixed** — `JCraftsOAuth2AuthService::validateToken()` had a real bug in the account-activation
   check: it used `trim((string) $user->activation) !== ''`, but Joomla stores the string `"0"` (not an
   empty string) in `activation` for already-active accounts, and `!== ''` doesn't special-case that —
   so every legitimately active user was rejected as "pending activation," silently falling through to
   the legacy path and producing a generic 401. Fixed to match the working pattern already used in
   `plg_api-authentication_jcraftsoauth2server`: `!empty(trim((string) $user->activation))` — PHP's
   `empty()` does treat `"0"` as empty, which is the behavior we actually want here.
2. **Known upstream bug, not fixed here** — `com_jcraftsoauth2server`'s
   `oauth2-server:client:create --public` console command computes `is_confidential` from the `--public`
   flag but never includes it in the row passed to `ClientTable::save()`
   (`plugins/console/jcraftsoauth2server/src/Command/CreateClientCommand.php`), so every client created
   via that command ends up confidential (`is_confidential=1`) in the DB regardless of `--public`. Workaround:
   create the client as confidential with an explicit secret (3rd CLI argument) and include `client_secret`
   in the `/token` request, or fix it via the client's admin-UI edit screen instead of the CLI. This is
   third-party code (`com_jcraftsoauth2server`), not part of this change — not patched here.

After adding/removing classes under `com_mcp`, delete `administrator/cache/autoload_psr4.php` to force
Joomla to regenerate its PSR-4 autoload map.

## Troubleshooting

- **Always 401, "Invalid or expired token"**: check `com_jcraftsoauth2server`'s `public_key` param is
  set — `JCraftsOAuth2AuthService::validateToken()` returns `null` immediately if it's empty. Note that
  `ChainAuthService` masks this: if the same token also happens to match a row in `#__mcp.client_token`
  it will still succeed via the legacy path, so an OAuth2 misconfiguration can go unnoticed until you
  test with a token that is *only* a JWT.
- **Token validates but MCP still 401s**: the user resolved from `oauth_user_id` is either
  blocked/unactivated/requires a password reset, or lacks the `mcp.access` permission — check
  **Options → Permissions** for the user's group. If the user genuinely is active or is a Super User,
  confirm `JCraftsOAuth2AuthService`'s activation check uses `!empty(trim(...))`, not `!== ''` — see the
  2026-07-15 fix note above; the old comparison rejects every active user because Joomla stores `"0"` in
  `activation` for active accounts.
- **`/token` returns `invalid_request` / "Check the `client_secret` parameter" for a client created with
  `--public`**: hit the upstream `oauth2-server:client:create --public` bug described above — the client
  was actually saved as confidential. Either re-create it with an explicit secret and send that secret to
  `/token`, or edit the client in the jCrafts admin UI and toggle it to public there.
- **`Class "League\OAuth2\Server\..." not found`**: the League vendor autoloader
  (`libraries/oauth2server4jcrafts/src/vendor/autoload.php`) wasn't loaded — it's required defensively
  in `JCraftsOAuth2AuthService`'s constructor (kept there, not in `provider.php`, so the DI wiring doesn't
  need to know about another extension's vendor layout); confirm `com_jcraftsoauth2server` is still
  installed (that library ships with it).
