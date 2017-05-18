<?php

    namespace Inc\Modules\Pages;

    class Site
    {

        public $core;

        public function __construct($object)
        {
            $this->core = $object;

            $path = $this->core->router->execute(true);
            if(empty($path))
                $this->core->router->changeRoute($this->core->getSettings('settings', 'homepage'));

            $this->core->router->set('(:str)', function($slug) {
                $langs = $this->_getLanguages();
                if(array_key_exists($slug, $langs))
                {
                    $_SESSION['lang'] = $langs[$slug];
                    $slug = $this->core->getSettings('settings', 'homepage');
                }
                else if(!isset($_SESSION['lang']))
                {
                    $_SESSION['lang'] = $this->core->getSettings('settings', 'lang_site');
                }

                $this->_importPage($slug);
            });
            $this->core->router->set('(:str)/(:str)', function($lang, $slug) {
                $langs = $this->_getLanguages();
                $reload = ($langs[$lang] != $_SESSION['lang']);
                if(array_key_exists($lang, $langs))
                    $_SESSION['lang'] = $langs[$lang];
                else
                    $_SESSION['lang'] = $this->core->getSettings('settings', 'lang_site');

                if($reload)
                    redirect(url(trim(preg_replace("/_[a-z]+/", $_SESSION['lang']).'/'.$slug, '/')));

                $this->_importPage($slug);
            });

            $this->_importAllPages();
		}

        /**
        * get a specific page 
        */
        private function _importPage($slug = null)
        {
            if(!empty($slug))
            {
                $row = $this->core->db('pages')->where('slug', $slug)->where('lang', $this->_getCurrentLang())->oneArray();

                if(empty($row))
                {
                    header('HTTP/1.0 404 Not Found');

                    if(!($row = $this->_get404()))
                    {
                        echo '<h1>404 Not Found</h1>';
                        echo $this->core->lang['pages']['not_found'];
                        exit;
                    }
                }
            }
            else
            {
                // Get page from slected language
                // $row = $this->core->db('pages')->where('slug', $this->core->getSettings('settings', 'homepage'))->where('lang', $this->_getCurrentLang())->oneArray();

                // if(!is_array($row))
                // {
                    header('HTTP/1.0 404 Not Found');
                    if(!($row = $this->_get404()))
                    {
                        echo '<h1>404 Not Found</h1>';
                        echo $this->core->lang['pages']['not_found'];
                        exit;
                    }
                // }
            }

            if(intval($row['markdown']))
            {
                $parsedown = new \Inc\Core\Lib\Parsedown();
                $row['content'] = $parsedown->text($row['content']);
            }

            $this->core->template = $row['template'];
            $this->core->append('<meta name="generator" content="Batflat" />', 'header');
            $this->core->tpl->set('page', $row);
        }

        /**
        * get array with all pages
        */
        private function _importAllPages()
        {
            $rows = $this->core->db('pages')->where('lang', $this->_getCurrentLang())->toArray();

            $assign = [];
            foreach($rows as $row)
            {
                $assign[$row['id']] = $row;
            }
            $this->core->tpl->set('pages', $assign);
        }

        private function _get404()
        {
            $row = $this->core->db('pages')->where('title', '404')->where('lang', $this->_getCurrentLang())->oneArray();
            if(!empty($row)) return $row;
            else return false;
        }
        
        private function _getCurrentLang()
        {
            if(!isset($_SESSION['lang']))
                return $this->core->getSettings('settings', 'lang_site');
            else
                return $_SESSION['lang'];
        }

        private function _getLanguages()
        {
            $langs = glob('inc/lang/*', GLOB_ONLYDIR);
            $return = [];
            foreach($langs as $lang)
            {
                preg_match_all('/([a-z]{2})_([a-z]+)/', str_replace('inc/lang/', null, $lang), $matches);
                $return[ $matches[1][0] ] = $matches[0][0];
            }

            return $return;
        }

    }