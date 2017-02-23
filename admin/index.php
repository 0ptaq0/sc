<?php
 	ob_start();
	header('Content-Type:text/html;charset=utf-8');
    define('BASE_DIR', __DIR__.'/..');
    require_once('../inc/engine/defines.php');

    if(DEV_MODE)
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
    else
        error_reporting(0);

    require_once('../inc/engine/lib/Autoloader.php');

    // Admin core init
    $core = new Inc\Engine\Admin;

    if($core->loginCheck())
    {
        $core->createModulesObject();
        
        // Modules routing
        $core->router->set('(:str)/(:str)(:any)', function($module, $method, $params) use($core)
        {
            $core->createNav($module, $method);
            
            if($params)
                $core->loadModule($module, $method, explode('/', trim($params, '/')));
            else
                $core->loadModule($module, $method);
        });

        $core->router->execute();
        $core->drawTheme('index.html');
    }
    else
    {
    	if(isset($_POST['login']))
        {
			if($core->login($_POST['username'], $_POST['password']))
            {
                if(count($arrayURL = parseURL()) > 1)
                {
                    $url = array_merge([ADMIN], $arrayURL);
                    redirect(url($url));
                }
                redirect(url([ADMIN, 'dashboard', 'main']));
            }
		}
		$core->drawTheme('login.html');
    }

    ob_end_flush();