<?php
/**
 * Plugin Name: Duplicate Content Tool
 * Plugin URI: https://example.com/duplicate-content-tool
 * Description: Un plugin convivial pour dupliquer rapidement des articles et des pages dans WordPress.
 * Version: 1.0
 * Author: Yassine
 * Author URI: https://example.com
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Function to duplicate posts/pages
function dct_duplicate_post_as_draft() {
    global $wpdb;
    
    // Verify if a post ID has been supplied
    if (! (isset($_GET['post']) || isset($_POST['post']) || (isset($_REQUEST['action']) && 'dct_duplicate_post_as_draft' == $_REQUEST['action']))) {
        wp_die(__('No post to duplicate has been supplied!', 'duplicate-content-tool'));
    }
    
    // Get the ID of the post to duplicate
    $post_id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
    $post = get_post($post_id);
    
    // Check if the post exists
    if (isset($post) && $post != null) {
        
        // Prepare the data for duplication
        $args = array(
            'post_author' => $post->post_author,
            'post_content' => $post->post_content,
            'post_title' => $post->post_title . ' (' . __('Copy', 'duplicate-content-tool') . ')',
            'post_status' => 'draft',
            'post_type' => $post->post_type,
            'post_parent' => $post->post_parent,
            'post_excerpt' => $post->post_excerpt,
            'post_category' => wp_get_post_categories($post->ID),
            'post_date' => current_time('mysql')
        );
        
        // Insert the duplicate as a draft
        $new_post_id = wp_insert_post($args);
        
        // Duplicate the taxonomies
        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
        }

        // Redirect to the edit screen for the new post
        wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
        exit;
        
    } else {
        wp_die(__('Post duplication failed, could not find original post.', 'duplicate-content-tool'));
    }
}
add_action('admin_action_dct_duplicate_post_as_draft', 'dct_duplicate_post_as_draft');

// Add the duplicate link to the post/page list
function dct_duplicate_post_link($actions, $post) {
    if (current_user_can('edit_posts')) {
        $actions['duplicate'] = '<a href="' . wp_nonce_url('admin.php?action=dct_duplicate_post_as_draft&post=' . $post->ID, basename(__FILE__), 'duplicate_nonce' ) . '" title="' . __('Duplicate this post', 'duplicate-content-tool') . '" rel="permalink">' . __('Duplicate', 'duplicate-content-tool') . '</a>';
    }
    return $actions;
}
add_filter('post_row_actions', 'dct_duplicate_post_link', 10, 2);
add_filter('page_row_actions', 'dct_duplicate_post_link', 10, 2);
