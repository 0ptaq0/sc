<?php

    return [
        'name'          =>  $core->lang['settings']['module_name'],
        'description'   =>  $core->lang['settings']['module_desc'],
        'author'        =>  'Sruu.pl',
        'version'       =>  '1.0',
        'icon'          =>  'wrench',

        'install'       =>  function() use($core)
        {
            $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `settings` (
                `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                `module` text NOT NULL,
                `field` text NOT NULL,
                `value` text NOT NULL
            )");

            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'title', 'Batflat')");
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'description', 'Gotham’s time has come.')");
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'keywords', 'key, words')");
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'footer', 'Copyright {?=date(\"Y\")?} &copy; by Company Name. All rights reserved.')");
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'homepage', 'home')");
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'theme', 'default')");
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'editor', 'wysiwyg')");
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'lang_site', 'en_english')");
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'lang_admin', 'en_english')");
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'version', '1.1.0')");
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'update_check', '0')");
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'update_changelog', '')");
            $core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('settings', 'update_version', '0')");
        },
        'uninstall'     =>  function() use($core)
        {
            $core->db()->pdo()->exec("DROP TABLE `settings`");
        }
    ];