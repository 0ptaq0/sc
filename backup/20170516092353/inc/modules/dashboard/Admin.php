<?php

    namespace Inc\Modules\Dashboard;
    
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
                'Main' => 'main'
            ];
        }

        public function main()
        {
            $this->core->tpl->set('modules', $this->_modulesList());

            $this->core->addCSS(url(MODULES.'/dashboard/css/style.css'));
        	return $this->core->tpl->draw(MODULES.'/dashboard/view/admin/dashboard.html');
		}

        private function _modulesList()
        {
            $modules = array_column($this->core->db('modules')->toArray(), 'dir');
            $result = [];

            if($this->core->getUserInfo('access') != 'all')
                $modules = array_intersect($modules, explode(',', $this->core->getUserInfo('access')));

            foreach($modules as $name)
            {
    			$files = [
    				'info'  => MODULES.'/'.$name.'/Info.php',
    				'admin' => MODULES.'/'.$name.'/Admin.php',
    			];

        		if(file_exists($files['info']) && file_exists($files['admin']))
        		{
                    $details        = $this->core->getModuleInfo($name);
                    $features       = $this->core->getModuleNav($name);
                    $details['url'] = url([ADMIN, $name, array_shift($features)]);

        			$result[] = $details;
            	}
        	}
        	return $result;
        }

    }