# Service Schema YAML Reference

This document describes the YAML schema format used by the Web Service Manager plugin.

## Schema Structure

A service schema YAML file must contain the following sections:

```yaml
meta:
  id: "example.service"           # Required: Unique identifier (letters, numbers, dots only; max 50 chars)
  name: "Example Service"         # Required: Display name (must be unique across all schemas)
  version: "1.0.0"               # Required: Version number (must increment on definition changes)
  maintainer: "Your Name"         # Optional: Maintainer info
  description: "Description"      # Optional: Service description

requirements:                     # Optional section
  plugins:                        # List of required plugins
    - mod_forum
    - mod_assign
  download_files: false           # Allow file downloads via this service (default: false)
  upload_files: false             # Allow file uploads via this service (default: false)

definition:
  functions:                      # Required: List of web service functions
    - core_user_get_users
    - name: core_course_get_courses
      critical: true              # Default: true (blocks creation if missing)
    - name: mod_forum_get_forums
      critical: false             # Non-critical: warning only, creation continues

  extra_capabilities:             # Optional: Additional capabilities to assign
    - moodle/user:viewdetails     # webservice/rest:use and webservice/soap:use are always added automatically
    - moodle/course:view

  additional_users:               # Optional: Additional users to authorize
    - admin@example.com
    - teacher@example.com
```

## Field Reference

### meta (Required)

| Field | Required | Description |
|-------|:--------:|-------------|
| `id` | ✅ | Unique identifier. Only letters, numbers, and dots (.) allowed. Max 50 characters. Example: `myapp.users` |
| `name` | ✅ | Human-readable name for the service. Must be unique across all schemas. |
| `version` | ✅ | Version string (semantic versioning recommended). Must be incremented when the definition (functions or capabilities) changes. Metadata-only edits (name, maintainer, description) do not require a version bump. |
| `maintainer` | ❌ | Person or team responsible for the schema. |
| `description` | ❌ | Brief description of the service's purpose. |

### requirements (Optional)

| Field | Required | Description |
|-------|:--------:|-------------|
| `plugins` | ❌ | Array of plugin names that must be installed. A warning is shown if any are missing, but schema creation is not blocked. |
| `download_files` | ❌ | Boolean. If `true`, consumers of this service may download files via `webservice/pluginfile.php`. Defaults to `false`. |
| `upload_files` | ❌ | Boolean. If `true`, consumers of this service may upload files via `webservice/upload.php`. Defaults to `false`. |

### definition (Required)

| Field | Required | Description |
|-------|:--------:|-------------|
| `functions` | ✅ | Array of web service function names to include in the service. |
| `extra_capabilities` | ❌ | Additional Moodle capabilities to assign to the service role beyond those derived from the functions. `webservice/rest:use` and `webservice/soap:use` are always included automatically. |
| `additional_users` | ❌ | Email addresses of existing Moodle users to also authorize for this service. If an email is not found, a warning is shown and creation continues. |

## Function Formats

Functions can be specified in two ways:

**Simple format** (defaults to `critical: true`):
```yaml
functions:
  - core_user_get_users
  - core_course_get_courses
```

**Extended format** (explicit critical flag):
```yaml
functions:
  - name: core_user_get_users
    critical: true    # Schema creation fails if this function is missing
  - name: mod_forum_get_forums
    critical: false   # Warning only — creation continues even if missing
```

## Capability Resolution

When a schema is created or updated, the plugin automatically calculates the full set of required capabilities by reading the PHP declarations of each web service function in Moodle's codebase. These derived capabilities are merged with any `extra_capabilities` listed in the YAML. The final set is assigned to the schema's dedicated role. `webservice/rest:use` and `webservice/soap:use` are always included regardless of the functions defined.

### Special case: `moodle/role:*` capabilities

A handful of capabilities are **not enough on their own** — granting them to the service role does not, by itself, let the service perform the action. Moodle gates them behind a second layer, the *role-allow matrices*, which say *which target roles* a role may act on:

| Capability | Also requires (matrix) | Configured in *Define roles* → tab |
|------------|------------------------|------------------------------------|
| `moodle/role:assign` | `role_allow_assign` | Allow role assignments |
| `moodle/role:review` | `role_allow_view` | Allow role to view |
| `moodle/role:override` | `role_allow_override` | Allow role overrides |
| `moodle/role:switch` | `role_allow_switch` | Allow role switches |

The service role this plugin creates has **no archetype**, so it starts with no entries in any of these matrices. For example, even with `moodle/role:assign` granted, a call to `core_role_assign_roles` runs `get_assignable_roles()` and throws `Can not assign roleid=X` until the matrix permits it.

To make these work, after provisioning the schema go to **Site administration → Users → Permissions → Define roles**, open the relevant tab (e.g. *Allow role assignments*), and allow the service role (`ws_{id}`) to act on the specific target roles it needs. The plugin does not manage these matrices automatically.

## Naming Conventions

When a schema is created, the following resources are automatically provisioned:

| Resource | Pattern | Example |
|----------|---------|---------|
| Username | `ws.{id}` | `ws.example.service` |
| Display name | `User Webservice {name}` | `User Webservice Example Service` |
| Email | `ws.{id}@devnull.{domain}` | `ws.example.service@devnull.campus.edu` |
| Role display name | `Role for {name}` | `Role for Example Service` |
| Role shortname | `ws_{id}` (dots → underscores) | `ws_example_service` |
| Service shortname | `ws_{id}` | `ws_example_service` |
| Token name | `Token - {name}` | `Token - Example Service` |

The `{domain}` in the email is the site's domain with any leading subdomain stripped (e.g. `campus.example.com` → `example.com`).

## Validation Rules

| Rule | Details |
|------|---------|
| Schema ID format | Letters, numbers, and dots only |
| Schema ID length | Maximum 50 characters |
| Schema ID uniqueness | Each schema ID must be unique |
| Schema name uniqueness | Each schema name must be unique (drives role and service names which have DB unique constraints) |
| Critical functions | If a critical function is missing from the Moodle installation, the schema cannot be created |
| Non-critical functions | Missing non-critical functions generate a warning but do not block creation |
| Version increment | If the definition (functions or capabilities) changes, the version number must be incremented |

## Example Files

See the `examples/` folder for complete example files:

- `sample_schema.yaml` — Basic example with all sections and inline comments

## Download Example

[Download sample_schema.yaml](../examples/sample_schema.yaml)
