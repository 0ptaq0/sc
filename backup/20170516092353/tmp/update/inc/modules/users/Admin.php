<?php

    namespace Inc\Modules\Users;

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
                $this->core->lang['general']['manage']	=> 'manage',
                $this->core->lang['users']['add_new']	=> 'add'
            ];
        }

        /**
        * users list
        */
        public function manage()
        {
        	$rows = $this->core->db('users')->toArray();
        	foreach($rows as &$row)
            {
	        	if(empty($row['fullname'])) $row['fullname'] = '----';
	        	$row['editURL'] = url([ADMIN, 'users', 'edit', $row['id']]);
	        	$row['delURL']  = url([ADMIN, 'users', 'delete', $row['id']]);
        	}

            $this->core->tpl->set('myId', $this->core->getUserInfo('id'));
        	$this->core->tpl->set('users', $rows);
        	return $this->core->tpl->draw(MODULES.'/users/view/admin/manage.html');
		}

        /**
        * add new user
        */
        public function add()
        {
            if(!empty($redirectData = getRedirectData()))
                $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
            else
        		$this->assign['form'] = ['username' => '', 'email' => '', 'fullname' => ''];


        	$this->assign['title'] = $this->core->lang['users']['new_user'];
        	$this->assign['modules'] = $this->_getModules();

        	$this->core->tpl->set('users', $this->assign);
        	return $this->core->tpl->draw(MODULES.'/users/view/admin/form.html');
		}

		/**
		* edit user
		*/
		public function edit($id)
		{
            $user = $this->core->db('users')->oneArray($id);

			if(!empty($user))
			{
				$this->assign['form'] = $user;
				$this->assign['title'] = $this->core->lang['users']['edit_user'];
    			$this->assign['modules'] = $this->_getModules($user['access']);

    			$this->core->tpl->set('users', $this->assign);
				return $this->core->tpl->draw(MODULES.'/users/view/admin/form.html');
			}
			else
        		redirect(url([ADMIN, 'users', 'manage']));
		}

		/**
		* save user data
		*/
		public function save($id = null)
		{
			$errors = 0;

            // location to redirect
            if(!$id)
                $location = url([ADMIN, 'users', 'add']);
            else
                $location = url([ADMIN, 'users', 'edit', $id]);

            // admin
            if($id == 1) $_POST['access'] = ['all'];

        	// check if required fields are empty
        	if(checkEmptyFields(['username', 'email', 'access'], $_POST))
        	{
        		$this->core->setNotify('failure', $this->core->lang['general']['empty_inputs']);
                redirect($location, $_POST);
        	}

			// check if user already exists
            if($this->_userAlreadyExists($id))
            {
		        $errors++;
				$this->core->setNotify('failure', $this->core->lang['users']['user_already_exists']);
            }
			// chech if e-mail adress is correct
			$_POST['email'] = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
			if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
			{
				$errors++;
				$this->core->setNotify('failure', $this->core->lang['users']['wrong_email']);
			}
			// check if password is longer than 4 characters
			if(!empty($_POST['password']) && strlen($_POST['password']) < 5)
			{
				$errors++;
				$this->core->setNotify('failure', $this->core->lang['users']['too_short_pswd']);
			}
			// access to modules
			if((count($_POST['access']) == count($this->_getModules())) || ($id == 1))
			    $_POST['access'] = 'all';
			else
            {
                $_POST['access'][] = 'dashboard';
				$_POST['access'] = implode(',', $_POST['access']);
            }

            // CREATE / EDIT
            if(!$errors)
            {
                unset($_POST['save']);

                if(!empty($_POST['password']))
    			    $_POST['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
                else
                    unset($_POST['password']);

    			if(!$id) 	// new
                    $query = $this->core->db('users')->save($_POST);
                else 		// edit
                    $query = $this->core->db('users')->where('id', $id)->save($_POST);

                if($query)
				    $this->core->setNotify('success', $this->core->lang['users']['save_success']);
				else
					$this->core->setNotify('failure', $this->core->lang['users']['save_failure']);
					
				redirect($location);
            }

            redirect($location, $_POST);
		}

		/**
		* remove user
		*/
		public function delete($id)
		{
			if($id != 1 && $this->core->getUserInfo('id') != $id)
			{
				if($this->core->db('users')->delete($id))
					$this->core->setNotify('success', $this->core->lang['users']['delete_success']);
				else
					$this->core->setNotify('failure', $this->core->lang['users']['delete_failure']);
            }
			redirect(url([ADMIN, 'users', 'manage']));
		}

		/**
		* list of active modules
		* @return array
		*/
		private function _getModules($access = null)
		{
			$result = [];
			$rows = $this->core->db('modules')->toArray();

			if(!$access) $accessArray = [];
            else $accessArray = explode(',', $access);

			foreach($rows as $row)
            {
                if($row['dir'] != 'dashboard')
                {
                	$details = $this->core->getModuleInfo($row['dir']);

                	if(empty($accessArray)) $attr = '';
                	else
                    {
    		            if(in_array($row['dir'], $accessArray) || ($accessArray[0] == 'all'))
                            $attr = 'selected';
    		            else
                            $attr = '';
    	            }
                    $result[] = ['dir' => $row['dir'], 'name' => $details['name'], 'attr' => $attr];
                }
			}
			return $result;
		}

    	/**
		* check if user already exists
		* @return array
		*/
        private function _userAlreadyExists($id = null)
        {
            if(!$id) 	// new
                $count = $this->core->db('users')->where('username', $_POST['username'])->count();
			else 		// edit
                $count = $this->core->db('users')->where('username', $_POST['username'])->where('id', '<>', $id)->count();
			if($count > 0)
				return true;
            else
                return false;
        }

    }