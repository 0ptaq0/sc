<?php

    namespace Inc\Modules\Contact;

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
                $this->core->lang['general']['settings'] => 'settings',
            ];
        }

        public function settings()
        {
            $value = $this->core->getSettings('contact');

            if(is_numeric($value['email']))
            {
                $assign['users'] = $this->_getUsers($value['email']);
                $assign['email'] = NULL;
            }
            else
            {
                $assign['users'] = $this->_getUsers();
                $assign['email'] = $value['email'];
            }
            
            $assign['driver'] = $value['driver'];
            $assign['phpmailer'] = [
                'server' => $value['phpmailer.server'],
                'port' => $value['phpmailer.port'],
                'username' => $value['phpmailer.username'],
                'password' => $value['phpmailer.password'],
                'name' => $value['phpmailer.name'],
            ];

            $this->core->tpl->set('contact', $assign);
            return $this->core->tpl->draw(MODULES.'/contact/view/admin/settings.html');
        }

        public function save()
        {
            $update = [
                'email' => ($_POST['user'] > 0 ? $_POST['user'] : $_POST['email']),
                'driver' => $_POST['driver'],
                'phpmailer.server' => $_POST['phpmailer']['server'],
                'phpmailer.port' => $_POST['phpmailer']['port'],
                'phpmailer.username' => $_POST['phpmailer']['username'],
                'phpmailer.password' => $_POST['phpmailer']['password'],
                'phpmailer.name' => $_POST['phpmailer']['name'],
            ];

            $errors = 0;
            foreach($update as $field => $value)
            {
                if(!$this->core->db('settings')->where('module', 'contact')->where('field', $field)->save(['value' => $value]))
                    $errors++;
            }

            if(!$errors)
                $this->core->setNotify('success', $this->core->lang['contact']['save_success']);
            else
                $this->core->setNotify('failure', $this->core->lang['contact']['save_failure']);

            redirect(url([ADMIN, 'contact', 'settings']));
        }

        private function _getUsers($id = null)
        {
            $rows = $this->core->db('users')->where('role', 'admin')->toArray();
        	if(count($rows))
        	{
	        	foreach($rows as $row)
	        	{
                    if($id == $row['id']) $attr = 'selected';
		        	else $attr = null;
                    $result[] = $row + ['attr' => $attr];
	        	}
        	}
            return $result;
        }
    }