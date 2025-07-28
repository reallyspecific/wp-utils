<?php

namespace ReallySpecific\Utils\DB;

function table_results_to_assoc( $results, $key_column, $value_column, $array_values = false ) {

	$array = [];
	foreach( $results as $row ) {
		$value = null;
		$key = null;
		if ( is_object( $row ) ) {
			$value = $row->$value_column;
			$key = $row->$key_column;
		} elseif ( is_array( $row ) ) {
			$value = $row[$value_column];
			$key = $row[$key_column];
		}
		if ( is_null( $value ) || is_null( $key ) ) {
			continue;
		}

		if ( $array_values ) {
			if ( ! isset( $array[ $key ] ) ) {
				$array[ $key ] = [];
			}
			$array[ $key ][] = $value;
		} else {
			$array[ $key ] = $value;
		}

	}

	return $array;

}