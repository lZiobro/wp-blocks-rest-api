<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Security check
}

/**
 * Custom logic dedicated exclusively to Photo Gallery by 10Web (tw/bwg) blocks.
 */
add_filter( 'blocks_rest_api_process_block_tw_bwg', function( $block, $post ) {
    $shortcode = $block['attrs']['shortcode'] ?? '';
    if ( empty( $shortcode ) ) {
        return $block;
    }

    $shortcode_atts = shortcode_parse_atts( $shortcode );
    $shortcode_id   = $shortcode_atts['id'] ?? null;

    if ( ! is_numeric( $shortcode_id ) ) {
        return $block;
    }

    global $wpdb;

    // Retrieve raw shortcode parameters stored by Photo Gallery plugin
    $tagtext = $wpdb->get_var( $wpdb->prepare(
        "SELECT tagtext FROM {$wpdb->prefix}bwg_shortcode WHERE id = %d",
        $shortcode_id
    ) );

    if ( empty( $tagtext ) ) {
        return $block;
    }

    // Extract gallery_id attribute from tagtext
    if ( ! preg_match( '/gallery_id=["\']?(\d+)["\']?/', $tagtext, $matches ) ) {
        return $block;
    }

    $gallery_id = (int) $matches[1];

    // Fetch images associated with the gallery
    $images = $wpdb->get_results( $wpdb->prepare(
        "SELECT image_url, thumb_url FROM {$wpdb->prefix}bwg_image WHERE gallery_id = %d",
        $gallery_id
    ), ARRAY_A );

    $site_url = get_site_url();
    $base_upload_url = $site_url . '/wp-content/uploads/photo-gallery';

    $block['attrs']['gallery_images'] = array_map( function( $img ) use ( $base_upload_url ) {
        return [
            'image_url' => $base_upload_url . $img['image_url'],
            'thumb_url' => $base_upload_url . $img['thumb_url'],
        ];
    }, $images ?: [] );

    return $block;
}, 10, 2 );
