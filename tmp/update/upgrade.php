<?php
	/**
	 * Batflat migration/upgrade file
	 * @copyright Copyright by Sruu.pl (http://sruu.pl) (c) 2016
	 * @author Sruu.pl <kontakt@sruu.pl>
	 */
	if(!defined("UPGRADABLE"))
		exit();

	function rrmdir($dir)
    { 
        $files = array_diff(scandir($dir), array('.','..')); 
        foreach ($files as $file) { 
            (is_dir("$dir/$file")) ? rrmdir("$dir/$file") : unlink("$dir/$file"); 
        }
        return rmdir($dir); 
    }

	switch($version)
	{
		case '1.0.0':
			/*
				Change homepage id to slug
			 */
			$homepage = $this->core->getSettings('settings', 'homepage');
			$homepage = $this->core->db('pages')->where('id', $homepage)->oneArray();
			$this->core->db('settings')->where('field', 'homepage')->save(['value' => $homepage['slug']]);

			/*
				Add 404 pages if does not exist
			 */
			if(!$this->core->db('pages')->where('slug', '404')->where('lang', 'en_english')->oneArray())
			{
				// 404 - EN
            	$this->core->db()->pdo()->exec("INSERT INTO `pages` (`title`, `slug`, `desc`, `lang`, `template`, `date`, `content`)
                	VALUES ('404', '404', 'Not found', 'en_english', 'index.html', datetime('now'),
                	'<p>Sorry, page does not exist.</p>')
            	");
            }
            if(!$this->core->db('pages')->where('slug', '404')->where('lang', 'pl_polski')->oneArray())
            {
	            // 404 -PL
	            $this->core->db()->pdo()->exec("INSERT INTO `pages` (`title`, `slug`, `desc`, `lang`, `template`, `date`, `content`)
	                VALUES ('404', '404', 'Not found', 'pl_polski', 'index.html', datetime('now'),
	                '<p>Niestety taka strona nie istnieje.</p>')
	            ");
			}

			/*
				Remove LESS directory
			 */
			deleteDir('inc/less');


			// Upgrade version
			$return = '1.0.1';

		case '1.0.1':

			$return = "1.0.2";

		case '1.0.2':

			$return = "1.0.3";

		case '1.0.3':
			// Add columns for markdown flag - blog and pages
			$this->core->db()->pdo()->exec("ALTER TABLE blog ADD COLUMN markdown INTEGER DEFAULT 0");
			$this->core->db()->pdo()->exec("ALTER TABLE pages ADD COLUMN markdown INTEGER DEFAULT 0");
			$this->core->db()->pdo()->exec("CREATE TABLE `login_attempts` (
				`ip`	TEXT NOT NULL,
				`attempts`	INTEGER NOT NULL,
				`expires`	INTEGER NOT NULL DEFAULT 0
			)");
			$this->rcopy(BASE_DIR.'/tmp/update/admin', BASE_DIR.'/admin');
			$return = "1.0.4";

		case '1.0.4':
			$return = '1.0.4a';
			
		case '1.0.4a':
			$this->core->db()->pdo()->exec("ALTER TABLE modules ADD COLUMN sequence INTEGER DEFAULT 0");
			$this->rcopy(BASE_DIR.'/tmp/update/admin', BASE_DIR.'/admin');
			$this->rcopy(BASE_DIR.'/tmp/update/.htaccess', BASE_DIR.'/.htaccess');
			$this->rcopy(BASE_DIR.'/tmp/update/inc/fonts', BASE_DIR.'/inc/fonts');
			$this->rcopy(BASE_DIR.'/tmp/update/themes/admin', BASE_DIR.'/themes/admin');
			$return = '1.0.5';

		case '1.0.5':
			if(file_exists(BASE_DIR.'/themes/default'))
			{
				$this->rcopy(BASE_DIR.'/tmp/update/themes/default/preview.png', BASE_DIR.'/themes/default/preview.png');
				$this->rcopy(BASE_DIR.'/tmp/update/themes/default/manifest.json', BASE_DIR.'/themes/default/manifest.json');
				$this->rcopy(BASE_DIR.'/tmp/update/themes/admin', BASE_DIR.'/themes/admin');
			}
			$return = '1.1.0';
		
		case '1.1.0';
			$this->core->db()->pdo()->exec('CREATE TABLE "blog_tags" (
							`id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
							`name`	TEXT,
							`slug` TEXT
						);');
            $this->core->db()->pdo()->exec('CREATE TABLE `blog_tags_relationship` (
							`blog_id`	INTEGER NOT NULL,
							`tag_id`	INTEGER NOT NULL
						);');
			$this->core->db()->pdo()->exec("INSERT INTO `settings`
						(`module`, `field`, `value`)
						VALUES
						('contact', 'email', 1),
						('contact', 'driver', 'mail'),
						('contact', 'phpmailer.server', 'smtp.example.com'),
						('contact', 'phpmailer.port', '587'),
						('contact', 'phpmailer.username', 'login@example.com'),
						('contact', 'phpmailer.name', 'Batflat contact'),
						('contact', 'phpmailer.password', 'yourpassword')");
			
			$this->rcopy(BASE_DIR.'/tmp/update/inc/core', BASE_DIR.'/inc/core');
			$this->rcopy(BASE_DIR.'/tmp/update/themes/admin', BASE_DIR.'/themes/admin');
			$this->rcopy(BASE_DIR.'/tmp/update/admin', BASE_DIR.'/admin');
			$this->rcopy(BASE_DIR.'/tmp/update/index.php', BASE_DIR.'/index.php');

			$return = '1.2.0';
	}

	return $return;