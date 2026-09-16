<?php
/**
 * Plugin Name: Blocks REST API
 * Description: Registers a custom REST endpoint to deliver parsed block data, server-rendered dynamic blocks, and customizable hooks.
 * Version:     0.1.0
 * Author:      Lukasz Ziobro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Security check
}

/**
 * 1. Register the Custom Endpoint
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'blocks/v1', '/posts/(?P<id>\d+)', array(
        'methods'             => 'GET',
        'callback'            => 'brapi_get_parsed_post_blocks',
        'permission_callback' => '__return_true', // Adjust if private access is needed
        'args'                => array(
            'id' => array(
                'validate_callback' => function( $param ) {
                    return is_numeric( $param );
                },
            ),
        ),
    ) );
} );

/**
 * 2. Endpoint Callback Handler
 */
function brapi_get_parsed_post_blocks( WP_REST_Request $request ) {
    $post_id = $request['id'];
    $post_status = get_post_status($post_id);
    $is_post_visible = is_post_status_viewable($post_status);

    if( ! $is_post_visible ) {
        return new WP_Error( 'no_post', 'Post not found', array( 'status' => 404 ) );
    }

    $post = get_post( $post_id );

    if ( ! $post ) {
        return new WP_Error( 'err_retrieve_post', 'Error retrieving post', array( 'status' => 500 ) );
    }

    // Convert Gutenberg content comments into structured arrays
    $raw_blocks       = parse_blocks( $post->post_content );
    $processed_blocks = array();

    foreach ( $raw_blocks as $block ) {
        // Global filter: Allows modification of ANY block (e.g. blocks from some group like acf/)
        $block = apply_filters( 'blocks_rest_api_process_block', $block, $post );

        // Block-specific filter: Allows targeted overrides (e.g. blocks_rest_api_process_block_core_footnotes)
        if ( ! empty( $block['blockName'] ) ) {
            $sanitized_name = str_replace( '/', '_', $block['blockName'] );
            $block          = apply_filters( "blocks_rest_api_process_block_{$sanitized_name}", $block, $post );
        }

        // Append block if not filtered out (returning false/null removes a block)
        if ( $block ) {
            $processed_blocks[] = $block;
        }
    }

    // Attach basic post info
    $shortDescription = get_the_excerpt($post);
    $author_all_meta = get_user_meta((int)$post->post_author, true);
    $author_avatar_url = get_avatar_url((int)$post->post_author, true);
    $author = array(
        'nickname' => $author_all_meta['nickname'],
        'name' => $author_all_meta['first_name'] . ' ' . $author_all_meta['last_name'],
        'description' => $author_all_meta['description'],
        'avatarUrl' => $author_avatar_url
    );
    $preview_image = get_the_post_thumbnail_url($post);

    $response = array(
        "id" => $post->ID,
        "title" => $post->post_title,
        "shortDescription" => $shortDescription,
        "previewImage" => $preview_image,
        "date" => $post->post_date,
        "author" => $author,
        "blocks" => $processed_blocks
    );

    return rest_ensure_response( $response );
}

/**
 * Recursive Auto-loader: Loads all .php files from /includes/filters/ and any nested subdirectories.
 */
add_action( 'plugins_loaded', function() {
    $filters_dir = plugin_dir_path( __FILE__ ) . 'includes/filters/';

    if ( ! is_dir( $filters_dir ) ) {
        return;
    }

    $directory = new RecursiveDirectoryIterator( $filters_dir );
    $iterator  = new RecursiveIteratorIterator( $directory );

    foreach ( $iterator as $file ) {
        // Only load regular PHP files, ignoring hidden files or folders
        if ( $file->isFile() && $file->getExtension() === 'php' ) {
            require_once $file->getPathname();
        }
    }
} );
