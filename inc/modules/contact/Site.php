<?php

    namespace Inc\Modules\Contact;

    class Site
    {

        public $core;
        private $_headers, $_params;
        private $_error = null;

        public function __construct($object)
        {
            $this->core = $object;
            $this->_insertForm();
		}

        private function _insertForm()
        {
            $assign = [];

            if(isset($_POST['send-email']))
            {
                $this->_getParams($_POST);
                $this->_checkErrors($this->_params);
                if(!$this->_error)
                {
                    if($this->_sendEmail())
                        $this->core->setNotify('success', $this->core->lang['contact']['send_success']);
                    else
                        $this->core->setNotify('failure', $this->core->lang['contact']['send_failure']);
                }
                else
                    $this->core->setNotify('failure', $this->_error);
            }

            $assign['form'] = $this->core->tpl->draw(MODULES.'/contact/view/form.html');
            $this->core->tpl->set('contact', $assign);
        }

        private function _getParams($array)
        {
            htmlspecialchars_array($array);
            $this->_params = $array;

            $setting = $this->core->getSettings('contact', 'email');
            if(!is_numeric($setting))
            {
                $this->_params['to'] = $setting;
            }
            else
            {
                $user = $this->core->db('users')->where($setting)->oneArray();
                $this->_params['to'] = $user['email'];
            }
        }

        private function _checkErrors($array)
        {
            if(!filter_var($array['from'], FILTER_VALIDATE_EMAIL))
                $this->_error = $this->core->lang['contact']['wrong_email'];

            if(checkEmptyFields(['name', 'subject', 'from', 'message'], $array))
                $this->_error = $this->core->lang['contact']['empty_inputs'];

            // antibot field
            if(!empty($array['title'])) exit();

            if(isset($_COOKIE['MailWasSend']))
                $this->_error = $this->core->lang['contact']['antiflood'];
        }

        private function _setHeaders()
        {
            $this->_headers  = "From: noreply@{$_SERVER['HTTP_HOST']}\n";
            $this->_headers .= "Reply-To: {$this->_params['from']}\n";
            $this->_headers .= "MIME-Version: 1.0\n";
            $this->_headers .= "Content-type: text/html; charset=utf-8\n";
        }

        private function _sendEmail()
        {
            $this->_setHeaders();
            
            if(mail($this->_params['to'], '=?UTF-8?B?'.base64_encode($this->_params['subject']).'?=', $this->_params['message'], $this->_headers))
            {
                // cookies antiflood
                $cookieParams = session_get_cookie_params();
                setcookie("MailWasSend", 'BATFLAT', time()+360, $cookieParams["path"], $cookieParams["domain"], null, true);
                return TRUE;
            }
            else
                return FALSE;
        }

        private function _showEmail()
        {
            $email = "Subject: " . $this->_params['subject'] . '<br/>';
            $email .= "To: " . $this->_params['to'] . '<br/>';
            $this->_setHeaders();
            $email .= $this->_headers . '<br/>';
            $email .= $this->_params['message'];
            echo $email;
        }

    }