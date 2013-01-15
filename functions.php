<?php 

/**
 * Aggie, the aggregation theme
 *
 * @package Aggie
 * @subpackage Main
 */

/*  Copyright 2013 Code for the People Ltd

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class CFTP_Aggie {
	
	/**
	 * A version for cache busting, DB updates, etc.
	 *
	 * @var string
	 **/
	public $version;

	/**
	 * Singleton stuff, purloined from Jetpack
	 * 
	 * @access @static
	 * 
	 * @return void
	 */
	static public function init() {
		static $instance = false;

		if ( ! $instance )
			$instance = new CFTP_Aggie;

		return $instance;

	}
	
	/**
	 * Let's go!
	 *
	 * @access public
	 * 
	 * @return void
	 **/
	public function __construct() {

		if ( is_admin() ) {
			add_action( 'admin_init',    array( $this, 'action_admin_init' ) );
		}

		add_action( 'init',					array( $this, 'action_init' ) );
		add_action( 'parse_request',		array( $this, 'action_parse_request' ) );

		add_filter( 'the_permalink_rss',	array( $this, 'filter_the_permalink_rss' ) );
		add_filter( 'query_vars',			array( $this, 'filter_query_vars' ) );

		$this->version = 1;
	}

	// HOOKS0
	// =====
	
	/**
	 * Sets up the API endpoint which Github will ping with their
	 * Post Receive Service Hook.
	 * 
	 * @action init
	 * 
	 * @return void
	 */
	public function action_init() {
		add_rewrite_rule( 'gh/([0-9]+)/?$', 'index.php?cftp_github_jump=1&p=$matches[1]', 'top' );
	}
	
	/**
	 * Sets up the API endpoint which Github will ping with their
	 * Post Receive Service Hook.
	 * 
	 * @action init
	 * 
	 * @return void
	 */
	public function action_admin_init() {
		$this->maybe_update();
	}

	/**
	 * Adds the cftp_github_jump query var to WordPress.
	 * 
	 * @filter query_vars
	 * 
	 * @param array $query_vars
	 * @return array The Query Vars
	 */
	function filter_query_vars( $query_vars ) {
		$query_vars[] = 'cftp_github_jump';
		return $query_vars;
	}

	/**
	 * Detect and process any Github redirects before WP_Query gets involved.
	 * 
	 * @action parse_request 
	 * 
	 * @param object $wp WP object, passed by reference (so no need to return)
	 * @return void
	 **/
	public function action_parse_request( $wp ) {
		if ( ! isset( $wp->query_vars[ 'cftp_github_jump' ] ) || ! $wp->query_vars[ 'cftp_github_jump' ] )
			return;

		if ( ! $post_id = isset( $wp->query_vars[ 'p' ] ) ? absint( $wp->query_vars[ 'p' ] ) : false )
			return;

		if ( ! $github_url = esc_url( get_post_meta( $post_id, '_github_commit_url', true ) ) )
			return;

		wp_redirect( $github_url );
		exit;
	}

	/**
	 * Use a short hyperlink style.
	 * 
	 * @param type $permalink
	 * @return string A shorter permalink
	 */
	function filter_the_permalink_rss( $permalink ) {
		if ( is_main_query() && 'post' == get_post_field( 'post_type', get_the_ID() ) )
			$permalink = esc_url( sprintf( '%s/gh/%d/', home_url(), get_the_ID() ) );
		return $permalink;
	}
	
	// METHODS
	// =======
	
	/**
	 * Checks the DB structure is up to date, rewrite rules, 
	 * theme image size options are set, etc.
	 *
	 * @return void
	 **/
	public function maybe_update() {
		global $wpdb;
		$option_name = 'cftp_aggie_version';
		$version = absint( get_option( $option_name, 0 ) );
		
		// Debugging and dev:
		// delete_option( "{$option_name}_running", true, null, 'no' );

		if ( $version == $this->version )
			return;

		// Institute a lock, for long running operations
		if ( $start_time = get_option( "{$option_name}_running", false ) ) {
			$time_diff = time() - $start_time;
			// Check the lock is less than 30 mins old, and if it is, bail
			if ( $time_diff < ( 60 * 30 ) ) {
				error_log( "CFTP Aggie: Existing update routine has been running for less than 30 minutes" );
				return;
			}
			error_log( "CFTP Aggie: Update routine is running, but older than 30 minutes; going ahead regardless" );
		} else {
			add_option( "{$option_name}_running", time(), null, 'no' );
		}

		// Flush the rewrite rules
		if ( $version < 1 ) {
			flush_rewrite_rules();
			error_log( "CFTP Aggie: Flushed the rewrite rules" );
		}

		// N.B. Remember to increment $this->version in self::__construct above when you add a new IF

		delete_option( "{$option_name}_running", true, null, 'no' );
		update_option( $option_name, $this->version );
		error_log( "CFTP Aggie: Done upgrade, now at version " . $this->version );
	}
}

// Initiate the singleton
CFTP_Aggie::init();
