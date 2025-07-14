<?php
/**
 * Plugin Name: Custom User Avatar (No Gravatar)
 * Description: Allow users to upload a custom avatar without using Gravatar.
 * Version: 1.0
 * Author: Lorenzo Galassi
 */

if (!defined('ABSPATH')) exit;

// 1. Add field to user profile
add_action('show_user_profile', 'cua_avatar_field');
add_action('edit_user_profile', 'cua_avatar_field');

function cua_avatar_field($user) {
    $avatar_id = get_user_meta($user->ID, 'cua_avatar_id', true);
    $avatar_url = $avatar_id ? wp_get_attachment_image_url($avatar_id, 'thumbnail') : '';

    ?>
    <h3>Custom Avatar</h3>
    <table class="form-table">
        <tr>
            <th><label for="cua_avatar_id">Upload Avatar</label></th>
            <td>
                <input type="hidden" name="cua_avatar_id" id="cua_avatar_id" value="<?php echo esc_attr($avatar_id); ?>">
                <button type="button" class="button" id="cua_upload_button">Upload Image</button>
                <div id="cua_preview" style="margin-top:10px;">
                    <?php if ($avatar_url): ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" style="max-width:100px; border-radius:50%;">
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
    <script>
        jQuery(document).ready(function($){
            var frame;
            $('#cua_upload_button').on('click', function(e){
                e.preventDefault();
                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: 'Select or Upload Avatar',
                    button: { text: 'Use this image' },
                    multiple: false
                });

                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#cua_avatar_id').val(attachment.id);
                    $('#cua_preview').html('<img src="'+attachment.url+'" style="max-width:100px; border-radius:50%;">');
                });

                frame.open();
            });
        });
    </script>
    <?php
}

// 2. Save the avatar
add_action('personal_options_update', 'cua_save_avatar');
add_action('edit_user_profile_update', 'cua_save_avatar');

function cua_save_avatar($user_id) {
    if (!current_user_can('edit_user', $user_id)) return;
    if (isset($_POST['cua_avatar_id'])) {
        update_user_meta($user_id, 'cua_avatar_id', intval($_POST['cua_avatar_id']));
    }
}

// 3. Replace the default avatar
add_filter('get_avatar', 'cua_filter_avatar', 10, 5);

function cua_filter_avatar($avatar, $id_or_email, $size, $default, $alt) {
    $user = false;

    if (is_numeric($id_or_email)) {
        $user = get_user_by('id', $id_or_email);
    } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
        $user = get_user_by('id', $id_or_email->user_id);
    } elseif (is_string($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
    }

    if ($user) {
        $avatar_id = get_user_meta($user->ID, 'cua_avatar_id', true);
        $avatar_url = $avatar_id ? wp_get_attachment_image_url($avatar_id, [$size, $size]) : false;

        if ($avatar_url) {
            return "<img alt='" . esc_attr($alt) . "' src='" . esc_url($avatar_url) . "' class='avatar avatar-$size photo' height='$size' width='$size' style='border-radius:50%;'>";
        }
    }

    return $avatar;
}

// 4. Enqueue media uploader on profile pages
add_action('admin_enqueue_scripts', function($hook) {
    if (in_array($hook, ['profile.php', 'user-edit.php'])) {
        wp_enqueue_media();
    }
});

// Hide the default Gravatar "Profile Picture" section on user profile
add_action('admin_head', function() {
    $screen = get_current_screen();
    if ($screen && in_array($screen->base, ['profile', 'user-edit'])) {
        echo '<style>
            tr.user-profile-picture { display: none !important; }
        </style>';
    }
});

// Expose custom avatar in REST API
add_action('rest_api_init', function () {
    register_rest_field('user', 'custom_avatar_url', [
        'get_callback' => function ($user) {
            $avatar_id = get_user_meta($user['id'], 'cua_avatar_id', true);
            return $avatar_id ? wp_get_attachment_image_url($avatar_id, 'thumbnail') : null;
        },
        'schema' => [
            'description' => 'Custom uploaded avatar URL',
            'type'        => 'string',
            'context'     => ['view', 'edit'],
        ],
    ]);
});
