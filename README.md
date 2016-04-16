# php-pdo-wrapper-class
Automatically exported from code.google.com/p/php-pdo-wrapper-class

## Project overview
This project provides a minimal extension for PHP's PDO (PHP Data Objects) class designed for ease-of-use and saving development time/effort. This is achieved by providing methods - delete, insert, select, and update - for quickly building common SQL statements, handling exceptions when SQL errors are produced, and automatically returning results/number of affected rows for the appropriate SQL statement types.

##System Requirements
* PHP 5
* PDO Extension
* Appropriate PDO Driver(s) - PDO_SQLITE, PDO_MYSQL, PDO_PGSQL
* Only MySQL, SQLite, and PostgreSQL database types are currently supported.
