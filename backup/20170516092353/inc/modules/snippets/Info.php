<?php

    return [
        'name'          =>  $core->lang['snippets']['module_name'],
        'description'   =>  $core->lang['snippets']['module_desc'],
        'author'        =>  'Sruu.pl',
        'version'       =>  '1.0',
        'icon'          =>  'puzzle-piece',

        'install'       =>  function() use($core)
        {
            $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `snippets` (
                `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                `name` text NOT NULL,
                `slug` text NOT NULL,
                `content` text NOT NULL
                )");
        },
        'uninstall'     =>  function() use($core)
        {
            $core->db()->pdo()->exec("DROP TABLE `snippets`");
        }
    ];