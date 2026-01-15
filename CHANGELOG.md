

# **CHANGELOG**

## **v0.0.1 – Initial Prototype Release**

* Added base project structure for *Laravel DB Introspection*.
* Implemented core service classes for schema reading.
* Introduced initial config file `db-introspection.php`.
* Added basic CLI command scaffold `db:scan`.
* Added helpers for database connection detection.
* Added early-stage model generator draft.

---

## **v0.0.2 – Stable Schema Reader Update**

* Improved MySQL, PostgreSQL, and SQL Server schema introspection logic.
* Fixed issues with nullable columns not being detected correctly.
* Added detection for primary keys, foreign keys, and indexes.
* Added more robust error handling.

* Generated models now include:

  * Fillable attributes
  * Cast types
  * Table names and primary keys
* Improved naming strategy for classes.
* Added support for pivot tables.

* Enhanced CLI command output formatting.
* Added interactive confirmation options.
* Added `--force` option for regenerating models.
* Added warnings for unrecognized database types.

* Introduced `ConfigValidator` to validate namespaces and paths.
* Added validation warnings when directories are missing.
* Added tests for configuration validator.
* Introduced publishable config file.
  
* Improved directory creation logic for generated models.
* Added support for custom output paths.
* Added `--dry-run` mode (preview output).
* Reduced duplicate file generation issues.
* Added auto-generation for model observers.
* Included new Artisan command:
  `php artisan db:generate-observers`
* Observers handle created/updated/deleted events scaffolding.
* Added fallback logic for models without timestamps.
* Added full README with usage examples.
* Added installation instructions for Laravel 10–12.
* Added GitHub Actions workflow for automated tests.
* Cleaned unused classes and improved namespaces.
--
