# Article operation chain

The `Article` proof of concept uses one canonical contract and convention-derived projections.

```text
Article DTO + ArticlesController #[ResourceOperations]
                    |
                    v
             OperationCompiler
                    |
       +------------+------------+
       |            |            |
       v            v            v
Joomla routes   MCP tools    OpenAPI 3.1
```

## Conventions

- `ArticlesController` implies the collection `articles`.
- Its component namespace implies `com_content` and the `content` operation prefix.
- `Article` is inferred by singularising `Articles`.
- `ArticleListQuery` is inferred from the resource name.
- The base path is `v1/content/articles`.
- Standard CRUD methods are inherited from `ApiController`; no method attributes are required.
- MCP tool names and OpenAPI operation IDs are identical: `content.articles.list`, `.get`, `.create`, `.update` and `.delete`.
- HTTP methods, controller tasks, default ACL actions and MCP annotations follow the CRUD convention.
- Attribute arguments are overrides rather than mandatory configuration.
- Article filters are exposed as `filter[...]`; ordering and direction use Joomla's established `list[...]` convention.

## Resource conventions

- A public typed property is part of the resource contract.
- A property without a default is required on create.
- A guarded property is readable but omitted from create and update schemas.
- A write-only property is available in create and update inputs but omitted from read and list outputs.
- `Hidden` removes the property from selected profiles where the default visibility convention is insufficient.
- PATCH schemas have no required resource properties and require at least one property.
- Update hydration unsets declared defaults before assigning supplied values.
- An uninitialised property therefore means “not supplied”; an initialised nullable property containing `null` means “explicitly clear”.
- Create hydration retains declared PHP defaults.
- Property types, defaults, enums and date-time formats are reflected into JSON Schema.
- `Description`, `Example` and `Items` are used only where PHP types do not convey enough semantics.
- `AdditionalProperties` preserves installation-specific Joomla custom fields in hydration and normalisation.

## REST projection

`RestRouteFactory` projects every `OperationDefinition` to a Joomla `Route`. The content web services plugin now obtains the five article CRUD routes from `OperationCompiler`; the remaining content routes are unchanged.

The REST projection preserves the current controller tasks:

| Operation | Method | Route | Task |
|---|---|---|---|
| `content.articles.list` | GET | `v1/content/articles` | `articles.displayList` |
| `content.articles.get` | GET | `v1/content/articles/:id` | `articles.displayItem` |
| `content.articles.create` | POST | `v1/content/articles` | `articles.add` |
| `content.articles.update` | PATCH | `v1/content/articles/:id` | `articles.edit` |
| `content.articles.delete` | DELETE | `v1/content/articles/:id` | `articles.delete` |

## MCP projection

`WebserviceTool` exposes an `OperationDefinition` through the existing MCP `ToolInterface`. `WebserviceToolProvider` compiles an attributed controller, creates one generic tool per exposed operation and registers the tools with `AbilityRegistry`.

The provider deliberately depends on `OperationInvokerInterface`. The concrete invocation strategy remains selectable: an internal Joomla dispatcher or an HTTP adapter can be supplied without changing the generated tool contracts.

## OpenAPI projection

`OpenApiDocumentFactory` produces an OpenAPI 3.1 document from the same definitions. It includes:

- paths, HTTP methods and operation IDs;
- path and Joomla-style query parameters;
- create and update request bodies;
- profile-specific response schemas;
- the `X-Joomla-Token` security scheme;
- operation tags, summaries and descriptions.

The smoke test writes `article-openapi.json` and `article-mcp-tools.json`, making the derived contracts directly inspectable.

## Deliberate boundary

This slice compiles and projects the complete contract but does not prescribe the final MCP invocation transport. The invoker is intentionally a separate dependency because the team still needs to choose between internal dispatch and a loopback HTTP request. The REST routes continue to use Joomla's existing API controller implementation during this transition.

## Migration from the administrator prototypes

The transport-neutral `OperationDefinition` in `libraries/src/WebService/Operation` supersedes the initial MCP-specific prototypes under `administrator/components/com_mcp/src/Operations`. Those prototype files should be removed when applying this vertical slice. The package `apply.sh` performs that clean-up automatically.
