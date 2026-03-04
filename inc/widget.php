<?php
/**
 * Registers the dashboard widget.
 *
 * @package Dashboard-Changelog
 */

namespace jazzsequence\DashboardChangelog\Widget;

use function jazzsequence\DashboardChangelog\parsedown_enabled;
use jazzsequence\DashboardChangelog\API;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Parsedown;

/**
 * Initialize the Widget.
 */
function bootstrap() {
	add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\register_dashboard_widget' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_styles' );
}

/**
 * Enqueue the styles, but only on the main WordPress dashboard page.
 *
 * @param string $pagenow Current page
 */
function enqueue_styles( string $pagenow ) {
	// Bail if we're not on the dashboard.
	if ( $pagenow !== 'index.php' ) {
		return;
	}

	$asset_file = include plugin_dir_path(__FILE__) . 'build/style.scss.asset.php';

	wp_enqueue_script(
		'dashboard-changelog',
        plugin_dir_url(__FILE__) . 'build/style-style.scss.css',
        $asset_file['dependencies'],
        $asset_file['version']
	);

	wp_enqueue_script( 'jquery-ui-accordion' );

	wp_add_inline_script(
		'jquery-ui-accordion',
		'jQuery(function($){ $("#changelog-accordion").accordion({ active: 0, collapsible: true, heightStyle: "content" }); });'
	);
}

/**
 * Register the dashboard widget.
 */
function register_dashboard_widget() {
	add_meta_box(
		'js-dashboard-changelog',
		API\get_code() === 200 ? sprintf( __( '%s Updates', 'js-dashboard-changelog' ), API\get_name() ) : __( 'Error in Dashboard Changelog', 'js-dashboard-changelog' ),
		__NAMESPACE__ . '\\render_dashboard_widget',
		'dashboard',
		'side',
		'high'
	);
}

/**
 * Display the dashboard widget.
 * Widget displays the 3 most recent GitHub releases.
 */
function render_dashboard_widget() {
	$parsedown = new Parsedown();
	$parsedown->setMarkupEscaped( true ); // Sanitize Markdown.
	$updates = API\get_body();

	// If there was an error, display the error message and bail.
	if ( isset( $updates['error'] ) ) {
		$error = wpautop( $updates['message'] );
		$error .= '<div class="error">';
		$error .= '<span class="error-code">' . sprintf( esc_html__( 'Error %d', 'js-dashboard-changelog' ), $updates['code'] ) . '</span>';
		$error .= '<span class="error-message">' . $updates['error'] . '</span>';
		$error .= '</div>';

		echo wp_kses_post( $error );
		return;
	}

	$body = '<div id="changelog-accordion">';
	$i = 0;

	/**
	 * Allow the maximum number of releases to display to be filtered.
	 *
	 * @param int $max_display The number of release updates to display.
	 */
	$max_display = apply_filters( 'dc.widget.max_display', 3 );

	if ( ! empty( $updates ) ) {
		foreach ( $updates as $update ) {
			// Only show the 3 most recent updates.
			if ( $i >= $max_display ) {
				continue;
			}

			$body_lines = explode( "\n", $update->body, 2 );
			$version = trim( preg_replace( '/^#+\s*/', '', $body_lines[0] ) );
			$version_html = parsedown_enabled() ? $parsedown->line( $version ) : wp_kses_post( $version );
			$body_content = isset( $body_lines[1] ) ? ltrim( $body_lines[1] ) : '';
			// If we have Parsedown, use it. Otherwise just use wpautop for basic parsing.
			$description = parsedown_enabled() ? $parsedown->text( $body_content ) : wpautop( $body_content );
			$tr = new GoogleTranslate();
			$tr->setSource('en');
			$tr->setTarget(get_locale());
			# Do not translate if description exceeds google translate 5000 characters limit
			$translated_desc = strlen($description) > 5000 ? $description : $tr->translate($description);


			$link = $update->html_url;

			$description = str_replace("roots/wordpress", "wordpress", $description);
			$description = str_replace("wpackagist-plugin/", "", $description);
			$body .= '<h3 class="entry-header">' . wp_kses( $version_html, [ 'strong' => [], 'em' => [], 'code' => [] ] ) . '</h3>';
			$body .= '<div class="entry">';
			$body .= $translated_desc;
			$body .= '<span class="version"><a target="_blank" href="' . esc_url( $link ) . '">' . wp_kses_post( $version_html ) . '</a></span>';
			$body .= '</div>';

			$i++;
		}
	}

	$body .= '</div>';

	echo wp_kses_post( $body );
}
