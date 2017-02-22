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
            $value = $this->core->getSettings('contact', 'email');
            if(is_numeric($value))
            {
                $assign['users'] = $this->_getUsers($value);
                $assign['email'] = NULL;
            }
            else
            {
                $assign['users'] = $this->_getUsers();
                $assign['email'] = $value;
            }

            $this->core->tpl->set('contact', $assign);
            return $this->core->tpl->draw(MODULES.'/contact/view/admin/settings.html');
        }

        public function save()
        {
            $update = ['field' => 'email', 'value' => ($_POST['user'] > 0 ? $_POST['user'] : $_POST['email'])];

            if($this->core->db('settings')->where('module', 'contact')->save($update))
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