<?php

    namespace Inc\Modules\Modules;

    class Admin
    {

        public $core;

        public function __construct($object)
        {
            $this->core = $object;
		}

        public function navigation()
        {
            return [
                $this->core->lang['modules']['active']		=> 'active',
                $this->core->lang['modules']['unactive']	=> 'unactive',
                $this->core->lang['modules']['upload_new']	=> 'upload'
            ];
        }

        /**
        * list of active modules
        */
        public function active()
        {
        	$modules = $this->_modulesList('active');
            $this->core->tpl->set('modules', array_chunk($modules, 2));
            return $this->core->tpl->draw(MODULES.'/modules/view/admin/active.html');
		}

        /**
        * list of unactive modules
        */
        public function unactive()
        {
            $modules = $this->_modulesList('unactive');
     		$this->core->tpl->set('modules', array_chunk($modules, 2));
     		return $this->core->tpl->draw(MODULES.'/modules/view/admin/unactive.html');
		}

        /**
        * module upload
        */
        public function upload()
        {
        	return $this->core->tpl->draw(MODULES.'/modules/view/admin/upload.html');
		}

        /**
         * module extract
         */
        public function extract()
        {
            if(isset($_FILES['zip_module']['tmp_name']) && !FILE_LOCK)
            {
                $backURL = url([ADMIN, 'modules', 'upload']);
                $file = $_FILES['zip_module']['tmp_name'];

                // Verify ZIP
                $zip = zip_open($file);
                $modules = array();
                while($entry = zip_read($zip))
                {
                    $entry = zip_entry_name($entry);
                    if(preg_match('/^(.*?)\/Info.php$/', $entry, $matches))
                        $modules[] = ['path' => $matches[0], 'name' => $matches[1]];

                    if(strpos($entry, '/') === FALSE)
                    {
                        $this->core->setNotify('failure', $this->core->lang['modules']['upload_bad_file']);
                        redirect( $backURL );
                    }
                }

                // Extract to modules
                $zip = new \ZipArchive;
                if($zip->open($file) === TRUE)
                {
                    foreach($modules as $module)
                    {
                        if(file_exists(MODULES.'/'.$module['name']))
                        {
                            $tmpName = md5(time().rand(1,9999));
                            file_put_contents('tmp/'.$tmpName, $zip->getFromName($module['path']));
                            $info_new = include('tmp/'.$tmpName);
                            $info_old = include(MODULES.'/'.$module['name'].'/Info.php');
                            unlink('tmp/'.$tmpName);

                            if(cmpver($info_new['version'], $info_old['version']) <= 0)
                            {
                                $this->core->setNotify('failure', $this->core->lang['modules']['upload_bad_version']);
                                continue;
                            }
                        }
                        $this->unzip($file, MODULES.'/'.$module['name'], $module['name']);
                    }
                    
                    $this->core->setNotify('success', $this->core->lang['modules']['upload_success']);
                }
                else
                    $this->core->setNotify('failure', $this->core->lang['modules']['upload_bad_file']);
            }
            
            redirect( $backURL );
        }

		public function install($dir)
		{
    		$files = [
    			'info'  => MODULES.'/'.$dir.'/Info.php',
    			'admin' => MODULES.'/'.$dir.'/Admin.php',
    			'site'  => MODULES.'/'.$dir.'/Site.php'
    		];

    		if((file_exists($files['info']) && file_exists($files['admin'])) || (file_exists($files['info']) && file_exists($files['site'])))
    		{
				if($this->core->db('modules')->save(['dir' => $dir, 'sequence' => $this->core->db('modules')->count()]))
				{
                    $core = $this->core;
    				$info = include($files['info']);
                    if(isset($info['install'])) $info['install']();

	            	$this->core->setNotify('success', $this->core->lang['modules']['activate_success'], $dir);
            	}
                else
            		$this->core->setNotify('failure', $this->core->lang['modules']['activate_failure'], $dir);
        	}
            else
        		$this->core->setNotify('failure', $this->core->lang['modules']['activate_failure_files'], $dir);

        	redirect(url([ADMIN, 'modules', 'unactive']));
		}

		public function uninstall($dir)
		{
			if($this->core->db('modules')->delete('dir', $dir) && !in_array($dir, unserialize(BASIC_MODULES)))
			{
                $core = $this->core;
                $info = include(MODULES.'/'.$dir.'/Info.php');
                if(isset($info['uninstall'])) $info['uninstall']();

				$this->core->setNotify('success', $this->core->lang['modules']['deactivate_success'], $dir);
			}
            else
				$this->core->setNotify('failure', $this->core->lang['modules']['deactivate_failure'], $dir);

			redirect(url([ADMIN, 'modules', 'active']));
		}

		public function remove($dir)
		{
		    $path = MODULES.'/'.$dir;
			if(is_dir($path))
			{
				if(deleteDir($path))
					$this->core->setNotify('success', $this->core->lang['modules']['remove_success'], $dir);
				else
					$this->core->setNotify('failure', $this->core->lang['modules']['remove_failure'], $dir);
			}
			redirect(url([ADMIN, 'modules', 'unactive']));
		}

		private function _modulesList($type)
		{
			$dbModules = array_column($this->core->db('modules')->toArray(), 'dir');
			$result = [];

			foreach(glob(MODULES.'/*', GLOB_ONLYDIR) as $dir)
			{
        		$dir = basename($dir);
    			$files = [
    				'info'  => MODULES.'/'.$dir.'/Info.php',
    				'admin' => MODULES.'/'.$dir.'/Admin.php',
    				'site'  => MODULES.'/'.$dir.'/Site.php'
    			];

    			if($type == 'active')
    				$inArray = in_array($dir, $dbModules);
    			else
    				$inArray = !in_array($dir, $dbModules);

        		if(((file_exists($files['info']) && file_exists($files['admin'])) || (file_exists($files['info']) && file_exists($files['site']))) && $inArray)
        		{
	                $details = $this->core->getModuleInfo($dir);
                    $features = $this->core->getModuleNav($dir);
					$urls = [
						'url'			=> (is_array($features) ? url([ADMIN, $dir, array_shift($features)]) : '#'),
						'uninstallUrl'	=> url([ADMIN, 'modules', 'uninstall', $dir]),
						'removeUrl'		=> url([ADMIN, 'modules', 'remove', $dir]),
        				'installUrl'	=> url([ADMIN, 'modules', 'install', $dir])
        			];

        			if(in_array($dir, unserialize(BASIC_MODULES)))
        				$basic = ['basic' => true];
        			else
        				$basic = ['basic' => false];
        			$result[] = $details + $urls + $basic;
	        	}
        	}
        	return $result;
		}

        private function unzip($zipFile, $to, $path = '/')
        {
            $path = trim($path, '/');
            $zip = new \ZipArchive;
            $zip->open($zipFile);

            for($i = 0; $i < $zip->numFiles; $i++)
            {
                $filename = $zip->getNameIndex($i);

                if(empty($path) || strpos($filename, $path) == 0)
                {
                    $file = $to.'/'.str_replace($path, null, $filename);
                    if(!file_exists( dirname($file) ))
                        mkdir(dirname($file), 0777, true);

                    if(substr($file, -1) != '/')
                        file_put_contents($to.'/'.str_replace($path, null, $filename), $zip->getFromIndex($i));
                }
            }

            $zip->close();
        }
    }