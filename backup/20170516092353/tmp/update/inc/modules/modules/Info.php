<?php

    return [
        'name'          =>  $core->lang['modules']['module_name'],
        'description'   =>  $core->lang['modules']['module_desc'],
        'author'        =>  'Sruu.pl',
        'version'       =>  '1.0',
        'icon'          =>  'plug',

        'install'       =>  function() use($core)
        {
            $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `modules` (
                    `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                    `dir` text NOT NULL,
                    `sequence` integer DEFAULT 0
                    )");
        },
        'uninstall'     =>  function() use($core)
        {
            $core->db()->pdo()->exec("DROP TABLE `modules`");
        }
    ];