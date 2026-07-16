# `com_mcp` authentication and authorisation

## Status

This document defines the target authentication and authorisation architecture for `com_mcp`.

It assumes that a valid OAuth 2.1 Authorization Server exists as an independent Joomla component.
The Authorization Server is responsible for OAuth clients, user consent, authorisation codes, access
tokens, refresh tokens, revocation and Authorization Server metadata.

`com_mcp` is an OAuth **Resource Server**. It does not implement an Authorization Server and does not
manage OAuth clients, authorisation codes or refresh tokens.

The legacy static `client_token` mechanism is not part of the target architecture.

## Key design decisions

Compared with the transitional implementation, this design makes the following decisions:

- `com_mcp` is a standards-based Resource Server and does not import the Authorization Server's
  internal PHP classes, database tables, component parameters or vendor autoloader.
- Access tokens are validated for issuer, resource-specific audience, token type, expiry and scopes;
  validating only signature and expiry is insufficient.
- The OAuth subject is resolved through an explicit adapter and is not assumed to be a Joomla user ID
  or a vendor-specific `oauth_user_id` claim.
- A validated principal and Joomla user are passed explicitly through the tool and operation chain.
- OAuth scopes, MCP exposure policy and Joomla ACL are separate authorisation layers.
- Invalid OAuth credentials do not fall through silently to a second Bearer-token format.
- `com_mcp` publishes OAuth Protected Resource Metadata and standards-compliant authentication
  challenges.

## Roles

The architecture separates four roles:

1. **MCP client**  
   Claude Code, Claude Desktop or another MCP client.

2. **OAuth Authorization Server**  
   Authenticates the Joomla user, obtains consent and issues an access token for the MCP resource.

3. **`com_mcp` Resource Server**  
   Validates the access token, creates the authenticated MCP request context, enforces scopes and
   dispatches MCP requests.

4. **Joomla components and controllers**  
   Execute operations under the resolved Joomla user identity and enforce Joomla ACL rules.

OAuth authorisation and Joomla ACL are complementary:

- OAuth scopes limit what the client has been authorised to request.
- Joomla ACL determines what the represented user is currently permitted to do.
- Neither mechanism replaces the other.

## Resource identity

The MCP endpoint has one canonical OAuth resource identifier:

```text
https://example.org/api/index.php/v1/mcp
```

The production value must be configured explicitly and must not be derived from untrusted `Host`,
`Forwarded` or `X-Forwarded-*` request headers.

The same resource identifier is used:

- as the `resource` value in OAuth authorisation and token requests;
- as the access-token audience;
- as the `resource` value in OAuth Protected Resource Metadata;
- as the expected resource passed to the access-token validator.

An access token issued for another resource must be rejected, even if it was issued by the trusted
Authorization Server.

## Architecture

```text
MCP client
    │
    │ 1. Discovers Protected Resource Metadata
    │ 2. Completes OAuth authorisation-code flow with PKCE
    │ 3. Receives an access token for the MCP resource
    │
    │ Authorization: Bearer <access-token>
    ▼
com_mcp Resource Server
    │
    ├── extracts the Bearer token from the Authorization header
    ├── validates issuer, audience, expiry, token type and signature or introspection result
    ├── resolves the OAuth subject to an active Joomla user
    ├── creates an AccessTokenPrincipal
    ├── creates a McpRequestContext
    ├── checks MCP resource and operation scopes
    └── dispatches the MCP request
            │
            ▼
WebserviceTool / InternalApiOperationInvoker
            │
            ├── receives the explicit McpRequestContext
            ├── loads the resolved Joomla user into the internal API application
            └── invokes the existing Joomla component controller
                    │
                    ▼
                Joomla ACL
```

No access token is forwarded to an internal REST endpoint. The internal operation chain receives an
authenticated principal and Joomla user identity, not the raw credential.

## OAuth discovery

### Protected Resource Metadata

`com_mcp` must publish OAuth 2.0 Protected Resource Metadata as required by RFC 9728 and the MCP
authorisation specification.

