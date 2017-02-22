<?php

    namespace Inc\Engine;

    class Admin extends Main
    {

		public $lang = [];
		private $assign = [];
        private $registerPage = [];
        public $module = null;

        public function __construct()
        {
            parent::__construct();

            $this->router->set('logout', function()
            {
                $this->logout();
            });
            $this->loadLanguage($this->getSettings('settings', 'lang_admin'));
        }

        /**
         * creates instance of installed modules
         * @return void
         */
        public function createModulesObject()
        {
            $this->module = new \Inc\Engine\Lib\ModulesCollection($this);
        }

        /**
        * set variables to template engine and display them
        * @param string $file
        * @return void
        */
        public function drawTheme($file)
        {
        	$this->assign['username']   = $this->getUserInfo('username');
        	$this->assign['notify']     = $this->getNotify();
            $this->assign['path']       = url();
            $this->assign['version']    = $this->getSettings('settings', 'version');

        	$this->assign['header'] = isset_or($this->appends['header'], ['']);
            $this->assign['footer'] = isset_or($this->appends['footer'], ['']);

			$this->tpl->set('bat', $this->assign);
            echo $this->tpl->draw(THEMES.'/admin/'.$file, true);
        }

        /**
        * load language files
        * @param string $lang
        * @return void
        */
        private function loadLanguage($language)
        {
            $this->lang['name'] = $language;
            
			foreach(glob(MODULES.'/*/lang/admin/'.$language.'.ini') as $file)
			{
            	$module = str_replace([MODULES.'/', '/lang/admin/'.$language.'.ini'], null, $file);
            	$this->lang[$module] = parse_ini_file($file);
			}

            foreach(glob('../inc/lang/'.$language.'/admin/*.ini') as $glob)
            {
                $file = pathinfo($glob);
                $this->lang[$file['filename']] = parse_ini_file($glob);
            }
			$this->tpl->set('lang', $this->lang);
		}

        /**
        * load module and set variables
        * @param string $name
        * @param string $feature
        * @param array $params (optional)
        * @return void
        */
        public function loadModule($name, $method, $params = [])
        {
            $row = $this->module->{$name};

            if($row && ($details = $this->getModuleInfo($name)))
            {
                if(($this->getUserInfo('access') == 'all') || in_array($name, explode(',', $this->getUserInfo('access'))))
                {
                    $details['content'] = $this->getModuleMethod($name, $method, $params);
                    $this->tpl->set('module', $details);
                }
                else exit;
            }
            else exit;
        }

        /**
        * create list of modules
        * @param string $activeModile
        * @param string $activeFeature
        * @return void
        */
        public function createNav($activeModule, $activeMethod)
        {
            $nav = [];
            $modules = $this->module->getArray();
            
            if($this->getUserInfo('access') != 'all')
                $modules = array_intersect_key($modules, array_fill_keys(explode(',', $this->getUserInfo('access')), null));

            foreach($modules as $dir => $module)
            {
                $subnav     = $this->getModuleNav($dir);
                $details    = $this->getModuleInfo($dir);

                if(isset($details['pages']))
                {
                    foreach($details['pages'] as $pageName => $pageSlug)
                        $this->registerPage($pageName, $pageSlug);
                }
                if($subnav)
                {
                    if($activeModule == $dir)
                        $activeElement = 'active';
                    else
                        $activeElement = null;

                    $subnavURLs = [];
                    foreach($subnav as $key => $val)
                    {
                        if(($activeModule == $dir) && isset($activeMethod) && ($activeMethod == $val))
                            $activeSubElement = 'active';
                        else
                            $activeSubElement = null;

                        $subnavURLs[] = [
                            'name'      => $key,
                            'url'       => url([ADMIN, $dir, $val]),
                            'active'    => $activeSubElement,
                        ];
                    }

                    if(count($subnavURLs) == 1)
                    {
                        $moduleURL = $subnavURLs[0]['url'];
                        $subnavURLs = [];
                    }
                    else
                        $moduleURL = '#';

                    $nav[] = [
                        'dir'       => $dir,
                        'name'      => $details['name'],
                        'icon'      => $details['icon'],
                        'url'       => $moduleURL,
                        'active'    => $activeElement,
                        'subnav'    => $subnavURLs,
                    ];
                }
            }
            $this->assign['nav'] = $nav;
        }

        /**
        * get module informations
        * @param string $dir
        * @return array
        */
		public function getModuleInfo($dir)
		{
			$file = MODULES.'/'.$dir.'/Info.php';
            $core = $this;
            
			if(file_exists($file))
                return include($file);
        	else 
                return false;
		}

		/**
        * get module's methods
        * @param string $dir
        * @return array
        */
		public function getModuleNav($dir)
		{
            if($this->module->has($dir))
                return $this->module->{$dir}->navigation();

            return false;
		}

		/**
        * get module method
        * @param string $dir
        * @param string $feature
        * @param array $params (optional)
        * @return array
        */
		public function getModuleMethod($name, $method, $params = [])
		{
            return call_user_func_array([$this->module->{$name}, $method], array_values($params));
		}

        /**
        * user login
        * @param string $username
        * @param string $password
        * @return bool
        */
        public function login($username, $password)
        {
            // Check attempt
            $attempt = $this->db('login_attempts')->where('ip', $_SERVER['REMOTE_ADDR'])->oneArray();

            // Create attempt if does not exist
            if(!$attempt)
            {
                $this->db('login_attempts')->save(['ip' => $_SERVER['REMOTE_ADDR'], 'attempts' => 0]);
                $attempt = ['ip' => $_SERVER['REMOTE_ADDR'], 'attempts' => 0, 'expires' => 0];
            }
            else
            {
                $attempt['attempts'] = intval($attempt['attempts']);
                $attempt['expires'] = intval($attempt['expires']);
            }

            // Is IP blocked?
            if((time() - $attempt['expires']) < 0)
            {
                $this->setNotify('failure', sprintf($this->lang['general']['login_attempts'], ceil(($attempt['expires']-time())/60)));
                return false;
            }

            $row = $this->db('users')->where('username', $username)->oneArray();
			if(count($row) && password_verify(trim($password), $row['password']))
            {
                // Reset fail attempts for this IP
                $this->db('login_attempts')->where('ip', $_SERVER['REMOTE_ADDR'])->save(['attempts' => 0]);

				$_SESSION['bat_user']   = $row['id'];
				$_SESSION['token']      = bin2hex(openssl_random_pseudo_bytes(6));
				$_SESSION['userAgent']  = $_SERVER['HTTP_USER_AGENT'];
				$_SESSION['IPaddress']  = $_SERVER['REMOTE_ADDR'];
				return true;
			}
            else
            {
                // Increase attempt
                $this->db('login_attempts')->where('ip', $_SERVER['REMOTE_ADDR'])->save(['attempts' => $attempt['attempts']+1]);
                $attempt['attempts'] += 1;

                // ... and block if reached maximum attempts
                if($attempt['attempts'] % 3 == 0)
                {
                    $this->db('login_attempts')->where('ip', $_SERVER['REMOTE_ADDR'])->save(['expires' => strtotime("+10 minutes")]);
                    $attempt['expires'] = strtotime("+10 minutes");

                    $this->setNotify('failure', sprintf($this->lang['general']['login_attempts'], ceil(($attempt['expires']-time())/60)));
                }
                else
                    $this->setNotify('failure', $this->lang['general']['login_failure']);

                return false;
            }
		}

        /**
        * user logout
        * @return void
        */
	    private function logout()
	    {
     		$_SESSION = [];
     		session_unset();
     		session_destroy();
            redirect(url(ADMIN.'/'));
	    }

        private function registerPage($name, $path)
        {
            $this->registerPage[] = ['id' => null, 'title' => $name, 'slug' => $path];
        }

        public function getRegisteredPages()
        {
            return $this->registerPage;
        }

	}