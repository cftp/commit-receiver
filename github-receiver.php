<?php 
/*
Plugin Name: Github Receiver
Plugin URI: https://github.com/cftp/github-receiver
Description: Provides an endpoint for the Github Post-Receive Webhook to ping, allowing WordPress to create a post for each Github commit.
Version: 1.0
Author: Code for the People
Author URI: http://www.codeforthepeople.com/ 
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

/**
 * Github Webhook Receiver
 *
 * Provides an endpoint for the Github Webhook to ping and create a post for each commit.
 *
 * @package Github-Commit-Receiver
 * @subpackage Main
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class CFTP_Github_Webhook_Receiver {
	
	/**
	 * A version for cache busting, DB updates, etc.
	 *
	 * @var string
	 **/
	public $version;
	
	/**
	 * An array of allowed remote IP addresses 
	 *
	 * @var array
	 **/
	public $allowed_remote_ips;

	/**
	 * Singleton stuff, purloined from Jetpack
	 * 
	 * @access @static
	 * 
	 * @return void
	 */
	static public function init() {
		static $instance = false;

		if ( ! $instance ) {
			load_plugin_textdomain( 'cftp_ghwr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			$instance = new CFTP_Github_Webhook_Receiver;
		}

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
			add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		}

		add_action( 'init',           array( $this, 'action_init' ) );
		add_action( 'parse_request',  array( $this, 'action_parse_request' ) );

		add_filter( 'query_vars',     array( $this, 'filter_query_vars' ) );

		$this->version = 1;
		$this->allowed_remote_ips = array( 
			'108.171.174.178', 
			'207.97.227.253', 
			'50.57.128.197', 
			'50.57.231.61,' 
		);
	}

	// HOOKS
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
		add_rewrite_rule( 'github-receiver/?$', 'index.php?cftp_github_webhook=1', 'top' );
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
	 * Adds the cftp_github_webhook query var to WordPress.
	 * 
	 * @filter query_vars
	 * 
	 * @param array $query_vars
	 * @return array The Query Vars
	 */
	function filter_query_vars( $query_vars ) {
		$query_vars[] = 'cftp_github_webhook';
		return $query_vars;
	}

	/**
	 * 
	 * @action parse_request 
	 * 
	 * @param object $wp WP object, passed by reference (so no need to return)
	 * @return void
	 **/
	public function action_parse_request( $wp ) {
		if ( ! isset( $wp->query_vars[ 'cftp_github_webhook' ] ) || ! $wp->query_vars[ 'cftp_github_webhook' ] )
			return;

		// Check remote IP address is whitelisted
		if ( ! in_array( $_SERVER[ 'REMOTE_ADDR' ], $this->allowed_remote_ips ) )
			return $this->terminate_failure( 'Unrecognised IP address' );

		// Process any ping we've just received
		// Check for Github X-Github-Event of "push"
		$http_headers = getallheaders();
		if ( isset( $http_headers[ 'X-Github-Event' ] ) && 'push' == $http_headers[ 'X-Github-Event' ] )
			return $this->process_push();

		// Either there is no X-Github-Event header, or we don't
		// yet deal with this event type.
		return $this->terminate_failure( 'Unrecognised event' );
	}
	
	// METHODS
	// =======
	
	/**
	 * Process a push type of webhook ping from Github.
	 * 
	 * @return void
	 */
	public function process_push() {
		// Check for payload in the POSTed data
		if ( ! isset( $_POST[ 'payload' ] ) || empty( $_POST[ 'payload' ] ) )
			return $this->terminate_failure( 'No payload data found' );

		// Process the commits
		error_log( "POSTed data: " . print_r( $_POST, true ) );
		$payload = json_decode( $_POST[ 'payload' ] );
		error_log( "Payload: " . print_r( $payload, true ) );
		foreach ( $payload->commits as & $commit_data )
			$this->process_commit_data( $commit_data );
		
		$this->terminate_ok();
	}
	
	/**
	 * Create the WP post object for a Github commit.
	 * 
	 * @param object $commit_data The Github commit data
	 * @return void
	 */
	public function process_commit_data( $commit_data ) {
		var_dump( $commit_data );

		// Abandon merges, i.e. anything with a message starting with "Merge"
		if ( 'Merge' == substr( $commit_data->message, 0, 5 ) )
			return;

		// Get the date in UTC/GMT
		// e.g. '2013-01-14T04:27:53-08:00'
		$datetime_gmt = DateTime::createFromFormat( DATE_ATOM, $commit_data->timestamp ); 
		$UTC = new DateTimeZone( 'UTC' );
		$datetime_gmt->setTimezone( $UTC );

		// Devise a title
		$lines = explode( "\n", $commit_data->message );
		$post_title = strip_tags( $lines[ 0 ] );
		
		// Create the post
		$post_data = array(
			'post_title' => $post_title, 
			'post_date_gmt' => $datetime_gmt->format( 'Y-m-d H:i:s' ),
			'post_content' => wp_kses( $commit_data->message, $GLOBALS[ 'allowedposttags' ] ),
			'post_status' => 'publish',
		);
		
		$post_id = wp_insert_post( $post_data );
		
		var_dump( $post_data );
		// Save the Github URL
		add_post_meta( $post_id, '_github_commit_url', $commit_data->url );
		// Save the portion of the payload remating to this commit commit portion of the payload
		add_post_meta( $post_id, '_github_commit_data', $commit_data );
	}
	
	/**
	 * Return HTTP Status 400 and a text message, then exit.
	 * 
	 * @return void
	 */
	public function terminate_failure( $msg = "Bad Request" ) {
		error_log( "Github webhook failed, Message: $msg" );
		status_header( 400 );
		echo strip_tags( $msg );
		exit;
	}
	
	/**
	 * Return HTTP Status 200 and "OK", then exit.
	 * 
	 * @return void
	 */
	public function terminate_ok() {
		error_log( "Github webhook succeeded" );
		status_header( 200 );
		echo "OK";
		exit;
	}
	
	/**
	 * Checks the DB structure is up to date, rewrite rules, 
	 * theme image size options are set, etc.
	 *
	 * @return void
	 **/
	public function maybe_update() {
		global $wpdb;
		$option_name = 'cftp_ghwr_version';
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
				error_log( "CFTP GHCR: Existing update routine has been running for less than 30 minutes" );
				return;
			}
			error_log( "CFTP GHCR: Update routine is running, but older than 30 minutes; going ahead regardless" );
		} else {
			add_option( "{$option_name}_running", time(), null, 'no' );
		}

		// Flush the rewrite rules
		if ( $version < 1 ) {
			flush_rewrite_rules();
			error_log( "CFTP GHCR: Flushed the rewrite rules" );
		}

		// N.B. Remember to increment $this->version in self::__construct above when you add a new IF

		delete_option( "{$option_name}_running", true, null, 'no' );
		update_option( $option_name, $this->version );
		error_log( "CFTP GHCR: Done upgrade, now at version " . $this->version );
	}
}

// Initiate the singleton
CFTP_Github_Webhook_Receiver::init();
