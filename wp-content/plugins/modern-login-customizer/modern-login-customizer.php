<?php
/**
 * Plugin Name: Modern Login Customizer
 * Description: Customize your WordPress login page with a custom logo, background color, and modern styles.
 * Version: 1.0
 * Author: Lorenzo Galassi
 */

if (!defined('ABSPATH')) exit;

// Enqueue custom styles on login page
add_action('login_enqueue_scripts', function () {
    $bg_color = get_option('mlc_bg_color', '#f0f0f0');
    $logo_url = wp_get_attachment_url(get_option('mlc_logo_id'));

    echo '<style>
        body.login {
            background-color: ' . esc_attr($bg_color) . ';
        }
        #login h1 a {
            background-image: url("' . esc_url($logo_url) . '");
            background-size: contain;
            width: 100%;
            height: 80px;
        }
        .login form {
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
    </style>';
});

// Add settings page
add_action('admin_menu', function () {
    add_options_page('Login Customizer', 'Login Customizer', 'manage_options', 'mlc-settings', 'mlc_settings_page');
});

// Register settings
add_action('admin_init', function () {
    register_setting('mlc_settings_group', 'mlc_bg_color');
    register_setting('mlc_settings_group', 'mlc_logo_id');
});

// Settings page HTML
function mlc_settings_page() {
    ?>
    <div class="wrap">
        <h1>Modern Login Customizer</h1>
        <form method="post" action="options.php">
            <?php settings_fields('mlc_settings_group'); ?>
            <?php do_settings_sections('mlc_settings_group'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Background Color</th>
                    <td>
                        <input type="text" name="mlc_bg_color" value="<?php echo esc_attr(get_option('mlc_bg_color', '#f0f0f0')); ?>" class="mlc-color-field" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Login Logo</th>
                    <td>
                        <?php $logo_id = get_option('mlc_logo_id'); ?>
                        <input type="hidden" name="mlc_logo_id" id="mlc_logo_id" value="<?php echo esc_attr($logo_id); ?>" />
                        <button type="button" class="button" id="mlc_upload_logo">Upload Logo</button>
                        <div id="mlc_logo_preview" style="margin-top:10px;">
                            <?php if ($logo_id) echo wp_get_attachment_image($logo_id, 'medium'); ?>
                        </div>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <script>
        jQuery(document).ready(function($){
            $('.mlc-color-field').wpColorPicker();

            $('#mlc_upload_logo').on('click', function(e) {
                e.preventDefault();
                var custom_uploader = wp.media({
                    title: 'Select Logo',
                    button: { text: 'Use this logo' },
                    multiple: false
                });

                custom_uploader.on('select', function() {
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    $('#mlc_logo_id').val(attachment.id);
                    $('#mlc_logo_preview').html('<img src="'+attachment.url+'" style="max-width:200px;">');
                });

                custom_uploader.open();
            });
        });
    </script>
    <?php
}

// Load color picker
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'settings_page_mlc-settings') return;
    wp_enqueue_media();
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
});
