<?php

    return [
        'name'          =>  $core->lang['langswitcher']['module_name'],
        'description'   =>  $core->lang['langswitcher']['module_desc'],
        'author'        =>  'Sruu.pl',
        'version'       =>  '1.1',
        'icon'          =>  'flag',
        'install'       =>  function() use ($core) {
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'autodetectlang', 1)");
        },
        'uninstall'     =>  function() use ($core) {
            $core->db()->pdo()->exec("DELETE FROM `settings` WHERE `field` = 'autodetectlang'");
        }
    ];