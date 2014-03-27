<?php
/*
Plugin Name: Taxonomy Metadata
Description: Infrastructure plugin which implements metadata functionality for taxonomy terms, including for tags and categories.
Version: 0.3
Author: mitcho (Michael Yoshitaka Erlewine), sirzooro
Author URI: http://mitcho.com/
*/

class Taxonomy_Metadata {
	function __construct() {
		add_action( 'init', array( $this, 'wpdbfix' ) );
		add_action( 'switch_blog', array( $this, 'wpdbfix' ) );
		add_action( 'wpmu_new_blog', array( $this, 'new_blog' ), 10, 6 );
		add_action( 'delete_term', array( $this, 'remove_meta_after_deletion' ), 10, 4 );
	}

	/*
	 * Quick touchup to wpdb
	 */
	function wpdbfix() {
		global $wpdb;
		$wpdb->taxonomymeta = "{$wpdb->prefix}taxonomymeta";
	}

	/*
	 * TABLE MANAGEMENT
	 */

	function activate( $network_wide = false ) {
		global $wpdb;

		// if activated on a particular blog, just set it up there.
		if ( !$network_wide ) {
			$this->setup_blog();
			return;
		}

		$blogs = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}'" );
		foreach ( $blogs as $blog_id ) {
			$this->setup_blog( $blog_id );
		}
		// I feel dirty... this line smells like perl.
		do {} while ( restore_current_blog() );
	}

	function setup_blog( $id = false ) {
		global $wpdb;

		if ( $id !== false )
			switch_to_blog( $id );

		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";

		$tables = $wpdb->get_results( "show tables like '{$wpdb->prefix}taxonomymeta'" );
		if ( !count( $tables ) )
			$wpdb->query( "CREATE TABLE {$wpdb->prefix}taxonomymeta (
				meta_id bigint(20) unsigned NOT NULL auto_increment,
				taxonomy_id bigint(20) unsigned NOT NULL default '0',
				meta_key varchar(255) default NULL,
				meta_value longtext,
				PRIMARY KEY	(meta_id),
				KEY taxonomy_id (taxonomy_id),
				KEY meta_key (meta_key)
			) $charset_collate;" );
	}

	function new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
		if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) )
			$this->setup_blog( $blog_id );
	}

	function remove_meta_after_deletion( $term, $tt_id, $taxonomy, $deleted_term ) {
		$keys = get_term_custom_keys( $term );
		foreach ( $keys as $key ) {
			delete_term_meta( $term, $key );
		}
	}
}
$taxonomy_metadata = new Taxonomy_Metadata;
register_activation_hook( __FILE__, array( $taxonomy_metadata, 'activate' ) );

// THE REST OF THIS CODE IS FROM http://core.trac.wordpress.org/ticket/10142
// BY sirzooro

//
// Taxonomy meta functions
//

/**
 * Add meta data field to a term.
 *
 * @param int     $term_id Post ID.
 * @param string  $key     Metadata name.
 * @param mixed   $value   Metadata value.
 * @param bool    $unique  Optional, default is false. Whether the same key should not be added.
 * @return bool False for failure. True for success.
 */
function add_term_meta( $term_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'taxonomy', $term_id, $meta_key, $meta_value, $unique );
}

/**
 * Remove metadata matching criteria from a term.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @param int     $term_id    term ID
 * @param string  $meta_key   Metadata name.
 * @param mixed   $meta_value Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function delete_term_meta( $term_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'taxonomy', $term_id, $meta_key, $meta_value );
}

/**
 * Retrieve term meta field for a term.
 *
 * @param int     $term_id Term ID.
 * @param string  $key     The meta key to retrieve.
 * @param bool    $single  Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
function get_term_meta( $term_id, $key = '', $single = false ) {
	return get_metadata( 'taxonomy', $term_id, $key, $single );
}

/**
 * Update term meta field based on term ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and term ID.
 *
 * If the meta field for the term does not exist, it will be added.
 *
 * @param int     $term_id    Term ID.
 * @param string  $key        Metadata key.
 * @param mixed   $value      Metadata value.
 * @param mixed   $prev_value Optional. Previous value to check before removing.
 * @return bool False on failure, true if success.
 */
function update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'taxonomy', $term_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Retrieve term meta fields, based on term ID.
 *
 * The term meta fields are retrieved from the cache where possible,
 * so the function is optimized to be called more than once.
 *
 * @param int     $term_id term ID.
 * @return array
 */
function get_term_custom( $term_id = 0 ) {
	$term_id = absint( $term_id );
	if ( ! $term_id )
		$term_id = get_the_ID();

	return get_term_meta( $term_id );
}

/**
 * Retrieve meta field names for a term.
 *
 * If there are no meta fields, then nothing (null) will be returned.
 *
 * @param int     $term_id term ID
 * @return array|null Either array of the keys, or null if keys could not be retrieved.
 */
function get_term_custom_keys( $term_id = 0 ) {
	$custom = get_term_custom( $term_id );

	if ( !is_array( $custom ) )
		return;

	if ( $keys = array_keys( $custom ) )
		return $keys;
}

/**
 * Retrieve values for a custom term field.
 *
 * The parameters must not be considered optional. All of the term meta fields
 * will be retrieved and only the meta field key values returned.
 *
 * @param string  $key     Meta field key.
 * @param int     $term_id term ID
 * @return array Meta field values.
 */
function get_term_custom_values( $key = '', $term_id = 0 ) {
	if ( !$key )
		return null;

	$custom = get_term_custom( $term_id );

	return isset( $custom[$key] ) ? $custom[$key] : null;
}
