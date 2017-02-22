<?php

    return [
        'name'          =>  $core->lang['contact']['module_name'],
        'description'   =>  $core->lang['contact']['module_desc'],
        'author'        =>  'Sruu.pl',
        'version'       =>  '1.0',
        'icon'          =>  'envelope',
        
        'install'   => function() use($core)
        {
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('contact', 'email', 1)");
        },
        'uninstall' => function() use($core)
        {
            $core->db()->pdo()->exec("DELETE FROM `settings` WHERE `module` = 'contact'");
        }
    ];

?>