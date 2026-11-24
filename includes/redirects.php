<?php
/**
 * Redirect and retired-path manifests.
 *
 * @package Headless_WP_Kit
 */

defined( 'ABSPATH' ) || exit;

if ( ! hwk_feature_enabled( 'redirects' ) ) {
	return;
}

add_action( 'rest_api_init', 'hwk_register_redirect_routes' );

/**
 * Register public manifests.
 */
function hwk_register_redirect_routes(): void {
	register_rest_route(
		'headless/v1',
		'/redirects',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => static function (): WP_REST_Response {
				$response = rest_ensure_response( hwk_redirects_list() );
				$response->header( 'Cache-Control', 'no-store' );
				return $response;
			},
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'headless/v1',
		'/retired',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => static function (): WP_REST_Response {
				$response = rest_ensure_response( hwk_retired_list() );
				$response->header( 'Cache-Control', 'no-store' );
				return $response;
			},
			'permission_callback' => '__return_true',
		)
	);
}
