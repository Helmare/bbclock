<?php
    /**
     * The core class for interacting with a MySQL database.
     *
     * @since v0.1.0
     * @version v0.1.0
     * @author Christopher Bishop
     */
    final class DB {
        private $host;
        private $username;
        private $password;
        private $dbName;

        private $conn;
        private $bindMethod;
        
        /**
         * Initializes a new DB.
         * 
         * @param string $host The host address of the database server.
         * @param string $username The username used to log in.
         * @param string $password The password used to log in.
         * @param string $dbName The name of the database to connect to.
         */
        public function __construct($host, $username, $password, $dbName) {
            $this->host = $host;
            $this->username = $username;
            $this->password = $password;
            $this->dbName = $dbName;

            $stmtClass = new ReflectionClass('mysqli_stmt');
            $this->bindMethod = $stmtClass->getMethod('bind_param');
        }

        /**
         * @return string the name fo the database to connect to.
         */
        public function getDatabaseName() { return $this->dbName; }
        /**
         * @return string the host address of the database server.
         */
        public function getHost() { return $this->host; }
        /**
         * @return string the username used to get into the database.
         */
        public function getUsername() { return $this->username; }
        /**
         * @return string the password used to get into the database.
         */
        public function getPassword() { return $this->password; }

        /**
         * Connects to the MySQL database.
         * 
         * @return mysqli the MySQL connection.
         */
        public function connect() {
            if($this->conn == null) {
                $this->conn = new mysqli($this->host, $this->username, $this->password);
                $this->conn->select_db($this->dbName);
                
                if($this->conn->connect_error) {
                    trigger_error('Could not connect to MySQL Server: ' . $this->conn->connect_error, E_USER_ERROR);
                    $this->conn = null;
                }
            }
            return $this->conn;
        }
        /**
         * Disconnects from the MySQL database.
         */
        public function disconnect() {
            if($this->conn !== null) {
                $this->conn->close();
            }
        }

        /**
         * Execute a query.
         *
         * @param mixed $query - A QueryBuilder with information to build the statement or an SQL string.
         * @param array $params - A parameter array for the prepared statement. This is only valid is the first argument is a SQL string.
         * @return QueryResult the result of the query.
         */
        public function query($query, $params = []) {
            // Initialize QueryBuilder.
            $qb = null;
            if(gettype($query) == 'string') $qb = new QueryBuilder($query, $params);
            else $qb = $query;
            
            // Setup statement and get results.
            $conn = $this->connect();
            $stmt = $conn->prepare($qb->getQuery());
            if($stmt) {
                if($qb->hasParameters()) $this->bindMethod->invokeArgs($stmt, $qb->getArgs());
                $stmt->execute();
            }

            // return query result.
            $qr = QueryResult::fromStmt($conn, $stmt);
            if($stmt) $stmt->close();
            return $qr;
        }

        /**
         * Selects rows from a table with optional arguments.
         * 
         * @param string $table Table name to select from.
         * @param array $args Optional arguments.
         * @return QueryResult Result of the select query.
         */
        public function select($table, $args = []) {
            $qb = new QueryBuilder();

            // SELECT
            $selecting = isset($args['select']) ? $args['select'] : '*';
            $this->appendArg($qb, $selecting, 'SELECT ');
            $qb->append(" FROM $table");

            // WHERE
            if(isset($args['where'])) $this->appendArg($qb, $args['where'], ' WHERE ');

            // ORDER BY
            if(isset($args['orderBy'])) {
                $this->appendArg($qb, $args['orderBy'], ' ORDER BY ');
                if(isset($args['orderDir'])) {
                    $qb->append(' ' . $args['orderDir']);
                }
            }

            // LIMIT AND PAGANATION
            if(isset($args['limit'])) {
                $qb->append(' LIMIT ' . $args['limit']);
                if(isset($args['offset'])) {
                    $qb->append(' OFFSET ' . $args['offset']);
                }
                elseif(isset($args['page']) && $page > 1) {
                    $qb->append(' OFFSET ' . ($args['page'] - 1) * $args['limit']);
                }
            }

            return $this->query($qb);
        }
        private function appendArg($qb, $arg, $prefix) {
            $type = gettype($arg);
            if($type == 'string') {
                $qb->append($prefix . $arg);
            }
            else if($type == 'array') {
                $qb->append($prefix . $arg[0], array_splice($arg, 1));
            }
        }

        /**
         * Counts rows from a table with optional arguments.
         * 
         * @param string $table Table name to count from.
         * @param array $args Optional arguments.
         * @return QueryResult Result of the count query.
         */
        public function count($table, $args = []) {
            $args['select'] = 'count(' . (isset($args['select']) ? $args['select'] : '*') . ') as total';
            return $this->select($table, $args);
        }
        
        /**
         * Counts the rows from a table with optional arguments
         * and provides the number of pages based on $limit.
         * 
         * @param string $table Table name to count from.
         * @param string $limit Length of a page (does not limit the count).
         * @param array $args Optional arguments.
         * @return QueryResult Result of the count query.
         */
        public function pageCount($table, $limit, $args = []) {
            $result = $this->count($table, $args);
            $result->rows[0]['pageTotal'] = $result->rows[0]['total'] / $len;

            return $result;
        }

        /**
         * Inserts a row into the table.
         * 
         * @param string $table Table being inserted into.
         * @param array $row An assosiative array which includes the column and values.
         * @return QueryResult the result of the query.
         */
        public function insert($table, $row) {
            // Build strings.
            $columnStr = '';
            $valueStr = '';
            $values = [];

            foreach($row as $key => $value) {
                $columnStr .= "$key, ";
                $valueStr .= "?, ";
                $values[] = $value;
            }

            // Adjust column and value strings.
            $columnStr = substr($columnStr, 0, strlen($columnStr) - 2);
            $valueStr = substr($valueStr, 0, strlen($valueStr) - 2);

            // Build and call queries.
            $iresult = $this->query("INSERT INTO $table ($columnStr) VALUES ($valueStr)", $values);
            if($iresult->errorCode != 0) return $iresult;
            
            $sresult = $this->query('SELECT LAST_INSERT_ID() as id');
            return $sresult;
        }

        /**
         * Updates rows in a table.
         * 
         * @param string $table Table used for updating
         * @param array An associative array representing the columns and values to update.
         * @param array $args Optional arguments.
         * @return QueryResult
         */
        public function update($table, $row, $args = []) {
            // Build COLUMN=VALUE.
            $cev = '';
            $values = [];
            foreach($row as $key => $value) {
                $cev .= "$key=?, ";
                $values[] = $value;
            }

            // Start Query
            $cev = substr($cev, 0, strlen($cev) - 2);
            $qb = new QueryBuilder("UPDATE $table SET $cev", $values);

            // Add condition.
            if(isset($args['where'])) {
                $this->appendArg($qb, $args['where'], ' WHERE ');
            }

            return $this->query($qb);
        }

        /**
         * Deletes all the rows which follow the $where condition.
         * 
         * @param string $table Table which is being deleted from.
         * @param mixed $where The condition each row being deleted must meet.
         */
        public function delete($table, $where) {
            $qb = new QueryBuilder("DELETE FROM $table WHERE ");
            $this->appendArg($qb, $where, '');
            return $this->query($qb);
        }
    }

    /**
     * A univiersal way of storing query information.
     * 
     * @since v0.1.0
     * @version v0.1.0
     * @author Christopher Bishop
     */
    final class QueryResult {
        /**
         * The rows extracted from the result set.
         */
        public $rows = [];
        /**
         * How many rows were affected.
         */
        public $affectedRows = 0;
        /**
         * The error number which given by the mysql database.
         */
        public $errorCode = 0;
        /**
         * The error given by the mysql database.
         */
        public $error = '';

        private function __construct() { }

        /**
         * @return int the number of rows.
         */
        public function count() {
            return count($this->rows);
        }

        /**
         * @param callback $callback A callback function to pass the $row and $index data.
         */
        public function eachRow($callback) {
            for($i = 0; $i < count($this->rows); $i++) {
                $callback($this->rows[$i], $i);
            }
        }

        /**
         * Builds a query result from a executed statement and it's connection.
         * 
         * @param mysqli $conn - A connection to the MySQL server.
         * @param mysqli_stmt $stmt - A MySQL statement which was just executed.
         */
        public static function fromStmt($conn, $stmt) {
            $result = new QueryResult();
            $result->errorCode = $conn->errno;
            $result->error = $conn->error;

            if($stmt) {
                $result->affectedRows = $stmt->affected_rows;

                if($stmtResult = $stmt->get_result()) {
                    while($row = $stmtResult->fetch_assoc()) {
                        $result->rows[] = $row;
                    }
                }
            }

            return $result;
        }
    }

    /**
     * A class for building prepared statement queries with more flexability.
     * 
     * @since v0.1.0
     * @version v0.1.0
     * @author Christopher Bishop
     */
    final class QueryBuilder {
        private $query = '';
        private $params = [];
        private $types = '';
        
        public function __construct($query = '', $params = []) {
            $this->append($query, $params);
        }

        /**
         * @return string the current state of the SQL query.
         */
        public function getQuery() {
            return $this->query;
        }
        /**
         * @return array the current parameters used in the entire SQl
         */
        public function getParameters() {
            return $this->params;
        }
        /**
         * @return bool whether this query has parameters.
         */
        public function hasParameters() {
            return count($this->params) > 0;
        }
        /**
         * @return string all the types for each parameter, used when binding the parameters to a statement.
         */
        public function getTypeString() {
            return $this->types;
        }
        /**
         * @return string gets the array of arguments to pass to the bind_param method.
         */
        public function getArgs() {
            $args = [$this->types];
            $params = $this->params;
            for($i = 0; $i < count($params); $i++) {
                $args[] = &$params[$i];
            }
            return $args;
        }

        /**
         * Appends the current query with a new query and parameter pair.
         * 
         * @author Christopher Bishop
         * @param string $query - An sql query with parameters.
         * @param array $params - An array of parameters (optional).
         * @return QueryBuilder this QueryBuilder for chaining.
         */
        public function append($query, $params = []) {
            for($i = 0; $i < count($params); $i++) {
                $param = $params[$i];
                if($type = $this->getTypeChar($param)) {
                    $this->types .= $type;
                    $this->params[] = $params[$i];
                }
                else trigger_error("Failed to add parameter $i to the list of parameters.", E_USER_ERROR);
            }
            $this->query .= $query;

            return $this;
        }
        private function getTypeChar($obj) {
            $type = gettype($obj);
            if($type == 'object') $class = get_class($obj);

            if($type == 'string') return 's';
            else if($type == 'boolean' || $type == 'integer') return 'i';
            else if($type == 'double') return 'd';
            else if($type == 'object' && ($class == 'Closure' || $class == 'Callable')) return false;
            else return 'b';
        }
    }
?>