<?php

session_start();

include('config.php');
require_once('src/system.php');

$system = new ReplicationManagement\System($config);
$system->run();
