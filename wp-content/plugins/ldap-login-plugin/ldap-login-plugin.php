<?php
/**
 * Plugin Name: LDAP Login Plugin
 * Description: Authenticate WordPress users via Microsoft Active Directory (LDAP). Automatically creates users, maps data, and allows login via AD.
 * Version: 1.0
 * Author: Lorenzo Galassi
 */

defined('ABSPATH') or die('No script kiddies please!');

require_once plugin_dir_path(__FILE__) . 'includes/ldap-login.php';
