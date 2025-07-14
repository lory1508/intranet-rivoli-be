<?php
 
add_action( 'wp_enqueue_scripts', 'grand_sunrise_enqueue_styles' );

function grand_sunrise_enqueue_styles() {
	wp_enqueue_style(
		'grand-sunrise-style',
		get_stylesheet_uri()
	);
}

function filter_employee_by_service( $args, $request ) {
    // Check if 'service' and 'meta_value' are set in the request
    if ( isset( $request['meta_key'] ) && isset( $request['meta_value'] ) && $request['meta_key'] === 'service' ) {
        $args['meta_query'] = array(
            array(
                'key'     => 'service', // The ACF field key
                'value'   => $request['meta_value'], // The service ID you're looking for
                'compare' => 'LIKE' // Use LIKE to match against the serialized data
            ),
        );
    }
    return $args;
}
add_filter( 'rest_employee_query', 'filter_employee_by_service', 10, 2 );
function filter_employee_by_department( $args, $request ) {
    // Check if 'department' and 'meta_value' are set in the request
    if ( isset( $request['meta_key'] ) && isset( $request['meta_value'] ) && $request['meta_key'] === 'department' ) {
        $args['meta_query'] = array(
            array(
                'key'     => 'department', // The ACF field key
                'value'   => $request['meta_value'], // The department ID you're looking for
                'compare' => 'LIKE' // Use LIKE to match against the serialized data
            ),
        );
    }
    return $args;
}
add_filter( 'rest_employee_query', 'filter_employee_by_department', 10, 2 );
function filter_employee_by_office( $args, $request ) {
    // Check if 'office' and 'meta_value' are set in the request
    if ( isset( $request['meta_key'] ) && isset( $request['meta_value'] ) && $request['meta_key'] === 'office' ) {
        $args['meta_query'] = array(
            array(
                'key'     => 'office', // The ACF field key
                'value'   => $request['meta_value'], // The office ID you're looking for
                'compare' => 'LIKE' // Use LIKE to match against the serialized data
            ),
        );
    }
    return $args;
}
add_filter( 'rest_employee_query', 'filter_employee_by_office', 10, 2 );

// function remove_subscribers_manage_categories() {
//     // get_role returns an instance of WP_Role.
//     $role = get_role( 'editor' );
//     $role->remove_cap( 'manage_categories' );
// }
// add_action( 'admin_init', 'remove_subscribers_manage_categories' );


add_action('template_redirect', function() {
	if(!is_admin() && !is_user_logged_in()) {
		wp_redirect(wp_login_url());
		exit;
	}
});

add_filter('rest_prepare_user', function ($response, $user, $request) {
    // Try to get first name
    $first_name = get_user_meta($user->ID, 'first_name', true);

    // Fallback to display_name if first_name is empty
    if (!empty($first_name)) {
        $response->data['name'] = $first_name;
    } else {
        $response->data['name'] = $user->display_name;
    }

    return $response;
}, 10, 3);

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/ldap-users', [
        'methods' => 'GET',
        'callback' => function () {
            $users = get_users();

            $result = array_map(function ($user) {
                $data = [];

                // Include all standard WP_User fields
                foreach (get_object_vars($user) as $key => $value) {
                    $data[$key] = $value;
                }
                
                // ✅ Add avatar manually
                $data['avatar_url'] = get_avatar_url($user->ID);

                // Include all user meta fields
                $meta = get_user_meta($user->ID);
                foreach ($meta as $meta_key => $meta_value) {
                    $value = (count($meta_value) === 1) ? maybe_unserialize($meta_value[0]) : array_map('maybe_unserialize', $meta_value);

                    // 🔍 If the meta key is department/office/service and is a post ID, load the post object
                    if (in_array($meta_key, ['department', 'office', 'service'])) {
                        if (is_array($value)) {
                            $data[$meta_key] = array_map(function ($id) {
                                $post = get_post($id);
                                return $post ? [
                                    'ID' => $post->ID,
                                    'title' => get_the_title($post),
                                    'slug' => $post->post_name,
                                    'type' => $post->post_type,
                                    'content' => $post->post_content,
                                ] : null;
                            }, $value);
                        } else {
                            $post = get_post($value);
                            $data[$meta_key] = $post ? [
                                'ID' => $post->ID,
                                'title' => get_the_title($post),
                                'slug' => $post->post_name,
                                'type' => $post->post_type,
                                'content' => $post->post_content,
                            ] : null;
                        }
                    } else {
                        $data[$meta_key] = $value;
                    }
                }

                return $data;
            }, $users);


            return $result;
        },
        'permission_callback' => '__return_true',
    ]);
});

add_filter('get_avatar_url', function($url, $id_or_email, $args) {
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
        if ($avatar_id) {
            $custom_url = wp_get_attachment_image_url($avatar_id, $args['size']);
            if ($custom_url) return $custom_url;
        }
    }

    return $url; // fallback to original
}, 10, 3);



?>

