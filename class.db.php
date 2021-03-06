<?php
class db extends PDO {
    private $error;
    private $sql;
    private $bind;
    private $errorCallbackFunction;
    private $errorMsgFormat;
    private $stripTags = true;

    public function __construct($dsn, $user="", $passwd="", $options=array()) {
        if(empty($options)){
            $options = array(
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false
            );
        }

        try {
            parent::__construct($dsn, $user, $passwd, $options);
        } catch (PDOException $e) {
            trigger_error($e->getMessage());
            return false;
        }
    }

    private function debug() {
        if(!empty($this->errorCallbackFunction)) {
            $error = array("Error" => $this->error);
            if(!empty($this->sql)) {
                $error["SQL Statement"] = $this->sql;
            }
            if(!empty($this->bind)) {
                $error["Bind Parameters"] = trim(print_r($this->bind, true));
            }

            $backtrace = debug_backtrace();
            if(!empty($backtrace)) {
                foreach($backtrace as $info) {
                    if(isset($info["file"] ) && $info["file"] != __FILE__) {
                        $error["Backtrace"] = $info["file"] . " at line " . $info["line"];
                    }
                }
            }

            $msg = "";
            if($this->errorMsgFormat == "html") {
                if(!empty($error["Bind Parameters"]))
                    $error["Bind Parameters"] = "<pre>" . $error["Bind Parameters"] . "</pre>";
                $css = trim(file_get_contents(dirname(__FILE__) . "/error.css"));
                $msg .= '<style type="text/css">' . "\n" . $css . "\n</style>";
                $msg .= "\n" . '<div class="db-error">' . "\n\t<h3>SQL Error</h3>";
                foreach($error as $key => $val) {
                    $msg .= "\n\t<label>" . $key . ":</label>" . $val;
                }
                $msg .= "\n\t</div>\n</div>";
            }
            elseif($this->errorMsgFormat == "text") {
                $msg .= "SQL Error\n" . str_repeat("-", 50);
                foreach($error as $key => $val) {
                    $msg .= "\n\n$key:\n$val";
                }
            }

            $func = $this->errorCallbackFunction;
            $func($msg);
        }
    }

    public function delete($table, $where, $bind="") {
        $sql = "DELETE FROM " . $table . " WHERE " . $where . ";";
        return $this->run($sql, $bind);
    }

    private function filter($table, $info) {
        $driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
        if($driver == 'sqlite') {
            $sql = "PRAGMA table_info('" . $table . "');";
            $key = "name";
        } elseif($driver == 'mysql') {
            $sql = "DESCRIBE " . $table . ";";
            $key = "Field";
        } else {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '" . $table . "';";
            $key = "column_name";
        }

        if(false !== ($list = $this->run($sql))) {
            $fields = array();
            foreach($list as $record) {
                $fields[] = $record[$key];
            }
            return array_values(array_intersect($fields, array_keys($info)));
        }
        return array();
    }

    private function cleanup($bind) {
        if(!is_array($bind)) {
            if(!empty($bind)) {
                $bind = array($bind);
            } else {
                $bind = array();
            }
        }
        
        // Only call stripslashes() if magic quotes is on. Thankfully, this abomination was removed from PHP entirely in version 5.4.
        // Punch your webhost in the face if they're still running PHP < 5.4 with magic quotes enabled...
        // Also, stripslashes() replaces NULL values with an empty string, which we obviously don't want (I certainly don't, anyway) 
        if (function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc() && !is_null($val)) {
            foreach($bind as $key => $val) {
                $bind[$key] = stripslashes($val);
            }
        }
        
        if ($this->stripTags === true) {
            foreach($bind as $key => $val) {
                $bind[$key] = strip_tags($val);
            }
        }
        
        return $bind;
    }

    public function insert($table, $info, $returnRowCount=true) {
        $bind = array();
        if(isset($info[0]) && is_array($info[0])) { // adding multiple rows
            $fields = $this->filter($table, $info[0]);
            $sql = "INSERT INTO " . $table . " (" . implode($fields, ", ") . ") VALUES ";
            foreach($info as $row) {
                $sql .= "(:" . implode($row, ", :") . ")";
                $sql .= ($row !== end($info)) ? ", " : ";";
                foreach ($row as $field) {
                    $bind[":$field"] = $field;
                }
            }
        } else { // single row
            $fields = $this->filter($table, $info);
            $sql = "INSERT INTO " . $table . " (" . implode($fields, ", ") . ") VALUES (:" . implode($fields, ", :") . ");";
            foreach($fields as $field) {
                $bind[":$field"] = $info[$field];
            }
        }
        return $this->run($sql, $bind, $returnRowCount);
    }

    public function run($sql, $bind="", $returnRowCount=true) {
        $this->sql = trim($sql);
        $this->bind = $this->cleanup($bind);
        $this->error = "";

        try {
            $pdostmt = $this->prepare($this->sql);
            if($pdostmt->execute($this->bind) !== false) {
                if(preg_match("/^(" . implode("|", array("select", "describe", "pragma")) . ") /i", $this->sql)) {
                    return $pdostmt->fetchAll(PDO::FETCH_ASSOC);
                } elseif(preg_match("/^(" . implode("|", array("delete", "update")) . ") /i", $this->sql)) {
                    return $pdostmt->rowCount();
                } elseif(preg_match("/^(" . implode("|", array("insert")) . ") /i", $this->sql)) {
                    return ($returnRowCount) ? $pdostmt->rowCount() : $this->lastInsertId();
                }
            }
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->debug();
            return false;
        }
    }

    public function select($table, $where="", $bind="", $fields="*", $orderby="") {
        if (empty($fields)) {
            $fields = "*";
        }
        $sql = "SELECT " . $fields . " FROM " . $table;
        if(!empty($where)) {
            $sql .= " WHERE " . $where;
        }
        if(!empty($orderby)) {
            $sql .= " ORDER BY " . $orderby;
        }
        $sql .= ";";
        return $this->run($sql, $bind);
    }

    public function setErrorCallbackFunction($errorCallbackFunction, $errorMsgFormat="html") {
        //Variable functions for won't work with language constructs such as echo and print, so these are replaced with print_r.
        if(in_array(strtolower($errorCallbackFunction), array("echo", "print"))) {
            $errorCallbackFunction = "print_r";
        }

        if(function_exists($errorCallbackFunction)) {
            $this->errorCallbackFunction = $errorCallbackFunction;
            if(!in_array(strtolower($errorMsgFormat), array("html", "text"))) {
                $errorMsgFormat = "html";
            }
            $this->errorMsgFormat = $errorMsgFormat;
        }
    }

    public function update($table, $info, $where, $bind="") {
        $fields = $this->filter($table, $info);
        $fieldSize = sizeof($fields);

        $sql = "UPDATE " . $table . " SET ";
        for($f = 0; $f < $fieldSize; ++$f) {
            if($f > 0) {
                $sql .= ", ";
            }
            $sql .= $fields[$f] . " = :update_" . $fields[$f];
        }
        $sql .= " WHERE " . $where . ";";

        $bind = $this->cleanup($bind);
        foreach($fields as $field) {
            $bind[":update_$field"] = $info[$field];
        }

        return $this->run($sql, $bind);
    }
}
?>