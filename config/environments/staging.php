<?php
/* Staging */
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_HOST', getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost');

define('WP_HOME', getenv('WP_HOME'));
define('WP_SITEURL', getenv('WP_SITEURL'));

ini_set('display_errors', 0);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', false);

/* Set the cookie path to ensure the debug bar plugin works */
define( 'ADMIN_COOKIE_PATH', '/' );

/* No updates and no cron */
define('AUTOMATIC_UPDATER_DISABLED', true);
define('DISABLE_WP_CRON', true);
define('DISALLOW_FILE_EDIT', true);
define( 'DISALLOW_FILE_MODS', true );

/* Get FTP working */
define ('FS_METHOD', 'direct');
// define( 'FS_CHMOD_FILE', 0755 );
// define( 'FS_CHMOD_DIR', 0755 );
define( 'FTP_BASE', $root_dir . '/wp/' );
define( 'FTP_CONTENT_DIR', $root_dir . CONTENT_DIR);
define( 'FTP_PLUGIN_DIR ', $root_dir . CONTENT_DIR . '/plugins/' );

/* Multisite */
define( 'WP_ALLOW_MULTISITE', true );