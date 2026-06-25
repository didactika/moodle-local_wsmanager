# Changelog

All notable changes to the Web Service Manager plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-06-25

Initial release.

### Added

- YAML-driven provisioning: an isolated user, role, external service, capabilities, and token per schema.
- Automatic capability resolution from each function's Moodle declaration.
- Dashboard with schema listing, detail view, and in-browser YAML editor with live validation.
- Import/export of schemas as YAML or ZIP, with conflict handling.
- Bulk enable/disable, delete, and export.
- Schema versioning with history, diff, and rollback.
- Scheduled health checks with email notifications and log cleanup.
- REST API (`local_servicemanager_*`) for managing schemas programmatically.

### Security

- Tokens shown only once; service users use non-routable emails; services restricted to authorized users; least-privilege roles.

[Unreleased]: https://github.com/didactika/moodle-local_servicemanager/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/didactika/moodle-local_servicemanager/releases/tag/v1.0.0
