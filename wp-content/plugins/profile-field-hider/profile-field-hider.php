<?php
/**
 * Plugin Name: Profile Field Hider 
 * Description: Hide user profile fields and sections. Completely disable password/account management for LDAP environments.
 * Version: 1.2
 * Author: Lorenzo Galassi
 */

if (!defined('ABSPATH')) exit;

// Admin menu
add_action('admin_menu', function () {
    add_options_page(
        'Profile Field Hider',
        'Profile Field Hider',
        'manage_options',
        'profile-field-hider',
        'pfh_settings_page'
    );
});

// Disable WP password/account management UI + JS on profile pages
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'profile.php' && $hook !== 'user-edit.php') return;
    wp_deregister_script('user-profile');
});

// Remove application passwords & sessions UI hooks
add_action('admin_init', function () {
    remove_action('show_user_profile', 'wp_application_passwords_show_user_profile');
    remove_action('edit_user_profile', 'wp_application_passwords_show_user_profile');
    remove_action('personal_options_update', 'wp_application_passwords_personal_options_update');
    remove_action('edit_user_profile_update', 'wp_application_passwords_personal_options_update');

    remove_action('show_user_profile', 'show_user_sessions');
    remove_action('edit_user_profile', 'show_user_sessions');

    remove_action('show_user_profile', 'user_profile_password_fields');
    remove_action('edit_user_profile', 'user_profile_password_fields');
});

// Known profile field selectors for hiding
function pfh_get_fields() {
    return [
        'username'         => 'tr.user-user-login-wrap',
        'first_name'       => 'tr.user-first-name-wrap',
        'last_name'        => 'tr.user-last-name-wrap',
        'nickname'         => 'tr.user-nickname-wrap',
        'display_name'     => 'tr.user-display-name-wrap',
        'email'            => 'tr.user-email-wrap',
        'website'          => 'tr.user-url-wrap',
        'bio'              => 'tr.user-description-wrap',
        'profile_picture'  => 'tr.user-profile-picture',
        'color_scheme'     => '#color-picker',
        'toolbar'          => 'tr.user-admin-bar-front-wrap',
        'language'         => 'tr.user-language-wrap',
        'account_section'  => '#application-passwords-section, .user-pass1-wrap, tr.user-sessions-wrap, h2:contains("Account Management")',
    ];
}

// Admin settings page
function pfh_settings_page() {
    $hidden_fields = get_option('pfh_hidden_fields', []);
    $fields = pfh_get_fields();

    // Save on POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('pfh_save')) {
        $selected = isset($_POST['pfh_fields']) ? array_map('sanitize_text_field', $_POST['pfh_fields']) : [];
        update_option('pfh_hidden_fields', $selected);
        $hidden_fields = $selected;
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    echo '<div class="wrap"><h1>Profile Field Hider</h1>';
    echo '<form method="post">';
    wp_nonce_field('pfh_save');
    echo '<table class="form-table"><tbody>';

    foreach ($fields as $key => $selector) {
        echo '<tr><th scope="row">' . ucfirst(str_replace('_', ' ', $key)) . '</th>';
        echo '<td><input type="checkbox" name="pfh_fields[]" value="' . esc_attr($selector) . '" ' . checked(in_array($selector, $hidden_fields), true, false) . '> Hide</td></tr>';
    }

    echo '</tbody></table>';
    echo '<p><input type="submit" class="button-primary" value="Save Changes"></p>';
    echo '</form></div>';
}

// Inject CSS + JS to hide selected fields and clean up empty sections
add_action('admin_head', function () {
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->base, ['profile', 'user-edit'])) return;

    $hidden_fields = get_option('pfh_hidden_fields', []);
    echo '<style>';

    // Inject field-specific hiding
    foreach ($hidden_fields as $selector) {
        echo $selector . ' { display: none !important; }';
    }

    // Always hide password & account management UI
    echo '
        tr.user-pass1-wrap,
        tr.user-pass2-wrap,
        .user-pass1-wrap,
        #password,
        #pass1,
        #pass2,
        .user-password,
        #application-passwords-section,
        .user-sessions-wrap,
        #application-passwords-section + h2 {
            display: none !important;
        }';

    echo '</style>';

    // Hide section titles if all their fields are hidden
    echo '<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("h2").forEach(h => {
            const table = h.nextElementSibling;
            if (table && table.tagName === "TABLE") {
                const visibleRows = table.querySelectorAll("tr:not([style*=\'display: none\'])");
                if (visibleRows.length === 0) {
                    h.style.display = "none";
                    table.style.display = "none";
                }
            }
        });
    });
    </script>';
});
