<?php

    namespace Inc\Engine\Lib;

    class Router
    {

        private $routes = array();
        private $patterns = array(
                ':any' => '.*',
                ':int' => '[0-9]+',
                ':str' => '[a-zA-Z0-9_-]+',
            );

        public function set($pattern, $callback)
        {
            $pattern = str_replace('/', '\/', $pattern);

            $this->routes[$pattern] = $callback;
        }

        public function execute($returnPath = false)
        {
            if(empty($path) && empty($_SERVER['PATH_INFO']))
                $_SERVER['PATH_INFO'] = explode("?", $_SERVER['REQUEST_URI'])[0];

            $url = rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/');
            $url = trim(str_replace($url, '', $_SERVER['PATH_INFO']), '/');

            if($returnPath)
                return $url;

            $this->routes = array_reverse($this->routes);

            foreach($this->routes as $pattern => $callback)
            {
                if(strpos($pattern, ':') !== false)
                {
                    $pattern = str_replace(array_keys($this->patterns), array_values($this->patterns), $pattern);
                }
                if(preg_match('#^'.$pattern.'$#', $url, $params) === 1)
                {
                    array_shift($params);
                    array_walk($params, function(&$val) { $val = $val ?: NULL; });

                    return call_user_func_array($callback, array_values($params));
                }
            }
        }

        public function changeRoute($path)
        {
            $_SERVER['PATH_INFO'] = $path;
        }

    }