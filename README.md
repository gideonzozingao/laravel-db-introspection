## 📦 Laravel DB Introspection

> **Zuqongtech/LaravelDbIntrospection** — A Laravel package for automatic **database introspection** and **model generation**, supporting MySQL, PostgreSQL, SQL Server, and other major relational databases.

This package provides a powerful Artisan command that scans your database schema, reads its structure, and **automatically generates Eloquent models** with proper relationships, table mappings, and fillable fields.

It’s ideal for developers working with existing databases or large schemas who want to **bootstrap Laravel models instantly** without writing them manually.

---

## 🧠 Features

* 🔍 Database introspection for multiple engines:

  * MySQL
  * PostgreSQL
  * SQL Server
  * SQLite
* 🧩 Auto-generates Eloquent models for all tables
* 🔗 Detects relationships (hasOne, hasMany, belongsTo) where possible
* ⚙️ Configurable output directory and namespace
* 📁 Uses Laravel’s native filesystem and schema builder
* 🧪 Fully testable with [Orchestra Testbench](https://github.com/orchestral/testbench)

---

## 🚀 Installation

### Step 1 — Install via Composer

```bash
composer require zuqongtech/laravel-db-introspection
```

If you’re using it **inside a Laravel application**, that’s all you need.
Laravel will automatically discover the service provider.

If you’re using it as a **standalone package for development**, clone or install locally:

```bash
git clone https://github.com/zuqongtech/laravel-db-introspection.git
cd laravel-db-introspection
composer install
```

---

## ⚙️ Configuration

If you’d like to publish the config file to customize output paths or namespace:

```bash
php artisan vendor:publish --provider="Zuqongtech\LaravelDbIntrospection\LaravelDbIntrospectionServiceProvider" --tag=config
```

This will create:

```
config/db-introspection.php
```

Inside, you can set:

```php
return [
    'output_path' => app_path('Models/Generated'),
    'namespace' => 'App\\Models\\Generated',
];
```

---

## 🧭 Usage

### Step 1 — Run the Introspection Command

```bash
php artisan introspect:database
```

### Step 2 — Choose Database Connection

You can specify which connection to introspect:

```bash
php artisan introspect:database --connection=pgsql
```

### Step 3 — Check Generated Models

Models will be generated automatically in the configured output directory.

Example output:

```
app/Models/Generated/User.php
app/Models/Generated/Order.php
app/Models/Generated/Product.php
```

Each model includes:

* `$table` property
* `$fillable` fields
* Detected relationships

---

## 🧪 Running Tests

To ensure everything is working properly, run:

```bash
composer test
```

or directly:

```bash
vendor/bin/phpunit
```

---

## 🧰 Development

If you want to contribute or modify the package:

1. Clone the repo
2. Install dependencies

   ```bash
   composer install
   ```
3. Run tests

   ```bash
   composer test
   ```
4. Make your changes and submit a PR

---

## 📁 Example Generated Model

```php
<?php

namespace App\Models\Generated;

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

## 🧩 Requirements

* PHP 8.2 or higher
* Laravel 10.x or 11.x
* Composer 2.x

---

## 🪄 Credits

Developed and maintained by **Zuqongtech**
© 2025 Zuqongtech. All rights reserved.

---

Would you like me to extend this README to include **example output for PostgreSQL and SQL Server introspection** (so users can see what relationships look like for those engines)?
# laravel-db-introspection
