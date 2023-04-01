<?php
/**
 * Plugin Name: WhatsApp Form
 * Description: A simple form that forwards data to WhatsApp and stores it in the WordPress database.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://kongsiweb.com/
 */

// Plugin code goes here
 
function whatsapp_form_shortcode() {
  ob_start(); ?>
  
  <form method="post">
    <label for="name">Nama:</label>
    <input type="text" name="name" id="name" required>

    <label for="phone">Nombor Telefon:</label>
    <input type="text" name="phone" id="phone" required>

    <label for="address">Alamat:</label>
    <textarea name="address" id="address" rows="4" cols="50" required></textarea>

    <input type="submit" value="Cek Coverage">
  </form>
  
  <?php
  $form = ob_get_clean();
  return $form;
}

add_shortcode('whatsapp_form', 'whatsapp_form_shortcode');

// Create database table for form submissions
function create_form_submissions_table() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'whatsapp_form_submissions';

  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    name varchar(50) NOT NULL,
    phone varchar(20) NOT NULL,
    message text NOT NULL,
    date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id)
  ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_form_submissions_table');

// Handle form submission
function handle_form_submission() {
  if (isset($_POST['name']) && isset($_POST['phone'])) {
    $name = sanitize_text_field($_POST['name']);
    $phone = sanitize_text_field($_POST['phone']);
    $address = sanitize_text_field($_POST['address']);

    // Store form submission in database
    global $wpdb;
    $table_name = $wpdb->prefix . 'whatsapp_form_submissions';
    $wpdb->insert(
      $table_name,
      array(
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
      )
    );

    // Generate WhatsApp message text
    $whatsapp_message = "Salam,saya $name (Tel: $phone).\nBoleh cek can coverage unifi alamat : $address.";

    // Generate WhatsApp URL with message text
    $whatsapp_url = "https://wa.me/60189777622?text=" . urlencode($whatsapp_message);

    // Redirect user to WhatsApp chat window
    wp_redirect($whatsapp_url);
    exit;
  }
}
add_action('init', 'handle_form_submission');

// Add custom menu item for the plugin's database table
function add_menu_item() {
  add_menu_page(
    'Whatsapp Database', // Page title
    'Whatsapp Lead', // Menu title
    'manage_options', // Capability required to access the page
    'form-submissions', // Menu slug
    'view_submissions' // Function to render the page
  );
}
add_action('admin_menu', 'add_menu_item');

// Function to render the plugin's database table in the dashboard
function view_submissions() {
  // Check user capabilities
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  // Get all form submissions from the database
  global $wpdb;
  $table_name = $wpdb->prefix . 'whatsapp_form_submissions';
  $submissions = $wpdb->get_results("SELECT * FROM $table_name");

  // Render table of form submissions
  echo '<div class="wrap">';
  echo '<h1>Whatsapp Database</h1>';
  echo '<table class="widefat">';
  echo '<thead>';
  echo '<tr><th>Name</th><th>Phone</th><th>Address</th><th>Date</th></tr>';
  echo '</thead>';
  echo '<tbody>';
  foreach ($submissions as $submission) {
    echo '<tr>';
    echo '<td>' . $submission->name . '</td>';
    echo '<td>' . $submission->phone . '</td>';
    echo '<td>' . $submission->address . '</td>';
    echo '<td>' . $submission->date . '</td>';
    echo '</tr>';
  }
  echo '</tbody>';
  echo '</table>';
  echo '</div>';
}

// Style
function whatsapp_form_enqueue_styles() {
    wp_enqueue_style( 'whatsapp-form-style', plugin_dir_url( __FILE__ ) . 'style.css' );
}
add_action( 'wp_enqueue_scripts', 'whatsapp_form_enqueue_styles' );

?>
