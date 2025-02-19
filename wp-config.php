<?php

//Begin Really Simple Security session cookie settings
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@ini_set('session.use_only_cookies', true);
//END Really Simple Security cookie settings
//Begin Really Simple Security key
define('RSSSL_KEY', 'FNZIYIP0gH6D2MCRiE5HuVs9lUPJGst3cWskWauW3bmRvlQQl8QbQLVldCC0mjoV');
//END Really Simple Security key
define( 'WP_CACHE', false /* Modified by NitroPack */ );
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'local');

/** Database username */
define('DB_USER', 'root');

/** Database password */
define('DB_PASSWORD', 'root');

/** Database hostname */
define('DB_HOST', 'localhost:/Users/jpstones/Library/Application Support/Local/run/nkulsOfi-/mysql/mysqld.sock');

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', '1faeec791b7f5cd26de83129983978dbc309bbd6dd448779a23a4862b6a9b817');
define('SECURE_AUTH_KEY', '02e0f8091f0712540478f557a7b223a3b69db7d8ead75435a0659110992bdbbe');
define('LOGGED_IN_KEY', 'd1894f4c804e9ea1db7c37e7d96dbe14d614e779207ea26248eda97bfd41f6e9');
define('NONCE_KEY', '8c70ffae72e6858f5618e0e2745a5bb833c7098c4f7e4e2f648d05aa0e9585cd');
define('AUTH_SALT', '1690c4a2fe4c041053a171a83164bc31a0fd42f07fdc949946a3b7c8f5df2b11');
define('SECURE_AUTH_SALT', 'd92242d5180b4ac675d9c452d1ff044c92cfc63a2651c63ed5d1bbe5e45c6f61');
define('LOGGED_IN_SALT', '9ce4b71a9cc5e162f5074ecac9a14ffe147732f3dd1922fc6daef34711af4702');
define('NONCE_SALT', '8198a7c773be6232756a4002193c0c68956d1b1e91cd01e8711417944b5e1ecd');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'GJN_';
define('WP_CRON_LOCK_TIMEOUT', 120);
define('AUTOSAVE_INTERVAL', 300);
define('WP_POST_REVISIONS', 20);
define('EMPTY_TRASH_DAYS', 7);
define('WP_AUTO_UPDATE_CORE', true);

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
/* Add any custom values between this line and the "stop editing" line. */



define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set( 'display_errors', 0 );
define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

define('WP_HOME', 'http://ccc.local');
define('WP_SITEURL', 'http://ccc.local');