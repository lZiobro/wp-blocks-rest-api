<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Security check
}

/**
 * Custom logic dedicated exclusively to core/footnotes blocks.
 */
add_filter( 'blocks_rest_api_process_block_core_footnotes', function( $block, $post ) {
    // Retrieve and set data necessary to process footnotes block
    $footnotes_meta = get_post_meta($post->ID, 'footnotes', true);
    $footnotes_data = !empty($footnotes_meta) ? json_decode($footnotes_meta, true) : [];
    $block['attrs']['items'] = $footnotes_data;
    
    return $block;
}, 10, 2 );