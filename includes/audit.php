<?php
/**
 * Minimal audit log.
 *
 * @package Headless_WP_Kit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Create or update the audit table.
 */
function hwk_install_audit_table(): void {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table   = $wpdb->prefix . 'hwk_audit';
	$charset = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		created_at datetime NOT NULL,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		action varchar(100) NOT NULL,
		object_type varchar(50) NOT NULL DEFAULT '',
		object_id bigint(20) unsigned NOT NULL DEFAULT 0,
		detail longtext NULL,
		PRIMARY KEY  (id),
		KEY created_at (created_at),
		KEY action (action)
	) {$charset};";

	dbDelta( $sql );
}

/**
 * Write an audit row when the feature is enabled.
 *
 * @param string               $action      Action name.
 * @param string               $object_type Object type.
 * @param int                  $object_id   Object id.
 * @param array<string, mixed> $detail      Extra data.
 */
function hwk_audit_log( string $action, string $object_type = '', int $object_id = 0, array $detail = array() ): void {
	if ( ! hwk_feature_enabled( 'audit' ) ) {
		return;
	}

	global $wpdb;

	$table = $wpdb->prefix . 'hwk_audit';

	$wpdb->insert(
		$table,
		array(
			'created_at'  => current_time( 'mysql', true ),
			'user_id'     => get_current_user_id(),
			'action'      => substr( $action, 0, 100 ),
			'object_type' => substr( $object_type, 0, 50 ),
			'object_id'   => max( 0, $object_id ),
			'detail'      => wp_json_encode( $detail ),
		),
		array( '%s', '%d', '%s', '%s', '%d', '%s' )
	);
}
