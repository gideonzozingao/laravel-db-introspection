# 📜 CHANGELOG

All notable changes to Laravel DB Introspection will be documented in this file.

This project follows Semantic Versioning (SemVer) as closely as possible.
During early development (0.x), minor versions may include breaking changes, which will always be clearly documented.

## v0.1.0 — Initial Public Release

Release date: 2025-XX-XX
Packagist: zuqongtech/laravel-db-introspection

✨ Added
### Core Functionality

Multi-database schema introspection support:

- MySQL
- PostgreSQL
- SQL Server 

** Automatic Eloquent model generation from existing databases

Accurate detection of:
- Primary keys (single & composite)
- Foreign keys 
- Indexes and constraints
- Automatic relationship generation based on foreign keys  
- Optional inverse relationship generation (hasMany, hasOne)
- Pivot table detection and handling
- Soft delete (deleted_at) detection
- Timestamp (created_at, updated_at) detection

Model Generation

Generated models now include:

$table declaration

$fillable attributes

$primaryKey handling (including composite keys)

$casts where applicable

Relationship methods (belongsTo, hasMany, hasOne)

Optional PHPDoc blocks for IDE autocomplete

Optional constraint and index notes in model comments

Artisan Command

Introduced the primary Artisan command:

php artisan zt:generate-models


Supported flags include:

--force — Overwrite existing models

--backup — Backup existing models before overwriting

--dry-run — Preview generated output without writing files

--tables=* — Generate models only for specified tables

--ignore=* — Skip specific tables

--connection= — Specify database connection

--namespace= — Override model namespace

--path= — Override output directory

--with-phpdoc — Include PHPDoc blocks

--with-constraints — Include constraint notes

--with-inverse — Generate inverse relationships

--validate-fk — Validate foreign key integrity

--analyze-constraints — Display schema constraint summary

--show-recommendations — Display optimization suggestions

Configuration

Added publishable configuration file:

config/zt-introspection.php


Configurable options:

Output paths

Namespaces

Ignored tables

Introduced ConfigValidator for:

Namespace validation

Path validation

Developer-friendly warnings

CLI & Developer Experience

Improved console output formatting

Interactive confirmation prompts

Graceful handling of unsupported database engines

Improved directory auto-creation logic

Reduced duplicate file generation issues

Quality & Tooling

Added automated tests for:

Configuration validation

Schema edge cases

Added GitHub Actions CI workflow

Improved error handling and exception clarity

Verified compatibility with:

PHP 8.2+

Laravel 10.x

Laravel 11.x

Documentation

Added comprehensive README with:

Installation instructions

Configuration guide

Full CLI flag documentation

Example commands and outputs

Added example generated models

Added contribution guidelines

🛠 v0.1.1 — Maintenance & Stability Update (Planned)

Status: Planned
Type: Patch release

Planned Improvements

Improved handling of legacy schemas

Better enum and custom column type detection

Performance optimizations for large databases

Additional PostgreSQL & SQL Server test coverage

Minor CLI UX improvements

No breaking changes expected.

🚧 v0.2.0 — Generator Expansion & Architecture Upgrade (Planned)

Status: Planned
Type: Minor release (⚠️ may include breaking changes)

🎯 Goals

Transition the package from a model-only generator into a metadata-driven Laravel scaffolding framework, while preserving trust, stability, and configurability.

✨ Planned Features
Unified Generation Pipeline

Introduce metadata-driven generation layer:

Database is introspected once

All generators consume unified metadata

Centralized generation orchestration

New Optional Generators

Controllers (RESTful)

API Resources

Model Observers

Authorization Policies

New Artisan Command
php artisan zt:generate


With granular flags:

--models

--controllers

--resources

--observers

--policies

--all

--only=users,orders

🧠 Smart Automation (Planned)

Ownership-based policy inference (user_id)

Audit-aware observer templates

Relationship-aware resource generation

Foreign key–driven controller scaffolding

⚙️ Configuration Enhancements

Generator-level enable/disable options

Per-generator namespace and path configuration

Plugin-based generator registration

🧪 Quality Improvements

Expanded test suite for generators

Metadata validation tests

Improved error reporting for partial failures

⚠️ BREAKING CHANGES POLICY

This project follows Semantic Versioning with the following guarantees:

During 0.x Releases

Minor versions (0.x) may introduce breaking changes

All breaking changes will be:

Clearly documented in this CHANGELOG

Explained with upgrade notes

From 1.0.0 Onwards

Breaking changes will only occur in major releases

Minor and patch releases will be backward-compatible

What Counts as a Breaking Change

Renaming or removing Artisan commands

Changing default namespaces or paths

Modifying generated model behavior

Removing or changing configuration keys

📌 Upgrade Guidance

Upgrade notes will be included in:

GitHub Releases

CHANGELOG entries

README where applicable

🧾 Notes

Laravel DB Introspection is designed to evolve incrementally from a model introspection tool into a full Laravel database-driven scaffolding system, without sacrificing correctness, transparency, or developer trust.

Maintained by: Zuqongtech
Author: Gideon Zozingao
© 2025