For the example MCP resource, the endpoint-specific well-known URI is:

```text
https://example.org/.well-known/oauth-protected-resource/api/index.php/v1/mcp
```

A root metadata endpoint may additionally be provided when the site exposes only one OAuth-protected
resource:

```text
https://example.org/.well-known/oauth-protected-resource
```

Example metadata:

```json
{
  "resource": "https://example.org/api/index.php/v1/mcp",
  "authorization_servers": [
    "https://example.org/oauth"
  ],
  "scopes_supported": [
    "mcp:use",
    "content.articles:read",
    "content.articles:write",
    "content.articles:delete"
  ],
  "bearer_methods_supported": [
    "header"
  ],
  "resource_documentation": "https://example.org/mcp/documentation"
}
```

The `authorization_servers` value contains the issuer URI of the independent Authorization Server.
The MCP client uses the issuer's Authorization Server Metadata or OpenID Connect Discovery document
to discover its authorisation, token and registration endpoints.

### Authorisation flow

The normal interactive flow is:

```text
1. MCP client calls the MCP endpoint without an access token.
2. com_mcp returns 401 and advertises its Protected Resource Metadata.
3. The client reads the metadata and discovers the Authorization Server.
4. The client starts an Authorization Code flow with PKCE S256.
5. The authorisation request includes:
       resource=https://example.org/api/index.php/v1/mcp
       scope=mcp:use content.articles:read ...
6. The user authenticates with Joomla and grants consent.
7. The client exchanges the code at the token endpoint.
8. The token request repeats the same resource value.
9. The Authorization Server issues an access token whose audience is the MCP resource.
10. The client sends the token only to the MCP endpoint.
```

### Authentication challenge

When a request contains no access token, `com_mcp` returns `401 Unauthorized` and advertises the
Protected Resource Metadata location:

```http
HTTP/1.1 401 Unauthorized
WWW-Authenticate: Bearer resource_metadata="https://example.org/.well-known/oauth-protected-resource/api/index.php/v1/mcp", scope="mcp:use"
```

Tokens must be accepted only through the `Authorization` header. Query-string tokens and form-body
tokens are not supported.

## Authorization Server requirements

The independent Authorization Server must provide the following capabilities.

### Required

- OAuth 2.1 security behaviour for public and confidential clients.
- Authorization Code Grant.
- PKCE with `S256`; `plain` must not be accepted.
- Authorization Server Metadata according to RFC 8414 or OpenID Connect Discovery.
- Resource Indicators according to RFC 8707.
- Access tokens bound to the MCP resource.
- Explicit scope grants.
- Exact redirect-URI validation.
- Token revocation.
- A stable OAuth subject that can be resolved to a Joomla user.
- TLS for all non-loopback endpoints.

### Recommended

- Refresh-token rotation for public clients.
- OAuth Client ID Metadata Documents.
- Dynamic Client Registration where operationally appropriate.
- Short-lived access tokens.
- Key rotation and a published JWKS when JWT access tokens are used.
- Audit records for client registration, grants, token issuance and revocation.

### Not supported

- Implicit Grant.
- Resource Owner Password Credentials Grant.
- Direct or manually generated OAuth access tokens that bypass a grant.
- PKCE `plain`.
- Wildcard redirect URIs.
- Access tokens without a resource-specific audience.

OpenID Connect may be supported by the Authorization Server, but `com_mcp` validates OAuth access
tokens. It must never accept an OpenID Connect ID token as an access token.

## Token format and validation

`com_mcp` depends on an `AccessTokenValidatorInterface`, not on the internal classes, database tables,
configuration or vendor autoloader of a particular Authorization Server component.

```php
interface AccessTokenValidatorInterface
{
    /**
     * Validates an access token for the expected OAuth resource.
     *
     * @throws TokenValidationException
     */
    public function validate(
        string $accessToken,
        ResourceIdentifier $expectedResource,
    ): AccessTokenPrincipal;
}
```

The concrete validator may support one or both of the following standard mechanisms.

### JWT access tokens

JWT access tokens should conform to RFC 9068. Validation must include at least:

