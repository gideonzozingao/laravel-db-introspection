# Laravel DB Introspection

[![Packagist Version](https://img.shields.io/packagist/v/zuqongtech/laravel-db-introspection.svg?style=for-the-badge)](https://packagist.org/packages/zuqongtech/laravel-db-introspection)
[![License](https://img.shields.io/github/license/gideonzozingao/laravel-db-introspection.svg?style=for-the-badge)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/zuqongtech/laravel-db-introspection/tests.yml?style=for-the-badge)](https://github.com/gideonzozingao/laravel-db-introspection/actions)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-%5E8.2-blue?style=for-the-badge&logo=php)](https://www.php.net)

> A Laravel package for automatic database introspection, model discovery, constraint analysis, and Eloquent model generation. Scans your connected database, analyzes schema metadata, and generates robust Eloquent models — complete with relationships, indexes, PHPDoc, and constraints.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Dependencies](#dependencies)
- [Laravel 12 Compatibility](#laravel-12-compatibility)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [Available Flags](#available-flags)
  - [Example Commands](#example-commands)
- [Generated Output](#generated-output)
- [Development](#development)
- [Contributing](#contributing)
- [Credits](#credits)

---

## Features

| Category | Capability |
|---|---|
| **Database Support** | Multi-engine support (MySQL, PostgreSQL, SQLite, SQL Server) |
| **Model Generation** | Auto-generates Eloquent models from live schema |
| **Relationships** | FK-based detection with optional inverse relationships |
| **Schema Analysis** | Constraint, index, and integrity analysis |
| **Developer Experience** | Full PHPDoc generation, dry-run preview, model backups |
| **Configurability** | Custom paths, namespaces, table filters, and connections |

---

## Requirements

- PHP `^8.2`
- Laravel `10.x` or `11.x`
- Composer `2.x`

---

## Dependencies

The following locked versions are used in this package. All runtime dependencies carry an MIT license unless noted.

**Runtime**

| Package | Version | Purpose |
|---|---|---|
| `laravel/framework` | `v11.46.1` | Core Laravel framework |
| `nesbot/carbon` | `3.10.3` | Date/time handling |
| `doctrine/inflector` | `2.1.0` | String inflection for model naming |
| `guzzlehttp/guzzle` | `7.10.0` | HTTP client |
| `monolog/monolog` | `3.9.0` | Logging |
| `ramsey/uuid` | `4.9.1` | UUID generation |
| `league/flysystem` | `3.30.2` | Filesystem abstraction |
| `vlucas/phpdotenv` | `v5.6.2` | Environment variable loading |
| `symfony/console` | `v7.3.6` | CLI command infrastructure |
| `brick/math` | `0.14.0` | Arbitrary-precision arithmetic |

**Development**

| Package | Version | Purpose |
|---|---|---|
| `phpunit/phpunit` | `11.5.44` | Test runner |
| `orchestra/testbench` | `v9.15.0` | Laravel package testing harness |
| `mockery/mockery` | `1.6.12` | Mock object framework |
| `fakerphp/faker` | `v1.24.1` | Fake data generation for tests |
| `laravel/pint` | `v1.27.0` | PHP code style fixer |
| `nunomaduro/collision` | `v8.8.2` | CLI error reporting |
| `laravel/tinker` | `v2.10.1` | Interactive REPL |

---

## Laravel 12 Compatibility

This package supports both **Laravel 11** and **Laravel 12** from the same codebase. No breaking changes are required in your application — only the dependency constraints need updating.

### What changed in Laravel 12 that affects this package

**`Schema::getTableListing()` now returns schema-qualified names**

In Laravel 12, `Schema::getTableListing()` returns table names prefixed with the schema name by default (e.g. `main.users` instead of `users`). If the package calls this method internally to discover tables, you must strip the schema prefix or pass `schemaQualified: false`:

```php
// Laravel 11 behaviour (unqualified)
Schema::getTableListing();
// ['migrations', 'users', 'orders']

// Laravel 12 default (qualified)
Schema::getTableListing();
// ['main.migrations', 'main.users', 'main.orders']

// Laravel 12 — opt out of qualification to restore old behaviour
Schema::getTableListing(schemaQualified: false);
// ['migrations', 'users', 'orders']
```

**`Grammar` and `Blueprint` constructor signatures changed**

Laravel 12 requires a `Connection` instance to be passed to `Illuminate\Database\Grammar` and `Illuminate\Database\Schema\Blueprint` constructors. The old `setConnection()` method has been removed. If the package instantiates either class directly, update the call:

```php
// Laravel 11
$grammar = new MySqlGrammar;
$grammar->setConnection($connection);

// Laravel 12+
$grammar = new MySqlGrammar($connection);
```

**Carbon 2.x support removed**

Laravel 12 drops Carbon 2. This package's lock file already uses Carbon `3.10.3`, so no action is needed.

**`HasUuids` now generates UUIDv7**

The `HasUuids` trait switched from UUIDv4 to UUIDv7. This only matters if you rely on UUID ordering in generated models. No change is required for the generator itself.

---

### Upgrading the package to support Laravel 12

**1. Update `composer.json` constraints**

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0|^12.0"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0|^10.0",
        "phpunit/phpunit": "^11.0|^12.0",
        "pestphp/pest": "^2.0|^3.0"
    }
}
```

**2. Expand the CI matrix**

Add Laravel 12 alongside Laravel 11 in your GitHub Actions workflow:

```yaml
# .github/workflows/tests.yml
strategy:
  matrix:
    php: [8.2, 8.3, 8.4]
    laravel: ['11.*', '12.*']
    include:
      - laravel: '11.*'
        testbench: '9.*'
      - laravel: '12.*'
        testbench: '10.*'
    exclude:
      - php: 8.1
        laravel: '11.*'
      - php: 8.1
        laravel: '12.*'
```

**3. Guard schema-qualified table names**

If the package uses `Schema::getTableListing()`, add a version guard to normalise output across both framework versions:

```php
use Illuminate\Support\Facades\Schema;

$tables = version_compare(app()->version(), '12.0.0', '>=')
    ? Schema::getTableListing(schemaQualified: false)
    : Schema::getTableListing();
```

**4. Run the full test suite against both versions**

```bash
# Test against Laravel 11
composer require laravel/framework:^11.0 orchestra/testbench:^9.0 --no-update
composer update && composer test

# Test against Laravel 12
composer require laravel/framework:^12.0 orchestra/testbench:^10.0 --no-update
composer update && composer test
```

### Version support matrix

| Package version | Laravel 11 | Laravel 12 | PHP |
|---|---|---|---|
| `1.x` (current) | ✅ | ❌ | `^8.2` |
| `2.x` (planned) | ✅ | ✅ | `^8.2` |

> Laravel 11 security support ended March 12, 2026. Laravel 12 receives bug fixes until August 2026 and security fixes until February 2027.

---

## Installation

Install via Composer:

```bash
composer require zuqongtech/laravel-db-introspection
```

For local development:

```bash
git clone https://github.com/zuqongtech/laravel-db-introspection.git
cd laravel-db-introspection
composer install
```

---

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish \
  --provider="Zuqongtech\LaravelDbIntrospection\LaravelDbIntrospectionServiceProvider" \
  --tag=config
```

This creates `config/zt-introspection.php`:

```php
return [
    'output_path'   => app_path('Models'),
    'namespace'     => 'App\\Models',
    'ignore_tables' => [],
];
```

---

## Usage

### Basic Usage

```bash
php artisan zt:generate-models
```

### Available Flags

#### Model Generation

| Flag | Description | Example |
|---|---|---|
| `--force` | Overwrite existing models without prompting | `--force` |
| `--backup` | Back up existing models before overwriting | `--backup` |
| `--dry-run` | Preview actions without writing any files | `--dry-run` |
| `--namespace=` | Override the namespace for generated models | `--namespace="App\\Domain\\Models"` |
| `--path=` | Override the output directory | `--path=modules/Core` |

#### Table Selection & Filtering

| Flag | Description | Example |
|---|---|---|
| `--tables=*` | Generate models only for the specified tables | `--tables=users --tables=orders` |
| `--ignore=*` | Exclude specific tables from generation | `--ignore=migrations` |
| `--connection=` | Use a specific database connection | `--connection=pgsql` |

#### Documentation & Metadata

| Flag | Description | Example |
|---|---|---|
| `--with-phpdoc` | Add PHPDoc blocks for IDE autocompletion | `--with-phpdoc` |
| `--with-constraints` | Embed constraint details in model comments | `--with-constraints` |

#### Relationships

| Flag | Description | Example |
|---|---|---|
| `--with-inverse` | Generate inverse relations (`hasMany`, `hasOne`) | `--with-inverse` |
| `--validate-fk` | Validate all foreign key references exist | `--validate-fk` |

#### Constraint & Integrity Analysis

| Flag | Description | Example |
|---|---|---|
| `--analyze-constraints` | Display a summary of PKs, FKs, and indexes | `--analyze-constraints` |
| `--validate-fk` | Validate FK integrity across all tables | `--validate-fk` |
| `--show-recommendations` | Show schema optimization suggestions | `--show-recommendations` |

---

### Example Commands

Generate models with PHPDoc and inverse relationships:

```bash
php artisan zt:generate-models --with-phpdoc --with-inverse
```

Generate models for specific tables only:

```bash
php artisan zt:generate-models --tables=users --tables=orders
```

Validate foreign keys and analyze constraints:

```bash
php artisan zt:generate-models --validate-fk --analyze-constraints
```

Run a full analysis with optimization recommendations:

```bash
php artisan zt:generate-models --analyze-constraints --show-recommendations
```

Preview what would be generated without writing files:

```bash
php artisan zt:generate-models --dry-run
```

---

## Generated Output

Models are written to `app/Models/` by default:

```
app/
└── Models/
    ├── User.php
    ├── Order.php
    └── Product.php
```

Each generated model includes:

- `$table` — explicit table binding
- `$fillable` — all column names
- `$primaryKey` — including composite key support
- Soft delete detection (`SoftDeletes` trait)
- Timestamp detection (`$timestamps`)
- Relationships from foreign keys
- Optional inverse relationships
- Optional PHPDoc property blocks
- Optional constraint annotations

**Example generated model:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'id',
        'user_id',
        'product_id',
        'status',
        'created_at',
        'updated_at',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## Development

Clone and install dependencies:

```bash
git clone https://github.com/zuqongtech/laravel-db-introspection.git
cd laravel-db-introspection
composer install
```

Run the test suite:

```bash
composer test
```

Pull requests with tests and a clean commit history are welcome.

---

## Contributing

Bug reports and feature requests can be submitted via [GitHub Issues](https://github.com/gideonzozingao/laravel-db-introspection/issues).

When submitting a pull request:

- Include tests for any new behaviour
- Keep commits focused and well-described
- Follow PSR-12 coding standards

---

## Credits

Developed and maintained by **Gideon Zozingao** / [Zuqongtech](https://github.com/zuqongtech).

© 2025 Gideon Zozingao. Licensed under the [MIT License](LICENSE).
