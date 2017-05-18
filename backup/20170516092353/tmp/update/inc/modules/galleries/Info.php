<?php

    return [
        'name'          =>  $core->lang['galleries']['module_name'],
        'description'   =>  $core->lang['galleries']['module_desc'],
        'author'        =>  'Sruu.pl',
        'version'       =>  '1.0',
        'icon'          =>  'camera',

        'install'       =>  function() use($core)
        {
            $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `galleries` (
                `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                `name` text NOT NULL,
                `slug` text NOT NULL,
                `img_per_page` integer NOT NULL DEFAULT 0,
                `sort` text NOT NULL DEFAULT 'DESC'
            )");

            $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `galleries_items` (
                `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                `gallery` integer NOT NULL,
                `src` text NOT NULL,
                `title` text NULL,
                `desc` text NULL
            )");

            if(!file_exists(UPLOADS))
                mkdir(UPLOADS, 0755, true);
        },
        'uninstall'     => function() use($core)
        {
            $core->db()->pdo()->exec("DROP TABLE `galleries`");
            deleteDir(UPLOADS.'/galleries');
        }
    ];