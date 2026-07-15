# `guide.joomla.org`

Create a new top-level section **“APIs and Automation”** to avoid duplicated content for REST and MCP.

## APIs and Automation

| Title                                | Content                                                                                                                                                                                         |
|--------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **APIs and Automation**              | Introduces the ways in which external applications and AI clients can interact with a Joomla site. Explains the distinction between the Web Services API and the Model Context Protocol server. |
| **Preparing a User for API Access**  | Explains how to create or select a dedicated Joomla user and grant only the permissions required for API or MCP access. Emphasises the use of separate accounts for different integrations.     |
| **Creating and Revoking API Tokens** | Shows how to create, copy, protect and revoke Joomla API tokens. Explains what to do when a token may have been disclosed.                                                                      |
| **Managing API Permissions**         | Explains how Joomla ACL controls which content and operations an API user may access. Includes guidance on testing access with a restricted user rather than a Super User.                      |

## Web Services API

| Title                                 | Content                                                                                                                                                                                             |
|---------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Using the Joomla Web Services API** | Introduces Joomla REST endpoints, request methods, authentication headers and JSON:API responses. Provides a simple read and write example.                                                         |
| **Viewing the OpenAPI Reference**     | Shows where administrators can access or export the generated OpenAPI description for their installation. Explains that the document reflects the components and operations available on that site. |
| **Testing the Web Services API**      | Provides practical steps for testing an endpoint with Postman or another HTTP client. Covers authentication, content types, common status codes and safe testing of write operations.               |

## Model Context Protocol

| Title                                       | Content                                                                                                                                                                                                                       |
|---------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **About the Joomla MCP Server**             | Explains what the Model Context Protocol is and how an AI client can use Joomla tools and resources. Clarifies that the client acts with the permissions of the authenticated Joomla user.                                    |
| **Enabling and Configuring the MCP Server** | Shows how to enable the required component and plugins and locate the MCP endpoint. Describes the principal configuration and exposure options.                                                                               |
| **Connecting an MCP Client**                | Provides step-by-step examples for connecting supported MCP clients such as Claude Code. Covers the endpoint URL, authentication header and connection verification.                                                          |
| **Reviewing Available MCP Capabilities**    | Explains how to inspect the tools and resources currently exposed by a Joomla installation. Notes that the available capabilities depend on installed extensions, configuration and user permissions.                         |
| **Using the MCP Server Safely**             | Describes recommended account separation, least-privilege permissions, supervision of write operations and protection of credentials. Explains the implications of allowing an AI client to create, update or delete content. |
| **Troubleshooting API and MCP Access**      | Covers authentication failures, missing permissions, unavailable tools, invalid arguments and server-side errors. Provides a checklist for distinguishing client, configuration and extension problems.                       |

# `manual.joomla.org`: Web Services API

The existing top-level **Web Services API** section should be rebuilt as the canonical developer documentation for the
shared contract and REST projection architecture. This fits the current Manual structure better than placing the
material under a specific component tutorial.

## Concepts and architecture

| Title                                   | Content                                                                                                                                                                                                    |
|-----------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Web Services API Overview**           | Introduces Joomla web services, the API application and the relationship between routes, controllers, resources and JSON:API responses. Explains the legacy and contract-based extension models.           |
| **API Contracts and Projections**       | Describes the canonical operation contract from which REST routes, MCP tools and OpenAPI documentation are projected. Explains why transport-specific metadata should not be maintained independently.     |
| **Resource DTOs**                       | Explains how strictly typed resource classes describe the canonical data contract for an entity. Covers read, list, create and update projections.                                                         |
| **Resource Property Conventions**       | Documents how PHP property types, nullability, defaults and initialisation state are translated into JSON Schema and runtime behaviour. Explains which information is inferred by convention.              |
| **Resource Property Attributes**        | Provides the reference for attributes such as `Guarded`, `WriteOnly`, `Hidden`, `Source`, `Items`, `Description` and `Example`. Each entry should state its effect on input, output and generated schemas. |
| **Dynamic Fields and Schema Providers** | Explains how custom fields and other installation-dependent properties extend a resource contract. Covers schema generation when the complete property set is not known statically.                        |
| **JSON:API Requests and Responses**     | Documents request bodies, resource identifiers, attributes, relationships, errors and pagination. This should replace or substantially expand the existing JSON response page.                             |

## Operations and routing

| Title                                                | Content                                                                                                                                                                                                                                            |
|------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Resource Operations and Controller Conventions**   | Explains how `ResourceOperations`, controller names and inherited CRUD tasks produce canonical operation definitions. Lists the conventions for component, resource, collection, route and operation names.                                        |
| **Operation Attributes and Overrides**               | Documents how individual operations or non-standard behaviour are declared when conventions are insufficient. Includes custom HTTP methods, paths, permissions and exposure settings.                                                              |
| **Operation Discovery and the Operations Catalogue** | Describes how participating controllers are registered, discovered, compiled and made available to REST, MCP and OpenAPI projections. Covers the relationship between events, providers, request-level catalogues and optional compiled manifests. |
| **Generated REST Routes**                            | Explains how operation definitions become Joomla API routes and how those routes continue to use existing component dispatchers and controllers. Documents route defaults and parameter mapping.                                                   |
| **Legacy Web Services Plugins and Route Precedence** | Defines the backwards-compatibility rules for existing webservices plugins. Explicit legacy routes take precedence, while generated routes fill only unregistered gaps.                                                                            |
| **Custom Routes and Non-CRUD Operations**            | Shows how to expose actions that do not fit list, get, create, update or delete conventions. Includes item actions, collection actions and special route parameters.                                                                               |

