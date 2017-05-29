<?php
/*
Plugin Name: Fandom Community Custom Post Types
Plugin URI:  http://www.amy-codes.com/portfolio/wordpress/fandom-plugin
Description: Custom Post Types and Taxonomies for Fandom Communities including Fanfics and Galleries.
Version:     1.0
Author:      Amy Negrette
Author URI:  http://www.amy-codes.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function init_posttypes() {
  // Register Custom Post Type
  register_post_type( 'news',
		array(
			'labels' => array(
				'name' => __( 'News' ),
        'singular_name' => __('News'),
        'items_list'            => __( 'Items list', 'text_domain' ),
			),
			'public' => true,
			'has_archive' => true,
			'rewrite' => array('slug' => 'news'),
      'capability_type' => 'news_post',
      'hierarchical' => false,
      'supports' => array('title', 'editor', 'comments', 'excerpt'),
      'taxonomies' => array( 'news-tags' ),
      'menu_position' => 5,
      'map_meta_cap' => true,
      'capabilities' => array(
        'delete_post' => 'delete_news_post'
      )
		)
  );
	register_post_type( 'fanfic',
		array(
			'labels' => array(
				'name' => __( 'Fanfics' ),
				'singular_name' => __( 'Fanfic' )
			),
			'public' => true,
			'has_archive' => false,
			'rewrite' => array('slug' => 'fanfic'),
      'hierarchical' => true,
      'capability_type' => 'fanfic',
      'supports' => array('title', 'editor', 'comments', 'excerpt'),
      'taxonomies' => array( 'fandom-tags', 'fandom-genre', 'fandom-series', 'fandom-warning' ),
      'show_in_admin_bar' => true,
      'menu_position' => 6,
      'capabilities' => array(
        'delete_post' => 'delete_fanfic'
      ),
      'map_meta_cap' => true,
		)
	);
}

function register_meta_boxes() {
  add_meta_box( 'fanfic-author-notes', 'Author Notes', 'metabox_author_notes', 'fanfic', 'normal', 'high');
  add_meta_box( 'news-extra', 'Other Settings', 'metabox_news_extra', 'news', 'side', 'high');
}

function metabox_author_notes($post) {
  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'author_notes_nonce' );

  $field_value = get_post_meta( $post->ID, 'author_notes', false );
  $default = isset($field_value[0]) ? $field_value[0] : '';
  wp_editor( $default, 'author_notes' , array(
    'media_buttons' => false,
    'textarea_rows' => 5,
  ));
}

function save_author_notes($post_id) {
  // verify if this is an auto save routine.
  // If it is our form has not been submitted, so we dont want to do anything
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
      return;

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times
  if ( ( isset ( $_POST['author_notes_nonce'] ) ) && ( ! wp_verify_nonce( $_POST['author_notes_nonce'], plugin_basename( __FILE__ ) ) ) )
      return;
  // Check permissions
  if ( !isset($_POST['post_type']) || $_POST['post_type'] != 'fanfic' || !current_user_can('edit_fanfic')) {
    return;
  }

  // OK, we're authenticated: we need to find and save the data
  if ( isset ( $_POST['author_notes'] ) ) {
    update_post_meta( $post_id, 'author_notes', $_POST['author_notes'] );
  }

  $wordcount = str_word_count($_POST['content']);
  update_post_meta($post_id,'wordcount', $wordcount);

}

function metabox_news_extra($post) {
  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'news_extra_nonce' );

  $field_value = get_post_meta( $post->ID, 'news_pinned', false );
  $checked = isset($field_value[0]) && boolval($field_value[0]) ? 'checked="checked"' : '';
  ?>
  <p>
      <label for='pinned' class="prfx-row-title">Pin to Top? </label>
      <input type='checkbox' name='news-pinned' id='news-pinned' <?php echo $checked; ?>>
  </p>
  <?php
}

function save_news_extra($post_id) {
  // verify if this is an auto save routine.
  // If it is our form has not been submitted, so we dont want to do anything
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
      return;

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times
  if ( ( isset ( $_POST['news_extra_nonce'] ) ) && ( ! wp_verify_nonce( $_POST['news_extra_nonce'], plugin_basename( __FILE__ ) ) ) )
      return;
  // Check permissions
  if ( !isset($_POST['post_type']) || $_POST['post_type'] != 'news' || !current_user_can('edit_news_post')) {
    // return;
  }

  // OK, we're authenticated: we need to find and save the data
  if ( isset ( $_POST['news-pinned'] ) && !empty($_POST['news-pinned']) ) {
    update_post_meta( $post_id, 'news_pinned', 1 );
  } else {
    update_post_meta( $post_id, 'news_pinned', 0 );
  }

}

function init_taxonomies() {
  register_taxonomy( 'news-tags', 'news', array(
    'description' => 'Tags for News Stories',
    'public' => true,
    'hierarchical' => false,
    'capabilities' => array(
      'manage_terms' => 'edit_news',
      'edit_terms' => 'edit_news',
      'delete_terms' => 'edit_news',
      'assign_terms' => 'edit_news',
    )
  ));
  // Fandom Taxonomy: Genre, Series, Content Warnings, Characters, Tags (Fandom)
  // For Fanfics, Galleries, etc.
  $fandom_post_types = array('fanfic');
  register_taxonomy( 'fandom-tags', $fandom_post_types, array(
    'description' => 'Tags for Fandom Work',
    'public' => true,
    'hierarchical' => false,
    'capabilities' => array(
      'manage_terms' => 'manage_fandom',
      'edit_terms' => 'manage_fandom',
      'delete_terms' => 'manage_fandom',
      'assign_terms' => 'manage_fandom',
    )
  ));
  register_taxonomy( 'fandom-genre', $fandom_post_types, array(
    'description' => 'Genre, such as Fantasy, Sci-Fi, Modern, Time Travel, etc.',
    'public' => true,
    'hierarchical' => false,
    'labels' => array(
      'name' => 'Genre',
      'search_items' => 'Search by Genre',
      'popular_item' => 'Popular Genres',
      'all_items' => 'All Genres',
      'edit_item' => 'Edit Genre',
      'view_item' => 'View Genre',
      'update_item' => 'Update Genre',
      'add_new_item' => 'Add New Genre',
      'new_item_name' => 'New Genre Name',
      'separate_items_with_commas' => 'Separate Genres with Commas',
      'add_or_remove_items' => 'Add or Remove Genre',
      'choose_from_most_used' => 'Choose from the most used Genres',
      'not_found' => 'No Genres found',
      'no_terms' => 'No Genres'
    ),
    'capabilities' => array(
      'manage_terms' => 'edit_fandom',
      'edit_terms' => 'edit_fandom',
      'delete_terms' => 'edit_fandom',
      'assign_terms' => 'edit_fandom',
    ),
    'rewrite' => array(
      'slug' => 'genre'
    )
  ));
  register_taxonomy( 'fandom-series', $fandom_post_types, array(
    'description' => 'TV Series, Movie, or Video Game',
    'public' => true,
    'hierarchical' => true,
    'labels' => array(
      'name' => 'Series',
      'search_items' => 'Search by Series',
      'all_items' => 'All Series',
      'parent_item' => 'Parent Series',
      'parent_item_colon' => 'Parent Series:',
      'edit_item' => 'Edit Series',
      'view_item' => 'View Series',
      'update_item' => 'Update Series',
      'add_new_item' => 'Add New Series',
      'new_item_name' => 'New Series Name',
      'not_found' => 'No Series found',
      'no_terms' => 'No Series'
    ),
    'capabilities' => array(
      'manage_terms' => 'edit_fandom',
      'edit_terms' => 'edit_fandom',
      'delete_terms' => 'edit_fandom',
      'assign_terms' => 'edit_fandom',
    ),
    'rewrite' => array(
      'slug' => 'series'
    )
  ));
  register_taxonomy( 'fandom-warning', $fandom_post_types, array(
    'description' => 'Content Warnings including Blood, Violence, Lanuage, etc...',
    'public' => true,
    'hierarchical' => false,
    'labels' => array(
      'name' => 'Content Warnings',
      'singular_name' => 'Content Warning',
      'search_items' => 'Search by Content Warning',
      'popular_item' => 'Popular Content Warnings',
      'all_items' => 'All Content Warning',
      'edit_item' => 'Edit Content Warning',
      'view_item' => 'View Content Warning',
      'update_item' => 'Update Content Warnings',
      'add_new_item' => 'Add New Content Warning',
      'new_item_name' => 'New Content Warning',
      'separate_items_with_commas' => 'Separate Warnings with Commas',
      'add_or_remove_items' => 'Add or Remove Content Warnings',
      'choose_from_most_used' => 'Choose from the most used Content Warnings',
      'not_found' => 'No Content Warning found',
      'no_terms' => 'No Content Warning'
    ),
    'capabilities' => array(
      'manage_terms' => 'manage_fandom',
      'edit_terms' => 'manage_fandom',
      'delete_terms' => 'manage_fandom',
      'assign_terms' => 'edit_fandom',
    ),
    'rewrite' => array(
      'slug' => 'cw'
    )
  ));



}


function update_user_roles() {
  $admin = get_role('admin');
  $admin->add_cap('manage_fandom');
  $admin->add_cap('edit_fandom');
  $admin->add_cap('edit_news');
  $editor = get_role('editor');
  $editor->add_cap('manage_fandom');
  $editor->add_cap('edit_fandom');
  $editor->add_cap('edit_news');
  $author = get_role('author');
  $author->add_cap('edit_fandom');
}

add_action( 'init', 'init_posttypes' );
add_action( 'init', 'init_taxonomies' );
add_action( 'add_meta_boxes', 'register_meta_boxes' );
add_action( 'save_post', 'save_author_notes' );
add_action( 'save_post', 'save_news_extra' );
