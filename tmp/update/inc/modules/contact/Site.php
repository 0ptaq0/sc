<?php

    namespace Inc\Modules\Contact;

    class Site
    {
        public $core;
        private $_headers, $_params;
        private $_error = null;

        private $mail = [];

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
                if($this->_initDriver())
                {
                    if($this->_sendEmail())
                        $this->core->setNotify('success', $this->core->lang['contact']['send_success']);
                    else
                        $this->core->setNotify('failure', $this->_error);
                }
                else
                    $this->core->setNotify('failure', $this->_error);
            }

            $assign['form'] = $this->core->tpl->draw(MODULES.'/contact/view/form.html');
            $this->core->tpl->set('contact', $assign);
        }

        private function _initDriver()
        {
            $settings = $this->core->getSettings('contact');

            $this->email['driver'] = $settings['driver'];

            $data = $_POST;
            htmlspecialchars_array($data);

            if($this->_checkErrors($data))
                return false;

            $this->email['subject'] = $data['subject'];
            $this->email['from'] = $data['from'];

            if($settings['driver'] == 'mail')
            {
                $this->email['sender'] = $this->core->getSettings('settings','title')." <no-reply@{$_SERVER['HTTP_HOST']}>";
            }
            else if($settings['driver'] == 'phpmailer' && class_exists('PHPMailer'))
            {
                $this->email['sender'] = [
                    $this->core->getSettings('contact', 'phpmailer.username'),
                    $this->core->getSettings('contact', 'phpmailer.name'),
                ];
            }

            if(!is_numeric($settings['email']))
            {
                $this->email['to'] = $settings['email'];
            }
            else
            {
                $user = $this->core->db('users')->where($settings['email'])->oneArray();
                $this->email['to'] = $user['email'];
            }

            $this->core->tpl->set('mail', $data);
            $this->email['message'] = $this->core->tpl->draw(MODULES.'/contact/view/mail.html');

            return true;
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

            if($this->_error)
                return true;

            return false;
        }

        private function _sendEmail()
        {
            if($this->email['driver'] == 'mail')
            {
                $headers  = "From: {$this->email['sender']}\n";
                $headers .= "Reply-To: {$this->email['from']}\n";
                $headers .= "MIME-Version: 1.0\n";
                $headers .= "Content-type: text/html; charset=utf-8\n";

                if(mail($this->email['to'], '=?UTF-8?B?'.base64_encode($this->email['subject']).'?=', $this->email['message'], $headers))
                {
                    // cookies antiflood
                    $cookieParams = session_get_cookie_params();
                    setcookie("MailWasSend", 'BATFLAT', time()+360, $cookieParams["path"], $cookieParams["domain"], null, true);
                    return TRUE;
                }
                else
                {
                    $this->core->setNotify('failure', $this->core->lang['contact']['send_failure']);
                    return FALSE;
                }
            }
            else if($this->email['driver'] == 'phpmailer')
            {
                $settings = $this->core->getSettings('contact');

                try {
                    $mail = new \PHPMailer(true);
                    $mail->isSMTP();                                            // Set mailer to use SMTP
                    $mail->Host = $settings['phpmailer.server'];                             // Specify main and backup SMTP servers
                    $mail->SMTPAuth = true;                         // Enable SMTP authentication
                    $mail->Username = $settings['phpmailer.username'];                     // SMTP username
                    $mail->Password = $settings['phpmailer.password'];                     // SMTP password
                    $mail->SMTPSecure = 'TLS';                     // Enable TLS encryption, `ssl` also accepted
                    $mail->Port = $settings['phpmailer.port'];                             // TCP port to connect to
                    $mail->CharSet = 'UTF-8';

                    $mail->Subject = $this->email['subject'];
                    $mail->Body = $this->email['message'];

                    $mail->addReplyTo($this->email['from']);
                    $mail->setFrom($this->email['sender'][0], $this->email['sender'][1]);
                    $mail->addAddress($this->email['to']);

                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );

                    $mail->isHTML(true);

                    if($mail->send())
                    {
                        $cookieParams = session_get_cookie_params();
                        setcookie("MailWasSend", 'BATFLAT', time()+360, $cookieParams["path"], $cookieParams["domain"], null, true);
                    }
                } catch (\phpmailerException $e) {
                    $this->_error = $e->errorMessage();
                } catch (\Exception $e) {
                    $this->_error = $e->getMessage();
                }

                if($this->_error)
                    return false;

                return true;
            }  
        }
    }