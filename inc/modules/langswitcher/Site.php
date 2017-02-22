<?php

    namespace Inc\Modules\LangSwitcher;

    class Site
    {

        public $core;

        public function __construct($object)
        {
            $this->core = $object;

            $this->core->tpl->set('langSwitcher', $this->_insertSwitcher());
            
            if(isset($_GET['lang']))
            {
                $this->_setLanguage($_GET['lang']);
                redirect(url($_SERVER['HTTP_REFERER']));
            }
        }

        private function _insertSwitcher()
        {
            $this->core->tpl->set('languages', $this->_getLanguages());
            return $this->core->tpl->draw(MODULES.'/langswitcher/view/switcher.html');
        }

        private function _getLanguages($selected = null)
        {
            $langs = glob('inc/lang/*', GLOB_ONLYDIR);

            $result = [];
            foreach($langs as $lang)
            {
                $lang = basename($lang);

                if(($selected ? $selected : $this->core->lang['name']) == $lang) $attr = 'selected';
                else $attr = null;
                
                $result[] = [
                    'dir'  => $lang,
                    'name' => preg_replace('/[a-z]{2}_/', null, $lang),
                    'attr' => $attr
                ];
            }
            return $result;
        }

        private function _setLanguage($value)
        {
            if(in_array($value, array_column($this->_getLanguages(), 'dir')))
            {
                $_SESSION['lang'] = $value;
                return true;
            }
            return false;
        }

    }