<?php

    return [
        'name'          =>  $core->lang['users']['module_name'],
        'description'   =>  $core->lang['users']['module_desc'],
        'author'        =>  'Sruu.pl',
        'version'       =>  '1.0',
        'icon'          =>  'user',

        'install'       =>  function() use($core)
        {
            $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `users` (
                `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                `username` text NOT NULL,
                `fullname` text NULL,
                `password` text NOT NULL,
                `email` text NOT NULL,
                `role` text NOT NULL DEFAULT 'admin',
                `access` text NOT NULL DEFAULT 'all'
            )");
            $core->db()->pdo()->exec("CREATE TABLE `login_attempts` (
                `ip`    TEXT NOT NULL,
                `attempts`  INTEGER NOT NULL,
                `expires`   INTEGER NOT NULL DEFAULT 0
            )");
            
            $core->db()->pdo()->exec('INSERT INTO `users` (`username`, `fullname`, `password`, `email`, `role`, `access`)
                VALUES ("admin", NULL, "$2y$10$pgRnDiukCbiYVqsamMM3ROWViSRqbyCCL33N8.ykBKZx0dlplXe9i", "admin@localhost", "admin", "all")');
        },
        'uninstall'     =>  function() use($core)
        {
            $core->db()->pdo()->exec("DROP TABLE `users`");
        }
    ];