# Testing the MCP OAuth Resource Server

## Prerequisites

The Joomla installation requires an independent OAuth Authorization Server that can issue RFC 9068-style JWT access tokens.
The token must contain at least:

- `iss`: the configured issuer;
- `sub`: a subject accepted by the configured Joomla subject resolver;
- `aud`: the canonical MCP resource URI;
- `client_id`: the OAuth client identifier;
- `scope`: `mcp:use` and any operation scopes;
- `iat`, `nbf` and `exp`;
- a `typ` header of `at+jwt`;
- an RSA signature whose public key is published by the configured JWKS endpoint.

The default subject adapter interprets `sub` as the numeric Joomla user identifier. It is a deployment adapter, not part of the Resource Server contract.

## Component configuration

Configure **Components → MCP Server → Options → OAuth Resource Server**:

- Resource URI: `https://example.org/api/index.php/v1/mcp`
- Authorization Server Issuer: for example `https://example.org/oauth`
- JWKS URI: the Authorization Server's published `jwks_uri`
- Protected Resource Metadata URI: normally `https://example.org/api/index.php/v1/mcp/oauth-protected-resource`
- Base MCP Scope: `mcp:use`

Grant **Access MCP** to the Joomla groups whose users may be represented by OAuth tokens.

## Metadata

```bash
curl -i \
  https://example.org/api/index.php/v1/mcp/oauth-protected-resource
```

The response must contain the exact MCP resource URI, the Authorization Server issuer and the compiled operation scopes.

## Missing token

```bash
curl -i \
  -X POST \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  https://example.org/api/index.php/v1/mcp \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}'
```

Expected:

- HTTP 401;
- `WWW-Authenticate: Bearer`;
- a `resource_metadata` parameter.

## Valid token

```bash
curl -i \
  -X POST \
  -H 'Authorization: Bearer ACCESS_TOKEN' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  https://example.org/api/index.php/v1/mcp \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}'
```

Expected: a normal MCP initialisation response.

## Operation scopes

Convention-derived article scopes are:

```text
content.articles:read
content.articles:write
content.articles:delete
```

`tools/list` hides tools for which the token lacks the required scope. `tools/call` checks the scope again before invoking the operation. The existing Joomla controller then applies its normal ACL checks to the represented user and concrete resource.

## Required negative tests

Verify all of the following:

- invalid signature → 401;
- expired token → 401;
- wrong issuer → 401;
- wrong audience → 401;
- ID token or incorrect `typ` → 401;
- missing `mcp:use` → 403 with `insufficient_scope`;
- missing operation scope → tool omitted from discovery and invocation rejected;
- inactive or unresolved Joomla subject → 401;
- user without `mcp.access` → 403;
- valid scope but Joomla ACL denied → operation error with no data modification;
- Authorization headers and token contents do not appear in logs.
