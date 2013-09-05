<?php

/*  Copyright 2013 Code for the People Ltd
				_____________
			   /      ____   \
		 _____/       \   \   \
		/\    \        \___\   \
	   /  \    \                \
	  /   /    /          _______\
	 /   /    /          \       /
	/   /    /            \     /
	\   \    \ _____    ___\   /
	 \   \    /\    \  /       \
	  \   \  /  \____\/    _____\
	   \   \/        /    /    / \
		\           /____/    /___\
		 \                        /
		  \______________________/

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



class CFTP_CR_Command extends WP_CLI_Command {

	public function __construct() {

	}

	/**
	 * Post as though from GitLab
	 *
	 * @subcommand test-gitlab
	 */
	public function test_commit_gitlab( $args, $assoc_args ) {
		$post_args = array(
			'body' => '{"before":"6d0ed7cb7fd2721ecb2455da8151bf92df3b767e","after":"d3eb4a74bd94dc7737430868a5ea0d693b2a850a","ref":"refs/heads/master","user_id":8214,"user_name":"Keith Devon","repository":{"name":"Kew Responsive","url":"git@gitlab.com:simond/kew-responsive.git","description":null,"homepage":"https://gitlab.com/simond/kew-responsive"},"commits":[{"id":"36d0beb8b982dde337f03366a2319b74b1a34401","message":"Create \'part-social\' template part and ass to single attraction template\n\nNew settings page created for the social profiles too\n\nSigned-off-by: Keith Devon <keith@keithdevon.com>","timestamp":"2013-09-03T16:45:21+00:00","url":"https://gitlab.com/simond/kew-responsive/commit/36d0beb8b982dde337f03366a2319b74b1a34401","author":{"name":"Keith Devon","email":"keith@keithdevon.com"}},{"id":"c21a10a64f15dc0e96572edc170258be7e97cf69","message":"Add event calendar template\n\nSigned-off-by: Keith Devon <keith@keithdevon.com>","timestamp":"2013-09-03T16:45:45+00:00","url":"https://gitlab.com/simond/kew-responsive/commit/c21a10a64f15dc0e96572edc170258be7e97cf69","author":{"name":"Keith Devon","email":"keith@keithdevon.com"}},{"id":"d3eb4a74bd94dc7737430868a5ea0d693b2a850a","message":"Add single event template\n\nSigned-off-by: Keith Devon <keith@keithdevon.com>","timestamp":"2013-09-03T16:45:57+00:00","url":"https://gitlab.com/simond/kew-responsive/commit/d3eb4a74bd94dc7737430868a5ea0d693b2a850a","author":{"name":"Keith Devon","email":"keith@keithdevon.com"}}],"total_commits_count":3}',
		);
		$url = home_url( '/commit-receiver/', 'http' );
		$response = wp_remote_post( $url, $post_args );

		if ( is_wp_error( $response ) )
			return \WP_CLI::error( $response->get_error_message() );

		if ( 200 != wp_remote_retrieve_response_code( $response ) )
			return \WP_CLI::error( sprintf( 'The URL %s responded with %s (%s)', $url, wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_body( $response ) ) );

		\WP_CLI::success( "It's all worked" );		
	}

	/**
	 * Post as though from GitHub
	 *
	 * @subcommand test-github
	 */
	public function test_commit_github( $args, $assoc_args ) {
		$post_args = array(
			'body' => '{"before":"6d0ed7cb7fd2721ecb2455da8151bf92df3b767e","after":"d3eb4a74bd94dc7737430868a5ea0d693b2a850a","ref":"refs/heads/master","user_id":8214,"user_name":"Keith Devon","repository":{"name":"Kew Responsive","url":"git@gitlab.com:simond/kew-responsive.git","description":null,"homepage":"https://gitlab.com/simond/kew-responsive"},"commits":[{"id":"36d0beb8b982dde337f03366a2319b74b1a34401","message":"Create \'part-social\' template part and ass to single attraction template\n\nNew settings page created for the social profiles too\n\nSigned-off-by: Keith Devon <keith@keithdevon.com>","timestamp":"2013-09-03T16:45:21+00:00","url":"https://gitlab.com/simond/kew-responsive/commit/36d0beb8b982dde337f03366a2319b74b1a34401","author":{"name":"Keith Devon","email":"keith@keithdevon.com"}},{"id":"c21a10a64f15dc0e96572edc170258be7e97cf69","message":"Add event calendar template\n\nSigned-off-by: Keith Devon <keith@keithdevon.com>","timestamp":"2013-09-03T16:45:45+00:00","url":"https://gitlab.com/simond/kew-responsive/commit/c21a10a64f15dc0e96572edc170258be7e97cf69","author":{"name":"Keith Devon","email":"keith@keithdevon.com"}},{"id":"d3eb4a74bd94dc7737430868a5ea0d693b2a850a","message":"Add single event template\n\nSigned-off-by: Keith Devon <keith@keithdevon.com>","timestamp":"2013-09-03T16:45:57+00:00","url":"https://gitlab.com/simond/kew-responsive/commit/d3eb4a74bd94dc7737430868a5ea0d693b2a850a","author":{"name":"Keith Devon","email":"keith@keithdevon.com"}}],"total_commits_count":3}',
			'headers' => array(
				'X-GitHub-Event' => 'push',
			),
		);
		$url = home_url( '/commit-receiver/', 'http' );
		$response = wp_remote_post( $url, $post_args );

		if ( is_wp_error( $response ) )
			return \WP_CLI::error( $response->get_error_message() );

		if ( 200 != wp_remote_retrieve_response_code( $response ) )
			return \WP_CLI::error( sprintf( 'The URL %s responded with %s', $url, wp_remote_retrieve_response_code( $response ) ) );

		return \WP_CLI::error( 'This command is currently incorrect, do not rely on it' );
	}

}

WP_CLI::add_command( 'receiver', 'CFTP_CR_Command' );

