<?php
/*
Plugin Name: Media Tags and Categories
Description: Allows categories and tags to be assigned to media attachments.
Version: 1.0
Author: Lorenzo Galassi - SIA Città di Rivoli
*/


if (!defined('ABSPATH')) {
    exit;
}

// Register categories and tags for attachments
add_action('init', function () {
    register_taxonomy_for_object_type('category', 'attachment');
    register_taxonomy_for_object_type('post_tag', 'attachment');
});

// Add standard taxonomy meta boxes to media edit screen
add_action('add_meta_boxes_attachment', function () {
    add_meta_box('categorydiv', __('Categories'), 'post_categories_meta_box', 'attachment', 'side', 'default');
    add_meta_box('tagsdiv-post_tag', __('Tags'), 'post_tags_meta_box', 'attachment', 'side', 'default');
});

// Save taxonomy data when the attachment is saved
add_action('edit_attachment', function ($post_id) {
    // Save categories
    if (isset($_POST['post_category'])) {
        $categories = array_map('intval', $_POST['post_category']);
        wp_set_post_categories($post_id, $categories, false);
    }

    // Save tags
    if (isset($_POST['tax_input']['post_tag'])) {
        $tags = sanitize_text_field($_POST['tax_input']['post_tag']);
        wp_set_post_terms($post_id, $tags, 'post_tag', false);
    }
});
