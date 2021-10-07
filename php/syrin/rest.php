<?php
    /**
     * An abstract class used for running REST operations.
     * 
     * @since v0.1.0
     * @version v0.1.0
     * @author Christopher Bishop
     */
    abstract class Restable {
        /**
         * This function is called right before the rest operation function is called.
         * This function is only called if the REST request is valid.
         * 
         * @param string $action - The which will be called.
         * @param string $methodName - The name of the method which is going to be called.
         * @param array $args - The arguments from the body passed by reference.
         * @param string $error - An error message string passed in by reference.
         * @return int The status code for the error or 0 for no error.
         */
        protected function validate($action, $methodName, &$args, &$error) {
            $error = '';
            return 0;
        }

        /**
         * Runs this instance of REST.
         * 
         * This function will call other functions based on the action request variable.
         * It will attempted to call the function 'r<request method>[_<action>]', which should
         * accept an assoc array and return a RestResult.
         * 
         * @return RestResult the result from the rest operation.
         */
        public function rest() {
            // Get content type.
            $headers = getallheaders();
            $contentType = '';
            if(isset($headers['Content-Type'])) $contentType = $headers['Content-Type'];

            // Process body arguments.
            $args = [];
            if($_SERVER['REQUEST_METHOD'] == 'GET') {
                $args = $_GET;
            }
            else {
                $body = file_get_contents('php://input');
                if(strlen($body) > 0) {
                    if($contentType == 'application/json') {
                        $args = json_decode($body, true);
                        if(json_last_error() != JSON_ERROR_NONE) return new RestResult(104, 'JSON Error (' . json_last_error() . '): ' . json_last_error_msg());
                    }
                    elseif($contentType == 'application/x-www-form-urlencoded') {
                        parse_str($body, $args);
                    }
                    elseif(strlen($contentType) > 0) return new RestResult(101, 'Invalid Content-Type. Accepts application/json and application/x-www-form-urlencoded.');
                }
            }

            // Find action.
            $action = '';
            if(isset($args['action'])) {
                $action = $args['action'];
                unset($args['action']);
            }
            elseif(isset($_GET['action'])) $action = $_GET['action'];

            // Check if method exists.
            $class = new ReflectionClass($this);
            $methodName = 'r' . strtolower($_SERVER['REQUEST_METHOD']) . (strlen($action) > 0 ? "_$action" : '');
            if(!$class->hasMethod($methodName)) return new RestResult(102, "Method '$methodName' does not exist.");

            // Validate request.
            $error = '';
            $status = $this->validate($action, $methodName, $args, $error);
            if($status != null && $status != 0) return new RestResult($status, strlen($error) > 0 ? $error : 'Request validation failed.');

            // Invoke method.
            $method = $class->getMethod($methodName);
            $method->setAccessible(true);
            $result = $method->invoke($this, $args);

            // Check if results is a RestResult.
            if(get_class($result) != 'RestResult') return new RestResult(103, 'Invalid return type, expected RestResult.');
            return $result;
        }

        /**
         * Runs this instance of REST.
         * 
         * This function will call other functions based on the action request variable.
         * It will attempted to call the function 'r<request method>[_<action>]', which should
         * accept an assoc array and return a RestResult.
         * 
         * @return string a JSON representation of the RestResult.
         */
        public function restJson() {
            return json_encode($this->rest(), JSON_PRETTY_PRINT);
        }
    }

    /**
     * A result which is produced from calling a rest operation.
     * 
     * @since v0.1.0
     * @version v0.1.0
     * @author Christopher Bishop
     */
    final class RestResult {
        /**
         * An int value representing the rest status (0 for OK).
         */
        public $status = 0;
        /**
         * An error string for the rest result.
         */
        public $error = '';
        /**
         * An object representing the data from the rest operation.
         */
        public $data;

        public function __construct($status = 0, $error = '') {
            $this->status = $status;
            $this->error = $error;
        }

        /**
         * Sets the status and the error string.
         * 
         * @param int $status - The status of the rest result.
         * @param string $error - The error string for the rest result.
         */
        public function setStatus($status, $error = '') {
            $this->status = $status;
            $this->error = $error;
        }
        
        /**
         * A function which converts a QueryResult to a RestResult.
         */
        public static function fromQueryResult($qr) {
            $result = new RestResult();
            $result->data = $qr;
            if($qr->errorCode != 0) {
                $result->setStatus(105, 'MySQL Error');
            }

            return $result;
        }
    }
?>