<?php

    return [
        'name'          =>  $core->lang['navigation']['module_name'],
        'description'   =>  $core->lang['navigation']['module_desc'],
        'author'        =>  'Sruu.pl',
        'version'       =>  '1.0',
        'icon'          =>  'list-ul',

        'install'       =>  function() use($core)
        {
            $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `navs` (
                `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                `name` text NOT NULL
            )");
            $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `navs_items` (
                `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                `name` text NOT NULL,
                `url` text NULL,
                `page` integer NULL,
                `lang` text NOT NULL,
                `parent` integer NOT NULL DEFAULT 0,
                `nav` integer NOT NULL,
                `order` integer NOT NULL
            )");
            $core->db()->pdo()->exec("INSERT INTO `navs` (`name`) VALUES ('main')");
            $core->db()->pdo()->exec("INSERT INTO `navs_items` (`name`, `page`, `lang`, `nav`, `order`)
                VALUES ('Home', 1, 'en_english', 1, 1)");
            $core->db()->pdo()->exec("INSERT INTO `navs_items` (`name`, `page`, `lang`, `nav`, `order`)
                VALUES ('Home', 2, 'pl_polski', 1, 1)");
            $core->db()->pdo()->exec("INSERT INTO `navs_items` (`name`, `page`, `lang`, `nav`, `order`)
                VALUES ('About me', 3, 'en_english', 1, 2)");
            $core->db()->pdo()->exec("INSERT INTO `navs_items` (`name`, `page`, `lang`, `nav`, `order`)
                VALUES ('O mnie', 4, 'pl_polski', 1, 2)");
            $core->db()->pdo()->exec("INSERT INTO `navs_items` (`name`, `url`, `page`, `lang`, `nav`, `order`)
                VALUES ('Blog', 'blog', 0, 'en_english', 1, 3)");
            $core->db()->pdo()->exec("INSERT INTO `navs_items` (`name`, `url`, `page`, `lang`, `nav`, `order`)
                VALUES ('Blog', 'blog', 0, 'pl_polski', 1, 3)");
            $core->db()->pdo()->exec("INSERT INTO `navs_items` (`name`, `page`, `lang`, `nav`, `order`)
                VALUES ('Contact', 5, 'en_english', 1, 4)");
            $core->db()->pdo()->exec("INSERT INTO `navs_items` (`name`, `page`, `lang`, `nav`, `order`)
                VALUES ('Kontakt', 6, 'pl_polski', 1, 4)");
        },
        'uninstall'     =>  function() use($core)
        {
            $core->db()->pdo()->exec("DROP TABLE `navs`");
            $core->db()->pdo()->exec("DROP TABLE `navs_items`");
        }
    ];