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

class Globie_Vimeo_Sucker {
  public function __construct() {
    register_activation_hook( __FILE__, array( $this, 'after_activation' ) );
    add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    add_action( 'add_meta_boxes', array( $this, 'add_vimeo_field' ) );
    add_action( 'save_post', array( $this, 'save_vimeo_id' ) );
    add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    add_action( 'admin_init', array( $this, 'settings_init' ) );
  }

  public function after_activation() {
    if( !get_option( 'gvsucker_settings' ) ) {
      update_option( 'gvsucker_settings', array(
        'gvsucker_checkbox_post_type_post' => true
      ) );
    }
    //delete_option( 'gvsucker_settings' );
  }

  /** Load JS scripts
   *  Only on post.php and post-new.php
   */
  public function enqueue_scripts( $hook ){
    if( 'post.php' != $hook && 'post-new.php' != $hook )
      return;
    wp_register_script( 'globie-vimeo-sucker-script', plugins_url( '/globie-vimeo-sucker.js', __FILE__ ), array( 'jquery' ) );

    // Get plugin options
    $options = get_option( 'gvsucker_settings' );

    // Pass options to js script
    wp_localize_script( 'globie-vimeo-sucker-script', 'gVSuckerOptions', $options );

    // Enqueue script
    wp_enqueue_script( 'globie-vimeo-sucker-script' );
  }

  public function add_vimeo_field() {
    $options = get_option( 'gvsucker_settings' );

    // Get post types
    $post_types= get_post_types(
      array(
        'public' => true
      )
    );

    foreach( $post_types as $post_type ) {
      $field_name = 'gvsucker_checkbox_post_type_' . $post_type;
      if(array_key_exists( $field_name, $options ) ) {
        add_meta_box(
          'gvsucker-vimeo-id-meta-box',
          'Vimeo ID',
          array( $this, 'vimeo_id_meta_box_callback' ),
          $post_type
        );
      }
    }
  }

  /**
   * Prints the Vimeo ID box.
   *
   * @patam WP_Post $post The object for the current post.
   */
  public function vimeo_id_meta_box_callback( $post ) {

    // Add an nonce field so we can check for it later.
    wp_nonce_field( 'globie_vimeo_sucker', 'gvsucker_nonce' );

    /*
     * Use get_post_meta() to retrieve an existing value
     * from the database and use the value for the form.
     */
    $vimeo_id_value = get_post_meta( $post->ID, '_vimeo_id_value', true );
    $vimeo_width_value = get_post_meta( $post->ID, '_vimeo_width_value', true );
    $vimeo_height_value = get_post_meta( $post->ID, '_vimeo_height_value', true );
    $vimeo_ratio_value = get_post_meta( $post->ID, '_vimeo_ratio_value', true );

    echo '<input type="text" id="gvsucker-id-field" name="gvsucker-id-field" value="' . esc_attr( $vimeo_id_value ) . '" size="25" />';
    echo '<input type="hidden" id="gvsucker-img-field" name="gvsucker-img-field" value="" />';

    echo '<input type="hidden" id="gvsucker-width-field" name="gvsucker-width-field" value="' . esc_attr( $vimeo_width_value ) . '" />';
    echo '<input type="hidden" id="gvsucker-height-field" name="gvsucker-height-field" value="' . esc_attr( $vimeo_height_value ) . '" />';
    echo '<input type="hidden" id="gvsucker-ratio-field" name="gvsucker-ratio-field" value="' . esc_attr( $vimeo_ratio_value ) . '" />';

    echo ' <input type="submit" id="suck-vimeo-data" value="Suck it!" class="button">';
    echo ' <div id="globie-spinner" style="background: url(\'/wp-admin/images/wpspin_light.gif\') no-repeat; background-size: 16px 16px; display: none; opacity: .7; filter: alpha(opacity=70); width: 16px; height: 16px; margin: 0 10px;"></div>';
  }
  
