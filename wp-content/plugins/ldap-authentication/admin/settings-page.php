<?php
add_action('admin_menu', function () {
    add_options_page('LDAP Authentication', 'LDAP Auth', 'manage_options', 'lg_ldap_auth', 'lg_ldap_auth_settings_page');
});

add_action('admin_init', function () {
    register_setting('lg_ldap_auth_settings_group', 'lg_ldap_auth_settings');
});

function lg_ldap_auth_settings_page() {
    $options = get_option('lg_ldap_auth_settings');
    ?>
    <div class="wrap">
        <h1>LDAP Authentication Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('lg_ldap_auth_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="ldap_host">LDAP Host</label></th>
                    <td><input name="lg_ldap_auth_settings[ldap_host]" type="text" value="<?= esc_attr($options['ldap_host'] ?? '') ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="ldap_port">LDAP Port</label></th>
                    <td><input name="lg_ldap_auth_settings[ldap_port]" type="number" value="<?= esc_attr($options['ldap_port'] ?? 389) ?>" class="small-text" /></td>
                </tr>
                <tr>
                    <th><label for="ldap_base_dn">Base DN</label></th>
                    <td><input name="lg_ldap_auth_settings[ldap_base_dn]" type="text" value="<?= esc_attr($options['ldap_base_dn'] ?? '') ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="ldap_bind_user">Service Account Username</label></th>
                    <td><input name="lg_ldap_auth_settings[ldap_bind_user]" type="text" value="<?= esc_attr($options['ldap_bind_user'] ?? '') ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="ldap_bind_pass">Service Account Password</label></th>
                    <td><input name="lg_ldap_auth_settings[ldap_bind_pass]" type="password" value="<?= esc_attr($options['ldap_bind_pass'] ?? '') ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="ldap_user_suffix">User Suffix (optional)</label></th>
                    <td><input name="lg_ldap_auth_settings[ldap_user_suffix]" type="text" value="<?= esc_attr($options['ldap_user_suffix'] ?? '') ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="ldap_search_filter">Search Filter</label></th>
                    <td><input name="lg_ldap_auth_settings[ldap_search_filter]" type="text" value="<?= esc_attr($options['ldap_search_filter'] ?? '(sAMAccountName=%s)') ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Group Filter DN (optional)</th>
                    <td>
                        <input type="text" name="lg_ldap_auth_settings[group_filter_dn]" value="<?php echo esc_attr($options['group_filter_dn'] ?? ''); ?>" size="60" />
                        <p class="description">Enter full DN of the LDAP group to restrict imported users, e.g. <code>CN=WPUsers,OU=Groups,DC=example,DC=com</code>. Leave empty to disable.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Import Disabled Accounts?</th>
                    <td>
                        <label>
                            <input type="checkbox" name="lg_ldap_auth_settings[import_disabled]" value="1" <?php checked(1, $options['import_disabled'] ?? 0); ?> />
                            Yes, import disabled accounts
                        </label>
                        <p class="description">Imports all users matching your filter and optional group. Disabled accounts are skipped unless checked above.</p>
                    </td>
                </tr>

            </table>
            <?php submit_button(); ?>
        </form>

        <h2>Test Connection</h2>
        <button id="ldap-test-connection" class="button">Test LDAP Connection</button>
        <div id="ldap-test-result" style="margin-top:10px;"></div>

        <h2>Bulk Import Users from LDAP</h2>
        <p>
            <button type="button" class="button" id="ldap-import-users">Import Users</button>
        </p>
        <div id="ldap-import-result" style="margin-top: 10px;"></div>

    </div>

    <script>
    document.getElementById('ldap-test-connection').addEventListener('click', function () {
        const resultDiv = document.getElementById('ldap-test-result');
        resultDiv.innerHTML = 'Testing...';

        fetch(ajaxurl + '?action=lg_ldap_test_connection')
            .then(res => res.text())
            .then(data => {
                resultDiv.innerHTML = data;
            })
            .catch(() => {
                resultDiv.innerHTML = 'Failed to contact server.';
            });
    });
    
    // document.getElementById("ldap-import-users").addEventListener("click", function () {
    //     const result = document.getElementById("ldap-import-result");
    //     result.innerHTML = "⏳ Importing users...";
    //     fetch(ajaxurl + "?action=lg_ldap_import_users")
    //         .then((res) => res.text())
    //         .then((data) => {
    //             result.innerHTML = data;
    //         })
    //         .catch(() => {
    //             result.innerHTML = "❌ Error importing users.";
    //         });
    // });

    document.getElementById("ldap-import-users").addEventListener("click", function () {
    const result = document.getElementById("ldap-import-result");
    result.innerHTML = "⏳ Starting import...";

    let offset = 0;

    function importBatch() {
        fetch(ajaxurl + `?action=lg_ldap_import_users&offset=${offset}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const resp = data.data;
                    result.innerHTML = resp.message;

                    offset = resp.next_offset;
                    if (offset < resp.total) {
                        // Small delay to avoid overload (optional)
                        setTimeout(importBatch, 500);
                    } else {
                        result.innerHTML += "<br>🎉 Import finished.";
                    }
                } else {
                    result.innerHTML = "❌ Import failed: " + data.data;
                }
            })
            .catch(() => {
                result.innerHTML = "❌ Error importing users.";
            });
    }

    importBatch();
});

</script>

    <?php
}

add_action('wp_ajax_lg_ldap_test_connection', function () {
    $opt = get_option('lg_ldap_auth_settings');
    $conn = @ldap_connect($opt['ldap_host'], (int)$opt['ldap_port']);
    if (!$conn) {
        echo '❌ Could not connect to LDAP server.';
        wp_die();
    }

    ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

    if (@ldap_bind($conn, $opt['ldap_bind_user'], $opt['ldap_bind_pass'])) {
        echo '✅ Successfully connected and authenticated!';
    } else {
        echo '❌ Bind failed. Check service account credentials.';
    }

    ldap_unbind($conn);
    wp_die();
});