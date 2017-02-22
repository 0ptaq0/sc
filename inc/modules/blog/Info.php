<?php

    return [
        'name'          =>  $core->lang['blog']['module_name'],
        'description'   =>  $core->lang['blog']['module_desc'],
        'author'        =>  'Sruu.pl',
        'version'       =>  '1.0',
        'icon'          =>  'pencil-square',

        'pages'			=>  ['Blog' => 'blog'],

        'install'       =>  function() use($core)
        {
            $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `blog` (
							`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
							`title`	TEXT NOT NULL,
							`slug`	TEXT NOT NULL,
							`user_id`	INTEGER NOT NULL,
							`content`	TEXT NOT NULL,
							`intro`	TEXT DEFAULT NULL,
							`cover_photo`	TEXT DEFAULT NULL,
							`status`	INTEGER NOT NULL,
							`comments`	INTEGER DEFAULT 1,
							`markdown`	INTEGER DEFAULT 0,
							`published_at`	INTEGER DEFAULT 0,
							`updated_at`	INTEGER NOT NULL,
							`created_at`	INTEGER NOT NULL
						);");
			
			$core->db()->pdo()->exec("INSERT INTO `blog` VALUES (1,'Let’s put a smile on that face','lets-put-a-smile-on-that-face',1,'<p>Every man who has lotted here over the centuries, has looked up to the light and imagined climbing to freedom. So easy, so simple! And like shipwrecked men turning to seawater foregoing uncontrollable thirst, many have died trying. And then here there can be no true despair without hope. So as I terrorize Gotham, I will feed its people hope to poison their souls. I will let them believe that they can survive so that you can watch them climbing over each other to stay in the sun. You can watch me torture an entire city. And then when you’ve truly understood the depth of your failure, we will fulfill Ra’s Al Ghul’s destiny. We will destroy Gotham. And then, when that is done, and Gotham is... ashes Then you have my permission to die.</p>','<p>You wanna know how I got these scars? My father was… a drinker, and a fiend. And one night, he goes off crazier than usual. Mommy gets the kitchen knife to defend herself. He doesn’t like that, not one bit. So, me watching he takes the knife to her, laughing while he does it.</p>','',2,1,0,".time().",".time().",".time().")");
			$core->db()->pdo()->exec("INSERT INTO `settings` (`module`, `field`, `value`) VALUES ('blog', 'perpage', '5'), ('blog', 'disqus', ''), ('blog', 'dateformat', 'M d, Y'), ('blog', 'title', 'Blog'), ('blog', 'desc', '... Why so serious? ...')");

			if(!is_dir(UPLOADS."/blog"))
				mkdir(UPLOADS."/blog", 0777);
        },
        'uninstall'     =>  function() use($core)
        {
            $core->db()->pdo()->exec("DROP TABLE `blog`");
            $core->db()->pdo()->exec("DELETE FROM `settings` WHERE `module` = 'blog'");
			
			deleteDir(UPLOADS."/blog");
        }
    ];