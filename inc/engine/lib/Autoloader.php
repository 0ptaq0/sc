<?php

    class Autoloader
    {
        public static function init($className)
        {
            // Convert directories to lowercase and process uppercase for class files
            $className = explode('\\', $className);
            $file = array_pop($className);
            $file = strtolower(implode('/',$className)).'/'.$file.'.php';

            if(strpos($_SERVER['SCRIPT_NAME'], '/'.ADMIN.'/') !== false)
                $file = '../'.$file;
            if(is_readable($file))
                require_once($file);
        }
    }

    header("X-Created-By: Batflat <batflat.org>");
    spl_autoload_register('Autoloader::init');