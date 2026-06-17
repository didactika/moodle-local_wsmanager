# Changelog

All notable changes to the Web Service Manager plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/2.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-02

Initial release. Define a web service once as a YAML schema and Moodle Web Service Manager provisions the service user, role, external service, function mappings, capabilities, and token automatically — with a dashboard to manage, import/export, version, and audit schemas.

### Added

- YAML-driven service provisioning (user, role, external service, capabilities, token)
- Dashboard with schema listing, detail view, and interactive documentation
- Import/export of schemas as YAML or ZIP, with conflict handling (skip/overwrite/rename)
- Bulk operations: enable/disable, delete, export
- Schema versioning with history, comparison, and rollback
- Scheduled health checks with email notifications, and scheduled log cleanup
- Multi-language support: English, Spanish, Portuguese, Italian, French

### Security

- Tokens displayed only once after generation
- Service users use non-routable email addresses
- Services restricted to authorized users by default
- Role capabilities calculated automatically from assigned functions

### Requirements

- Moodle 4.5 or later
- PHP 8.1 or later
