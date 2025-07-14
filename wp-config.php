<?php
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
define( 'DB_NAME', 'intranetNew' );

/** Database username */
define( 'DB_USER', 'admin_intranet' );

/** Database password */
define( 'DB_PASSWORD', 'R83&!v$2P3jj#yND4L%5X^$j%724!^P6' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'U{FRoncTM!_aH6LHX~/(n[zA/&B<{_7OXu ?zO9G5Hypf]dq~^/v?,@aW:vg@zA_' );
define( 'SECURE_AUTH_KEY',  'wq]Lh4nXk9L%]YRwmWqpoybtD/6NzOr3nr+gyev2dh2}Vfi>sJ$e,se+`sslI:V<' );
define( 'LOGGED_IN_KEY',    'mr:5)$gQi _h8O-jDzM#+Ei?aTapcG95uoDuHYWHA0|$>bGhb4(F(=oWDq6aF:;F' );
define( 'NONCE_KEY',        '.FTm)KF/U;3K|hi*P~ub9eF$v=#M@(J$_?&%6, ]r]>ustNZ]r6 ly4!!t4q!9s~' );
define( 'AUTH_SALT',        '2v`*n}Ov%M~:}jMc]Bx)nCO]^ktz#[HcQ>bOJ9CZa!e11%#~n,09^3y8EC_wbsMW' );
define( 'SECURE_AUTH_SALT', '(SI(YEB-d](8v1r#j9}m,rV$>woH1z]Oe)oC2l?=VKq{;w_DInqZzyruik3`w8|=' );
define( 'LOGGED_IN_SALT',   'IVsK=[xG->Rmf@K,46Hw~cP941jQea,t&`EepiQwpDC_!|857JniU+OjN4q-2yRI' );
define( 'NONCE_SALT',       'GGM@8J4JHXX4aX>&X$W&(*O!u2 uklkQa]1q=%YmZn,Vkx{D8c*[L>QpsGp=yY<i' );

define('JWT_AUTH_SECRET_KEY', 'aCstjMHwL5tSAokAVnAvg9xaZQ7VdE3J2/VQaNG4mqW021pKgVp5+UfdcSzrCXa07OloXFBBvGMTgk2H3qFoJw==');
define('JWT_AUTH_CORS_ENABLE', true);

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', true ); // Enable debugging
define( 'WP_DEBUG_LOG', true ); // Enable logging to wp-content/debug.log
define( 'WP_DEBUG_DISPLAY', false ); // Disable displaying errors on the page


/* Add any custom values between this line and the "stop editing" line. */


define('WP_HOME', 'http://admin-intranetbeta.comune.rivoli.to.it');
define('WP_SITEURL', 'http://admin-intranetbeta.comune.rivoli.to.it');
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
