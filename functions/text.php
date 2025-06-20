<?php

namespace ReallySpecific\Utils\Text;

use Parsedown;

function array_to_attr_string( $attributes = [] ) {

	$attr_string = '';

	foreach ( $attributes as $key => $value ) {
		if ( is_null( $value ) ) {
			continue;
		}
		if ( $value === true ) {
			$value = 'true';
		}
		if ( $value === false ) {
			$value = 'false';
		}
		$attr_string .= ' ' . $key . '="' . esc_attr( $value ) . '"';
	}

	return trim( $attr_string );

}

function parsedown( string $text, string $field = '', string $context = '' ) {

	static $parsedown;
	if ( ! isset( $parsedown ) ) {
		$parsedown = new Parsedown();
		do_action_ref_array( 'rs_util_text_parsedown_instance', [ &$parsedown ] );
	}

	$text = $parsedown->text( $text );

	$text = apply_filters( 'rs_util_text_parsedown', $text, $field, $context, $parsedown );

	return $text;
}
