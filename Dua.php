<?php

/**
Use $wpdb->insert instead of wp_insert_post: If you're inserting a large number of posts, it may be faster to use the $wpdb->insert function instead of wp_insert_post. The $wpdb->insert function inserts data directly into the database, bypassing many of the checks and filters that wp_insert_post performs. 
*/
function bulk_insert_posts_from_csv($filename) {
    global $wpdb;

    // Open CSV file for reading
    if (($handle = fopen($filename, "r")) !== FALSE) {
        // Loop through each row of the CSV file
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Extract post data from CSV row
            $post_title = $data[0];
            $post_content = $data[1];
            $post_author = $data[2];
            $post_date = $data[3];

            // Generate post slug
            $post_slug = sanitize_title($post_title);

            // Create post object
            $post = array(
                'post_title' => $post_title,
                'post_content' => $post_content,
                'post_author' => $post_author,
                'post_date' => $post_date,
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_name' => $post_slug
            );

            // Insert post into database
            $wpdb->insert($wpdb->prefix . 'posts', $post);
        }

        // Close CSV file
        fclose($handle);
    }
}

// update woocommerce version
function bulk_insert_products_from_csv($filename) {
    // Open CSV file for reading
    if (($handle = fopen($filename, "r")) !== FALSE) {
        // Loop through each row of the CSV file
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Extract product data from CSV row
            $product_name = $data[0];
            $product_description = $data[1];
            $product_price = $data[2];
            $product_image_url = $data[3];

            // Create product object
            $product = new WC_Product();
            $product->set_name($product_name);
            $product->set_description($product_description);
            $product->set_regular_price($product_price);
            $product->set_image_id(attachment_url_to_postid($product_image_url));
            $product->set_status('publish');

            // Insert product into database
            $product_id = wc_create_product($product);
        }

        // Close CSV file
        fclose($handle);
    }
}

// update image fetch from url
// updated version of the function that downloads and sets the product image if it's not uploaded yet
function bulk_insert_products_from_csv($filename) {
    // Open CSV file for reading
    if (($handle = fopen($filename, "r")) !== FALSE) {
        // Loop through each row of the CSV file
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Extract product data from CSV row
            $product_name = $data[0];
            $product_description = $data[1];
            $product_price = $data[2];
            $product_image_url = $data[3];

            // Download product image and set as featured image
            $image_id = null;
            if (!empty($product_image_url)) {
                $image = media_sideload_image($product_image_url, 0);
                if (!empty($image)) {
                    $image_id = (int)$image;
                }
            }

            // Create product object
            $product = new WC_Product();
            $product->set_name($product_name);
            $product->set_description($product_description);
            $product->set_regular_price($product_price);
            if ($image_id) {
                $product->set_image_id($image_id);
            }
            $product->set_status('publish');

            // Insert product into database
            $product_id = wc_create_product($product);
        }

        // Close CSV file
        fclose($handle);
    }
}

/** Note that media_sideload_image function requires the admin_url function to be defined, so if this function is used outside of the WordPress admin area, you may need to include the following code to define it
if (!function_exists('admin_url')) {
    function admin_url() {
        return get_home_url() . '/wp-admin';
    }
}
**/

// updated version of the function that creates variable products with variations
function bulk_insert_products_from_csv($filename) {
    // Open CSV file for reading
    if (($handle = fopen($filename, "r")) !== FALSE) {
        // Loop through each row of the CSV file
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Extract product data from CSV row
            $product_name = $data[0];
            $product_description = $data[1];
            $product_image_url = $data[2];
            $product_variations = array();

            // Loop through each variation in the CSV row
            for ($i = 3; $i < count($data); $i += 2) {
                // Extract variation data from CSV
                $variation_name = $data[$i];
                $variation_price = $data[$i + 1];

                // Create variation object
                $variation = new WC_Product_Variation();
                $variation->set_name($variation_name);
                $variation->set_regular_price($variation_price);
                $variation->set_parent_id($product_id);

                // Add variation to array
                $product_variations[] = $variation;
            }

            // Create product object
            $product = new WC_Product_Variable();
            $product->set_name($product_name);
            $product->set_description($product_description);
            $product->set_status('publish');

            // Download product image and set as featured image
            if (!empty($product_image_url)) {
                $image_id = (int)media_sideload_image($product_image_url, 0);
                if ($image_id) {
                    $product->set_image_id($image_id);
                }
            }

            // Set product variations
            if (!empty($product_variations)) {
                $product->set_attributes(array(
                    'size' => array(
                        'name' => 'size',
                        'value' => implode('|', array_column($product_variations, 'get_name')),
                        'is_visible' => '1',
                        'is_variation' => '1',
                        'is_taxonomy' => '0',
                    )
                ));
                $product->set_variations($product_variations);
            }

            // Insert product into database
            $product_id = $product->save();
        }

        // Close CSV file
        fclose($handle);
    }
}




?>

