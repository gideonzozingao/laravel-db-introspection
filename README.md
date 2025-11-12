
# 📦 Laravel DB Introspection

[![Packagist Version](https://img.shields.io/packagist/v/zuqongtech/laravel-db-introspection.svg?style=for-the-badge)](https://packagist.org/packages/zuqongtech/laravel-db-introspection)
[![License](https://img.shields.io/github/license/gideonzozingao/laravel-db-introspection.svg?style=for-the-badge)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/zuqongtech/laravel-db-introspection/tests.yml?style=for-the-badge)](https://github.com/gideonzozingao/laravel-db-introspection/actions)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x-FF2D20?style=for-the-badge\&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-%5E8.2-blue?style=for-the-badge\&logo=php)](https://www.php.net)

---

> **Zuqongtech/Laravel-DB-Introspection** — a Laravel package for automatic **database introspection** and **model generation**, supporting MySQL, PostgreSQL, SQL Server, and other major relational databases.

This package provides a single, powerful Artisan command that analyzes your connected database schema and **automatically generates elegant Eloquent models** — including table mappings, fillable fields, and detected relationships.

It’s perfect for developers working with **existing or enterprise-scale databases** who want to bootstrap Laravel models instantly, with **no manual typing**.

---

## 🧠 Features

* 🔍 Multi-database engine support:

  * MySQL
  * PostgreSQL
  * SQL Server
* 🧩 Auto-generates Eloquent models for every table
* 🔗 Detects and maps relationships (`hasOne`, `hasMany`, `belongsTo`)
* ⚙️ Customizable output directory and namespace
* 📁 Uses Laravel’s filesystem and schema builder for seamless integration

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require zuqongtech/laravel-db-introspection
```

If used inside a Laravel app, the service provider is automatically registered.

For local or standalone development:

```bash
git clone https://github.com/zuqongtech/laravel-db-introspection.git
cd laravel-db-introspection
composer install
```

---

## ⚙️ Configuration

To publish the configuration file for custom paths and namespace:

```bash
php artisan vendor:publish --provider="Zuqongtech\LaravelDbIntrospection\LaravelDbIntrospectionServiceProvider" --tag=config
```

This creates:

```
config/db-introspection.php
```

Example configuration:

```php
return [
    'output_path' => app_path('Models'),
    'namespace'   => 'App\\Models',
];
```

---

## 🧭 Usage

Run the package with one simple Artisan command:

```bash
php artisan db:generate-models
```

### Optional Flags

| Flag           | Description                  | Example                                |
| -------------- | ---------------------------- | -------------------------------------- |
| `--connection` | Use a specific DB connection | `--connection=pgsql`                   |
| `--path`       | Override output directory    | `--path=app/Models/Generated`          |
| `--namespace`  | Override generated namespace | `--namespace="App\\Models\\Generated"` |
| `--force`      | Overwrite existing models    | `--force`                              |
| `--relations`  | Auto-detect relationships    | `--relations`                          |
| `--timestamps` | Include timestamp properties | `--timestamps`                         |

Example usage:

```bash
php artisan db:generate-models --connection=mysql --path=app/Models/Auto --relations --force
```

---

## 📂 Example Output

After running the command, your models are automatically generated in the configured directory:

```
app/Models/Auto/User.php
app/Models/Auto/Order.php
app/Models/Auto/Product.php
```

Each model includes:

* `$table` property
* `$fillable` attributes
* Relationship definitions (if enabled)

---

## 💡 Example Generated Model

```php
<?php

namespace App\Models\Auto;

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

## 🧪 Running Tests

Run the test suite to validate functionality:

```bash
composer test
```

or directly via PHPUnit:

```bash
vendor/bin/phpunit
```

---

## 🧰 Development

If you want to contribute or customize:

```bash
git clone https://github.com/zuqongtech/laravel-db-introspection.git
cd laravel-db-introspection
composer install
composer test
```

Submit a pull request with well-documented commits and test coverage.

---

## 🧩 Requirements

* PHP 8.2 or higher
* Laravel 10.x or 11.x
* Composer 2.x

---

## 🪄 Credits

Developed and maintained by **Zuqongtech**
© 2025 Zuqongtech. All rights reserved.

---

## 🤝 Contributing & Bug Reports

We welcome all contributions from the Laravel community! 💪

If you discover a bug, want to request a feature, or improve performance:

1. Open an [issue](https://github.com/gideonzozingao/laravel-db-introspection/issues) describing the problem.
2. Submit a pull request with tests where possible.

Every contribution helps make **Laravel DB Introspection** more reliable and developer-friendly — empowering teams working with **large-scale databases** to move faster and smarter. ✨

👉 **GitHub:** [github.com/gideonzozingao/laravel-db-introspection](https://github.com/gideonzozingao/laravel-db-introspection)

---

