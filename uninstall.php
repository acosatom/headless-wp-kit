<?php
/**
 * Uninstall: remove plugin settings, manifests and audit table.
 *
 * Content, posts and terms are not deleted.
 *
 * @package Headless_WP_Kit
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'hwk_settings' );
delete_option( 'hwk_redirects' );
delete_option( 'hwk_retired' );

global $wpdb;

$table = $wpdb->prefix . 'hwk_audit';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
