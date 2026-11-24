<?php
/**
 * Application passwords behind PHP-FPM.
 *
 * Some hosting panels pass the Authorization header as HTTP_AUTHORIZATION but
 * do not populate PHP_AUTH_USER / PHP_AUTH_PW. WordPress only reads the latter
 * when validating application passwords. This bridge translates the header the
 * server already accepted; it does not add permissions.
 *
 * @package Headless_WP_Kit
 */

defined( 'ABSPATH' ) || exit;

$hwk_authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

if (
	( empty( $_SERVER['PHP_AUTH_USER'] ) || empty( $_SERVER['PHP_AUTH_PW'] ) )
	&& is_string( $hwk_authorization )
	&& 0 === stripos( ltrim( $hwk_authorization ), 'basic ' )
) {
	$hwk_decoded = base64_decode( substr( ltrim( $hwk_authorization ), 6 ), true );

	if ( is_string( $hwk_decoded ) && str_contains( $hwk_decoded, ':' ) ) {
		list( $hwk_user, $hwk_password ) = explode( ':', $hwk_decoded, 2 );

		if ( '' !== $hwk_user && '' !== $hwk_password ) {
			$_SERVER['PHP_AUTH_USER'] = $hwk_user;
			$_SERVER['PHP_AUTH_PW']   = $hwk_password;
		}
	}
}

unset( $hwk_authorization, $hwk_decoded, $hwk_user, $hwk_password );
