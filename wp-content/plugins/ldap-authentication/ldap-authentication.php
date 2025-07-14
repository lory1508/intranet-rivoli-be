<?php
 /**
 * Plugin Name: LDAP Authentication
 * Description: Authenticate WordPress users via Microsoft Active Directory and sync user data.
 * Version: 1.0.0
 * Author: Lorenzo Galassi
 */

define('LG_LDAP_AUTH_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include admin settings
if (is_admin()) {
    require_once LG_LDAP_AUTH_PLUGIN_DIR . 'admin/settings-page.php';
}

// LDAP Authentication function
function lg_ldap_authenticate($username, $password) {
    $options = get_option('lg_ldap_auth_settings');
    $server = $options['ldap_host'] ?? '';
    $port = $options['ldap_port'] ?? 389;
    $bind_dn = $options['ldap_bind_user'] ?? '';
    $bind_password = $options['ldap_bind_pass'] ?? '';
    $base_dn = $options['ldap_base_dn'] ?? '';
    $search_filter = $options['ldap_search_filter'] ?? '(sAMAccountName=%s)';
    $user_suffix = $options['ldap_user_suffix'] ?? '';

    if (!$server || !$base_dn) {
        return new WP_Error('ldap_config_error', 'LDAP server or Base DN is not configured.');
    }

    $conn = ldap_connect($server, (int)$port);
    if (!$conn) {
        return new WP_Error('ldap_connection_failed', 'Could not connect to LDAP server.');
    }

    ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

    // Service account bind
    if ($bind_dn && $bind_password) {
        $bind = @ldap_bind($conn, $bind_dn, $bind_password);
        if (!$bind) {
            $err = ldap_error($conn);
            ldap_unbind($conn);
            return new WP_Error('ldap_bind_failed', 'Service account bind failed: ' . $err);
        }
    } else {
        $bind = @ldap_bind($conn);
        if (!$bind) {
            $err = ldap_error($conn);
            ldap_unbind($conn);
            return new WP_Error('ldap_bind_failed', 'Anonymous bind failed: ' . $err);
        }
    }

    // Search for user
    $filter = sprintf($search_filter, ldap_escape($username, '', LDAP_ESCAPE_FILTER));
    $search = ldap_search($conn, $base_dn, $filter);

    if (!$search) {
        $err = ldap_error($conn);
        ldap_unbind($conn);
        return new WP_Error('ldap_search_failed', 'LDAP search failed: ' . $err);
    }

    $entries = ldap_get_entries($conn, $search);
    if ($entries['count'] == 0) {
        ldap_unbind($conn);
        return new WP_Error('ldap_user_not_found', "User not found with filter: $filter");
    }

    $user_dn = $entries[0]['dn'];
    $bind_target = $user_dn . $user_suffix;

    // Debug info
    error_log("[LDAP] Trying to bind as: $bind_target");

    // Try user bind
    $user_bind = @ldap_bind($conn, $bind_target, $password);
    if (!$user_bind) {
        $err = ldap_error($conn);
        ldap_unbind($conn);
        return new WP_Error('ldap_auth_failed', "Invalid username or password. Tried DN: $bind_target — Error: $err");
    }

    ldap_unbind($conn);
    return true;
}


// Ajax handler for testing connection
add_action('wp_ajax_lg_ldap_test_connection', function () {
    $options = get_option('lg_ldap_auth_settings');
    $conn = @ldap_connect($options['ldap_host'] ?? '', (int)($options['ldap_port'] ?? 389));
    if (!$conn) {
        echo '❌ Could not connect to LDAP server.';
        wp_die();
    }

    ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

    if (@ldap_bind($conn, $options['ldap_bind_user'] ?? '', $options['ldap_bind_pass'] ?? '')) {
        echo '✅ Successfully connected and authenticated!';
    } else {
        echo '❌ Bind failed. Check service account credentials.';
    }

    ldap_unbind($conn);
    wp_die();
});

add_filter('authenticate', 'lg_ldap_auth_hook', 10, 3);

function lg_ldap_auth_hook($user, $username, $password) {
    if (!empty($username) && !empty($password)) {
        // Strip domain if present (e.g., user@domain.local)
        if (strpos($username, '@') !== false) {
            $username = explode('@', $username)[0];
        }

        // Prevent LDAP login with system accounts like krbtgt
        if (strtolower($username) === 'krbtgt') {
            return new WP_Error('[jwt_auth] ldap_error', '❌ Cannot login as internal system user.');
        }

        // First try WordPress auth
        $user = wp_authenticate_username_password(null, $username, $password);
        if (!is_wp_error($user)) return $user;

        // Try LDAP auth
        $result = lg_ldap_authenticate($username, $password);

        if (is_wp_error($result)) {
            return new WP_Error('[jwt_auth] ldap_error', '❌ LDAP Error...: ' . $result->get_error_message());
        }

        // LDAP success: create or get WordPress user
        $wp_user = get_user_by('login', $username);
        if (!$wp_user) {
            $random_pass = wp_generate_password();
            $user_id = wp_create_user($username, $random_pass, $username . '@example.com');

            if (is_wp_error($user_id)) {
                return new WP_Error('[jwt_auth] wp_create_error', '❌ Failed to create WordPress user.');
            }

            $wp_user = get_user_by('id', $user_id);
        }

        return $wp_user;
    }

    return $user;
}


add_filter('login_errors', 'lg_show_login_errors');
function lg_show_login_errors($error) {
    return $error; // Return raw error messages
}

add_action('wp_ajax_lg_ldap_import_users', 'lg_ldap_import_users_callback');
function lg_ldap_import_users_callback() {
    if (!current_user_can('manage_options')) {
        wp_die('❌ Unauthorized');
    }

    $options = get_option('lg_ldap_auth_settings');
    $server = $options['ldap_host'] ?? '';
    $port = $options['ldap_port'] ?? 389;
    $bind_dn = $options['ldap_bind_user'] ?? '';
    $bind_password = $options['ldap_bind_pass'] ?? '';
    $base_dn = $options['ldap_base_dn'] ?? '';
    $search_filter = $options['ldap_search_filter'] ?? '(objectClass=user)';
    $group_filter_dn = $options['group_filter_dn'] ?? '';
    $import_disabled = !empty($options['import_disabled']);

    // Get offset parameter from AJAX call (default 0)
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $batch_size = 50;

    if (!$server || !$base_dn) {
        wp_die('❌ LDAP settings missing.');
    }

    $ldap = ldap_connect($server, $port);
    if (!$ldap) {
        wp_die('❌ Could not connect to LDAP server.');
    }

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

    if (!@ldap_bind($ldap, $bind_dn, $bind_password)) {
        $err = ldap_error($ldap);
        wp_die('❌ LDAP bind failed: ' . $err);
    }

    // Build filter with optional group filter
    $base_filter = $search_filter;
    if ($group_filter_dn) {
        $group_filter = "(memberOf=$group_filter_dn)";
        $filter = "(&{$base_filter}{$group_filter})";
    } else {
        $filter = $base_filter;
    }

    $search = ldap_search($ldap, $base_dn, $filter);
    if (!$search) {
        wp_die('❌ LDAP search failed.');
    }

    $entries = ldap_get_entries($ldap, $search);
    $total = $entries['count'];

    if ($offset >= $total) {
        wp_die('✅ Import complete! All ' . $total . ' users imported.');
    }

    $imported = 0;
    $skipped_disabled = 0;
    $end = min($offset + $batch_size, $total);

    for ($i = $offset; $i < $end; $i++) {
        $entry = $entries[$i];

        $username = $entry['samaccountname'][0] ?? null;
        $email = $entry['mail'][0] ?? ($username ? $username . '@example.com' : '');
        $first = $entry['givenname'][0] ?? '';
        $last = $entry['sn'][0] ?? '';

        
        if (!$username) continue;
        
        $userAccountControl = isset($entry['useraccountcontrol'][0]) ? intval($entry['useraccountcontrol'][0]) : 0;
        $is_disabled = ($userAccountControl & 2) === 2;


        if ($is_disabled && !$import_disabled) {
            $skipped_disabled++;
            continue;
        }

        if (!get_user_by('login', $username)) {
            $user_id = wp_create_user($username, wp_generate_password(), $email);
            if (!is_wp_error($user_id)) {
                wp_update_user([
                    'ID' => $user_id,
                    'first_name' => $first,
                    'last_name' => $last,
                ]);
                $imported++;
            }
        }
    }

    $next_offset = $end;
    $msg = "✅ Imported $imported user(s) from $offset to " . ($end - 1) . ".";
    if ($skipped_disabled > 0) {
        $msg .= " Skipped $skipped_disabled disabled account(s).";
    }
    $msg .= " Next batch starting at $next_offset.";

    // Return JSON response for easier frontend processing
    wp_send_json_success([
        'message' => $msg,
        'next_offset' => $next_offset,
        'total' => $total,
    ]);
}
