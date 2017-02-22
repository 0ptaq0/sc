<?php

    namespace Inc\Modules\Navigation;

    class Admin
    {

        public $core;
        private $assign = [];

        public function __construct($object)
        {
            $this->core = $object;
		}

        public function navigation()
        {
            return [
                $this->core->lang['general']['manage']			=> 'manage',
                $this->core->lang['navigation']['add_link']		=> 'newLink',
                $this->core->lang['navigation']['add_nav']		=> 'newNav'
            ];
        }

        /**
        * list of navs and their children
        */
        public function manage()
        {
        	// lang
        	if(isset($_GET['lang']) && !empty($_GET['lang']))
        		$lang = $_GET['lang'];
			else
				$lang = $this->core->getSettings('settings', 'lang_site');
			$this->assign['langs'] = $this->_getLanguages($lang);

        	// list
            $rows = $this->core->db('navs')->toArray();
        	if(count($rows))
        	{
	        	foreach($rows as $row)
	        	{
	        	    $row['name'] = $this->core->tpl->noParse('{$navigation.'.$row['name'].'}');
	        		$row['editURL'] = url([ADMIN, 'navigation', 'editNav', $row['id']]);
		        	$row['delURL'] = url([ADMIN, 'navigation', 'deleteNav', $row['id']]);
                    $row['items'] = $this->_getNavItems($row['id'], $lang);

		        	$this->assign['navs'][] = $row;
	        	}
        	}

        	$this->core->tpl->set('navigation', $this->assign);
        	return $this->core->tpl->draw(MODULES.'/navigation/view/admin/manage.html');
		}

        /**
        * add new link
        */
        public function newLink()
        {
			// lang
			if(isset($_GET['lang']))
				$lang = $_GET['lang'];
			else
				$lang = $this->core->getSettings('settings', 'lang_site');
			$this->assign['langs'] = $this->_getLanguages($lang);

			$this->assign['link'] = ['name' => '', 'lang' => '', 'page' => '', 'url' => '', 'parent' => ''];

			// list of pages
			$this->assign['pages'] = $this->_getPages($lang);
            foreach($this->core->getRegisteredPages() as $page)
                $this->assign['pages'][] = array_merge($page, ['id' => $page['slug'], 'attr' => null]);

			// list of parents
			$this->assign['navs'] = $this->_getParents($lang);

			$this->assign['title'] = $this->core->lang['navigation']['add_link'];
            $this->core->tpl->set('navigation', $this->assign);
        	return $this->core->tpl->draw(MODULES.'/navigation/view/admin/form.link.html');
        }     

        /**
        * edit link
        */
        public function editLink($id)
        {
			$row = $this->core->db('navs_items')->oneArray($id);

            if(!empty($row))
    	    {
                // lang
                if(isset($_GET['lang']))
                    $lang = $_GET['lang'];
                else
                    $lang = $row['lang'];
                $this->assign['langs'] = $this->_getLanguages($lang);

    	        $this->assign['link'] = filter_var_array($row, FILTER_SANITIZE_SPECIAL_CHARS);

                // list of pages
                $this->assign['pages'] = $this->_getPages($lang, $row['page']);
                foreach($this->core->getRegisteredPages() as $page)
                    $this->assign['pages'][] = array_merge($page, ['id' => $page['slug'], 'attr' => (($row['page'] == 0 && $row['url'] == $page['slug']) ? 'selected' : null)]);

                // list of parents
                $this->assign['navs'] = $this->_getParents($lang, $row['nav'], $row['parent'], $row['id']);
				
				$this->assign['title'] = $this->core->lang['navigation']['edit_link'];
				$this->core->tpl->set('navigation', $this->assign);
				return $this->core->tpl->draw(MODULES.'/navigation/view/admin/form.link.html');
            }
            else
                redirect(url([ADMIN, 'navigation', 'manage']));
        }

        /**
        * save link data
        */
        public function saveLink($id = null)
        {
			unset($_POST['save']);
            
            // check if it's an external link
            if($_POST['page']) $fields = ['name', 'page', 'lang', 'parent'];
            else $fields = ['name', 'url', 'lang', 'parent'];

            if(!$id)
                $location = url([ADMIN, 'navigation', 'newLink']);
            else
                $location = url([ADMIN, 'navigation', 'editLink', $id]);

            if(checkEmptyFields($fields, $_POST))
            {
            	$this->core->setNotify('failure', $this->core->lang['general']['empty_inputs']);
            	$this->assign['form'] = filter_var_array($_POST, FILTER_SANITIZE_SPECIAL_CHARS);
                redirect($location);
            }

			if($_POST['page']) $_POST['url'] = NULL;

            // get parent
            $parent = explode('_', $_POST['parent']);
            $_POST['nav'] = $parent[0];
            $_POST['parent'] = (isset($parent[1]) ? $parent[1] : 0);

            if(!is_numeric($_POST['page']))
            {
                $_POST['url'] = $_POST['page'];
                $_POST['page'] = 0;
            }

            if(!$id)
            {
				$_POST['"order"'] = $this->_getHighestOrder($_POST['nav'], $_POST['parent'], $_POST['lang']) + 1;
				$query = $this->core->db('navs_items')->save($_POST);
            }
            else
            {
				$query = $this->core->db('navs_items')->where($id)->save($_POST);
				if($query)
					$query = $this->core->db('navs_items')->where('parent', $id)->update(['nav' => $_POST['nav']]);
            }

            if($query)
        		$this->core->setNotify('success', $this->core->lang['navigation']['save_link_success']);
        	else
        		$this->core->setNotify('failure', $this->core->lang['navigation']['save_link_failure']);

            redirect($location);
        }

        /**
        * delete link
        */
        public function deleteLink($id)
        {
			if($this->core->db('navs_items')->where('id', $id)->orWhere('parent', $id)->delete())
				$this->core->setNotify('success', $this->core->lang['navigation']['delete_link_success']);
			else
				$this->core->setNotify('failure', $this->core->lang['navigation']['delete_link_failure']);

			redirect(url([ADMIN, 'navigation', 'manage']));
        }

        /**
        * add new nav
        */
        public function newNav()
        {
            $this->assign['title'] = $this->core->lang['navigation']['add_nav'];

            $this->assign['name'] = '';
            $this->core->tpl->set('navigation', $this->assign);
        	return $this->core->tpl->draw(MODULES.'/navigation/view/admin/form.nav.html');
        }

        /**
        * edit nav
        */
        public function editNav($id)
        {
            $this->assign['title'] = $this->core->lang['navigation']['edit_nav'];
            $row = $this->core->db('navs')->where($id)->oneArray();

            if(!empty($row))
            {
    	        $this->assign['name'] = $row['name'];
                $this->assign['id'] = $row['id'];
            }
            else
                redirect(url([ADMIN, 'navigation', 'manage']));

            $this->core->tpl->set('navigation', $this->assign);
        	return $this->core->tpl->draw(MODULES.'/navigation/view/admin/form.nav.html');
        }

        /**
        * save nav
        */
        public function saveNav($id = null)
        {
            if(empty($_POST['name']))
            {
                if(!$id)
                    redirect(url([ADMIN, 'navigation', 'newNav']));
                else
                    redirect(url([ADMIN, 'navigation', 'editNav', $id]));

                $this->core->setNotify('failure', $this->core->lang['general']['empty_inputs']);
            }

            $name = createSlug($_POST['name']);

            // check if nav already exists
            if(!$this->core->db('navs')->where('name', $name)->count())
			{
                if(!$id)
                {
					$query = $this->core->db('navs')->save(['name' => $name]);
                }
                else
                {
					$query = $this->core->db('navs')->where($id)->save(['name' => $name]);
                }

                if($query)
                   $this->core->setNotify('success', $this->core->lang['navigation']['save_nav_success']);
                else
                    $this->core->setNotify('success', $this->core->lang['navigation']['save_nav_failure']);
            }
            else
                $this->core->setNotify('failure', $this->core->lang['navigation']['nav_already_exists']);

            redirect(url([ADMIN, 'navigation', 'manage']));
        }

        /**
        * remove nav
        */
        public function deleteNav($id)
        {
			if($this->core->db('navs')->delete($id))
            {
		    	$this->core->db('navs_items')->delete('nav', $id);
                $this->core->setNotify('success', $this->core->lang['navigation']['delete_nav_success']);
			}
			else
				$this->core->setNotify('failure', $this->core->lang['navigation']['delete_nav_failure']);

			redirect(url([ADMIN, 'navigation', 'manage']));
        }

		/**
		* list of languages
		* @param string $selected
		* @return array
		*/
		private function _getLanguages($selected = null)
		{
			$langs = glob('../inc/lang/*', GLOB_ONLYDIR);

			$result = [];
			foreach($langs as $lang)
			{
				if($selected == basename($lang)) $attr = 'selected';
				else $attr = null;
				$result[] = ['name' => basename($lang), 'attr' => $attr];
			}
			return $result;
		}

		/**
		* list of pages
		* @param string $lang
        * @param integer $selected
		* @return array
		*/
        private function _getPages($lang, $selected = null)
        {
			$rows = $this->core->db('pages')->where('lang', $lang)->toArray();
        	if(count($rows))
        	{
	        	foreach($rows as $row)
	        	{
                    if($selected == $row['id']) $attr = 'selected';
		        	else $attr = null;
                    $result[] = ['id' => $row['id'], 'title' => $row['title'], 'slug' => $row['slug'], 'attr' => $attr];
	        	}
        	}
            return $result;
        }

		/**
		* list of parents
        * @param string $lang
        * @param integer $selected
		* @return array
		*/
        private function _getParents($lang, $nav = null, $page = null, $except = null)
        {
			$rows = $this->core->db('navs')->toArray();
        	if(count($rows))
        	{
	        	foreach($rows as &$row)
	        	{
	        	    $row['name'] = $this->core->tpl->noParse('{$navigation.'.$row['name'].'}');
	        	    $row['items'] = $this->_getNavItems($row['id'], $lang);

                    if($nav && !$page && ($nav == $row['id']))
                        $row['attr'] = 'selected';
                    else
                        $row['attr'] = null;

                    if(is_array($row['items']))
                    {
                        foreach($row['items'] as $key => &$value)
                        {
                            if($except && ($except == $value['id']))
                                unset($row['items'][$key]);
                            else
                            {
                                if($nav && $page && ($page == $value['id']))
                                    $value['attr'] = 'selected';
                                else
                                    $value['attr'] = null;
                            }
                        }
                    }
	        	}
            }
            return $rows;
        }

		/**
		* list of nav items
		* @param integer $nav
        * @param string $lang
		* @return array
		*/
        private function _getNavItems($nav, $lang)
        {
            $items = $this->core->db('navs_items')->where('nav', $nav)->where('lang', $lang)->asc('"order"')->toArray();

            if(count($items))
            {
                foreach($items as &$item)
                {
                	$item['editURL'] = url([ADMIN, 'navigation', 'editLink', $item['id']]);
		        	$item['delURL'] = url([ADMIN, 'navigation', 'deleteLink', $item['id']]);
                    $item['upURL'] = url([ADMIN, 'navigation', 'changeOrder', 'up', $item['id']]);
                    $item['downURL'] = url([ADMIN, 'navigation', 'changeOrder', 'down', $item['id']]);

                    if($item['page'] > 0)
                    {
                        $page = $this->core->db('pages')->where('id', $item['page'])->oneArray();
                        $item['fullURL'] = '/'.$page['slug'];
                    }
                    else
                        $item['fullURL'] = (parse_url($item['url'], PHP_URL_SCHEME) ? '' : '/' ).$item['url'];
                }
                return $this->buildTree($items);
            }
        }

		/**
		* generate tree from array
		* @param array $items
		* @return array
		*/
        public function buildTree(array $items)
        {
            $children = [0 => ''];

            foreach($items as &$item) $children[$item['parent']][] = &$item;
            unset($item);

            foreach($items as &$item) if (isset($children[$item['id']]))
                    $item['children'] = $children[$item['id']];

            return $children[0];
        }

		/**
		* change order of nav item
		* @param string $direction
        * @param integer $id
		* @return void
		*/
        public function changeOrder($direction, $id)
        {
            $item = $this->core->db('navs_items')->oneArray($id);

            if(!empty($item))
            {
                if($direction == 'up')
                    $nextItem = $this->core->db('navs_items')
                        ->where('"order"', '<', $item['order'])
                        ->where('nav', $item['nav'])
                        ->where('parent', $item['parent'])
                        ->where('lang', $item['lang'])
                        ->desc('"order"')
                        ->oneArray();
                else
                     $nextItem = $this->core->db('navs_items')
                        ->where('"order"', '>', $item['order'])
                        ->where('nav', $item['nav'])
                        ->where('parent', $item['parent'])
                        ->where('lang', $item['lang'])
                        ->asc('"order"')
                        ->oneArray();

                if(!empty($nextItem))
                {
                    $this->core->db('navs_items')->where('id', $item['id'])->save(['"order"' => $nextItem['order']]);
                    $this->core->db('navs_items')->where('id', $nextItem['id'])->save(['"order"' => $item['order']]);
                }
            }
            redirect(url([ADMIN, 'navigation', 'manage']));
        }

		/**
		* get item with highest order 
		* @param integer $nav
        * @param integer $parent
        * @param string $lang
		* @return integer
		*/
        private function _getHighestOrder($nav, $parent, $lang)
        {			
			$item = $this->core->db('navs_items')
				->where('nav', $nav)
				->where('parent', $parent)
				->where('lang', $lang)
				->desc('"order"')
				->oneArray();

            if(!empty($item))
                return $item['order'];
            else
                return 0;
        }

	}