<?php

	if (!version_compare(PHP_VERSION, '5.5.0', '>=')) exit("Batflat requires at least <b>PHP 5.5</b>");

	// Admin cat name
	define('ADMIN', 'admin');

	// Themes path
	define('THEMES', BASE_DIR . '/themes');

	// Modules path
	define('MODULES', BASE_DIR . '/inc/modules');

	// Uploads path
	define('UPLOADS', BASE_DIR . '/uploads');

	// Lock files
	define('FILE_LOCK', false);

	// Basic modules
	define('BASIC_MODULES', serialize([
        7 => 'settings',
		0 => 'dashboard',
		2 => 'pages',
        3 => 'navigation',
		6 => 'users',
		1 => 'blog',
        4 => 'snippets',
		5 => 'modules',
		8 => 'contact',
		9 => 'langswitcher',
	]));

	// HTML beautifier
	define('HTML_BEAUTY', false);

	// Developer mode
	define('DEV_MODE', false);