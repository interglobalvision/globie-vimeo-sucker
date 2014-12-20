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

/**
 * Gets the video data from Vimeo.
 *
 * @param string $video_id The ID of the video.
 */
function save_vimeo_data( $post_id ) {

  require 'vendor/autoload.php';

  $vimeo = new \Vimeo\Vimeo( 'cb3cb6706a0e37ce067cb9ec66cf17b253926a79','925468f2b2567d1ca9dd27026fcbcd4ec0e5a128' );
  $token = $vimeo->clientCredentials();
  $vimeo->setToken( $token['body']['access_token'] );

  $video_id = get_post_meta( $post_id, '_vimeo_id_value', true );

  $video_data = $vimeo->request('/videos/' . $video_id, array(), 'GET');

  $video_data = $video_data['body'];

  if( array_key_exists( 'error', $video_data ) ) {
    // TODO: error handling
  } else {

    // Concat all tags
    foreach( $video_data['tags'] as $tag ) {
      $tags .= $tag['tag'] . ', ';
    }

    $video_post = array(
      'ID' => $post_id,
      'post_title' => $video_data['name'],
      'post_content' => $video_data['description'], // TODO: check if interprets breaklines
      'tags_input' => $tags 
    );
    wp_update_post( $video_post );
  }
}
add_action( 'save_post', 'save_vimeo_data' );
?>
