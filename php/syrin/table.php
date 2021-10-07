<?php
    require_once(__DIR__ . '/db.php');

    /**
     * The class for interacting with the database at the table level.
     * 
     * @since v0.1.0
     * @version v0.1.0
     * @author Christopher Bishop
     */
    class Table implements ArrayAccess {
        private $db;
        private $tableName;
        private $primaryKey;

        /**
         * Initialize a new table.
         * 
         * @param DB $db The core database.
         * @param string $tableName The database name.
         * @param string $primaryKey The primary key column name.
         */
        public function __construct($db, $tableName, $primaryKey = null) {
            $this->db = $db;
            $this->tableName = $tableName;
            $this->primaryKey = $primaryKey;
        }

        /**
         * @return DB the core database.
         */
        public function getDatabase() {
            return $this->db;
        }
        /**
         * @return string the name of the table 
         */
        public function getTableName() {
            return $this->tableName;
        }
        /**
         * @return string the column name of the primary key.
         */
        public function getPrimaryKey() {
            return $this->primaryKey;
        }

        /**
         * Selects rows from this table with optional arguments.
         * 
         * @param array $args Optional arguments.
         * @return QueryResult Result of the select query.
         */
        public function select($args = []) {
            return $this->db->select($this->tableName, $args);
        }
        /**
         * Selects a single row with a specific primary key value.
         * 
         * @param mixed $value The primary key value.
         * @param array $args Optional arguments.
         * @return QueryResult Result of the select query.
         */
        public function selectOne($value) {
            return $this->select([
                'where' => ["$this->primaryKey = ?", $value],
                'limit' => 1
            ]);
        }

        /**
         * Counts rows from this table with optional arguments.
         * 
         * @param array $args Optional arguments.
         * @return QueryResult Result of the count query.
         */
        public function count($args = []) {
            return $this->db->count($this->tableName, $args);
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
        public function pageCount($limit, $args = []) {
            return $this->db->pageCount($this->tableName, $limit, $args);
        }

        /**
         * Inserts a row into this table.
         * 
         * @param array $row An assosiative array which includes the column and values.
         * @return QueryResult the result of the query.
         */
        public function insert($row) {
            return $this->db->insert($this->tableName, $row);
        }

        /**
         * Updates rows in a table.
         * 
         * @param array An associative array representing the columns and values to update.
         * @param array $args Optional arguments.
         * @return QueryResult
         */
        public function update($row, $args = []) {
            return $this->db->update($this->tableName, $row, $args);
        }
        /**
         * Updates a single row in this table where $primaryKey = $value.
         * @param array An associative array representing the columns and values to update.
         * @param mixed The value of the primary key. If null the value will be taken from the row.
         */
        public function updateOne($row, $value = null) {
            if($value == null) {
                $value = $row[$this->primaryKey];
            }
            
            return $this->db->update($this->tableName, $row, [
                'where' => ["$this->primaryKey = ?", $value]
            ]);
        }

        /**
         * Deletes all the rows in this table which follow the $where condition.
         * 
         * @param mixed $where The condition each row being deleted must meet.
         */
        public function delete($where) {
            return $this->db->delete($this->tableName, $where);
        }
        /**
         * Deletes a single row in this table which follow the $primaryKey = $value
         * 
         * @param mixed $value The value of the primary key.
         */
        public function deleteOne($value) {
            return $this->db->delete($this->tableName, [
                "$this->primaryKey = ?", $value
            ]);
        }

        //
        // Implementation of ArrayAccess where the offset is always the primary key.
        //

        /**
         * Checks to see if there is a row with a specific primary key.
         * 
         * @param mixed $offset - The primary key to check.
         * @return bool
         */
        public function offsetExists($offset) {
            return $this->count([
                'where' => ["$this->primaryKey = ?", $offset],
                'limit' => 1
            ])->count() == 1;
        }
        /**
         * Gets the row with the primary key of offset.
         * 
         * @param mixed $offset - The primary key to use.
         * @return array Assoc array representing the row.
         */
        public function offsetGet($offset) {
            $results = $this->selectOne($offset);
            if($results->count() == 1) return $results->rows[0];
            else return null;
        }
        /**
         * Updates the row with the primary key of offset.
         * This function cannot be used for inserting.
         * 
         * @param mixed $offset - The primary key to use.
         */
        public function offsetSet($offset, $value) {
            $this->updateOne($value, $offset);
        }
        /**
         * Deletes the row with the primary key of offset.
         * 
         * @param mixed $offset - The primary key to use.
         */
        public function offsetUnset($offset) {
            $this->deleteOne($offset);
        }
    }
?>