<?php

    namespace Inc\Modules\LangSwitcher;

    class Site
    {

        public $core;

        public function __construct($object)
        {
            $this->core = $object;

            $this->core->tpl->set('langSwitcher', $this->_insertSwitcher());
            
            if($this->core->getSettings('settings', 'autodetectlang') == 1 && empty(trim($_SERVER['REQUEST_URI'], '/')) && !isset($_SESSION['lang']))
            {
                $detedcted = false;
                $languages = $this->_detectBrowserLanguage();
                foreach($languages as $value => $priority)
                {
                    $value = substr($value, 0, 2);
                    if($detect = glob('inc/lang/'.$value.'_*'))
                    {
                        $_GET['lang'] = basename($detect[0]);
                        break;
                    }
                }
            }

            if(isset($_GET['lang']))
            {
                $lang = explode('_', $_GET['lang'])[0];
                $this->_setLanguage($_GET['lang']);

                $dir = trim(dirname($_SERVER['SCRIPT_NAME']),'/');
                $trimmed = trim(str_replace([$dir, '?'.$_SERVER['QUERY_STRING']], null, $_SERVER['REQUEST_URI']), '/');

                $e = explode('/', $trimmed);
                foreach($this->_getLanguages() as $lng) 
                {
                    if($lng['symbol'] == $e[0])
                    {
                        array_shift($e);
                        break;
                    }
                }
                $slug = implode('/', $e);

                if($this->core->db('pages')->where('slug', $slug)->where('lang', $_GET['lang'])->oneArray())
                    redirect(url($lang.'/'.$slug));
                else
                    redirect(url($slug));
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
                    'dir'   => $lang,
                    'name'  => mb_strtoupper(preg_replace('/_[a-z]+/', null, $lang)),
                    'symbol'=> preg_replace('/_[a-z]+/', null, $lang),
                    'attr'  => $attr
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

        private function _detectBrowserLanguage()
        {
            $prefLocales = array_reduce(
            explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']), 
            function ($res, $el) { 
                list($l, $q) = array_merge(explode(';q=', $el), [1]); 
                $res[$l] = (float) $q; 
                return $res; 
            }, []);
            arsort($prefLocales);

            return $prefLocales;
        }

    }