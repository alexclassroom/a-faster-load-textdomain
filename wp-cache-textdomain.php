<?php
/**
 * Plugin Name: A faster load_textdomain
 * Version: 1.0.0
 * Description: Cache the .mo file in a transient
 * Author: Per Soderlind
 * Author URI: https://soderlind.no
 * Plugin URI: https://github.com/soderlind/wp-cache-textdomain
 * License: GPLv2 or later
 * Credit: nicofuma , I just created the plugin based on his patch (https://core.trac.wordpress.org/ticket/32052)
 * Save the plugin in mu-plugins. You don't have to, but you should add an an object cache.
 *
 * @package wp-cache-textdomain
 */

/**
 * Load the .mo file from the transient cache.
 *
 * @param bool|null   $loaded The result of loading a .mo file. Default null.
 * @param string      $domain Text domain. Unique identifier for retrieving translated strings.
 * @param string      $mofile Path to the MO file.
 * @param string|null $locale Locale.
 *
 * @return bool True when textdomain is successfully loaded, false otherwise.
 */
function a_faster_load_textdomain( $loaded, $domain, $mofile, $locale = null ) {
	global $l10n;

	// Check if the .mo file is readable.
	if ( ! is_readable( $mofile ) ) {
		return false;
	}

	// Check if the data for the file is already in the transient cache.
	$data  = get_transient( md5( $mofile ) );
	$mtime = filemtime( $mofile );

	// If the data is not in the cache or if the file has been modified since the data was cached,
	// import the .mo file and store the data in the cache.
	$mo = new MO();
	if ( ! $data || ! isset( $data['mtime'] ) || $mtime > $data['mtime'] ) {
		if ( ! $mo->import_from_file( $mofile ) ) {
			return false;
		}
		$data = [
			'mtime'   => $mtime,
			'entries' => $mo->entries,
			'headers' => $mo->headers,
		];
		set_transient( md5( $mofile ), $data );
	} else {
		// If the data is already in the cache and the file has not been modified, retrieve the data from the cache.
		$mo->entries = $data['entries'];
		$mo->headers = $data['headers'];
	}

	// If the text domain already exists in the $l10n global variable, merge the new MO object with the existing object.
	if ( isset( $l10n[ $domain ] ) ) {
		$mo->merge_with( $l10n[ $domain ] );
	}

	// Set the $l10n global variable to the new MO object and return true.
	$l10n[ $domain ] = &$mo; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

	return true;
}
add_filter( 'pre_load_textdomain', 'a_faster_load_textdomain', 1, 4 );
