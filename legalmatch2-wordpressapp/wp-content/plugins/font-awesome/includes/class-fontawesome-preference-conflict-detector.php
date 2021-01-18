<?php
namespace FortAwesome;

/**
 * Class FontAwesome_Preference_Conflict_Detector
 */
class FontAwesome_Preference_Conflict_Detector {

	protected static function resolve_version( $configured_option, $current_preference, $latest_version ) {
		// If the version given as configured_option is 'latest', as it may be for
		// a kit, then we'll resolve that 'latest' into whatever actual version
		// is known as the latest right now.
		$resolved_version = ( 'latest' === $configured_option && is_string( $latest_version ) )
			? $latest_version
			: $configured_option;

		return self::version_satisfies( $resolved_version, $current_preference );
	}

	/**
	 * Because all of our options currently require strict equality, all or nothing, to be considered "satisfied" or
	 * non-conflicted, except for version:
	 * Use function_exists to see if something like resolve_$option exists, if not, assume equality.
	 * If it does, then delegate to it. We would need to delegate to it for the version option, because
	 * clients should be able to specify an expression to represent version compatibility.
	 *
	 * The $latest_version param will be passed as the third argument to any resolver
	 * functions, and should be set to the actual version number of the latest version known,
	 * like '5.12.0', not the word 'latest'. It is the value that can be used
	 * when checking whether a version preference is satisfied when 'latest' is the
	 * configured version in a kit.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * @internal
	 * @ignore
	 * @since 4.0.0
	 */
	public static function detect( $configured_options = array(), $client_preferences = array(), $latest_version = null ) {
		return array_reduce(
			array_keys( $configured_options ),
			function( $carry, $option ) use ( $configured_options, $client_preferences, $latest_version ) {
				$resolve_method_candidate = 'resolve_' . $option;
				if ( isset( $client_preferences[ $option ] ) ) {
					if ( method_exists( __CLASS__, $resolve_method_candidate ) ) {
						return call_user_func(
							array( __CLASS__, $resolve_method_candidate ),
							$configured_options[ $option ],
							$client_preferences[ $option ],
							$latest_version
						) ? $carry : array_merge( $carry, array( $option ) );
					} else {
						return $configured_options[ $option ] === $client_preferences[ $option ]
						? $carry
						: array_merge( $carry, array( $option ) );
					}
				} else {
					return $carry;
				}
			},
			array()
		);
	}

	/**
	 * Reports whether the given version satisfies the given constraints.
	 *
	 * Internal use only, not part of this plugin's public API.
	 *
	 * This is really just a generalized utility function, instead of incorporating a full-blown semver library.
	 *
	 * The constraints array should contain one element per constraint, where each individual constraint is itself
	 * an array of arguments that can be passed as the second and third arguments to the standard `version_compare`
	 * function.
	 *
	 * The constraints will be ANDed together.
	 *
	 * For example the following constraints...
	 *
	 * ```php
	 *   array(
	 *     [ '1.0.0', '>='],
	 *     [ '2.0.0', '<']
	 *   )
	 * ```
	 *
	 * ...mean: "assert that the given $version is greater than or equal 1.0.0 AND strictly less than 2.0.0"
	 *
	 * To express OR conditions, make multiple calls to this function and OR the results together in your own code.
	 *
	 * @link http://php.net/manual/en/function.version-compare.php
	 * @param string $version
	 * @param array $constraints
	 * @ignore
	 * @internal
	 * @throws ClientPreferencesSchemaException
	 * @return bool
	 */
	public static function version_satisfies( $version, $constraints ) {
		$valid_operators = array( '<', 'lt', '<=', 'le', '>', 'gt', '>=', 'ge', '==', '=', 'eq', '!=', '<>', 'ne' );

		if ( ! is_array( $constraints ) ) {
			throw new ClientPreferencesSchemaException();
		}
		$result_so_far = true;
		foreach ( $constraints as $constraint ) {
			if ( ! is_array( $constraint ) || 2 !== count( $constraint ) || false === array_search( $constraint[1], $valid_operators, true ) ) {
				throw new ClientPreferencesSchemaException();
			}
			if ( ! version_compare( $version, $constraint[0], $constraint[1] ) ) {
				$result_so_far = false;
				break;
			}
		}
		return $result_so_far;
	}
}



