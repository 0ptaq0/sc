<?php
 	ob_start();
	header('Content-Type:text/html;charset=utf-8');
    define('BASE_DIR', __DIR__);
    require_once('inc/engine/defines.php');

    if(DEV_MODE)
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
    else
        error_reporting(0);

    require_once('inc/engine/lib/Autoloader.php');

    // Site core init
    $core = new Inc\Engine\Site;

    ob_end_flush();