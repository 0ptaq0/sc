<?php

    namespace Inc\Modules\Settings;
    
    class Site
    {

        public $core;

        public function __construct($object)
        {
            $this->core = $object;
            $this->_importSettings();
		}

        private function _importSettings()
        {
            // general settings
            $settings = $this->core->getSettings();
            
            // modules settings
            $rows = $this->core->db('settings')->where('module', '<>', 'settings')->toArray();
            foreach($rows as $row)
            {
                $settings[$row['module']][$row['field']] = $row['value'];    
            }
            
            $this->core->tpl->set('settings', $settings);
        }

    }