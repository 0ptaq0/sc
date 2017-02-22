<?php

    namespace Inc\Modules\Navigation;

    class Site
    {

        public $core;

        public function __construct($object)
        {
            $this->core = $object;
            $this->_insertMenu();
		}

        /**
        * get nav data
        */
        private function _insertMenu()
        {
            $assign = [];
            $homepage = $this->core->getSettings('settings','homepage');

            // get nav
            $navs = $this->core->db('navs')->toArray();
            foreach($navs as $nav)
            {
                // get nav children
                $items = $this->core->db('navs_items')->where('nav', $nav['id'])->where('lang', $this->core->lang['name'])->asc('"order"')->toArray();

                if(count($items))
                {
                    // generate URL
                    foreach($items as &$item)
                    {
                        // if external URL field is empty, it means that it's a batflat page
                        $item['active'] = null;
                        if(!$item['url'])
                        {
                            $page = $this->core->db('pages')->where('id', $item['page'])->oneArray();
                            if($page['slug'] == $homepage)
                                $item['url'] = url('');
                            else
                                $item['url'] = url([$page['slug']]);

                            if(parseURL(1) == $page['slug'] || $this->_isChildActive($item['id'], parseURL(1)) || (parseURL(1) == null && $homepage == $page['slug']))
                            {
                                $item['active'] = 'active';
                            }
                        }
                        else
                        {
                            $item['url'] = url($item['url']);
                            $page = ['slug' => NULL];

                            if(url(parseURL(1)) == $item['url'] || $this->_isChildActive($item['id'], parseURL(1)) || (parseURL(1) == null && url($homepage) == $item['url']))
                            {
                                $item['active'] = 'active';
                            }

                            if($item['url'] == url($homepage))
                                $item['url'] = url('');
                        }
                    }

                    $navigation_admin = new Admin($this->core);
                    $this->core->tpl->set('navigation', ['list' => $navigation_admin->buildTree($items)]);
                    $assign[$nav['name']] = $this->core->tpl->draw(MODULES.'/navigation/view/nav.html');
                }
                else
                    $assign[$nav['name']] = NULL;   
            }

            $this->core->tpl->set('navigation', $assign);
        }

        /**
        * check if parent's child is active
        */
        private function _isChildActive($itemID, $slug)
        {
            $rows = $this->core->db('navs_items')->leftJoin('pages', 'pages.id = navs_items.page')->where('navs_items.parent', $itemID)->toArray();

            if(count($rows))
            {
                foreach($rows as $row)
                {
                    if($slug == $row['slug'])
                        return true;
                }
            }
            return false;
        }

    }