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
    'video'
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
  echo '<input type="hidden" id="globie-vimeo-img-field" name="globie-vimeo-img-field" value="" />';
  echo ' <input type="submit" id="suck-vimeo-data" value="Suck it!" class="button">';
  echo ' <div id="globie-spinner" style="background: url(\'/wp-admin/images/wpspin_light.gif\') no-repeat; background-size: 16px 16px; display: none; opacity: .7; filter: alpha(opacity=70); width: 16px; height: 16px; margin: 0 10px;"></div>';
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

  // Make sure that vimeo ID is set.
  if ( ! isset( $_POST['globie-vimeo-id-field'] ) ) {
    return;
  }

  // Sanitize vimeo ID input
  $vimeo_id = sanitize_text_field( $_POST['globie-vimeo-id-field'] );

  // Update the vimeo ID field in the database.
  update_post_meta( $post_id, '_vimeo_id_value', $vimeo_id );

  // Make sure that thumb url is set.
  if ( ! isset( $_POST['globie-vimeo-img-field'] ) ) {
    return;
  }

  // Sanitize user input
  $vimeo_img = sanitize_text_field( $_POST['globie-vimeo-img-field'] );
  $upload_dir = wp_upload_dir();

  //Get the remote image and save to uploads directory
  $img_name = time().'_'.basename( $vimeo_img );
  $img = wp_remote_get( $vimeo_img );
  if ( is_wp_error( $img ) ) {
    $error_message = $img->get_error_message();
    add_action( 'admin_notices', array( $this, 'wprthumb_admin_notice' ) );
  } else {
    $img = wp_remote_retrieve_body( $img );
    $fp = fopen( $upload_dir['path'].'/'.$img_name , 'w' );
    fwrite( $fp, $img );
    fclose( $fp );
    $wp_filetype = wp_check_filetype( $img_name , null );
    $attachment = array(
      'post_mime_type' => $wp_filetype['type'],
      'post_title' => preg_replace( '/\.[^.]+$/', '', $img_name ),
      'post_content' => '',
      'post_status' => 'inherit'
    );
    //require for wp_generate_attachment_metadata which generates image related meta-data also creates thumbs
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_id = wp_insert_attachment( $attachment, $upload_dir['path'].'/'.$img_name, $post_id );
    //Generate post thumbnail of different sizes.
    $attach_data = wp_generate_attachment_metadata( $attach_id , $upload_dir['path'].'/'.$img_name );
    wp_update_attachment_metadata( $attach_id,  $attach_data );
    //Set as featured image.
    delete_post_meta( $post_id, '_thumbnail_id' );
    add_post_meta( $post_id , '_thumbnail_id' , $attach_id, true );
  }

}
add_action( 'save_post', 'globie_save_vimeo_id' );
