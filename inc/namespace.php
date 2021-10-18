<?php
/**
 * Main plugin namespace file.
 *
* @package Dashboard-Changelog
 */

namespace jazzsequence\DashboardChangelog;

use jazzsequence\DashboardChangelog\Widget;

/**
 * Kick it off!
 */
function bootstrap() {
	Widget\bootstrap();

	if ( parsedown_enabled() ) {
		require_once composer_autoloader();
	}

	if ( ! defined( 'JSDC_REPOSITORY' ) ) {
		add_action( 'admin_init', __NAMESPACE__ . '\\add_setting' );
	}
}

/**
 * Determines whether Parsedown should be enabled.
 *
 * @return bool
 */
function parsedown_enabled() : bool {
	// Bail if the autoloader doesn't exist.
	if ( ! file_exists( composer_autoloader() ) ) {
		error_log( __( 'Autoloader file not found.', 'dashboard-changelog' ) );
		return false;
	}

	// Bail if the Parsedown library doesn't exist.
	if ( ! file_exists( get_vendor_dir() . 'erusev/parsedown/Parsedown.php' ) ) {
		error_log( __( 'Parsedown file does not exist', 'dashboard-changelog' ) );
		return false;
	}

	return true;
}

/**
 * Returns the vendor directory.
 *
 * @return string
 */
function get_vendor_dir() : string {
	return dirname( __DIR__, 2 ) . '/vendor/';
}

/**
 * Returns the path to the Composer autoload file.
 *
 * @return string
 */
function composer_autoloader() : string {
	return get_vendor_dir() . 'autoload.php';
}

/**
 * Get the cache expiration time.
 * We default to one day, but this can be filtered.
 *
 * @return int The cache expiration time.
 */
function get_cache_expiration() : int {
	/**
	 * Allow the expiration time to be filtered. Default to DAY_IN_SECONDS.
	 *
	 * @param int $expire The length to retain cached response codes.
	 */
	return apply_filters( 'dc.api_expiration', DAY_IN_SECONDS );
}

/**
 * Register the setting and add the field.
 */
function add_setting() {
	register_setting( 'general', 'dashboard_changelog', [
		'sanitize_callback' => 'sanitize_text_field',
		'default' => null,
	] );

	add_settings_field(
		'dc-repo',
		__( 'GitHub Repo', 'js-dashboard-changelog' ),
		__NAMESPACE__ . '\\render_settings_field',
		'general',
		'default',
		[
			'label_for' => 'dc-repo'
		]
	);
}

/**
 * Get the Dashboard Changelog options.
 *
 * @param string $option  The option to retrieve.
 * @param string $default (Optional) A default value.
 */
function get_repository_option( $option = '', $default = '' ) {
	$options = get_option( 'dashboard_changelog' );

	if ( empty( $option ) ) {
		return $options;
	}

	if ( ! isset( $options[ $option ] ) ) {
		return $default;
	}

	return $options[ $option ];
}

/**
 * Display the input field for the GitHub repository.
 */
function render_settings_field() {
	$repo = get_repository_option( 'repository' );

	?>
	<input type="text" id="dc-repo" class="regular-text" name="dashboard_changelog[repository]" value="<?php echo esc_attr( $repo ); ?>" />

	<p class="description">
		<?php esc_html_e( 'Add the repository user and name, e.g. jazzsequence/dashboard-changelog.', 'js-dashboard-changelog' ); ?>
	</p>
	<?php
}

/**
 * Get the repository to fetch updates from.
 */
function get_repository() {
	if ( defined( 'JSDC_REPOSITORY' ) ) {
		return \JSDC_REPOSITORY;
	}

	return get_repository_option( 'repository' );
}
