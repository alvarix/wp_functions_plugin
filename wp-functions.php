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


 // function sc_get_posts( $category_slug, $tag_slug, $post_type ) {
 function sc_get_posts( $post_type ) {
    $args = array(
        'post_type' => $post_type,
        'posts_per_page' => -1,
        /*
        'tax_query' => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => $category_slug,
            ),
            array(
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => $tag_slug,
            ),
        ),
        */
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        return $query;
    } else {
        return false;
    }
}



function sc_get_post_terms( $post_id, $tax ) {
    $term_list = wp_get_post_terms($post_id, $tax);
    $out_list = '';

    if (!empty($term_list) && !is_wp_error($term_list)) {
        foreach ($term_list as $term) {
            $out_lis .= "<li>$term->name </li>";
        }
    } 

    return $out_lis;
}



 function output_acf_fields_with_labels($post_id = null) {
  if (!$post_id) $post_id = get_the_ID();
  $output = '';
  $fields = get_fields($post_id);
  foreach ($fields as $field_name => $field_value) {
      $field_object = get_field_object($field_name, $post_id);
      if (empty($field_value)) continue; // Skip empty fields
      if ($field_object['type'] === 'repeater') {
          $output .= '<h3>' . $field_object['label'] . '</h3>';
          foreach ($field_value as $row_index => $row) {
              $output .= '<div class="acf-field">';
              $output .= '<h4>' . $field_object['label'] . '</h4>';
              foreach ($row as $subfield_name => $subfield_value) {
                  $subfield_object = get_field_object($subfield_name, $post_id);
                  if (empty($subfield_value)) continue; // Skip empty subfields
                  if (is_array($subfield_value)) {
                      $subfield_value = implode(', ', $subfield_value);
                  }
                  $output .= '<div class="acf-subfield">';
                  $output .= '<strong>' . $subfield_object['label'] . ':</strong> ' . $subfield_value;
                  $output .= '</div>';
              }
              $output .= '</div>';
          }
      } else {
          if (is_array($field_value)) {
              $field_value = implode(', ', $field_value);
          }
          $output .= '<div class="acf-field">';
          $output .= '<h4>' . $field_object['label'] . '</h4>';
          $output .= '<div class="acf-value">' . $field_value . '</div>';
          $output .= '</div>';
      }
  }
  return $output;
}


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
add_filter('posts_join', 'cf_search_join' );

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
add_filter( 'posts_where', 'cf_search_where' );

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
add_filter( 'posts_distinct', 'cf_search_distinct' );








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



function sc_custom_javascript() { ?>
    <script>

    (function($) {
    // ext links
    $('a').filter(function() {
        return this.hostname && this.hostname !== location.hostname;
    }).addClass('ext-link').attr('target','_blank');
    })( jQuery );

    </script>
<?php
}
//add_action('wp_head', 'sc_custom_javascript');


// include CPTs and Tax
// require_once( get_stylesheet_directory() . '/taxonomies/topic-area.php');
// require_once( get_stylesheet_directory() . '/post-types/staff-member.php' );


// remove default Posts link in wp-admin menu
/*
function post_remove () { 
   remove_menu_page('edit.php');
} 
add_action('admin_menu', 'post_remove');
*/


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



/**
 *
 * Bricks
 *
 */

/**
 * Register custom elements
 */
add_action( 'init', function() {
  $element_files = [
    __DIR__ . '/elements/title.php',
  ];

  foreach ( $element_files as $file ) {
    \Bricks\Elements::register_element( $file );
  }
}, 11 );

/**
 * Add text strings to builder
 */
add_filter( 'bricks/builder/i18n', function( $i18n ) {
  // For element category 'custom'
  $i18n['custom'] = esc_html__( 'Custom', 'bricks' );

  return $i18n;
} );

