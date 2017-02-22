<?php

    namespace Inc\Engine;

    require_once('lib/functions.php');
    date_default_timezone_set("Europe/Warsaw");

    class Main
    {

        public $tpl, $router;
        public $appends = [];

        public function __construct()
        {
           	$this->setSession();

            $dbFile = 'inc/data/database.sdb';
            if(stristr($_SERVER['SCRIPT_NAME'], '/'.ADMIN.'/'))
                $dbFile = '../'.$dbFile;

            lib\QueryBuilder::connect("sqlite:{$dbFile}");

            $this->tpl = new lib\Templates($this);
            $this->router = new lib\Router;
        }
        
        public function db($table = null)
        {
            return new lib\QueryBuilder($table);
        }

        /**
	    * get module settings
        * @param string $module
	    * @param string $field
	    * @return string or array
	    */
  		public function getSettings($module = 'settings', $field = null)
  		{
  			if($field)
  			{
  			    $result = $this->db('settings')->where('module', $module)->where('field', $field)->oneObject();
                return $result->value;
            }
            else
            {
                $result = $this->db('settings')->where('module', $module)->toObject();

            	$array = [];
            	foreach($result as $row)
            		$array[$row->field] = $row->value;
            	return $array;
            }
        }

        /**
        * safe session
        * @return void
        */
        private function setSession()
        {
			ini_set('session.use_only_cookies', 1);
		    $cookieParams = session_get_cookie_params();
		    session_set_cookie_params(
                24*60*60,
		        $cookieParams["path"],
		        $cookieParams["domain"],
		        false,
		        true
            );
		    session_name('bat');
		    session_start();
    	}

        /**
        * create notification
        * @param string $type ('success' or 'error')
        * @param string $text
        * @param [, mixed $args [, mixed $... ]]
        * @return void
        */
        public function setNotify($type, $text, $args = null)
        {
        	$variables = [];
        	$numargs = func_num_args();
        	$arguments = func_get_args();

        	if($numargs > 1)
            {
				for($i = 1; $i < $numargs; $i++)
					$variables[] = $arguments[$i];
				$text = call_user_func_array('sprintf', $variables);
          		$_SESSION[$arguments[0]] = $text;
          	}
        }

        /**
        * display notification
        * @return array or false
        */
        public function getNotify()
        {
          	if(isset($_SESSION['failure']))
          	{
                $result = ['text' => $_SESSION['failure'], 'type' => 'danger'];
                unset($_SESSION['failure']);
                return $result;
            }
            else if(isset($_SESSION['success']))
            {
          	    $result = ['text' => $_SESSION['success'], 'type' => 'success'];
                unset($_SESSION['success']);
                return $result;
            } else return false;
        }

        /**
        * adds CSS URL to array
        * @param string $path
        * @return void
        */
		public function addCSS($path)
		{
		    $this->appends['header'][] = '<link rel="stylesheet" href="'.$path.'" />';
		}

        /**
        * adds JS URL to array
        * @param string $path
        * @param string $location (header / footer)
        * @return void
        */
		public function addJS($path, $location = 'header')
		{
			$this->appends[$location][] = '<script src="'.$path.'"></script>';
		}

        /**
        * adds string to array
        * @param string $string
        * @param string $location (header / footer)
        * @return void
        */
		public function append($string, $location)
		{
			$this->appends[$location][] = $string;
		}

        /**
        * chcec if user is login
        * @return bool
        */
        public function loginCheck()
        {
            if(isset($_SESSION['bat_user']) && isset($_SESSION['token']) && isset($_SESSION['userAgent']) && isset($_SESSION['IPaddress']))
            {
                if($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR'])
                    return false;
                if($_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT'])
                    return false;
                if(($_SESSION['token'] != @$_GET['t']) || !isset($_GET['t']))
                    return false;
                return true;
            } else
                return false;
        }

        /**
        * get user informations
        * @param string $filed
        * @param int $id (optional)
        * @return string
        */
        public function getUserInfo($field, $id = NULL)
        {
            if(!$id) $id = @$_SESSION['bat_user'];
            $row = $this->db('users')->where('id', $id)->oneArray();
            if(count($row))
                return $row[$field];
        }

	}