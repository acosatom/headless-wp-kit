<?php
/**
 * REST capabilities endpoint for the app.
 *
 * @package Headless_WP_Kit
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', 'hwk_register_capabilities_route' );

/**
 * Register GET /headless/v1/capabilities.
 */
function hwk_register_capabilities_route(): void {
	register_rest_route(
		'headless/v1',
		'/capabilities',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'hwk_rest_capabilities',
			'permission_callback' => '__return_true',
		)
	);
}

/**
 * Public, non-sensitive description of the kit and its profile.
 *
 * @return WP_REST_Response
 */
function hwk_rest_capabilities(): WP_REST_Response {
	$profile = hwk_get_profile();
	$model   = hwk_content_model();

	$response = rest_ensure_response(
		array(
			'name'        => 'Headless WP Kit',
			'version'     => HWK_VERSION,
			'profile'     => array(
				'slug'        => isset( $profile['slug'] ) ? (string) $profile['slug'] : 'core',
				'name'        => isset( $profile['name'] ) ? (string) $profile['name'] : 'Core headless',
				'description' => isset( $profile['description'] ) ? (string) $profile['description'] : '',
			),
			'features'    => isset( $profile['features'] ) && is_array( $profile['features'] ) ? $profile['features'] : array(),
			'content'     => array(
				'author_post_type' => isset( $model['author_post_type'] ) ? $model['author_post_type'] : null,
				'taxonomies'       => isset( $model['taxonomies'] ) ? $model['taxonomies'] : array(),
				'meta'             => isset( $model['meta'] ) ? $model['meta'] : array(),
				'guide_format'     => isset( $model['guide_format'] ) ? $model['guide_format'] : null,
			),
			'frontend'    => array(
				'url' => hwk_frontend_url(),
			),
			'preview'     => array(
				'enabled' => hwk_feature_enabled( 'preview' ),
				'ttl'     => hwk_preview_ttl(),
			),
			'languages'   => hwk_languages(),
			'redirects'   => array(
				'enabled' => hwk_feature_enabled( 'redirects' ),
			),
			'audit'       => array(
				'enabled' => hwk_feature_enabled( 'audit' ),
			),
		)
	);

	$response->header( 'Cache-Control', 'no-store' );

	return $response;
}
