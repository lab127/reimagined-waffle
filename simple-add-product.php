<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../wp-load.php';

// add a new product to WooCommerce using $wpdb->insert() and complete all required fields

global $wpdb;

// Set up the product data
$product_data = array(
    'post_author' => 1, // ID of the user who authored the product
    'post_date' => current_time('mysql'), // The current date and time
    'post_date_gmt' => current_time('mysql', 1), // The current GMT date and time
    'post_content' => 'Product description',
    'post_title' => 'Product name',
    'post_excerpt' => '',
    'post_status' => 'publish',
    'comment_status' => 'open',
    'ping_status' => 'closed',
    'post_password' => '',
    'post_name' => 'dfsfa3243x-product-slug',
    'to_ping' => '',
    'pinged' => '',
    'post_modified' => current_time('mysql'),
    'post_modified_gmt' => current_time('mysql', 1),
    'post_content_filtered' => '',
    'post_parent' => 0,
    'guid' => home_url('/').'product/dfsfa3243x-product-slug/',
    'menu_order' => 0,
    'post_type' => 'product',
    'post_mime_type' => '',
    'comment_count' => 0
);

// Insert the product into the wp_posts table
$wpdb->insert( $wpdb->posts, $product_data );

// Get the ID of the newly inserted product
$product_id = $wpdb->insert_id;

// Set up the product meta data
$product_meta_data = array(
    '_sku' => 'product_sku',
    '_price' => 10.00,
    '_regular_price' => 10.00,
    '_manage_stock' => 'yes',
    '_stock' => 100
);

// Insert the product meta data into the wp_postmeta table
foreach ( $product_meta_data as $key => $value ) {
    $wpdb->insert(
        $wpdb->postmeta,
        array(
            'post_id' => $product_id,
            'meta_key' => $key,
            'meta_value' => $value
        )
    );
}

// Set up the product category data
$category_data = array(
    'term_id' => 1 // Uncategorized - ID of the category you want to add the product to
);

// Insert the product category data into the wp_term_relationships table
$wpdb->insert(
    $wpdb->term_relationships,
    array(
        'object_id' => $product_id,
        'term_taxonomy_id' => $category_data['term_id']
    )
);

// Update the term count in the wp_term_taxonomy table
$wpdb->query( 
    $wpdb->prepare( 
        "
        UPDATE $wpdb->term_taxonomy
        SET count = count + 1
        WHERE term_taxonomy_id = %d
        ", 
        $category_data['term_id'] 
    ) 
);

// Update the category count in the wp_term_taxonomy table
$wpdb->query( 
    $wpdb->prepare( 
        "
        UPDATE $wpdb->terms
        SET count = count + 1
        WHERE term_id = %d
        ", 
        $category_data['term_id'] 
    ) 
);



?>


