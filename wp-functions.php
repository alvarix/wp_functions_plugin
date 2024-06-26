<?php
/**
 * Plugin Name:     WP Functions
 * Author:          Alvarix
 * Text Domain:     wp-functions
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Wp_Functions
 */


# ========================================================
# = Basics
# ========================================================
// require_once( get_stylesheet_directory() . '/taxonomies/topic-area.php');
// require_once( get_stylesheet_directory() . '/post-types/staff-member.php' );

/**
 * Register/enqueue custom scripts and styles
 */
add_action( 'wp_enqueue_scripts', function() {
	// Enqueue your files on the canvas & frontend, not the builder panel. Otherwise custom CSS might affect builder)
  wp_enqueue_script( 'jquery' );

  wp_enqueue_script( 'bricks-child', get_stylesheet_directory_uri() . '/common.js', [], filemtime( get_stylesheet_directory() . '/common.js' ), true );
	if ( ! bricks_is_builder_main() ) {
		wp_enqueue_style( 'bricks-child', get_stylesheet_uri(), ['bricks-frontend'], filemtime( get_stylesheet_directory() . '/style.css' ) );
		wp_enqueue_style( 'bricks-child-responsive', get_stylesheet_directory_uri().'/responsive.css', ['bricks-frontend'], filemtime( get_stylesheet_directory() . '/responsive.css' ) );
	}
} );







require_once('_functions-post.php');




# ========================================================
# = Search
# ========================================================
/*
The following 3 filters are to add ACFs to search from
https://adambalee.com/search-wordpress-by-custom-fields-without-a-plugin/
 */


/**
 * Join posts and postmeta tables
 *
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
 */
function cf_search_join( $join ) {
    global $wpdb;
    if ( is_search() ) {    
        $join .=' LEFT JOIN '.$wpdb->postmeta. ' ON '. $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
    }
    return $join;
}
//add_filter('posts_join', 'cf_search_join' );

/**
 * Modify the search query with posts_where
 *
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
 */
function cf_search_where( $where ) {
    global $pagenow, $wpdb;
    if ( is_search() ) {
        $where = preg_replace(
            "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
            "(".$wpdb->posts.".post_title LIKE $1) OR (".$wpdb->postmeta.".meta_value LIKE $1)", $where );
    }
    return $where;
}
//add_filter( 'posts_where', 'cf_search_where' );

/**
 * Prevent duplicates
 *
 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
 */
function cf_search_distinct( $where ) {
    global $wpdb;
    if ( is_search() ) {
        return "DISTINCT";
    }
    return $where;
}
//add_filter( 'posts_distinct', 'cf_search_distinct' );





# ========================================================
# = Utils
# ========================================================


// display hierarchical post tax
// adapted from comments https://developer.wordpress.org/reference/functions/wp_list_categories/#user-contributed-notes
function hierarchical_post_tax($taxonomy, $postid) {
  $post_terms = wp_get_object_terms( $postid, $taxonomy, array( 'fields' => 'ids' ) );

  if ( ! empty( $post_terms ) && ! is_wp_error( $post_terms ) ) {
      $term_ids = implode( ',' , $post_terms );
      $terms = wp_list_categories( array(
          'title_li' => '',
          'echo'     => false,
          'hierarchical'        => true,
          'taxonomy' => $taxonomy,
          'include'  => $term_ids
      ) );
      return  '<ul>'.$terms.'</ul>';
  }
}




// https://wordpress.stackexchange.com/questions/100707/automatically-assign-parent-terms-when-a-child-term-is-selected
function assign_parent_terms($post_id, $post){
    if($post->post_type != 'resource')
        return $post_id;
    // get all assigned terms   
    $terms = wp_get_post_terms($post_id, 'topic-area' );
    foreach($terms as $term){
        while($term->parent != 0 && !has_term( $term->parent, 'topic-area', $post )){
            // move upward until we get to 0 level terms
            wp_set_post_terms($post_id, array($term->parent), 'topic-area', true);
            $term = get_term($term->parent, 'topic-area');
        }
    }
}
//add_action('save_post', 'assign_parent_terms', 10, 2);


// including JS 
// this one adds a class to external links
function custom_javascript() { ?>
<script>
    (function($) {

        // alert('hola')
        
        // ext links
        $('a').filter(function() {
            return this.hostname && this.hostname !== location.hostname;
        }).addClass('ext-link').attr('target','_blank');

    })( jQuery );

</script>
<?php
}
//add_action('wp_head', 'custom_javascript');



// remove default Posts link in wp-admin menu
function post_remove () { 
   remove_menu_page('edit.php');
} 
// add_action('admin_menu', 'post_remove');


// block WP enum scans
// https://m0n.co/enum

if (!is_admin()) {
	// default URL format
	if (preg_match('/author=([0-9]*)/i', $_SERVER['QUERY_STRING'])) die();
	add_filter('redirect_canonical', 'shapeSpace_check_enum', 10, 2);
}
function shapeSpace_check_enum($redirect, $request) {
	// permalink URL format
	if (preg_match('/\?author=([0-9]*)(\/*)/i', $request)) die();
	else return $redirect;
}


