<?php

    /**
    * check if array have an empty values
    * @param array $keys
    * @param array $array
    * @return boolean
    */
    function checkEmptyFields(array $keys, array $array)
    {
        foreach($keys as $field)
        {
            if(empty($array[$field]) && ($array[$field] != 0))
                return true;
        }
        return false;
    }

    /**
    * delte dir with files
    * @param string $path
    * @return boolean
    */
    function deleteDir($path)
    {
        return !empty($path) && is_file($path) ? @unlink($path) : (array_reduce(glob($path.'/*'),
        function ($r, $i) { return $r && deleteDir($i); }, TRUE)) && @rmdir($path);
    }

    /**
    * remove special chars from string
    * @param string $text
    * @return string
    */
	function createSlug($text) {
    	setlocale(LC_ALL, 'pl_PL');
        $text = str_replace(' ', '-', trim($text));
	    $text = str_replace('.', '-', trim($text));
    	$text = iconv('utf-8', 'ascii//translit', $text);
    	$text = preg_replace('#[^a-z0-9\-]#si', '', $text);
        return strtolower(str_replace('\'', '', $text));
    }

    /**
    * convert special chars from array
    * @param array $array
    * @return array
    */
    function htmlspecialchars_array(array $array)
    {
        foreach($array as $key => $value)
        {
            if(is_array($value)) $array[$key] = htmlspecialchars_array($value);
            else $array[$key] = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $array;
    }

    /**
    * redirect to URL
    * @param string $url
    * @param array $data
    * @return void
    */
    function redirect($url, array $data = [])
    {
        if($data)
            $_SESSION['REDIRECT_DATA'] = $data;
        
        header("Location: $url");
        exit();
    }
    
    /**
    * get data from session
    * @return array or null
    */
    function getRedirectData()
    {
        if(isset($_SESSION['REDIRECT_DATA']))
        {
            $tmp = $_SESSION['REDIRECT_DATA'];
            unset($_SESSION['REDIRECT_DATA']);
            return $tmp;
        }  
        return null;     
    }

    /**
    * parse URL
    * @param int $key
    * @return array
    */
    function parseURL($key = null)
    {
        $url    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $url    = trim(str_replace($url, '', $_SERVER['REQUEST_URI']), '/');
        $url    = strtok($url, '?');
        $array  = explode('/', $url);

        if($key) return $array[$key-1];
        else return $array;
    }

    /**
    * add token to URL
    * @param string $url
    * @return string
    */
    function addToken($url)
    {
        if(isset($_SESSION['token']))
        {
            if(parse_url($url, PHP_URL_QUERY))
                return $url.'&t='.$_SESSION['token'];
            else
                return $url.'?t='.$_SESSION['token'];
        }
        return $url;
    }

    /**
    * create URL
    * @param string / array $data
    * @return string
    */
    function url($data = null)
    {
        if(filter_var($data, FILTER_VALIDATE_URL) !== FALSE)
            return $data;
        
        $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https://' : 'http://';
        $url = trim($protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $url = str_replace('/'.ADMIN, '', $url);

        if(is_array($data))
            $url = $url.'/'.implode('/', $data);
        elseif($data)
        {
            $data = str_replace(BASE_DIR.'/', null, $data);
            $url = $url.'/'.$data;
        }

        if(strpos($url, '/'.ADMIN.'/') !== false)
            $url = addToken($url);

        return $url;
    }

    /**
    * toggle empty variable
    * @param mixed $var
    * @param mixed $alternate
    * @return mixed
    */
    function isset_or(&$var, $alternate = null)
    {
        return (isset($var)) ? $var : $alternate;
    }

    /**
    * compares two version number strings
    * @param string $a
    * @param string $b
    * @return int
    */
    function cmpver($a, $b) 
    { 
        $a = explode(".", $a);
        $b = explode(".", $b);
        foreach ($a as $depth => $aVal)
        {
            if(isset($b[$depth]))
                $bVal = $b[$depth];
            else
                $bVal = "0";

            list($aLen, $bLen) = [strlen($aVal), strlen($bVal)];

            if($aLen > $bLen)
                $bVal = str_pad($bVal, $aLen, "0");
            elseif($bLen > $aLen)
                $aVal = str_pad($aVal, $bLen, "0");

            if($aVal > $bVal) return 1;
        }

        if($aVal == $bVal) return 0;

        return -1;
    }
