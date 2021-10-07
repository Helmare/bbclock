<?php
    const AUTOMATE_VERSION = 5;
    if(isset($_GET['version'])) {
        if($_GET['version'] != AUTOMATE_VERSION) echo 'update';
        else echo 'no-update';
    }
    else {
        echo 'version ' . AUTOMATE_VERSION;
    }
?>