- a permitted signing algorithm;
- a valid signature;
- `typ` identifying an access token, preferably `at+jwt`;
- exact trusted `iss`;
- `aud` containing the canonical MCP resource identifier;
- valid `exp`;
- valid `nbf`, when present;
- reasonable `iat`, when present;
- a stable `sub`;
- a client identifier;
- the granted OAuth scopes;
- rejection of unsigned tokens and ID tokens.

Signing keys should be loaded from the Authorization Server's published JWKS and cached with support
for key rotation. A pinned public key may be used only as a deployment-specific adapter, not as the
core contract between `com_mcp` and the Authorization Server.

### Opaque access tokens

When opaque tokens are used, the Authorization Server must provide a standards-compliant token
introspection endpoint. The introspection result must be active and must contain or resolve:

- issuer;
- subject;
- client identifier;
- audience or resource;
- scopes;
- expiry.

The audience or resource must match the canonical MCP resource identifier.

## Principal and Joomla subject resolution

Successful token validation produces an immutable principal:

```php
final readonly class AccessTokenPrincipal
{
    /**
     * @param list<string> $audiences
     * @param list<string> $scopes
     * @param list<string> $authenticationMethods
     */
    public function __construct(
        public string $issuer,
        public string $subject,
        public string $clientId,
        public array $audiences,
        public array $scopes,
        public ?DateTimeImmutable $issuedAt,
        public DateTimeImmutable $expiresAt,
        public ?string $tokenId = null,
        public ?DateTimeImmutable $authenticatedAt = null,
        public array $authenticationMethods = [],
    ) {
    }
}
```

The OAuth `sub` claim is the authoritative external subject. Core `com_mcp` code must not depend on a
vendor-specific claim such as `oauth_user_id`.

A separate resolver maps the issuer and subject to a Joomla user:

```php
interface JoomlaSubjectResolverInterface
{
    /**
     * Resolves an OAuth subject to an active Joomla user.
     *
     * @throws SubjectResolutionException
     */
    public function resolve(
        string $issuer,
        string $subject,
    ): User;
}
```

A deployment-specific adapter may use a private Joomla user claim when the Authorization Server
provides one, but that claim name must not leak into the Resource Server contract.

The resolved user must exist and must not be blocked, pending activation or required to reset the
password. If the subject no longer maps to an active Joomla user, the access token is treated as
invalid for this resource.

## MCP request context

After validation and subject resolution, `com_mcp` creates an explicit request context:

```php
final readonly class McpRequestContext
{
    public function __construct(
        public AccessTokenPrincipal $principal,
        public User $user,
        public ResourceIdentifier $resource,
        public string $requestId,
    ) {
    }
}
```

The raw access token is not stored in this context.

The context must be passed through the entire execution chain:

```php
interface ToolInterface
{
    public function getName(): string;

    public function getSchema(): array;

    public function execute(
        array $arguments,
        McpRequestContext $context,
    ): CallToolResult;
}
```

```php
interface OperationInvokerInterface
{
    public function invoke(
        OperationDefinition $operation,
        array $arguments,
        McpRequestContext $context,
    ): OperationResult;
}
```

The internal API application must load the user explicitly:

```php
$internalApplication->loadIdentity($context->user);
```

It must not rely on an identity stored only in `McpEndpoint`, a static context or an unrelated parent
application.

## Authorisation model

An operation may run only when all three conditions are satisfied:

```text
operation is exposed to MCP
    AND
access token contains the required OAuth scopes
    AND
the resolved Joomla user passes the applicable Joomla ACL checks
```

### MCP exposure

Only operations explicitly exposed to MCP are registered as tools. OAuth does not make an operation
available automatically.

### OAuth scopes

The operation compiler derives default scopes by convention:

| Operation | Default scope |
|---|---|
| list, get | `<component>.<resource>:read` |
| create, update | `<component>.<resource>:write` |
| delete | `<component>.<resource>:delete` |

For the article resource:

```text
content.articles:read
content.articles:write
content.articles:delete
```

