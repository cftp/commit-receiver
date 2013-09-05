<?php 
/*
Plugin Name: Github Receiver
Plugin URI: https://github.com/cftp/github-receiver
Description: Provides an endpoint for the Github Post-Receive Webhook to ping, allowing WordPress to create a post for each Github commit.
Version: 1.2
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

if ( defined( 'WP_CLI' ) && WP_CLI && is_readable( $wp_cli = dirname( __FILE__ ) . '/class-wp-cli.php' ) )
	require_once $wp_cli;

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
		// $this->allowed_remote_ips = array( 
		// 	'108.171.174.178', 
		// 	'207.97.227.253', 
		// 	'50.57.128.197', 
		// 	'50.57.231.61,' 
		// );
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
		add_rewrite_rule( 'commit-receiver/?$', 'index.php?cftp_commit_webhook=1', 'top' );
		add_rewrite_rule( 'github-receiver/?$', 'index.php?cftp_commit_webhook=1', 'top' );
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
	 * Adds the cftp_commit_webhook query var to WordPress.
	 * 
	 * @filter query_vars
	 * 
	 * @param array $query_vars
	 * @return array The Query Vars
	 */
	function filter_query_vars( $query_vars ) {
		$query_vars[] = 'cftp_commit_webhook';
		return $query_vars;
	}

	/**
	 * Detect and process any webhook pings before WP_Query 
	 * gets involved.
	 * 
	 * @action parse_request 
	 * 
	 * @param object $wp WP object, passed by reference (so no need to return)
	 * @return void
	 **/
	public function action_parse_request( $wp ) {
		if ( ! isset( $wp->query_vars[ 'cftp_commit_webhook' ] ) || ! $wp->query_vars[ 'cftp_commit_webhook' ] )
			return;

		// Check remote IP address is whitelisted
		// if ( ! in_array( $_SERVER[ 'REMOTE_ADDR' ], $this->allowed_remote_ips ) )
		// 	return $this->terminate_failure( 'Unrecognised IP address' );

		// Process any ping we've just received

		// Check for Github X-Github-Event of "push"
		if ( isset( $_SERVER[ 'HTTP_X_GITHUB_EVENT' ] ) && 'push' == $_SERVER[ 'HTTP_X_GITHUB_EVENT' ] )
			return $this->process_github_push();

		// Either there is no X-Github-Event header, or we don't
		// yet deal with this event type.
		return $this->terminate_failure( 'Unrecognised event: ' . $http_headers[ 'X-GitHub-Event' ] );
	}
	
	// METHODS
	// =======
	
	/**
	 * Process a push type of webhook ping from GitHub.
	 * 
	 * @return void
	 */
	public function process_github_push() {

//		// Test data from the master branch
//		 $_POST[ 'payload' ] = addslashes( '{ "after": "9b3c2d184f342c6192c55db94a243b9b0567cad6", "before": "9051e4c888b9ea89cd43d60ce1e1f75576e1c0de", "commits": [ { "added": [], "author": { "email": "simon@sweetinteraction.com", "name": "Simon Wheatley", "username": "simonwheatley" }, "committer": { "email": "simon@sweetinteraction.com", "name": "Simon Wheatley", "username": "simonwheatley" }, "distinct": true, "id": "4b3cc0bd8ebf860e44c5ed633c5f2e053f9e2220", "message": "Triggering a commit", "modified": [ "github-receiver.php" ], "removed": [], "timestamp": "2013-01-14T10:10:14-08:00", "url": "https://github.com/cftp/github-receiver/commit/4b3cc0bd8ebf860e44c5ed633c5f2e053f9e2220" }, { "added": [ "readme.txt" ], "author": { "email": "simon@sweetinteraction.com", "name": "Simon Wheatley", "username": "simonwheatley" }, "committer": { "email": "simon@sweetinteraction.com", "name": "Simon Wheatley", "username": "simonwheatley" }, "distinct": true, "id": "d8238360794a339ee061333107216060b3404359", "message": "Rename readme file.", "modified": [], "removed": [ "readme.md" ], "timestamp": "2013-01-14T10:11:01-08:00", "url": "https://github.com/cftp/github-receiver/commit/d8238360794a339ee061333107216060b3404359" }, { "added": [], "author": { "email": "simon@sweetinteraction.com", "name": "Simon Wheatley", "username": "simonwheatley" }, "committer": { "email": "simon@sweetinteraction.com", "name": "Simon Wheatley", "username": "simonwheatley" }, "distinct": true, "id": "9b3c2d184f342c6192c55db94a243b9b0567cad6", "message": "Try emailing the log", "modified": [ "github-receiver.php" ], "removed": [], "timestamp": "2013-01-14T10:16:37-08:00", "url": "https://github.com/cftp/github-receiver/commit/9b3c2d184f342c6192c55db94a243b9b0567cad6" } ], "compare": "https://github.com/cftp/github-receiver/compare/9051e4c888b9...9b3c2d184f34", "created": false, "deleted": false, "forced": false, "head_commit": { "added": [], "author": { "email": "simon@sweetinteraction.com", "name": "Simon Wheatley", "username": "simonwheatley" }, "committer": { "email": "simon@sweetinteraction.com", "name": "Simon Wheatley", "username": "simonwheatley" }, "distinct": true, "id": "9b3c2d184f342c6192c55db94a243b9b0567cad6", "message": "Try emailing the log", "modified": [ "github-receiver.php" ], "removed": [], "timestamp": "2013-01-14T10:16:37-08:00", "url": "https://github.com/cftp/github-receiver/commit/9b3c2d184f342c6192c55db94a243b9b0567cad6" }, "hook_callpath": "new", "pusher": { "name": "none" }, "ref": "refs/heads/master", "repository": { "created_at": "2013-01-14T09:13:17-08:00", "description": "A WordPress plugin that provides an endpoint for the Github Post-Receive Webhook to ping, allowing WordPress to create a post for each Github commit.", "fork": false, "forks": 0, "has_downloads": true, "has_issues": true, "has_wiki": true, "id": 7608771, "name": "github-receiver", "open_issues": 0, "organization": "cftp", "owner": { "email": null, "name": "cftp" }, "private": false, "pushed_at": "2013-01-14T10:16:43-08:00", "size": 112, "stargazers": 0, "url": "https://github.com/cftp/github-receiver", "watchers": 0 } }' );
//		 // Test data from another branch
//		 $_POST[ 'payload' ] = addslashes( '{ "after": "79fe50de97b6f62165f37fb7ed40dae1ea9f9b51", "before": "ee9a9e36b113e03ec29e5cf33897aea81bece2d1", "commits": [ { "added": [], "author": { "email": "simon@sweetinteraction.com", "name": "Simon Wheatley", "username": "simonwheatley" }, "committer": { "email": "simon@sweetinteraction.com", "name": "Simon Wheatley", "username": "simonwheatley" }, "distinct": true, "id": "79fe50de97b6f62165f37fb7ed40dae1ea9f9b51", "message": "Code comment changes to trigger a ping.", "modified": [ "functions.php", "style.css" ], "removed": [], "timestamp": "2013-01-15T02:09:01-08:00", "url": "https://github.com/cftp/github-receiver/commit/79fe50de97b6f62165f37fb7ed40dae1ea9f9b51" } ], "compare": "https://github.com/cftp/github-receiver/compare/ee9a9e36b113...79fe50de97b6", "created": false, "deleted": false, "forced": false, "head_commit": { "added": [], "author": { "email": "simon@sweetinteraction.com", "name": "Simon Wheatley", "username": "simonwheatley" }, "committer": { "email": "simon@sweetinteraction.com", "name": "Simon Wheatley", "username": "simonwheatley" }, "distinct": true, "id": "79fe50de97b6f62165f37fb7ed40dae1ea9f9b51", "message": "Code comment changes to trigger a ping.", "modified": [ "functions.php", "style.css" ], "removed": [], "timestamp": "2013-01-15T02:09:01-08:00", "url": "https://github.com/cftp/github-receiver/commit/79fe50de97b6f62165f37fb7ed40dae1ea9f9b51" }, "hook_callpath": "new", "pusher": { "email": "simon@sweetinteraction.com", "name": "simonwheatley" }, "ref": "refs/heads/aggie-theme", "repository": { "created_at": "2013-01-14T09:13:17-08:00", "description": "A WordPress plugin that provides an endpoint for the Github Post-Receive Webhook to ping, allowing WordPress to create a post for each Github commit.", "fork": false, "forks": 0, "has_downloads": true, "has_issues": true, "has_wiki": true, "id": 7608771, "name": "github-receiver", "open_issues": 1, "organization": "cftp", "owner": { "email": null, "name": "cftp" }, "private": false, "pushed_at": "2013-01-15T02:09:05-08:00", "size": 260, "stargazers": 0, "url": "https://github.com/cftp/github-receiver", "watchers": 0 } }' );

		// Check for payload in the POSTed data
		if ( ! isset( $_POST[ 'payload' ] ) || empty( $_POST[ 'payload' ] ) )
			return $this->terminate_failure( 'No payload data found' );
		
		// Process the commits now
		$payload = json_decode( stripslashes( $_POST[ 'payload' ] ) );

		// Work out the branch path
		$branch_path = str_replace( 'refs/heads', '', $payload->ref );
		if ( isset( $payload->commits ) && is_array( $payload->commits ) )
			foreach ( $payload->commits as & $commit_data )
				$this->process_commit_data( $commit_data, $payload->repository->name, $branch_path );
		
		$this->terminate_ok();
	}
	
	/**
	 * Create the WP post object for a GitHub commit.
	 * 
	 * @param object $commit_data The Github commit data
	 * @param string $repo_name The repo name
	 * @param string $branch_path The branch path
	 * @return void
	 */
	public function process_github_commit_data( $commit_data, $repo_name, $branch_path ) {

		// Abandon merges, i.e. anything with a message starting with "Merge"
		if ( 'Merge' == substr( $commit_data->message, 0, 5 ) )
			return;

		// N.B. Posts get inserted in the order they are received, i.e.
		// we don't set the post_date to the commit date as this proved
		// to cause issues with the RSS feed.
		
		// Devise a title
		$lines = explode( "\n", $commit_data->message );
		$post_title = "[{$repo_name}{$branch_path}] " . $commit_data->author->name . ' – ' . strip_tags( $lines[ 0 ] );
		
		// Create the post
		$post_data = array(
			'post_title' => $post_title, 
			'post_content' => wp_kses( $commit_data->message, $GLOBALS[ 'allowedposttags' ] ),
			'post_status' => 'publish',
		);
		
		$post_id = wp_insert_post( $post_data );
		
		// Save the Github URL
		add_post_meta( $post_id, '_github_commit_url', $commit_data->url );
		// Save the portion of the payload remating to this commit commit portion of the payload
		add_post_meta( $post_id, '_github_commit_data', $commit_data );
	}

		// Abandon merges, i.e. anything with a message starting with "Merge"
		if ( 'Merge' == substr( $commit_data->message, 0, 5 ) )
			return;

		// N.B. Posts get inserted in the order they are received, i.e.
		// we don't set the post_date to the commit date as this proved
		// to cause issues with the RSS feed.
		
		// Devise a title
		$lines = explode( "\n", $commit_data->message );
		$post_title = "[{$repo_name}{$branch_path}] " . $commit_data->author->name . ' – ' . strip_tags( $lines[ 0 ] );
		
		// Create the post
		$post_data = array(
			'post_title' => $post_title, 
			'post_content' => wp_kses( $commit_data->message, $GLOBALS[ 'allowedposttags' ] ),
			'post_status' => 'publish',
		);
		
		$post_id = wp_insert_post( $post_data );
		
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
