# Exploratory testing of the article contract projection chain

This guide covers the first end-to-end testable vertical slice. The same `Article` contract now produces Joomla REST
routes, an OpenAPI document and five MCP tools. MCP execution deliberately calls the site's established REST API so
that Joomla's existing routing, controller, validation and authorisation behaviour remains authoritative during this
exploratory phase.

## Prerequisites

- A non-production Joomla installation built from the `contract-projection-chain` branch.
- The PHP cURL extension enabled for the web server PHP runtime.
- The **Web Services - Content** and **Web Services - MCP** plugins enabled.
- The **MCP - Joomla** plugin enabled.
- A Joomla API token belonging to a user with the required content permissions.
- A web server that can serve a loopback request while the MCP request is still running. Apache or Nginx with PHP-FPM
  is suitable. The single-process PHP development server is not suitable because the loopback request can deadlock.
- A valid TLS certificate when Claude Code connects over HTTPS.

After updating the branch, remove Joomla's generated namespace cache if it exists:

```bash
rm -f administrator/cache/autoload_psr4.php
```

Run the contract smoke test from the project root:

```bash
php tests/run-smoke.php
```

The command should report `Article operation chain smoke test passed.` It also writes:

- `article-openapi.json`, which can be imported into Postman;
- `article-mcp-tools.json`, which shows the five generated MCP tool definitions.

## REST testing with Postman

Use the standard Joomla API header for REST calls:

```text
X-Joomla-Token: YOUR_JOOMLA_API_TOKEN
Accept: application/vnd.api+json
```

### List articles

```http
GET https://example.test/api/index.php/v1/content/articles
```

Optional query examples:

```text
filter[category]=2
filter[search]=contract
list[ordering]=created
list[direction]=desc
```

### Read one article

```http
GET https://example.test/api/index.php/v1/content/articles/42
```

### Create a test article

Use a category that exists in the test installation:

```http
POST https://example.test/api/index.php/v1/content/articles
Content-Type: application/json
```

```json
{
  "title": "Contract projection test",
  "articletext": "Created through the generated REST contract.",
  "catid": 2,
  "language": "*",
  "state": 0
}
```

The canonical resource property is named `category`. The REST projection exposes the established Joomla transport
name `catid`; the MCP tool continues to accept `category` and maps it to `catid` before invoking REST.

### Update a test article

```http
PATCH https://example.test/api/index.php/v1/content/articles/42
Content-Type: application/json
```

```json
{
  "title": "Updated contract projection test"
}
```

### Delete a test article

```http
DELETE https://example.test/api/index.php/v1/content/articles/42
```

Only use create, update and delete against disposable content in a non-production installation.

## MCP testing with Claude Code

Register the remote MCP endpoint:

```bash
claude mcp add --transport http joomla-contracts \
  https://example.test/api/index.php/v1/mcp \
  --header "Authorization: Bearer YOUR_JOOMLA_API_TOKEN"
```

Check the connection:

```bash
claude mcp list
claude mcp get joomla-contracts
```

Inside Claude Code, open `/mcp`. The following generated tools should be visible:

```text
content.articles.list
content.articles.get
content.articles.create
content.articles.update
content.articles.delete
```

Suggested exploratory prompts:

```text
Use content.articles.list to list the five most recently created Joomla articles.
```

```text
Use content.articles.get to read article 42.
```

```text
Create an unpublished Joomla test article titled "MCP contract test" in category 2 with the article text
"Created by Claude Code through the generic web service tool".
```

```text
Update article 42 so that its title is "Updated through MCP". Do not change any other property.
```

Claude Code should request confirmation before potentially destructive actions according to its own client behaviour.
The server-side Joomla permissions remain decisive.

## Runtime path

A generated MCP call currently follows this path:

```text
Claude Code
  -> POST /api/index.php/v1/mcp
  -> WebserviceTool
  -> HttpOperationInvoker
  -> /api/index.php/v1/content/articles[/:id]
  -> Joomla REST router and ArticlesController
  -> JSON:API response
  -> flattened structured MCP result
```

The HTTP invoker forwards the authenticated MCP bearer token as `X-Joomla-Token`. JSON:API resource objects are
flattened to their attributes plus the identifier before they are returned as MCP structured content.

## Expected limitations

- MCP execution performs a loopback HTTP request. This is intentionally simple and replaceable, not the final internal
  dispatch architecture.
- Only the article CRUD operations are registered through the generic provider in this vertical slice.
- Dynamic custom fields are permitted by the resource contract, but their installation-specific schemas are not yet
  expanded by a property provider.
- The generic REST handler does not yet hydrate the resource DTO itself. REST continues to use Joomla's established API
  controller while routes and documentation come from the compiled contract.
- The request token context is a transitional mechanism until `ToolInterface` receives an explicit request context.

## Troubleshooting

### The MCP connection works, but article tools are missing

Confirm that **MCP - Joomla** is enabled, remove `administrator/cache/autoload_psr4.php`, and restart the PHP worker or
container so the new classes are discoverable.

### A tool reports that the authenticated token is unavailable

Confirm that the web server forwards the `Authorization` header to PHP. The MCP endpoint stores the validated token in
its request context during tool execution; the invoker also retains header-based fallbacks for exploratory setups.

### A tool times out

Confirm that the site can make an HTTP request to its own public base URL and that PHP cURL is enabled. Do not use the
single-process `php -S` server for this test.

### REST succeeds but MCP receives an authorisation error

The same token is used for both calls. Verify that the token owner has `core.create`, `core.edit` or `core.delete` for
the requested action and asset.