## Data handling and security

| Title                                              | Content                                                                                                                                                                                                             |
|----------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **PATCH and Partial Update Semantics**             | Explains the distinction between an omitted property, an explicit `null`, an empty string, zero, `false` and an empty collection. Documents how uninitialised typed properties preserve field presence information. |
| **Mapping Contract Fields to Existing API Fields** | Explains how canonical names are mapped to legacy REST or model field names, for example `category` to `catid`. Covers mapping for request bodies, query parameters and responses.                                  |
| **Authentication and Authorisation**               | Describes API authentication, Joomla identity propagation and ACL checks. Explains that schema exposure does not replace controller- and asset-level authorisation.                                                 |
| **Validation and Error Responses**                 | Documents input validation, controller exceptions, JSON:API errors and stable operation errors. Explains how failures are represented consistently across REST and MCP.                                             |

## Documentation and testing

| Title                                | Content                                                                                                                                                                                                  |
|--------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Generating OpenAPI Documentation** | Explains how OpenAPI 3.1 documents are generated from the operations catalogue and resource schemas. Covers installation-specific fields, security schemes and exporting the resulting document.         |
| **Testing Web Services**             | Describes unit, contract, integration and black-box testing for generated routes and existing controller behaviour. Includes recommendations for PATCH presence tests and backwards-compatibility tests. |
| **Migrating Existing Web Services**  | Provides a staged migration path from manually registered routes to contract-based operations. Emphasises that legacy routes and controllers may remain in place throughout the transition.              |

# `manual.joomla.org`: Model Context Protocol

MCP should receive its own top-level section. It uses the same operation contracts as REST, but has its own protocol
lifecycle, capability model, security considerations and extension points.

| Title                                                | Content                                                                                                                                                                                                             |
|------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Joomla Model Context Protocol Server**             | Introduces the Joomla MCP server, its purpose and its relationship to Joomla web services. Describes tools, resources and other MCP capabilities at a high level.                                                   |
| **MCP Server Architecture**                          | Describes the endpoint, ability registry, tool provider, generic webservice tool and operation invoker. Shows how the MCP projection reuses the shared operations catalogue.                                        |
| **MCP Request Lifecycle**                            | Follows `initialize`, `tools/list` and `tools/call` requests through authentication, capability lookup, argument mapping, internal dispatch and result normalisation.                                               |
| **Generating MCP Tools from Web Service Operations** | Explains how operation IDs, descriptions, input schemas, output schemas and behavioural annotations are derived from canonical operation definitions.                                                               |
| **Internal API Dispatch**                            | Documents how an MCP tool invokes an existing Joomla API controller without leaving the application. Explains the isolated request and response context and the Joomla services shared with the parent application. |
| **MCP Authentication and Authorisation**             | Explains how the authenticated Joomla identity is propagated into internal API dispatch and how existing ACL checks remain authoritative. Covers least-privilege design for MCP clients.                            |
| **MCP Tool Inputs and Results**                      | Documents canonical MCP arguments, mapping to REST fields and conversion of JSON:API responses into MCP text and structured content.                                                                                |
| **MCP Errors and Result Normalisation**              | Describes the mapping of validation failures, ACL errors, missing resources and controller exceptions to MCP tool results.                                                                                          |
| **Adding Custom MCP Abilities**                      | Shows how extensions can add tools or resources that are not projections of web service operations. Explains when a purpose-built MCP ability is preferable to a generic webservice tool.                           |
| **Testing and Debugging MCP Integrations**           | Covers protocol inspection, capability listing, tool invocation tests, isolated dispatcher tests and client testing with applications such as Claude Code.                                                          |
| **Compatibility Requirements for Extensions**        | Defines the Joomla-conformant request interfaces available during internal dispatch. States that extensions should use the injected application, input and Joomla services rather than PHP superglobals.            |

# `manual.joomla.org`: Tutorials under “Build Extensions”

The Manual’s existing separation between conceptual documentation and extension-building material suggests adding two
practical tutorials under **Build Extensions → Components**.

| Title                                                | Content                                                                                                                                                                                                       |
|------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Adding REST and MCP Support to a Component**       | Builds a small component with a typed resource, list query and `ResourceOperations` controller. Demonstrates the resulting REST routes, MCP tools and OpenAPI schemas.                                        |
| **Migrating a Web Services Plugin to API Contracts** | Starts with an existing component that registers routes manually and migrates its standard CRUD operations to the contract projection chain. Special and legacy routes remain active throughout the tutorial. |

# `manual.joomla.org`: References

The references should be generated from the OpenAPI and MCP discovery endpoints.

| Title                                 | Content                                                                                                                                                                                                |
|---------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Joomla Web Services API Reference** | The endpoint catalogue, parameters, request bodies and response schemas should be rendered directly from the installation-specific OpenAPI document.                                                   |
| **Available MCP Capabilities**        | The authoritative list of tools and resources should come from the MCP server’s discovery methods such as `tools/list`, because permissions and installed extensions can change the visible catalogue. |