  public function save_vimeo_id( $post_id ) {

    // Check nonce
    if ( ! isset( $_POST['gvsucker_nonce'] ) ) {
      return;
    }

    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['gvsucker_nonce'], 'globie_vimeo_sucker' ) ) {
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
    if ( ! isset( $_POST['gvsucker-id-field'] ) ) {
      return;
    }

    // Sanitize vimeo ID input
    $vimeo_id = sanitize_text_field( $_POST['gvsucker-id-field'] );

    // Update the vimeo ID field in the database.
    update_post_meta( $post_id, '_vimeo_id_value', $vimeo_id );

    // Sanitize video values
    $vimeo_width = sanitize_text_field( $_POST['gvsucker-width-field'] );
    $vimeo_height = sanitize_text_field( $_POST['gvsucker-height-field'] );
    $vimeo_ratio = sanitize_text_field( $_POST['gvsucker-ratio-field'] );

    // Update meta values
    update_post_meta( $post_id, '_vimeo_width_value', $vimeo_width );
    update_post_meta( $post_id, '_vimeo_height_value', $vimeo_height );
    update_post_meta( $post_id, '_vimeo_ratio_value', $vimeo_ratio ); 

    // Make sure that thumb url is set.
    if ( ! isset( $_POST['gvsucker-img-field'] ) ) {
      return;
    }

    // Sanitize user input
    $vimeo_img = sanitize_text_field( $_POST['gvsucker-img-field'] );
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

  public function add_admin_menu() { 
    add_options_page(
      'Globie Vimeo Sucker Options',
      'Globie Vimeo Sucker',
      'manage_options',
      'globie_vimeo_sucker',
      array( $this, 'options_page' )
    );
  }
  
  // Register settings, sections and fields
  public function settings_init() { 
    register_setting( 'gvsucker_options_page', 'gvsucker_settings' );

    // Add post type section
    add_settings_section(
      'gvsucker_post_types_section', 
      __( 'Enable/Disbale on post types', 'wordpress' ), 
      array( $this, 'settings_section_callback' ),
      'gvsucker_options_page'
    );

    // Post Types fields
    add_settings_field( 
      'gvsucker_post_types_fields', 
      __( 'Post types', 'wordpress' ), 
      array( $this, 'settings_post_types_fields_render' ), 
      'gvsucker_options_page', 
      'gvsucker_post_types_section' 
    );

    // Add whitelist section
    add_settings_section(
      'gvsucker_whitelist_section', 
      __( 'Tags whitelist', 'wordpress' ), 
      array( $this, 'settings_whitelist_section_callback' ),
      'gvsucker_options_page'
    );

    // Whitelist field
    add_settings_field( 
      'gvsucker_whitelist_fields', 
      __( 'Tags', 'wordpress' ), 
      array( $this, 'settings_whitelist_field_render' ),
      'gvsucker_options_page', 
      'gvsucker_whitelist_section' 
    );
  }
  
  public function settings_post_types_fields_render() {
    // Get options saved
    $options = get_option( 'gvsucker_settings' );

    // Get post types
    $post_types= get_post_types(
      array(
        'public' => true
      )
    );

    // Render fields
    echo "<fieldset>";
    foreach( $post_types as $post_type ) {
      $field_name = 'gvsucker_checkbox_post_type_' . $post_type;
      $checked = '';

      // Check if field is checked
      if( !empty( $options ) && array_key_exists( $field_name, $options ) )
        $checked = 'checked';

      echo '<label for="' .  $field_name . '"><input type="checkbox" name="gvsucker_settings[' .  $field_name . ']" id="' .  $field_name . '" value="1" ' . $checked . '> ' .  ucfirst($post_type) . '</label><br />';
    }
    echo "</fieldset>";
  }

  public function settings_section_callback() { 
    echo __( 'Select the post types where you want to enable the Viemo ID field', 'wordpress' );
  }

  public function settings_whitelist_field_render() { 

    // Get options saved
    $options = get_option( 'gvsucker_settings' );

    if(array_key_exists('gvsucker_input_whitelist', $options ) )
      $whitelist = $options['gvsucker_input_whitelist'];
    else
      $whitelist = '';

    // Render fields
    echo "<fieldset>";
    echo '<label for="gvsucker_input_whitelist"><input type="text" name="gvsucker_settings[gvsucker_input_whitelist]" id="gvsucker_input_whitelist" value="' . $whitelist  . '"></label><br />';
    echo "</fieldset>";
  }

  public function settings_whitelist_section_callback() { 
    echo __( 'Comma separeted list of whitelisted tags', 'wordpress' );
  }

  public function options_page() { 
    echo '<form action="options.php" method="post">';
    echo '<h2>Globie Vimeo Sucker Options</h2>';

    settings_fields( 'gvsucker_options_page' );
    do_settings_sections( 'gvsucker_options_page' );
    submit_button();
    
    echo '</form>';

  }
}
$gVSucker = new Globie_Vimeo_Sucker();

function pr( $var ) {
  echo '<pre>';
  print_r( $var );
  echo '</pre>';
}
