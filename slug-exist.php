<?php

function slug_exist($post_name) {
    global $wpdb;
    $stmt = $wpdb->get_var($wpdb->prepare(
        "SELECT 1 FROM {$wpdb->posts} WHERE post_name = %s LIMIT 1", $post_name
    ));

    return $stmt;
}

$post_name = 'xxx-privacy-policy';
$i = 1;
while ( slug_exist($post_name) ) {
    $post_name = $post_name . '-' . $i;
    $i++;
}

var_dump($post_name);

?>
