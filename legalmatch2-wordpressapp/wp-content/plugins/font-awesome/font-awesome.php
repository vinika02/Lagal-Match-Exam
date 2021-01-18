<?php
/**
 * Main loading logic.
 */
namespace FortAwesome;

use \Exception, \Error;

defined( 'WPINC' ) || die;

if ( ! defined( 'FONTAWESOME_PLUGIN_FILE' ) ) {
	/**
	 * Name of this plugin's entrypoint file.
	 *
	 * Relative to the WordPress plugins directory, as would
	 * be used for `$plugin` in the
	 * [`activate_{$plugin}`](https://developer.wordpress.org/reference/hooks/activate_plugin/) action hook.
	 *
	 * @since 4.0.0
	 */
	define( 'FONTAWESOME_PLUGIN_FILE', 'font-awesome/index.php' );
}

if ( ! defined( 'FONTAWESOME_MIN_PHP_VERSION' ) ) {
	/**
	 * Minimum PHP VERSION required
	 *
	 * @since 4.0.0
	 */
	define( 'FONTAWESOME_MIN_PHP_VERSION', '5.6' );
}

if ( ! class_exists( 'FortAwesome\FontAwesome_Loader' ) ) :
	/**
	 * Loader class, a Singleton. Coordinates potentially multiple installations of
	 * the Font Awesome plugin code, and ensures that the latest available semantic
	 * version is selected for execution at runtime. Exposes a few public API
	 * methods for initialization (activation), deactivation, and uninstallation
	 * of plugin code.
	 *
	 * Font Awesome plugin installations may be installed either directly
	 * as a plugin appearing in the plugins table, or as a composer dependency
	 * of any number of themes or other plugins.
	 *
	 * We only have in mind various installations of this code base,
	 * of course. Not other non-official Font Awesome plugins. We don't try
	 * to anticipate what _other_ potentially conflicting plugins might be installed.
	 *
	 * All Font Awesome plugin installations should attempt to load themselves via this loader,
	 * which will ensure that the code for plugin installation with the latest
	 * semantic version is what actually runs. It also ensures appropriate
	 * handling of initialization, deactivation and uninstallation, so that the
	 * actions of one plugin installation don't interfere with another's.
	 *
	 * Refer to `integrations/plugins/plugin-sigma`
	 * in this repo for an example of how to load the Font Awesome plugin code
	 * via this Loader when including it as a composer dependency.
	 *
	 * The client code should `require_once` the `index.php` found
	 * in the root of this code base:
	 *
	 * ```php
	 * require_once __DIR__ . '/vendor/fortawesome/wordpress-fontawesome/index.php';
	 * ```
	 *
	 * For example, suppose the following scenario: A later version of the plugin
	 * is installed from the WordPress plugins directory and appears in the
	 * plugins table. It is activated.
	 * Then suppose a page builder plugin is installed and activated. That page builder
	 * plugin includes this plugin code as a composer dependency. In that case,
	 * the plugin code with the later semantic version will be the one loaded
	 * and executed at runtime.
	 * The page builder plugin should work just as expected, even though it would
	 * be running against a newer version of the Font Awesome plugin code than it
	 * had shipped in its own vendor bundle.
	 *
	 * Now suppose that the site owner deactivates and deletes the plugin that
	 * appears in the plugins table, the one that had been installed from the
	 * WordPress plugins directory. Because this loader knows that the page builder
	 * plugin's installation is still present, that deactivation and uninstallation
	 * will not cause the Font Awesome plugin's options and transients to be removed from the
	 * database. And as soon as that plugin installation is removed,
	 * the Font Awesome plugin installation included in the page builder plugin's
	 * composer vendor bundle is automatically promoted and runs as expected with
	 * no change to the plugin's state in the database.
	 *
	 * This loader pattern follows that of [wponion](https://github.com/wponion/wponion/blob/master/wponion.php).
	 * Thanks to Varun Sridharan.
	 *
	 * @since 4.0.0
	 */
	final class FontAwesome_Loader {
		/**
		 * Stores Loader Instance.
		 *
		 * @ignore
		 * @internal
		 */
		private static $instance = null;

		/**
		 * Stores metadata about the various modules attempted to be
		 * loaded as the FontAwesome plugin.
		 *
		 * @ignore
		 * @internal
		 */
		private static $loaded = array();

		/**
		 * Stores data about each plugin installation that has
		 * invoked font_awesome_load().
		 *
		 * @ignore
		 * @internal
		 */
		private static $data = array();

		/**
		 * FontAwesome_Loader constructor.
		 *
		 * @ignore
		 * @internal
		 */
		private function __construct() {
			add_action( 'wp_loaded', array( &$this, 'run_plugin' ), -1 );
			add_action( 'activate_' . FONTAWESOME_PLUGIN_FILE, array( &$this, 'activate_plugin' ), -1 );
		}

		/**
		 * Choose the plugin installation with the latest semantic version to be
		 * the one that we'll load and use for other lifecycle operations like
		 * initialization, deactivation or uninstallation.
		 *
		 * @ignore
		 * @internal
		 * @throws Exception
		 */
		private function select_latest_version_plugin_installation() {
			if ( count( self::$loaded ) > 0 || count( self::$data ) === 0 ) {
				return;
			}

			usort(
				self::$data,
				function( $a, $b ) {
					if ( version_compare( $a['version'], $b['version'], '=' ) ) {
						return 0;
					} elseif ( version_compare( $a['version'], $b['version'], 'gt' ) ) {
						return -1;
					} else {
						return 1;
					}
				}
			);

			$selected_installation = self::$data[0];

			if ( empty( $selected_installation ) ) {
				throw new Exception(
					sprintf(
						esc_html__(
							'Unable To Load Font Awesome Plugin.',
							'font-awesome'
						)
					) .
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					' Data: ' . base64_encode( wp_json_encode( self::$data ) )
				);
			}

			if ( ! version_compare( PHP_VERSION, FONTAWESOME_MIN_PHP_VERSION, '>=' ) ) {
				throw(
					new Exception(
						sprintf(
							/* translators: 1: minimum required php version 2: current php version */
							esc_html__(
								'Font Awesome requires a PHP Version of at least %1$s. Your current version of PHP is %2$s.',
								'font-awesome'
							),
							FONTAWESOME_MIN_PHP_VERSION,
							PHP_VERSION
						)
					)
				);
			}

			self::$loaded = $selected_installation;
		}

		/**
		 * Runs the main plugin logic from the installation that has been selected.
		 *
		 * This is public because it's a callback, but should not be considered
		 * part of this plugin's API.
		 *
		 * For an example of how to use this loader when importing this plugin
		 * as a composer dependency, see `integrations/plugins/plugin-sigma.php`
		 * in this repo.
		 *
		 * @internal
		 * @ignore
		 */
		public function run_plugin() {
			try {
				$this->select_latest_version_plugin_installation();
				require self::$loaded['path'] . 'font-awesome-init.php';
			} catch ( Exception $e ) {
				add_action(
					'admin_notices',
					function() use ( $e ) {
						self::emit_admin_error_output( $e );
					}
				);
			} catch ( Error $e ) {
				add_action(
					'admin_notices',
					function() use ( $e ) {
						self::emit_admin_error_output( $e );
					}
				);
			}
		}

		/**
		 * Returns the path to the plugin installation that is actively loaded.
		 *
		 * @since 4.0.0
		 */
		public function loaded_path() {
			return self::$loaded['path'];
		}

		/**
		 * Loads the activation hook for the plugin installation that has been
		 * selected for loading.
		 *
		 * This is public because it's a callback, but should not be considered
		 * part of this plugin's API.
		 *
		 * @internal
		 * @ignore
		 */
		public function activate_plugin() {
			$activation_failed_message = __( 'Font Awesome could not be activated.', 'font-awesome' );

			try {
				$this->select_latest_version_plugin_installation();
				require_once self::$loaded['path'] . 'includes/class-fontawesome-activator.php';
				FontAwesome_Activator::activate();
			} catch ( Exception $e ) {
				self::emit_admin_error_output( $e, $activation_failed_message );
				exit;
			} catch ( Error $e ) {
				self::emit_admin_error_output( $e, $activation_failed_message );
				exit;
			}
		}

		/**
		 * Internal use only, not part of this plugin's public API.
		 *
		 * @internal
		 * @ignore
		 */
		public static function emit_admin_error_output( $e, $context_message = '' ) {
			if ( is_admin() && current_user_can( 'manage_options' ) ) {
				echo '<div class="error">';
				echo '<p>' . esc_html__( 'The Font Awesome plugin caught a fatal error', 'font-awesome' );
				if ( is_string( $context_message ) && '' !== $context_message ) {
					echo ': ' . esc_html( $context_message );
				} else {
					echo '.';
				}
				echo '</p><p>';

				if ( ! is_a( $e, 'Exception' ) && ! is_a( $e, 'Error' ) ) {
					esc_html_e( 'No error message available.', 'font-awesome' );
				} else {
					self::emit_error_output_to_console( $e );

					if ( boolval( $e->getMessage() ) ) {
						echo esc_html( $e->getMessage() );
					}
				}

				echo '</p></div>';
			}
		}

		/**
		 * Internal use only.
		 *
		 * @ignore
		 * @internal
		 * @param Error|Exception
		 */
		public static function emit_error_output_to_console( $e ) {
			global $wp_version;

			if ( ! is_a( $e, 'Exception' ) && ! is_a( $e, 'Error' ) ) {
				return;
			}

			$wp_error = null;

			if ( method_exists( $e, 'get_wp_error' ) ) {
				$wp_error = $e->get_wp_error();
			}

			$additional_diagnostics  = '';
			$additional_diagnostics .= 'php version: ' . phpversion() . "\n";
			$additional_diagnostics .= "WordPress version: $wp_version\n";
			$additional_diagnostics .= 'multisite: ' . ( is_multisite() ? 'true' : 'false' ) . "\n";
			$additional_diagnostics .= 'is_network_admin: ' . ( is_network_admin() ? 'true' : 'false' ) . "\n";

			echo '<script>';
			echo "console.group('" . esc_html__( 'Font Awesome Plugin Error Details', 'font-awesome' ) . "');";
			echo "console.info('message: " . esc_html( self::escape_error_output( $e->getMessage() ) ) . "');";
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "console.info('stack trace:\\n" . self::escape_error_output( $e->getTraceAsString() ) . "');";

			if ( $wp_error ) {
				$codes = $wp_error->get_error_codes();
				foreach ( $codes as $code ) {
					echo "console.group('WP_Error');";
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo "console.info('code: $code');";

					$messages = $wp_error->get_error_messages( $code );

					foreach ( $messages as $message ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo "console.info('message: " . self::escape_error_output( $message ) . "');";
					}

					$data = $wp_error->get_error_data( $code );

					if ( $data ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r, WordPress.Security.EscapeOutput.OutputNotEscaped
						echo "console.info('data:\\n" . self::escape_error_output( print_r( $data, true ) ) . "');";
					}

					echo 'console.groupEnd();';
				}
			}

			if ( strlen( $additional_diagnostics ) > 0 ) {
				echo "console.info('" . esc_html( self::escape_error_output( $additional_diagnostics ) ) . "');";
			}

			echo 'console.groupEnd();';
			echo '</script>';
		}

		/**
		 * Internal use only, not part of this plugin's public API.
		 *
		 * @internal
		 * @ignore
		 * @return escaped string, or '' if the argument is not a string
		 */
		public static function escape_error_output( $content ) {
			if ( ! is_string( $content ) ) {
				return '';
			}

			$result = preg_replace( '/\'/', "\\'", $content );
			$result = preg_replace( '/\"/', '\\"', $result );
			$result = preg_replace( '/\n/', '\\n', $result );

			return $result;
		}

		/**
		 * Initializes the Font Awesome plugin's options.
		 *
		 * If multiple installations of the plugin are installed, such as by
		 * composer dependencies in multiple plugins, this will ensure that the
		 * plugin is not re-initialized.
		 *
		 * If the plugin's options are empty, this will initialize with defaults.
		 * Otherwise, it will leave them alone.
		 *
		 * Any theme or plugin that uses this plugin as a composer dependency
		 * should call this method from its own activation hook. For example:
		 *
		 * ```php
		 * register_activation_hook(
		 *     __FILE__,
		 *     'FortAwesome\FontAwesome_Loader::initialize'
		 * );
		 * ```
		 *
		 * @since 4.0.0
		 */
		public static function initialize() {
			$initialization_failed_msg = __( 'A theme or plugin tried to initialize Font Awesome, but failed.', 'font-awesome' );

			try {
				self::instance()->select_latest_version_plugin_installation();
				require_once self::$loaded['path'] . 'includes/class-fontawesome-activator.php';
				FontAwesome_Activator::initialize();
			} catch ( Exception $e ) {
				self::emit_admin_error_output( $e, $initialization_failed_msg );
				exit;
			} catch ( Error $e ) {
				self::emit_admin_error_output( $e, $initialization_failed_msg );
				exit;
			}
		}

		/**
		 * Runs uninstall logic for the plugin, but only if its invocation
		 * represents the last plugin installation trying to clean up.
		 *
		 * Deletes options records in the database.
		 *
		 * If there would be other remaining installations previously added to this
		 * loader via {@link \FortAwesome\font_awesome_load()}, it does not delete the plugin options,
		 * since one of those others will end up becoming active and relying on the options data.
		 *
		 * Any theme or plugin that includes this Font Awesome plugin as a library
		 * dependency should call this from its own uninstall hook. For example:
		 *
		 * ```php
		 *  register_uninstall_hook(
		 *      __FILE__,
		 *      'FortAwesome\FontAwesome_Loader::maybe_uninstall'
		 *  );
		 * ```
		 *
		 * @since 4.0.0
		 */
		public static function maybe_uninstall() {
			if ( count( self::$data ) === 1 ) {
				// If there's only installation in the list, then it's
				// the one that has invoked this function and is is about to
				// go away, so it's safe to clean up.
				require_once trailingslashit( self::$data[0]['path'] ) . 'includes/class-fontawesome-deactivator.php';
				FontAwesome_Deactivator::uninstall();
			}
		}

		/**
		 * Deactivates, cleaning up temporary data, such as transients, if this
		 * represents the last installed copy of the Font Awesome plugin being deactivated.
		 *
		 * However, if this loader is aware of any remaining installations, it does
		 * not clean up temporary data, since one of those other Font Awesome plugin
		 * installations, if active, will be promoted and end up relying on the data.
		 *
		 * Any theme or plugin that includes this Font Awesome plugin as a library
		 * dependency should call this from its own uninstall hook. For example:
		 *
		 * ```php
		 *  register_deactivation_hook(
		 *      __FILE__,
		 *      'FortAwesome\FontAwesome_Loader::maybe_deactivate'
		 *  );
		 * ```
		 *
		 * @since 4.0.0
		 */
		public static function maybe_deactivate() {
			if ( count( self::$data ) === 1 ) {
				require_once trailingslashit( self::$data[0]['path'] ) . 'includes/class-fontawesome-deactivator.php';
				FontAwesome_Deactivator::deactivate();
			}
		}

		/**
		 * Creates and/or returns the static instance for this Singleton.
		 *
		 * It is probably not necessary for a theme or plugin that depends upon
		 * the Font Awesome plugin to invoke this. It's probably more convenient
		 * to access the Loader's functionality through the its public static methods.
		 *
		 * @return \FortAwesome\FontAwesome_Loader
		 * @see FontAwesome_Loader::initialize()
		 * @see FontAwesome_Loader::maybe_deactivate()
		 * @see FontAwesome_Loader::maybe_uninstall()
		 * @since 4.0.0
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Stores plugin version and details for an installation that is being
		 * registered with this loader.
		 *
		 * This method is not part of this plugin's public API.
		 *
		 * @param string      $data other information.
		 * @param string|bool $version framework version.
		 *
		 * @ignore
		 * @internal
		 * @return $this
		 */
		public function add( $data = '', $version = false ) {
			if ( file_exists( trailingslashit( $data ) . 'index.php' ) ) {
				if ( false === $version ) {
					$args    = get_file_data( trailingslashit( $data ) . 'index.php', array( 'version' => 'Version' ) );
					$version = ( isset( $args['version'] ) && ! empty( $args['version'] ) ) ? $args['version'] : $version;
				}
				array_push(
					self::$data,
					array(
						'version' => $version,
						'path'    => trailingslashit( $data ),
					)
				);
			}
			return $this;
		}
	}
endif; // ! class_exists

if ( ! function_exists( 'FortAwesome\font_awesome_load' ) ) {
	/**
	 * Adds plugin installation path to be managed by this loader.
	 *
	 * @param string $plugin_installation_path
	 * @param bool   $version
	 * @ignore
	 * @internal
	 * @since 4.0.0
	 */
	function font_awesome_load( $plugin_installation_path = __DIR__, $version = false ) {
		FontAwesome_Loader::instance()
			->add( $plugin_installation_path, $version );
	}
}

if ( function_exists( 'FortAwesome\font_awesome_load' ) ) {
	font_awesome_load( __DIR__ );
}
