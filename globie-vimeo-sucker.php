<?php
/**
 * Plugin Name: Globie Vimeo Sucker
 * Plugin URI: 
 * Description: Pull data directly from viemo and insert it on your post.
 * Version: 1.0.0
 * Author: Interglobal Vision
 * Author URI: http://interglobal.vision
 * License: GPL2 
*/

/** Load JS scripts 
 *  Only on post.php and post-new.php
 */
function globie_vimeo_sucker_enqueue($hook){
  if('post.php' != $hook && 'post-new.php' != $hook)
    return;
  wp_register_script( 'globie-vimeo-sucker-script', plugins_url('/globie-vimeo-sucker.js', __FILE__), array('jquery'));
  wp_enqueue_script( 'globie-vimeo-sucker-script' );
}
add_action('admin_enqueue_scripts', 'globie_vimeo_sucker_enqueue');

/**
 * Adds a box to the main column on the Post (FOR NOW) edit screen.
 */
function globie_add_vimeo_field() {
  add_meta_box(
    'globie-video-id-meta-box',
    'Vimeo ID',
    'globie_vimeo_id_meta_box_callback',
    'post'
  );
}
add_action( 'add_meta_boxes', 'globie_add_vimeo_field');

/**
 * Prints the Vimeo ID box.
 *
 * @patam WP_Post $post The object for the current post.
 */
function globie_vimeo_id_meta_box_callback( $post ) {

  // Add an nonce field so we can check for it later.
  wp_nonce_field( 'globie_vimeo_sucker', 'globie_vimeo_sucker_nonce' );

  /*
   * Use get_post_meta() to retrieve an existing value
   * from the database and use the value for the form.
   */
  $vimeo_id_value = get_post_meta( $post->ID, '_vimeo_id_value', true );
  
  echo '<label for="globie-vimeo-id-field">';
  //_e( 'Vimeo ID goes here', 'globie_vimeo_id' );
  echo '</label> ';
  echo '<input type="text" id="globie-vimeo-id-field" name="globie-vimeo-id-field" value="' . esc_attr( $vimeo_id_value ) . '" size="25" />';
  echo ' <input type="submit" id="suck-vimeo-data" value="Suck it!" class="button">';
}

function globie_save_vimeo_id( $post_id ) {
  
  // Check nonce
  if ( ! isset( $_POST['globie_vimeo_sucker_nonce'] ) ) {
    return;
  }

  // Verify nonce
  if ( ! wp_verify_nonce( $_POST['globie_vimeo_sucker_nonce'], 'globie_vimeo_sucker' ) ) {
    return;
  }

  // Prevent autosave
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
    return;
  }

  // Check the user's permissions.
  if ( ! current_user_can( 'edit_post', $post_id ) ) {
    return;
  }
  
  // OK, it's safe for us to save the data now. 
  
  // Make sure that it is set.
  if ( ! isset( $_POST['globie-vimeo-id-field'] ) ) {
    return;
  }

  // Sanitize user input
  $vimeo_id = sanitize_text_field( $_POST['globie-vimeo-id-field'] );

  // Update the meta field in the database.
  update_post_meta( $post_id, '_vimeo_id_value', $vimeo_id );
  
}
add_action( 'save_post', 'globie_save_vimeo_id' );
