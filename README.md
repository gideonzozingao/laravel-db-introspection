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
