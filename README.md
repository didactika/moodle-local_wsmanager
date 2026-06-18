# Web Service Manager

A Moodle local plugin for managing web service integrations from declarative YAML schemas.

Web Service Manager lets administrators define a complete Moodle web service setup in one version-controlled file. For each schema, the plugin provisions the service user, role, capabilities, external service, authorized functions, and token, then keeps those resources synchronized as the schema changes.

[![Moodle Plugin CI](https://img.shields.io/badge/Moodle-4.5+-blue.svg)](https://moodle.org)
[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

## Contents

- [Overview](#overview)
  - [The Security Model](#the-security-model)
  - [Automatic Capability Resolution](#automatic-capability-resolution)
  - [Full Lifecycle Management](#full-lifecycle-management)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [YAML Schema Format](#yaml-schema-format)
- [Configuration](#configuration)
- [API Reference](#api-reference)
  - [Plugin Web Service API](#plugin-web-service-api)
- [Testing](#testing)
- [Security](#security)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

## Overview

Web Service Manager allows administrators to define Moodle web services declaratively using YAML files. Instead of navigating multiple Moodle admin screens to manually create users, roles, capabilities, and services, you declare everything in a single YAML file and the plugin provisions and maintains the full infrastructure automatically.

### The Security Model

Each schema creates a **fully isolated, scoped environment** for one web service consumer:

- **Dedicated service user** — a system account created exclusively for this service, suspended automatically if the schema is disabled
- **Dedicated role** — a custom role assigned to that user at the system level, containing only the capabilities this service actually needs
- **Dedicated external service** — a Moodle web service restricted to that user and those functions only
- **Scoped token** — a token tied to that user and service, never shared across consumers

This means a compromised token or misbehaving consumer can only access exactly what its schema declares — nothing more. Revoking access is as simple as disabling or deleting the schema.

### Automatic Capability Resolution

Moodle web service functions each declare the capabilities they require in their PHP definition files. This plugin reads those declarations at provisioning time and automatically assigns all necessary capabilities to the service role — you don't need to know or look them up manually. You can also declare additional capabilities in the YAML for access patterns beyond the standard function requirements. The capabilities `webservice/rest:use` and `webservice/soap:use` are always included.

### Full Lifecycle Management

The plugin keeps all Moodle resources in sync with the schema throughout its lifetime:

| Action | What happens automatically |
|--------|---------------------------|
| **Create** | User, role, capabilities, service, token all provisioned in one step |
| **Update** | Role name, capabilities, and service functions updated to match the new YAML |
| **Enable / Disable** | User account unsuspended / suspended; service toggled |
| **Delete** | User, role, service, and token all removed cleanly |

### Why Use This Plugin?

- **Security by default**: Every consumer gets its own isolated user, role, and service — no shared credentials, minimal blast radius
- **No manual capability hunting**: Required capabilities are derived automatically from the function declarations in Moodle's codebase
- **Efficiency**: Create a complete, production-ready web service setup in seconds instead of navigating multiple Moodle admin screens
- **Reproducibility**: Schema files can be version-controlled and deployed identically across environments
- **Documentation**: YAML files serve as self-documenting service configurations — the schema is the spec
- **Auditability**: Version history, health check logs, and YAML diffs give a complete record of every change
- **Automation**: Import schemas via ZIP archive, integrate provisioning into CI/CD pipelines, or manage schemas programmatically via the plugin's own REST web service API

## Features

| Feature | Description |
|---------|-------------|
| **Declarative Configuration** | Define web services using YAML files |
| **Automatic Provisioning** | Users, roles, services, and tokens created automatically |
| **Health Monitoring** | Scheduled health checks with email notifications |
| **Token Management** | Secure token generation, display, and regeneration |
| **Multi-language** | English, Spanish, Portuguese, Italian, French |
| **In-Browser Editor** | Edit schemas directly in Moodle |
| **Validation** | Real-time syntax and function validation |
| **Capability Calculation** | Automatic capability assignment from functions |
| **Versioning** | Full history tracking with rollback and diff view |

## Requirements

| Requirement | Version |
|-------------|---------|
| Moodle | 4.5 or later |
| PHP | 8.1 or later |
| PHP YAML Extension | Recommended (fallback parser included) |

## Installation

### Method 1: Direct Download

1. Download the latest release
2. Extract it into `local/wsmanager`.
3. Visit **Site administration** in Moodle.
4. Complete the plugin installation wizard.

### Method 2: Git Clone

```bash
cd /path/to/moodle/local
git clone https://github.com/didactika/moodle-local_wsmanager.git wsmanager
```

### Method 3: Composer

```json
{
  "require": {
    "your-org/moodle-local_wsmanager": "^1.0"
  }
}
```

After installation, visit **Site Administration** to complete the setup.

## Usage

### Accessing the Dashboard

Navigate to: **Site Administration → Server → Service Manager → Dashboard**

### Creating a Schema

1. Click **"Import Schemas"**
2. Upload a YAML file or download the example by going to **View Documentation**
3. Check **"Generate token automatically"** if needed
4. Click **"Upload"**

### Editing a Schema

1. From the dashboard, click the **edit icon** (pencil)
2. Modify the YAML content in the editor
3. Click **"Save Changes"**

### Viewing Schema Details

Click on a schema name to view:

- Associated user, role, and service links
- Function status (available/missing)
- Token information and regeneration
- Health check history

### Managing Versions

1. Click on **Version History** while viewing an specific schema
2. View past versions and their changes
3. Select two versions and Click **"Compare Versions"** to view the definitions difference.
4. Click **"Rollback"** to restore a previous version

### Deleting a Schema

1. Click on the schema name to view details
2. Click **"Delete"** button
3. Confirm deletion

> ⚠️ **Warning**: Deleting a schema removes the associated user, role, service, and tokens.

## YAML Schema Format

### Complete Example

```yaml
meta:
  id: "myapp.users"                    # Required: Unique identifier
  name: "My Application User Service"  # Required: Display name
  version: "1.0.0"                     # Required: Version number
  maintainer: "IT Department"          # Optional: Maintainer info
  description: "User management API"   # Optional: Description

requirements:                          # Optional section
  plugins:
    - mod_forum                        # List of required plugins
    - mod_assign
  download_files: false                # Allow file downloads (default: false)
  upload_files: false                  # Allow file uploads (default: false)

definition:
  functions:                           # Required: Web service functions
    - core_user_get_users              # Simple format (critical: true)
    - core_user_create_users
    - name: core_user_update_users     # Extended format
      critical: true                   # Blocks creation if missing
    - name: mod_forum_get_forums
      critical: false                  # Warning only if missing
  
  extra_capabilities:                  # Optional: Additional capabilities
    - moodle/user:viewdetails
    - moodle/course:view
  
  additional_users:                    # Optional: Users to authorize
    - admin@example.com
    - apiuser@example.com
```

### Field Reference

#### Meta Section (Required)

| Field | Required | Description |
|-------|:--------:|-------------|
| `id` | ✅ | Unique identifier. Only letters, numbers, and dots (.). Max 50 characters. |
| `name` | ✅ | Human-readable display name |
| `version` | ✅ | Semantic version string (e.g., "1.0.0") |
| `maintainer` | ❌ | Responsible person or team |
| `description` | ❌ | Brief description of the service |

#### Requirements Section (Optional)

| Field | Required | Description |
|---|:---:|---|
| `plugins` | ❌ | List of plugins that must be installed |
| `download_files` | ❌ | Whether the service may download files. Defaults to `false`. |
| `upload_files` | ❌ | Whether the service may upload files. Defaults to `false`. |

#### Definition Section (Required)

| Field | Required | Description |
|-------|:--------:|-------------|
| `functions` | ✅ | Array of web service function names |
| `extra_capabilities` | ❌ | Additional Moodle capabilities to assign |
| `additional_users` | ❌ | Email addresses of users to authorize |

### Naming Conventions

When a schema is created, resources follow these patterns:

| Resource | Pattern | Example |
|----------|---------|---------|
| Username | `ws.{id}` | `ws.myapp.users` |
| Display name | `User Webservice {name}` | `User Webservice My Application` |
| Email | `ws.{id}@devnull.{domain}` | `ws.myapp.users@devnull.campus.edu` |
| Role shortname | `ws_{id}` (dots → underscores) | `ws_myapp_users` |
| Service shortname | `ws_{id}` | `ws_myapp_users` |
| Token Name | `Token - {name}` | `Token - My Application` |

## Configuration

### Settings Location

**Site Administration → Plugins → Local Plugins → Web Service Manager → Settings**

### Notification Settings

| Setting | Description |
|---------|-------------|
| Email recipients | Comma-separated list of notification recipients |
| Notify admins | Also send notifications to site administrators |
| Notification level | Minimum severity (All, Warning, Error, Critical) |

### Health Check Settings

| Setting | Description |
|---------|-------------|
| Enable health check | Run scheduled health monitoring |
| Check interval | Configured via Moodle scheduled tasks |

### Log Cleanup Settings

| Setting | Description |
|---------|-------------|
| Enable cleanup | Automatically delete old health logs |
| Retention days | Number of days to keep logs (default: 30) |

## API Reference

### PHP Classes

```php
// Schema Manager - Main entry point
$manager = new \local_wsmanager\schema\manager();
$result = $manager->create_schema($yaml_content, $generate_token);
$schema = $manager->get_schema($id);
$manager->update_schema($id, $new_yaml);
$manager->delete_schema($id);

// YAML Parser
$parser = new \local_wsmanager\schema\yaml_parser();
$data = $parser->parse($yaml_content);
$meta = $parser->get_meta($data);
$functions = $parser->get_functions($data);

// Validator
$validator = new \local_wsmanager\schema\validator();
$result = $validator->validate_content($yaml_content);
// Returns: ['errors' => [...], 'warnings' => [...]]
```

### Scheduled Tasks

| Task | Description | Default Schedule |
|------|-------------|------------------|
| `health_check_task` | Validates all schemas | Daily at 2:00 AM |
| `cleanup_logs_task` | Removes old health logs | Daily at 3:00 AM |

### Plugin Web Service API

The plugin exposes its own REST API so schemas can be managed programmatically — useful for CI/CD pipelines, deployment scripts, or any external tooling that needs to provision or update web services without accessing the Moodle UI.

A pre-configured external service (`ws_wsmanager`) is installed automatically. Authorize a user with the `local/wsmanager:manage` capability to that service and use the token to call these functions:

| Function | Type | Description |
|----------|------|-------------|
| `local_wsmanager_get_schemas` | read | List all schemas |
| `local_wsmanager_get_schema` | read | Get a single schema by ID |
| `local_wsmanager_create_schema` | write | Create a new schema from YAML content |
| `local_wsmanager_update_schema` | write | Update an existing schema with new YAML content |
| `local_wsmanager_delete_schema` | write | Delete a schema and all its provisioned resources |

**Example — create a schema via REST:**

```bash
curl -X POST "https://yourmoodle.example.com/webservice/rest/server.php" \
  -d "wstoken=YOUR_TOKEN" \
  -d "wsfunction=local_wsmanager_create_schema" \
  -d "moodlewsrestformat=json" \
  -d "yamlcontent=meta:%0A  id: my.service%0A  ..." \
  -d "generatetoken=1"
```

## Testing

### PHPUnit Tests

```bash
# Run all plugin tests
vendor/bin/phpunit --testsuite local_wsmanager_testsuite

# Run specific test class
vendor/bin/phpunit local/wsmanager/tests/yaml_parser_test.php
```

### Behat Tests

```bash
# Initialize Behat
php admin/tool/behat/cli/init.php

# Run plugin tests
vendor/bin/behat --config /path/to/behatrun/behat.yml --tags=@local_wsmanager
```

### Test Coverage

| Test File | Covers |
|-----------|--------|
| `yaml_parser_test.php` | YAML parsing, ID validation, data extraction |
| `validator_test.php` | Schema validation, error detection |
| `user_manager_test.php` | Service user CRUD operations |
| `role_manager_test.php` | Role creation, capability assignment |
| `service_manager_test.php` | External service management |
| `capability_calculator_test.php` | Capability calculation |
| `manager_test.php` | Full schema lifecycle |

## Security

### Design Principles

- **Isolation**: Each schema gets its own user, role, and service
- **Restricted Access**: Services are restricted to authorized users only
- **Non-routable Emails**: Service user emails use `@devnull.{domain}` pattern
- **Token Security**: Tokens displayed only once after generation
- **Capability Minimization**: Only required capabilities are assigned

### Best Practices

1. **Version Control**: Keep schema YAML files in version control
2. **Environment Separation**: Use different schema IDs per environment
3. **Token Rotation**: Regenerate tokens periodically
4. **Audit Logging**: Monitor health check logs for anomalies
5. **Principle of Least Privilege**: Only include necessary functions

## Troubleshooting

### Schema Not Creating

| Issue | Solution |
|-------|----------|
| YAML syntax error | Validate YAML at [yamllint.com](https://www.yamllint.com/) |
| Invalid schema ID | Use only letters, numbers, and dots |
| Missing critical function | Install required plugin or mark as non-critical |
| Duplicate ID | Choose a unique schema ID |
| Duplicate name | Schema names must be unique — use a different `meta.name` |

### Token Issues

| Issue | Solution |
|-------|----------|
| Token not copying | Enable JavaScript, use HTTPS |
| Token lost | Regenerate from schema detail page |
| Token not working | Verify service and user are enabled |

### Health Check Issues

| Issue | Solution |
|-------|----------|
| Not running | Check cron configuration |
| No notifications | Verify email settings |
| False positives | Review function availability |

### Common Errors

```
Error: Schema ID already exists
→ Use a unique ID or delete existing schema

Error: Invalid YAML syntax  
→ Check for indentation issues, missing quotes

Error: Critical function not found
→ Install required plugin or set critical: false
```

## Contributing

### Development Setup

```bash
# Clone into your Moodle installation
git clone https://github.com/didactika/moodle-local_wsmanager.git /path/to/moodle/local/wsmanager

# Run tests
vendor/bin/phpunit --testsuite local_wsmanager_testsuite

# Run code style checks (requires PHP_CodeSniffer with Moodle standard)
vendor/bin/phpcs --standard=moodle local/wsmanager/
```

### Pull Request Process

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests (`vendor/bin/phpunit --testsuite local_wsmanager_testsuite`)
5. Run code checks (`vendor/bin/phpcs --standard=moodle local/wsmanager/`)
6. Commit changes (`git commit -m 'Add amazing feature'`)
7. Push to branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

### Code Standards

- Follow [Moodle Coding Style](https://moodledev.io/general/development/policies/codingstyle)
- Add PHPDoc comments to all public methods
- Write tests for new functionality
- Update documentation as needed

## License

This plugin is licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0) or later.

---

**Made with ❤️ for the Moodle community**
