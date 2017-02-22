<?php

    namespace Inc\Modules\Snippets;

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
                $this->core->lang['general']['manage']	=> 'manage',
                $this->core->lang['snippets']['add']	=> 'add',
            ];
        }

        /**
        * list of snippets
        */
        public function manage()
        {
            $rows = $this->core->db('snippets')->toArray();
        	if(count($rows))
        	{
	        	foreach($rows as &$row)
	        	{
                    $row['tag'] = $this->core->tpl->noParse('{$snippet.'.$row['slug'].'}');
	        		$row['editURL'] = url([ADMIN, 'snippets', 'edit', $row['id']]);
		        	$row['delURL'] = url([ADMIN, 'snippets', 'delete', $row['id']]);
	        	}
        	}

        	$this->core->tpl->set('snippets', $rows);
        	return $this->core->tpl->draw(MODULES.'/snippets/view/admin/manage.html');
		}

        /**
        * add new snippet
        */
        public function add()
        {
            $this->_add2header();

            if(!empty($redirectData = getRedirectData()))
            {
                $assign = $redirectData;
                $assign['content'] = $this->core->tpl->noParse($assign['content']);
            }
            else
                $assign = ['name' => '', 'content' => ''];

            $assign['title'] = $this->core->lang['snippets']['add'];
            
        	$this->core->tpl->set('snippets', $assign);
        	return $this->core->tpl->draw(MODULES.'/snippets/view/admin/form.html');
		}

        /**
        * edit snippet
        */
        public function edit($id)
        {
            $this->_add2header();

            if(!empty($row = $this->core->db('snippets')->oneArray($id)))
    	    {                
                if(!empty($redirectData = getRedirectData()))
                {
                    $assign['name'] = $redirectData['name'];
                    $assign['content'] = $redirectData['content'];
                }
                $assign = htmlspecialchars_array($row);       
                $assign['content'] = $this->core->tpl->noParse($assign['content']);
            }
            else
                redirect(url([ADMIN, 'snippets', 'manage']));

            $assign['title'] = $this->core->lang['snippets']['edit'];
         	$this->core->tpl->set('snippets', $assign);
        	return $this->core->tpl->draw(MODULES.'/snippets/view/admin/form.html');
        }

        /**
        * remove snippet
        */
        public function delete($id)
        {
			if($this->core->db('snippets')->delete($id))
                $this->core->setNotify('success', $this->core->lang['snippets']['delete_success']);
			else
				$this->core->setNotify('failure', $this->core->lang['snippets']['delete_failure']);

			redirect(url([ADMIN, 'snippets', 'manage']));
        }

        /**
        * save snippet
        */
        public function save($id = null)
        {
            unset($_POST['save']);

        	if(checkEmptyFields(['name', 'content'], $_POST))
    		{
    			$this->core->setNotify('failure', $this->core->lang['general']['empty_inputs']);

                if(!$id)
                    redirect(url([ADMIN, 'snippets', 'add']));
                else
                    redirect(url([ADMIN, 'snippets', 'edit', $id]));
    		}

            $_POST['name'] = trim($_POST['name']);
            $_POST['slug'] = createSlug($_POST['name']);

            if(!$id) // new
            {
                $location = url([ADMIN, 'snippets', 'add']);
                if(!$this->core->db('snippets')->where('slug', $_POST['slug'])->count())
                {
                    if($this->core->db('snippets')->save($_POST))
                    {
                        $location = url([ADMIN, 'snippets', 'edit', $this->core->db()->lastInsertId()]);
                        $this->core->setNotify('success', $this->core->lang['snippets']['save_success']);
                    }
                    else
                        $this->core->setNotify('failure', $this->core->lang['snippets']['save_failure']);
                }
                else
                    $this->core->setNotify('failure', $this->core->lang['snippets']['already_exists']);
            }
            else    // edit
            {
                if(!$this->core->db('snippets')->where('slug', $_POST['slug'])->where('id', '<>', $id)->count())
                {
                    if($this->core->db('snippets')->where($id)->save($_POST))
                        $this->core->setNotify('success', $this->core->lang['snippets']['save_success']);
                    else
                        $this->core->setNotify('failure', $this->core->lang['snippets']['save_failure']);
                }
                else
                    $this->core->setNotify('failure', $this->core->lang['snippets']['already_exists']);    
                    
                $location =  url([ADMIN, 'snippets', 'edit', $id]);
            }

            redirect($location, $_POST);
        }

        private function _add2header()
        {
            $this->core->addCSS(url('inc/jscripts/markitup/skin/style.css'));
            $this->core->addCSS(url('inc/jscripts/markitup/set/style.css'));
            $this->core->addJS(url('inc/jscripts/markitup/markitup.min.js'));
            $this->core->addJS(url('inc/jscripts/markitup/set/'.$this->core->getSettings('settings', 'lang_admin').'.js'));
        }

    }