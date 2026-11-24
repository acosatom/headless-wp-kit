<?php
/**
 * CMS noindex and sitemap hardening.
 *
 * @package Headless_WP_Kit
 */

defined( 'ABSPATH' ) || exit;

if ( ! hwk_feature_enabled( 'cms_noindex' ) ) {
	return;
}

add_action( 'send_headers', 'hwk_send_noindex_header' );
add_filter( 'wp_robots', 'hwk_filter_robots' );
add_filter( 'robots_txt', 'hwk_filter_robots_txt', 20 );
add_filter( 'wp_sitemaps_enabled', '__return_false' );
add_filter( 'option_wpseo', 'hwk_disable_yoast_sitemap' );

/**
 * Send a noindex header from the CMS.
 */
function hwk_send_noindex_header(): void {
	if ( is_admin() ) {
		return;
	}

	header( 'X-Robots-Tag: noindex, nofollow', true );
}

/**
 * Add noindex/nofollow to the CMS document.
 *
 * @param array<string, bool> $robots Robots flags.
 * @return array<string, bool>
 */
function hwk_filter_robots( array $robots ): array {
	$robots['noindex']  = true;
	$robots['nofollow'] = true;

	return $robots;
}

/**
 * Disallow the whole CMS in robots.txt.
 */
function hwk_filter_robots_txt(): string {
	return "User-agent: *\nDisallow: /\n";
}

/**
 * Disable Yoast sitemaps if Yoast is active.
 *
 * @param mixed $option Yoast option.
 * @return mixed
 */
function hwk_disable_yoast_sitemap( $option ) {
	if ( is_array( $option ) ) {
		$option['enable_xml_sitemap'] = false;
	}

	return $option;
}
