<?php
/*
Plugin Name: Mobbees for Wordpress
Plugin URI: http://mobbees.com/
Description: Mobbees for Wordpress
Version: 1.0
Author: Solal Fitoussi
Author URI: http://mobbees.com/
License: GPL2
*/

function send_post($post) {

  // Get the post object
  global $post;
  $post_id = $post->ID;
  $post_title = get_the_title($post_id);
  $post_content = apply_filters('the_content', get_post($post_id)->post_content);
  $post_status = get_post_status($post_id);
  $post_type = get_post_type($post_id);
  // Get the post's date
  $post_publish_date = date("Y-m-d", get_the_date('U', $post_id));
  $post_publish_time = date("h:i:s", get_the_time('U', $post_id));
  $post_date_time = $post_publish_date . ' ' . $post_publish_time;
  $post_date = $post_date_time;
  $post_date_gmt = $post_date_time;

    // Fetch the post's categories
    $post_categories = wp_get_post_categories($post_id);
    $cats = array();
    foreach($post_categories as $c) {
      $cat = get_category($c);
      array_push($cats, $cat->name);
    }

  // Make the transmission
  $data = array(
    'id' => $post_id,
    'title' => $post_title,
    'content' => $post_content,
    'status' => $post_status,
    'date' => $post_date,
    'date_gmt' => $post_date_gmt,
    'categories' => $cats,
    'type' => $post_type
  );

  $data_string = json_encode($data);
  $ch = curl_init(get_option('mobbees_instance_name') .'/post-reception-page/');
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data_string))
  );
  $result = curl_exec($ch);

}

function posts_archive_transmission() {

  // Gather all the posts from the database
  global $wpdb;
  $post_archive = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_status != 'inherit'", ARRAY_A );
  $all_posts = array();

  // Fetch all the posts from the retrieved array
  for ($i = 0; $i < count($post_archive); $i++) {
    // Retrieve the current's post data
    $cur_post_id = $post_archive[$i]['ID'];
    $cur_post_title = $post_archive[$i]['post_title'];
    $cur_post_content = apply_filters('the_content', get_post($cur_post_id)->post_content);
    $cur_post_status = $post_archive[$i]['post_status'];
    $cur_post_date = $post_archive[$i]['post_date'];
    $cur_post_date_gmt = $post_archive[$i]['post_date_gmt'];
    $cur_post_type = $post_archive[$i]['post_type'];

    // Fetch the post's categories
    $post_categories = wp_get_post_categories($cur_post_id);
    $cats = array();
    foreach($post_categories as $c) {
      $cat = get_category($c);
      array_push($cats, $cat->name);
    }

    // Compile the post's data into an array
    $current_post = array(
      'id' => $cur_post_id,
      'title' => $cur_post_title,
      'content' => $cur_post_content,
      'categories' => $cats,
      'status' => $cur_post_status,
      'date' => $cur_post_date,
      'date_gmt' => $cur_post_date_gmt,
      'categories' => $cats,
      'type' => $cur_post_type
    );

    // Add the current post to the container array
    array_push($all_posts, $current_post);
  }

  // Make the transmission
  $data_string = json_encode($all_posts);
  $ch = curl_init(get_option('mobbees_instance_name') .'/post-archive-reception-page/');
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data_string))
  );
  $result = curl_exec($ch);

}

function full_zl_transmission() {

  // Fetch all the Ziplist recipes from the database
  global $wpdb;
  $all_zl_recipes = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "amd_zlrecipe_recipes", ARRAY_A );

  // Make the transmission
  $data = $all_zl_recipes;
  $data_string = json_encode($data);
  $ch = curl_init(get_option('mobbees_instance_name') .'/fullzl-reception-page/');
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data_string))
  );
  $result = curl_exec($ch);

}

function single_zl_transmission($post) {

  // Fetch the individual Ziplist recipe from the database
  global $wpdb;
  global $post;
  $zl_recipe = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "amd_zlrecipe_recipes WHERE post_id = " . $post->ID . "", ARRAY_A);

  // Make the transmission
  $data = $zl_recipe;
  $data_string = json_encode($data);
  $ch = curl_init(get_option('mobbees_instance_name') .'/singlezl-reception-page/');
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data_string))
  );
  $result = curl_exec($ch);

}

// Set the WP hook for the posts' related tasks
add_action('save_post', 'send_post');
add_action('publish_post', 'send_post');

/**/

// Mobbees custom menu
add_action('admin_menu', 'mobbees_options_menu');

function mobbees_options_menu() {
  add_menu_page('Mobbees Settings', 'Mobbees Settings', 'administrator', __FILE__, 'mobbees_settings_page');
  add_action( 'admin_init', 'register_mobbees_settings' );
}


function register_mobbees_settings() {
  register_setting( 'mobbees-settings-group', 'mobbees_instance_name' );
  full_zl_transmission();
  posts_archive_transmission();
}

function mobbees_settings_page() {
  ?>
  <div class="wrap">
    <h2>Mobbees</h2>
    <form method="post" action="options.php">
      <?php settings_fields( 'mobbees-settings-group' ); ?>
      <?php do_settings_sections( 'mobbees-settings-group' ); ?>
      <h3>Please enter your Mobbees Handle</h3>
      <input type="text" name="mobbees_instance_name" value="<?php echo get_option('mobbees_instance_name'); ?>" />
      <?php submit_button('Activate my app'); ?>
    </form>
    <p>Any issues? Please get in touch with support@mobbees.com or visit us at <a href="http://mobbees.com/" target="_blank">mobbees.com</a></p>
  </div>
  <?php
}
