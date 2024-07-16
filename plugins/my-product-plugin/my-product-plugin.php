<?php
/*
Plugin Name: Crispin Manda
Description: A plugin to handle product submissions.
Version: 1.0
Author: Your Name
*/

function my_product_plugin_enqueue() {
    wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'my_product_plugin_enqueue');

function my_product_plugin_form() {
    ob_start(); 
    
    // Check for status query parameters
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'success') {
            echo '<div class="alert alert-success" role="alert">Product added successfully!</div>';
        } elseif ($_GET['status'] == 'error') {
            echo '<div class="alert alert-danger" role="alert">There was an error adding the product. Please try again.</div>';
        }
    }
    ?>
    <div class="container">
        <h1>Add New Product</h1>
        <form id="product-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <div class="form-group">
                <label for="product_name">Product Name</label>
                <input type="text" class="form-control" id="product_name" name="product_name" required>
            </div>
            <div class="form-group">
                <label for="product_description">Product Description</label>
                <textarea class="form-control" id="product_description" name="product_description" rows="5" required></textarea>
            </div>
            <div class="form-group">
                <label for="product_category">Product Category</label>
                <select class="form-control" id="product_category" name="product_category" required>
                    <option value="">Select Category</option>
                    <option value="Sika">Sika</option>
                    <option value="Dr.Fixit">Dr.Fixit</option>
                    <option value="Kalekim">Kalekim</option>
                    <option value="Forsoc">Forsoc</option>
                </select>
            </div>
            <div class="form-group">
                <label for="product_image">Product Image</label>
                <input type="file" class="form-control-file" id="product_image" name="product_image" required>
            </div>
            <input type="hidden" name="action" value="add_product">
            <button type="submit" class="btn btn-primary">Add Product</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('product_form', 'my_product_plugin_form');


function handle_product_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_name']) && isset($_POST['product_category']) && isset($_FILES['product_image'])) {
        global $wpdb;

        $product_name = sanitize_text_field($_POST['product_name']);
        $product_description = sanitize_textarea_field($_POST['product_description']);
        $product_category = sanitize_text_field($_POST['product_category']);
        $product_image = $_FILES['product_image'];

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($product_image, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $wpdb->insert(
                $wpdb->prefix . 'products',
                array(
                    'product_name' => $product_name,
                    'product_description' => $product_description,
                    'product_category' => $product_category,
                    'product_image' => $movefile['url']
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                )
            );
            wp_redirect(add_query_arg('status', 'success', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('status', 'error', wp_get_referer()));
        }
        exit;
    }
}
add_action('admin_post_add_product', 'handle_product_form_submission');
add_action('admin_post_nopriv_add_product', 'handle_product_form_submission');



function my_product_plugin_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'products';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_name varchar(255) NOT NULL,
        product_description text NOT NULL,
        product_category varchar(255) NOT NULL,
        product_image varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'my_product_plugin_activate');

