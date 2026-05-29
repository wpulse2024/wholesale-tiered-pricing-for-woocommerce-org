<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.arrayFound, Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound
class Arr {

	/**
	 * Get an item from an array using "dot" notation.
	 */
	public static function get( array $array, string $key, $default = null ) {
		if ( $key === null ) {
			return $array;
		}

		foreach ( explode( '.', $key ) as $segment ) {
			if ( is_array( $array ) && array_key_exists( $segment, $array ) ) {
				$array = $array[ $segment ];
			} else {
				return $default;
			}
		}

		return $array;
	}

	/**
	 * Set an array item to a given value using "dot" notation.
	 */
	public static function set( array &$array, string $key, $value ): void {
		if ( $key === null ) {
			$array = $value;
			return;
		}

		$keys = explode( '.', $key );
		while ( count( $keys ) > 1 ) {
			$segment = array_shift( $keys );

			if ( ! isset( $array[ $segment ] ) || ! is_array( $array[ $segment ] ) ) {
				$array[ $segment ] = array();
			}

			$array = &$array[ $segment ];
		}

		$array[ array_shift( $keys ) ] = $value;
	}

	/**
	 * Check if an item exists in an array using "dot" notation.
	 */
	public static function has( array $array, string $key ): bool {
		if ( $key === null || $key === '' ) {
			return false;
		}

		foreach ( explode( '.', $key ) as $segment ) {
			if ( ! is_array( $array ) || ! array_key_exists( $segment, $array ) ) {
				return false;
			}

			$array = $array[ $segment ];
		}

		return true;
	}

	/**
	 * Remove an array item using "dot" notation.
	 */
	public static function forget( array &$array, string $key ): void {
		$keys = explode( '.', $key );
		while ( count( $keys ) > 1 ) {
			$segment = array_shift( $keys );

			if ( ! isset( $array[ $segment ] ) || ! is_array( $array[ $segment ] ) ) {
				return;
			}

			$array = &$array[ $segment ];
		}

		unset( $array[ array_shift( $keys ) ] );
	}

	/**
	 * Get only the specified keys from the array.
	 */
	public static function only( array $array, array $keys ): array {
		return array_intersect_key( $array, array_flip( $keys ) );
	}

	/**
	 * Get all items except for specified keys.
	 */
	public static function except( array $array, array $keys ): array {
		return array_diff_key( $array, array_flip( $keys ) );
	}

	/**
	 * Pluck a list of values from an array.
	 */
	public static function pluck( array $array, string $key ): array {
		$results = array();

		foreach ( $array as $item ) {
			$value = static::get( (array) $item, $key );
			if ( $value !== null ) {
				$results[] = $value;
			}
		}

		return $results;
	}

	/**
	 * Flatten a multi-dimensional array into a single level.
	 */
	public static function flatten( array $array ): array {
		$result = array();

		array_walk_recursive(
			$array,
			function ( $a ) use ( &$result ) {
				$result[] = $a;
			}
		);

		return $result;
	}

	/**
	 * Filter array by a truth-test callback.
	 */
	public static function where( array $array, callable $callback ): array {
		return array_filter( $array, $callback, ARRAY_FILTER_USE_BOTH );
	}

	/**
	 * Get the first length elements of an array.
	 */
}
