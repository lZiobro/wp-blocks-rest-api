
# Blocks REST API

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Expose WordPress posts as structured block data via custom REST API endpoints. Easily extend, transform, and enrich dynamic blocks, ACF fields, and third-party plugin blocks for headless WordPress architecture.

---

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start with Docker](#quick-start-with-docker)
- [REST Endpoint Reference](#rest-endpoint-reference)
- [Response Schema](#response-schema)
  - [Example JSON Response](#example-json-response)
  - [TypeScript Interfaces](#typescript-interfaces)
- [Extending Blocks via Filters](#extending-blocks-via-filters)
  - [Filter Naming Rules](#filter-naming-rules)
  - [1. Handling Dynamic Core Blocks (e.g. Footnotes)](#1-handling-dynamic-core-blocks-eg-footnotes)
  - [2. Handling Third-Party Plugin Blocks (e.g. Photo Gallery)](#2-handling-third-party-plugin-blocks-eg-photo-gallery)
  - [3. Global Block Filtering & ACF Integration](#3-global-block-filtering--acf-integration)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## Features

- **Structured Block Output**: Return post with basic informations and content as Gutenberg blocks.
- **Dynamic Block Resolution**: Enrich and proccess dynamic server-rendered blocks.
- **Extensible Hook Architecture**: Global and block-specific WordPress filters.
- **Auto-Loading Filters**: Modular filter organization—drop PHP filter scripts into `includes/filters/` and they auto-load recursively.
- **Headless Ready**: Fully typed schema models ready for TypeScript / React / React Native frontends.

---

## Installation

### Method 1: Upload via WordPress Admin (Recommended)

1. Download the latest `blocks-rest-api.zip` release from the [GitHub Releases](https://github.com/lZiobro/wp-blocks-rest-api/releases) page.
2. Log in to your WordPress Admin Dashboard.
3. Go to **Plugins** &rarr; **Add New Plugin** and click the **Upload Plugin** button at the top.
4. Choose the downloaded `blocks-rest-api.zip` file and click **Install Now**.
5. Once installed, click **Activate Plugin**.

### Method 2: Manual Installation (FTP / Git)

1. Clone or extract the `blocks-rest-api` plugin directory into your WordPress site's plugin folder:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/lZiobro/wp-blocks-rest-api.git blocks-rest-api
   ```
2. Log in to your WordPress Admin Dashboard, navigate to **Plugins** &rarr; **Installed Plugins**, and click **Activate** under **Blocks REST API**.

---

## Quick Start with Docker

Want to try the plugin immediately without setting up a WordPress server manually?

You can run a complete pre-configured WordPress environment (with example posts, active plugin, and test database) using Docker.

 Repository including all the files and instructions can be found at [wp-blocks-docker](https://github.com/lZiobro/wp-blocks-docker).

---

## Usage

Once activated, query any published post by ID using the plugin's REST endpoint:

```http
GET /wp-json/blocks/v1/posts/{id}
```

**Example Request:**
```bash
curl -X GET "https://example.com/wp-json/blocks/v1/posts/32"
```

---

## REST Endpoint Reference

### `GET /wp-json/blocks/v1/posts/{id}`

- **URL Parameters:**
  - `id` *(integer, required)*: The WordPress Post ID.
- **Status Codes:**
  - `200 OK`: Post found and parsed successfully.
  - `404 Not Found`: Post ID does not exist or its post status is not publicly viewable.
  - `500 Internal Server Error`: Failed to retrieve the post object from WordPress (`get_post`).

---

## Response Schema

### Example Success Response (`200 OK`)

```json
{
  "id": 12,
  "title": "Text in-depth - Lists",
  "shortDescription": "Lists in WordPress can be multi-levelled.",
  "previewImage": "https://example.com/wp-content/uploads/2026/09/building-400x400.png",
  "date": "2026-09-14 17:55:25",
  "author": {
    "nickname": "editor",
    "name": "Jane Doe",
    "description": "Content creator & developer",
    "avatarUrl": "https://secure.gravatar.com/avatar/8368b6b19c1f0ef13e2e416945f18182054a70e82f74bc1a2ea160021c70f8aa?s=96&d=mm&r=g"
  },
  "blocks": [
    {
      "blockName": "core/paragraph",
      "attrs": [],
      "innerBlocks": [],
      "innerHTML": "\n<p>Lists are quite versatile in WordPress as they can be multi-levelled.</p>\n",
      "innerContent": [
        "\n<p>Lists are quite versatile in WordPress as they can be multi-levelled.</p>\n"
      ]
    },
    {
      "blockName": null,
      "attrs": [],
      "innerBlocks": [],
      "innerHTML": "\n\n",
      "innerContent": [
        "\n\n"
      ]
    },
    {
      "blockName": "core/footnotes",
      "attrs": {
        "items": [
          {
            "id": "a760ad20-5384-4504-b8d9-e0df63df5687",
            "content": "<a href=\"https://wordpress.org\">WordPress.org</a>"
          }
        ]
      },
      "innerBlocks": [],
      "innerHTML": "",
      "innerContent": []
    }
  ]
}
```

> [!NOTE]
> `blockName` can be `null` for unbranded whitespace or raw HTML delimiters between blocks.

### Error Responses

#### `404 Not Found` (Post Not Found / Non-Viewable)
Returned when the requested post ID does not exist or its post status is private/draft:
```json
{
  "code": "no_post",
  "message": "Post not found",
  "data": {
    "status": 404
  }
}
```

#### `500 Internal Server Error` (Retrieval Failure)
Returned if WordPress fails to retrieve the post object (`get_post` returns false):
```json
{
  "code": "err_retrieve_post",
  "message": "Error retrieving post",
  "data": {
    "status": 500
  }
}
```

### TypeScript Interfaces

```typescript
export interface WpAuthor {
  nickname: string | null;
  name: string;
  description: string | null;
  avatarUrl: string;
}

export interface WpBlock {
  blockName: string | null;
  attrs: Record<string, unknown>;
  innerBlocks: WpBlock[];
  innerHTML: string;
  innerContent: (string | null)[];
}

export interface PostBlocksResponse {
  id: number;
  title: string;
  shortDescription: string;
  previewImage: string | false;
  date: string;
  author: WpAuthor;
  blocks: WpBlock[];
}
```

---

## Extending Blocks via Filters

Certain WordPress blocks (e.g., dynamic core blocks or third-party plugin shortcode blocks) do not persist their calculated output inside `post_content`. What that means is that they have to be additionally processed before their content is served to the user. Read more at [official docs](https://developer.wordpress.org/block-editor/getting-started/fundamentals/static-dynamic-rendering/).

This package allows you to hook into the processing pipeline to populate or modify block data before sending the API response.

### Filter Naming Rules

The plugin provides two filter hooks during block iteration:

1. **Global Hook**: `blocks_rest_api_process_block`
   - Triggered for **every** block.
   - Callback parameters: `( array $block, WP_Post $post )`.

2. **Block-Specific Hook**: `blocks_rest_api_process_block_{sanitized_block_name}`
   - The slash (`/`) in `blockName` is converted to an underscore (`_`).
   - Example: `core/footnotes` &rarr; `blocks_rest_api_process_block_core_footnotes`
   - Example: `tw/bwg` &rarr; `blocks_rest_api_process_block_tw_bwg`
   - Callback parameters: `( array $block, WP_Post $post )`.

> [!TIP]
> **Auto-loading filters**: Any `.php` file saved inside `includes/filters/` (or its subdirectories) will be automatically required on `plugins_loaded`.

---

### 1. Handling Dynamic Core Blocks (e.g. Footnotes)

Dynamic blocks like `core/footnotes` default to an empty `innerHTML` in Gutenberg content because footnote metadata is stored in post meta. Without any filters it would return:

```json
{
  "blockName": "core/footnotes",
  "attrs": [],
  "innerBlocks": [],
  "innerHTML": "",
  "innerContent": []
}
```

With filters however, we can easily hook into processing pipeline to embed additional information. Create `includes/filters/core/filter-footnotes.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Security check
}

/**
 * Custom logic dedicated exclusively to core/footnotes blocks.
 */
add_filter( 'blocks_rest_api_process_block_core_footnotes', function( $block, $post ) {
    $footnotes_meta = get_post_meta( $post->ID, 'footnotes', true );
    $footnotes_data = ! empty( $footnotes_meta ) ? json_decode( $footnotes_meta, true ) : [];
    
    $block['attrs']['items'] = $footnotes_data;
    
    return $block;
}, 10, 2 );
```

```json
Now the response would look like:

{
  "blockName": "core/footnotes",
  "attrs": {
    "items": [
      {
        "content": "<a href=\"https://github.com/react/react-native/blob/main/CHANGELOG-0.6x.md#android-specific-79\">https://github.com/react/react-native/blob/main/CHANGELOG-0.6x.md#android-specific-79</a>",
        "id": "a760ad20-5384-4504-b8d9-e0df63df5687"
      }
    ]
  },
  "innerBlocks": [],
  "innerHTML": "",
  "innerContent": []
}
```

---

### 2. Handling Third-Party Plugin Blocks (e.g. Photo Gallery)

Third-party plugin blocks often render content via shortcodes (e.g., `tw/bwg` from *Photo Gallery by 10Web*). Without custom processing, the REST output only contains raw shortcode strings:

```json
{
  "blockName": "tw/bwg",
  "attrs": {
    "shortcode": "[Best_Wordpress_Gallery id=\"2\" gal_title=\"sample_gallery_1\"]"
  }
}
```

By adding a custom filter in `includes/filters/photo-gallery/filter-gallery.php`, you can query database tables directly and attach structured asset URLs:

```php
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

```

---

### 3. Global Block Filtering & ACF Integration

To attach Advanced Custom Fields (ACF) data to all ACF blocks automatically, use the global `blocks_rest_api_process_block` filter:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Automatically inject ACF field data into any block under the 'acf/' namespace.
 */
add_filter( 'blocks_rest_api_process_block', function( $block, $post ) {
    if ( ! empty( $block['blockName'] ) && str_contains( $block['blockName'], 'acf/' ) ) {
        if ( function_exists( 'get_fields' ) && ! empty( $block['attrs']['id'] ) ) {
            $block['acf_fields'] = get_fields( $block['attrs']['id'] );
        }
    }
    
    return $block;
}, 10, 2 );
```

> [!NOTE]
> Returning `false` or `null` inside any block filter will exclude that block from the API response payload.

---

### Troubleshooting

If your permalink settings is Plain, the "default" rest-api route will not working and you'll instead have to request:

```http://example.com/index.php?rest_route=/blocks/v1/posts/{id}```

e.g.

```http://example.com/index.php?rest_route=/blocks/v1/posts/8```

## License

This project is open source and available under the [MIT License](LICENSE).

