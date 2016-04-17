# php-pdo-wrapper-class
Automatically exported from code.google.com/p/php-pdo-wrapper-class

## Project overview
This project provides a minimal extension for PHP's PDO (PHP Data Objects) class designed for ease-of-use and saving development time/effort. This is achieved by providing methods - delete, insert, select, and update - for quickly building common SQL statements, handling exceptions when SQL errors are produced, and automatically returning results/number of affected rows for the appropriate SQL statement types.

##System Requirements
* PHP 5
* PDO Extension
* Appropriate PDO Driver(s) - PDO_SQLITE, PDO_MYSQL, PDO_PGSQL
* Only MySQL, SQLite, and PostgreSQL database types are currently supported.

##db Class Methods
Below you will find a detailed explanation along with code samples for each of the 6 methods included in the db class.

###constructor
```php
//__contruct Method Declaration
public function __construct($dsn, $user="", $passwd="") { }

//MySQL
$db = new db("mysql:host=127.0.0.1;port=8889;dbname=mydb", "dbuser", "dbpasswd");

//SQLite
$db = new db("sqlite:db.sqlite");
```

###delete
```php
//delete Method Declaration
public function delete($table, $where, $bind="") { }

//DELETE #1
$db->delete("mytable", "Age < 30");

//DELETE #2 w/Prepared Statement
$lname = "Doe";
$bind = array(
    ":lname" => $lname
)
$db->delete("mytable", "LName = :lname", $bind);
```
If no SQL errors are produced, this method will return the number of rows affected by the DELETE statement.

###insert
```php
//insert Method Declaration
public function insert($table, $info, $returnRowCount=true) { }

$insert = array(
    "FName" => "John",
    "LName" => "Doe",
    "Age" => 26,
    "Gender" => "male"
);
$db->insert("mytable", $insert);
```
If no SQL errors are produced, this method will return either:

1. The row count of the INSERT operation, if `$returnRowCount` is set to `true` (it is by default);
2. The ID of the last inserted row, if `$returnRowCount` is set to `false`.

`$info` can be a multi-dimensional array, which will then insert multiple rows.

###run
```php
//run Method Declaration
public function run($sql, $bind="") { }

//MySQL
$sql = <<<STR
CREATE TABLE mytable (
    ID int(11) NOT NULL AUTO_INCREMENT,
    FName varchar(50) NOT NULL,
    LName varchar(50) NOT NULL,
    Age int(11) NOT NULL,
    Gender enum('male','female') NOT NULL,
    PRIMARY KEY (ID)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;
STR;
$db->run($sql);

//SQLite
$sql = <<<STR
CREATE TABLE mytable (
    ID INTEGER PRIMARY KEY,
    LName TEXT,
    FName TEXT,
    Age INTEGER,
    Gender TEXT
)
STR;
$db->run($sql);
```
This method is used to run free-form SQL statements that can't be handled by the included delete, insert, select, or update methods. If no SQL errors are produced, this method will return the number of affected rows for DELETE, and UPDATE statements, the ID of the inserted row for INSERT statements, or an associative array of results for SELECT, DESCRIBE, and PRAGMA statements.

###select
```php
//select Method Declaration
public function select($table, $where="", $bind="", $fields="*") { }

//SELECT #1
$results = $db->select("mytable");

//SELECT #2
$results = $db->select("mytable", "Gender = 'male'");

//SELECT #3 w/Prepared Statement
$search = "J";
$bind = array(
    ":search" => "%$search"
);
$results = $db->select("mytable", "FName LIKE :search", $bind);
```
This method will return an associative array of results.

###setErrorCallbackFunction
```php
//setErrorCallbackFunction Method Declaration
public function setErrorCallbackFunction($errorCallbackFunction, $errorMsgFormat="html") { }

//The error message can then be displayed, emailed, etc within the callback function.
function myErrorHandler($error) {
}

$db = new db("mysql:host=127.0.0.1;port=8889;dbname=mydb", "dbuser", "dbpasswd");
$db->setErrorCallbackFunction("myErrorHandler");
/*
Text Version
$db->setErrorCallbackFunction("myErrorHandler", "text");

Internal/Built-In PHP Function
$db->setErrorCallbackFunction("echo");
*/
$results = $db->select("mynonexistingtable");
```
When a SQL error occurs, this project will send a formatted (HTML or text) error message to a callback function specified through the setErrorCallbackFunction method. The callback function's name should be supplied as a string without parentheses. As you can see in the examples provided above, you can specify either an internal/built-in PHP function, or a custom function you've created.

###update
```php
//update Method Declaration
public function update($table, $info, $where, $bind="") { }

//Update #1
$update = array(
    "FName" => "Jane",
    "Gender" => "female"
);
$db->update("mytable", $update, "FName = 'John'");

//Update #2 w/Prepared Statement
$update = array(
    "Age" => 24
);
$fname = "Jane";
$lname = "Doe";
$bind = array(
    ":fname" => $fname,
    ":lname" => $lname
);
$db->update("mytable", $update, "FName = :fname AND LName = :lname", $bind);
```
If no SQL errors are produced, this method will return the number of rows affected by the UPDATE statement.
