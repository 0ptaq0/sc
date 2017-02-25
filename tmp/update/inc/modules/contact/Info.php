<?php

    return [
        'name'          =>  $core->lang['contact']['module_name'],
        'description'   =>  $core->lang['contact']['module_desc'],
        'author'        =>  'Sruu.pl',
        'version'       =>  '1.1',
        'icon'          =>  'envelope',
        
        'install'   => function() use($core)
        {
            $core->db()->pdo()->exec("INSERT INTO `settings`
            (`module`, `field`, `value`)
            VALUES
            ('contact', 'email', 1),
            ('contact', 'driver', 'mail'),
            ('contact', 'phpmailer.server', 'smtp.example.com'),
            ('contact', 'phpmailer.port', '587'),
            ('contact', 'phpmailer.username', 'login@example.com'),
            ('contact', 'phpmailer.name', 'Batflat contact'),
            ('contact', 'phpmailer.password', 'yourpassword')");
        },
        'uninstall' => function() use($core)
        {
            $core->db()->pdo()->exec("DELETE FROM `settings` WHERE `module` = 'contact'");
        }
    ];

?>