A base scope such as `mcp:use` may additionally be required for every MCP request.

Operations with special semantics may override the convention:

```php
#[Operation(scopes: ['content.articles:publish'])]
public function publish(int $id): Article;
```

The number of scopes should remain manageable. A separate scope for every ordinary CRUD method is not
recommended.

### Joomla ACL

The existing Joomla component and controller remain authoritative for resource-level access decisions.

Examples include:

```text
core.create on com_content
core.edit on com_content.article.<id>
core.edit.own on com_content.article.<id>
core.delete on com_content.article.<id>
core.edit.state on com_content.article.<id>
```

The `mcp.access` ACL action may be retained as a coarse Joomla eligibility check, but it is neither an
OAuth scope nor a replacement for operation-specific Joomla ACL checks.

### Tool discovery

`tools/list` should filter tools by:

- MCP exposure policy;
- scopes granted to the access token;
- coarse component-level ACL where this can be determined safely.

Object-level ACL must always be checked again when `tools/call` executes the operation.

## Error behaviour

Authentication and authorisation failures must be distinguished.

### Missing or invalid token

Return `401 Unauthorized`.

```http
HTTP/1.1 401 Unauthorized
WWW-Authenticate: Bearer error="invalid_token", resource_metadata="https://example.org/.well-known/oauth-protected-resource/api/index.php/v1/mcp"
```

Examples:

- malformed token;
- invalid signature;
- untrusted issuer;
- incorrect audience;
- expired token;
- unresolved or disabled Joomla subject.

When no token was supplied, the `error` parameter may be omitted.

### Insufficient OAuth scope

Return `403 Forbidden`.

```http
HTTP/1.1 403 Forbidden
WWW-Authenticate: Bearer error="insufficient_scope", scope="content.articles:write", resource_metadata="https://example.org/.well-known/oauth-protected-resource/api/index.php/v1/mcp"
```

### Joomla ACL denial

Return `403 Forbidden`.

A valid token for an active user must not be reported as an authentication failure merely because
the user lacks a Joomla permission.

### Internal failure

Return a sanitised server error. Never expose raw exceptions, SQL messages, filesystem paths, tokens
or Authorization headers.

## Component boundaries

### The independent OAuth component owns

- OAuth clients;
- redirect URIs;
- user consent;
- authorisation codes;
- access tokens;
- refresh tokens;
- grants;
- token revocation;
- signing keys;
- Authorization Server Metadata;
- optional OpenID Connect endpoints.

### `com_mcp` owns

- OAuth Protected Resource Metadata;
- Bearer-token extraction;
- resource-specific access-token validation;
- subject-to-Joomla-user resolution;
- MCP request context creation;
- MCP scope enforcement;
- MCP tool discovery and dispatch;
- propagation of the Joomla identity to internal operations.

### `com_mcp` must not own

- OAuth client registration data;
- authorisation-code processing;
- refresh-token processing;
- OAuth consent screens;
- OAuth signing keys;
- a bespoke token endpoint;
- a direct dependency on another component's database tables or internal PHP classes.

Any earlier OAuth Authorization Server scaffolding under `com_mcp` should be removed once it is
confirmed to be unused.

## Legacy static tokens

Static `#__mcp.client_token` credentials are a development and migration shortcut, not OAuth access
tokens.

The target production implementation must not silently try a static-token validator after OAuth
validation fails. A chain such as:

```text
try OAuth Bearer token
    then, on any failure, try legacy Bearer token
```

creates ambiguous authentication semantics, can conceal OAuth configuration errors and makes security
downgrades difficult to detect.

Once the independent Authorization Server is available:

- legacy static-token authentication should be disabled by default;
- new static tokens must not be issued;
- existing clients should be migrated to OAuth;
- legacy database fields, controllers and administration views should be removed after the migration
  period.

If temporary compatibility is unavoidable, it must be explicitly enabled for development, clearly
audited and separated from the production OAuth Bearer-token path. It must not be an automatic fallback
for the same `Authorization: Bearer` credential.

## Dependency injection

