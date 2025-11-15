
# 📦 Laravel DB Introspection

[![Packagist Version](https://img.shields.io/packagist/v/zuqongtech/laravel-db-introspection.svg?style=for-the-badge)](https://packagist.org/packages/zuqongtech/laravel-db-introspection)
[![License](https://img.shields.io/github/license/gideonzozingao/laravel-db-introspection.svg?style=for-the-badge)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/zuqongtech/laravel-db-introspection/tests.yml?style=for-the-badge)](https://github.com/gideonzozingao/laravel-db-introspection/actions)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x-FF2D20?style=for-the-badge\&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-%5E8.2-blue?style=for-the-badge\&logo=php)](https://www.php.net)

---

> **Zuqongtech/Laravel-DB-Introspection** — a Laravel package for automatic **database introspection**, model discovery, constraint analysis, and **Eloquent model generation**.

It scans your connected database, analyzes schema metadata, and **automatically generates robust Eloquent models** — complete with relationships, indexes, PHPDoc, and constraints.

Perfect for teams working with **enterprise databases**, existing legacy schemas, or large systems that need instant, accurate Eloquent models.

---

# 🧠 Features

### Core Features

✔ Multi-database engine support
✔ Auto-generates Eloquent models
✔ Relationship detection (FK-based)
✔ Inverse relationships (optional)
✔ Constraint & index analysis
✔ Optional validation of keys and schema integrity
✔ Full PHPDoc generation for IDEs
✔ Dry-run preview mode
✔ Backups of existing models
✔ Highly configurable paths, namespaces & filters

---

# 🚀 Installation

```bash
composer require zuqongtech/laravel-db-introspection
```

For local package development:

```bash
git clone https://github.com/zuqongtech/laravel-db-introspection.git
cd laravel-db-introspection
composer install
```

---

# ⚙️ Configuration

Publish the config file:

```bash
php artisan vendor:publish --provider="Zuqongtech\LaravelDbIntrospection\LaravelDbIntrospectionServiceProvider" --tag=config
```

Generated file:

```
config/zt-introspection.php
```

Basic config:

```php
return [
    'output_path' => app_path('Models'),
    'namespace'   => 'App\\Models',
    'ignore_tables' => [],
];
```

---

# 🧭 Usage

Run the command:

```bash
php artisan zt:generate-models
```

---

# 🔧 All Available Flags (Full Documentation)

Below is the full documentation of **all flags** from the command signature:

---

## 🎯 Model Generation Flags

| Flag           | Description                               | Example                             |
| -------------- | ----------------------------------------- | ----------------------------------- |
| `--force`      | Overwrite existing models                 | `--force`                           |
| `--backup`     | Backup existing models before overwriting | `--backup`                          |
| `--dry-run`    | Preview actions without writing files     | `--dry-run`                         |
| `--namespace=` | Set the namespace of generated models     | `--namespace="App\\Domain\\Models"` |
| `--path=`      | Base folder for generated models          | `--path=modules/Core`               |

---

## 🎛 Table Selection & Filtering

| Flag            | Description                              | Example                          |
| --------------- | ---------------------------------------- | -------------------------------- |
| `--tables=*`    | Only generate models for specific tables | `--tables=users --tables=orders` |
| `--ignore=*`    | Skip listed tables                       | `--ignore=migrations`            |
| `--connection=` | Specify database connection              | `--connection=pgsql`             |

---

## 📚 Documentation & Metadata Flags

| Flag                 | Description                                | Example              |
| -------------------- | ------------------------------------------ | -------------------- |
| `--with-phpdoc`      | Include PHPDoc blocks for IDE autocomplete | `--with-phpdoc`      |
| `--with-constraints` | Include constraints in model comments      | `--with-constraints` |

---

## 🔗 Relationship Flags

| Flag             | Description                                      | Example          |
| ---------------- | ------------------------------------------------ | ---------------- |
| `--with-inverse` | Generate inverse relations (`hasMany`, `hasOne`) | `--with-inverse` |
| `--validate-fk`  | Validate all foreign key references              | `--validate-fk`  |

---

## 🧱 Constraint & Integrity Analysis

| Flag                     | Description                                    | Example                  |
| ------------------------ | ---------------------------------------------- | ------------------------ |
| `--analyze-constraints`  | Display constraint summary (PKs, FKs, indexes) | `--analyze-constraints`  |
| `--validate-fk`          | Validate FK integrity across tables            | `--validate-fk`          |
| `--show-recommendations` | Show optimization suggestions                  | `--show-recommendations` |

---

# 🔥 Combined Example Commands

### Generate everything with PHPDoc + inverse relationships:

```bash
php artisan zt:generate-models --with-phpdoc --with-inverse
```

### Only generate the `users` and `orders` models:

```bash
php artisan zt:generate-models --tables=users --tables=orders
```

### Validate foreign keys + analyze constraints:

```bash
php artisan zt:generate-models --validate-fk --analyze-constraints
```

### Full analysis + recommendations:

```bash
php artisan zt:generate-models --analyze-constraints --show-recommendations
```

### Run without creating any files (dry-run):

```bash
php artisan zt:generate-models --dry-run
```

---

# 📂 Example Output

Generated files:

```
app/Models/User.php
app/Models/Order.php
app/Models/Product.php
```

Each contains:

* `$table`
* `$fillable`
* `$primaryKey` or composite keys
* Soft delete detection
* Timestamp detection
* Relationships (FK + inverse)
* Optional PHPDoc
* Optional constraint notes

---

# 💡 Example Generated Model

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

# 🧰 Development

```bash
git clone https://github.com/zuqongtech/laravel-db-introspection.git
cd laravel-db-introspection
composer install
```

PRs with tests and clean commit history are appreciated.

---

# 🧩 Requirements

* PHP 8.2+
* Laravel 10.x or 11.x
* Composer 2.x

---

# 🪄 Credits

Developed and maintained by **Zuqongtech**
© 2025 Gideon Zozingao.

---

# 🤝 Contributing & Bug Reports

Issues and PRs are welcome!

👉 GitHub: [https://github.com/gideonzozingao/laravel-db-introspection](https://github.com/gideonzozingao/laravel-db-introspection)


