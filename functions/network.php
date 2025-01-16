<?php

namespace ReallySpecific\WP_Util\Network;

use \WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sends a network request to Cloudflare to get the public facing IP of this server
 *
 * @param string $slug
 * @return WP_Post|null
 *
 * @throws \Exception
 */
function get_server_remote_ip() {
	$response = wp_remote_get( 'https://www.cloudflare.com/cdn-cgi/trace', [ 'sslverify' => false ] );
	if ( is_wp_error( $response ) ) {
		return false;
	}
	$body = wp_remote_retrieve_body( $response );
	if ( empty( $body ) ) {
		return false;
	}
	$parts = explode( "\n", $body );
	if ( empty( $parts ) ) {
		return false;
	}
	$parts = array_map( 'trim', $parts );
	foreach ( $parts as $part ) {
		if ( strpos( $part, 'ip=' ) === 0 ) {
			$address = substr( $part, 3 );
			return $address;
		}
	}

	return false;
}