The Resource Server services should be registered independently of a particular Authorization Server
implementation:

```text
AccessTokenValidatorInterface
    → JwtAccessTokenValidator or IntrospectionAccessTokenValidator

JoomlaSubjectResolverInterface
    → configured deployment adapter

ProtectedResourceMetadataProvider
    → com_mcp configuration and compiled operation scopes

ScopeAuthoriser
    → operation scope checks

McpRequestContextFactory
    → principal plus resolved Joomla user
```

The MCP endpoint depends only on these contracts.

## Setup

### Authorization Server

1. Register the canonical MCP resource identifier.
2. Register the MCP scopes.
3. Configure access-token audience binding to the MCP resource.
4. Ensure Authorization Server Metadata is published.
5. Configure Authorization Code Grant with PKCE `S256`.
6. Configure refresh-token rotation where refresh tokens are issued.
7. Configure JWT signing and JWKS publication, or token introspection for opaque tokens.
8. Register or dynamically accept the required MCP clients.

### `com_mcp`

1. Configure the canonical resource identifier.
2. Configure the trusted Authorization Server issuer.
3. Configure the metadata and JWKS or introspection validation strategy.
4. Configure subject-to-Joomla-user resolution.
5. Enable the required MCP scopes.
6. Grant suitable Joomla ACL permissions to the represented users.
7. Disable legacy static-token authentication.

## Verification

### Metadata discovery

```bash
curl -i \
  "https://<site>/.well-known/oauth-protected-resource/api/index.php/v1/mcp"
```

Expected:

- HTTP 200;
- the exact MCP `resource`;
- at least one trusted `authorization_servers` entry;
- supported MCP scopes.

### Missing token

```bash
curl -i \
  -X POST \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  "https://<site>/api/index.php/v1/mcp" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"<supported-protocol-version>","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}'
```

Expected:

- HTTP 401;
- `WWW-Authenticate: Bearer`;
- `resource_metadata` pointing to the Protected Resource Metadata document.

### Valid token

```bash
curl -i \
  -X POST \
  -H "Authorization: Bearer <access-token>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  "https://<site>/api/index.php/v1/mcp" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"<supported-protocol-version>","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}'
```

Expected:

- HTTP 200;
- a valid MCP initialisation response.

### Required negative tests

The test suite must also verify:

- expired token → 401;
- invalid signature → 401;
- untrusted issuer → 401;
- correct issuer but wrong audience → 401;
- ID token presented as access token → 401;
- missing required scope → 403 with `insufficient_scope`;
- valid scope but Joomla ACL denied → 403;
- blocked or unresolved Joomla subject → 401;
- legacy static token in production mode → 401;
- raw tokens and Authorization headers are absent from logs.

## Security and operational requirements

- Use HTTPS except for permitted native-client loopback redirects.
- Never log access tokens, refresh tokens, authorisation codes or complete Authorization headers.
- Do not log complete JWT payloads.
- Cache Authorization Server metadata and JWKS with bounded lifetimes.
- Support signing-key rotation.
- Allow only explicitly configured issuers and signing algorithms.
- Apply a small, documented clock-skew allowance.
- Use constant-time comparisons where secret values are compared.
- Audit successful and failed token validation without recording credentials.
- Audit tool calls with request ID, issuer, subject, client ID, scopes, operation ID and outcome.
- Re-evaluate Joomla ACL on every operation; do not encode Joomla ACL decisions permanently in access
  tokens.
- Keep access tokens short-lived.
- Ensure that revocation, user blocking and grant withdrawal take effect within an acceptable period.

## References

- MCP Authorization specification, protocol revision 2025-11-25
- RFC 6750 — OAuth 2.0 Bearer Token Usage
- RFC 8414 — OAuth 2.0 Authorization Server Metadata
- RFC 8707 — Resource Indicators for OAuth 2.0
- RFC 9068 — JWT Profile for OAuth 2.0 Access Tokens
- RFC 9700 — Best Current Practice for OAuth 2.0 Security
- RFC 9728 — OAuth 2.0 Protected Resource Metadata
