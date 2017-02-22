<?php

    namespace Inc\Engine;

    class Site extends Main
    {

		public $lang = [];
        public $template = 'index.html';

        public function __construct()
        {
            parent::__construct();
            $this->loadLanguage();
            $this->loadModules();

            $this->router->execute();
            $this->drawTheme($this->template);
        }

        /**
        * set variables to template engine and display them
        * @param string $file
        * @return void
        */
        private function drawTheme($file)
        {
            $assign = [];
        	$assign['notify']   = $this->getNotify();
            $assign['powered']  = 'Powered by <a href="http://batflat.org/">Batflat</a>';
            $assign['path']     = url();
            $assign['theme']    = url('themes/'.$this->getSettings('settings', 'theme'));

        	$assign['header']   = isset_or($this->appends['header'], ['']);
            $assign['footer']   = isset_or($this->appends['footer'], ['']);

			$this->tpl->set('bat', $assign);
            echo $this->tpl->draw(THEMES.'/'.$this->getSettings('settings', 'theme').'/'.$file, true);
        }

        /**
        * load files with language
        * @param string $lang
        * @return void
        */
        private function loadLanguage()
        {
        	if(!isset($_SESSION['lang']) || !is_dir('inc/lang/'.$_SESSION['lang']))
				$this->lang['name'] = $this->getSettings('settings', 'lang_site');
			else
				$this->lang['name'] = $_SESSION['lang'];

            foreach(glob(MODULES.'/*/lang/'.$this->lang['name'].'.ini') as $file)
            {
                $module = str_replace([MODULES.'/', '/lang/'.$this->lang['name'].'.ini'], null, $file);
                $this->lang[$module] = parse_ini_file($file);
            }
			foreach(glob('inc/lang/'.$this->lang['name'].'/*.ini') as $file)
			{
            	$pathInfo = pathinfo($file);
            	$this->lang[$pathInfo['filename']] = parse_ini_file($file);
			}

			$this->tpl->set('lang', $this->lang);
		}

        /**
        * load modules
        * @return void
        */
        private function loadModules()
        {
            $rows = $this->db('modules')->toObject();
            foreach($rows as $row)
            {
                $file = MODULES.'/'.$row->dir.'/Site.php';
                if(is_file($file))
                {
    				$clsName = 'Site';
            		$namespace = 'inc\modules\\'.$row->dir.'\\'.$clsName;
                    ${$clsName} = new $namespace($this);
                }
            }
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
                return true;
            } else
                return false;
        }
	}