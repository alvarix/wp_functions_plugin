<?php 


function register_post_list_with_acfs_shortcode() {
    add_shortcode('display_post_list_with_acfs', 'post_list_with_acfs');
}
add_action('init', 'register_post_list_with_acfs_shortcode');

function register_display_all_acfs_shortcode() {
    add_shortcode('display_all_acfs', 'output_acf_fields_with_labels');
}
add_action('init', 'register_display_all_acfs_shortcode');





function post_list_with_acfs( $post_type ) {
    $sc_query = sc_get_posts( $post_type );
    if ($sc_query) {
        
        /*
        echo '<h2>debug</h2><pre>';
        print_r($sc_query);
        echo '</pre>';
        */
        $out = '<ul class="posts">';
        while ($sc_query->have_posts()) {
            $sc_query->the_post();
                $post_id = get_the_ID();
                $title = get_the_title();
                $link = get_the_permalink();
                $excerpt = get_the_excerpt();
                $content = get_the_content();

               $pt = $post_type['post_type']; 
       
                $out .= "<li class='post $pt'>"; // $post_type returns array
                $out .= "<h2 class='post-title'><a href='$link'>$title</a></h2>";
          
                // $out .= "<div class='content'>$excerpt</div>";

                $out .= "<div class='meta'>";
                $out .= output_acf_fields_with_labels( $post_id );
                $out .= "</div> <!-- .meta -->";
                $out .= "</li>";

            }        
        $out .= "</ul>";
        $out .= my_custom_pagination($sc_query);

        wp_reset_postdata();
    } else {
        $out = 'No posts found.';
    }

    return $out;
}


function my_custom_pagination($query = null) {
    $big = 999999999; // Need an unlikely integer


    return paginate_links(array(
        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
        'format' => '?paged=%#%',

        'total' => $query->max_num_pages
    ));
}


//repeater conditional untested - it was removed on CSSN

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
          $output .= '<div class="acf-field ' . $field_object['name'] . '">';
          $output .= '<h4>' . $field_object['label'] . '</h4>';
          $output .= '<div class="acf-value">' . $field_value . '</div>';
          $output .= '</div>';
      }
  }
  return $output;
}
function sc_get_posts( $post_type ) {

    $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;


    $args = array(
        'paged' => $paged,
        'post_type'      => $post_type,
        'posts_per_page' => 8,
        "facetwp"        => true,
        'order'          => 'ASC', 
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


