<?php
    const AUTOMATE_VERSION = 5;

    require_once('../php/syrin/table.php');
    require_once('../php/syrin/rest.php');
    header('Content-Type: application/json', true);

    /**
     *  A rest server class for incoming requests.
     */
    class PunchRest extends Restable {
        public $punches;
        public $authTable;

        /**
         * Gets all punches or by ID.
         */
        private function rget($args) {
            $result = $this->punches->select([
                'orderBy' => 'punch_time',
                'orderDir' => 'ASC',
                'where' => 'punch_archived = 0'
            ]);
            if($result->errorCode != '0') return new RestResult($result->errorCode, $result->error);

            $rr = new RestResult();
            $rr->data = [];

            $punch = [];
            foreach($result->rows as $row) {
                if(isset($punch['in'])) {
                    $punch['out'] = $row;

                    if($this->includePunch($punch)) {
                        $rr->data[] = $this->format($punch);
                    }
                    $punch = [];
                }
                else {
                    $punch['in'] = $row;
                }
            }
            if($this->includePunch($punch)) $rr->data[] = $this->format($punch);

            return $rr;
        }
        private function includePunch($punch) {
            if(!isset($punch['in'])) return false;

            $start = isset($_GET['start']) ? $_GET['start'] : PHP_INT_MIN;
            $end = isset($_GET['end']) ? $_GET['end'] : PHP_INT_MAX;
            $timeIn = $punch['in']['punch_time'];
            $timeOut = isset($punch['out']) ? $punch['out']['punch_time'] : time();
            
            return min($end, $timeOut) >= max($start, $timeIn);
        }
        private function format($punch) {
            if(isset($punch['in'])) $punch['in']['punch_time'] = date('c', $punch['in']['punch_time']);
            if(isset($punch['out'])) $punch['out']['punch_time'] = date('c', $punch['out']['punch_time']);

            return $punch;
        }

        /**
         * Gets the last few punches made in order by ID.
         */
        private function rget_last($args) {
            $result = $this->punches->select([
                'orderBy' => 'punch_id',
                'orderDir' => 'DESC',
                'where' => 'punch_archived = 0',
                'limit' => (isset($args['count'])) ? $args['count'] : 5
            ]);
            if($result->errorCode != '0') return new RestResult($result->errorCode, $result->error);

            $rr = new RestResult();
            $rr->data = [];

            foreach($result->rows as $row) {
                $rr->data[] = $row;
            }

            return $rr;
        }

        /**
         * Posts a single punch at any time.
         */
        private function rpost($args) {
            $now = floor(time() / 60) * 60;

            // Authentication.
            $auth = $this->auth();
            if($auth != null) return $auth;

            // Submit punch.
            $type = 'DEFAULT';
            $time = $now;
            if(isset($args['time'])) {
                $time = (int) $args['time'];
                $type = 'MISSING';
            }
            if($time > $now) {
                $rr = new RestResult(302, 'Cannot submit punch for the future.');
                $rr->toast = 'Cannot submit punch for the future.';
                return $rr;
            }

            $assoc = 'DEFAULT';
            if(isset($args['assoc'])) $assoc = strtoupper($args['assoc']);

            $result = $this->punches->insert([
                'punch_auth' => $_SERVER['PHP_AUTH_USER'],
                'punch_time' => $time,
                'punch_type' => $type,
                'punch_assoc' => $assoc
            ]);

            $rr = RestResult::fromQueryResult($result);
            $rr->toast = 'SERVER: Failed to submit punch.';
            if($result->errorCode == 0) $rr->toast = 'Submitted ' . $assoc . ' punch succesfully.';

            return $rr;
        }
        private function auth() {
            if(!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
                $rr = new RestResult(301, 'Authentication failed.');
                $rr->toast = 'Authentication failed.';
                return $rr;
            }
            else {
                $result = $this->authTable->select([
                    'where' => ['auth_username = ?', $_SERVER['PHP_AUTH_USER']],
                    'limit' => 1
                ]);
                
                if($result->errorCode != 0 || $result->count() == 0 || !password_verify($_SERVER['PHP_AUTH_PW'], $result->rows[0]['auth_password'])) {
                    $rr = new RestResult(301, 'Authentication failed.');
                    $rr->toast = 'Authentication failed.';
                    return $rr;
                }
                else return null;
            }
        }

        private function rdelete($args) {
            if(!isset($args['id'])) {
                $rr = new RestResult(304, 'This function requires an id.');
            }

            // Authenticate
            $auth = $this->auth();
            if($auth != null) return $auth;

            // Archive punch.
            $result = $this->punches->updateOne([
                'punch_archived' => 1
            ], $args['id']);
            
            $rr = RestResult::fromQueryResult($result);
            $rr->toast = 'SERVER: Failed to remove punch.';
            if($result->errorCode == 0) $rr->toast = 'Removed punch succesfully.';

            return $rr;
        }

        /**
         * Fixes all the timestamps to not include seconds.
         */
        private function rpatch_fix($args) {
            $result = $this->punches->select();
            foreach($result->rows as $row) {
                $row['punch_time'] = (int) floor($row['punch_time'] / 60) * 60;
                $res = $this->punches->update($row);

                if($res->errorCode != 0) return RestResult::fromQueryResult($res);
            }

            $rr = new RestResult();
            $rr->data = 'Successfully fixed all timestamps.';
            return $rr;
        }
    }

    // Set the timezone.
    if(isset($_GET['timezone'])) {
        date_default_timezone_set(timezone_name_from_abbr("", $_GET['timezone'] * 3600, false));
    }

    $db = new DB('localhost', 'hazdry5_root', 'd439322b9c04499ef374af01fd29cc4e', 'hazdry5_bbclock');
    $db->connect();
    
    $rest = new PunchRest();
    $rest->punches = new Table($db, 'punch', 'punch_id');
    $rest->authTable = new Table($db, 'auth', 'auth_id');
    echo $rest->restJson();
?>