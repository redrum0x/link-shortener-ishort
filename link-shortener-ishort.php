<?php
/**
 * Plugin Name: Link Shortener by iShort
 * Plugin URI:  https://github.com/redrum0x/link-shortener-ishort
 * Description: Shorten URLs directly from the WordPress editor using iShort.su service.
 * Version:     1.0.0
 * Author:      iShort
 * Author URI:  https://ishort.su
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: link-shortener-ishort
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LSISHORT_VERSION', '1.0.0' );
define( 'LSISHORT_API_URL', 'https://ishort.su/api/link' );
define( 'LSISHORT_SETTINGS_URL', 'https://ishort.su/user/api-clients' );
define( 'LSISHORT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LSISHORT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// -------------------------------------------------------------------------
// Settings
// -------------------------------------------------------------------------

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=link-shortener-ishort' ) ) . '">'
		. esc_html__( 'Settings', 'link-shortener-ishort' )
		. '</a>';
	array_unshift( $links, $settings_link );
	return $links;
} );

add_action( 'admin_menu', function () {
	add_options_page(
		__( 'Link Shortener by iShort', 'link-shortener-ishort' ),
		__( 'iShort', 'link-shortener-ishort' ),
		'manage_options',
		'link-shortener-ishort',
		'lsishort_settings_page'
	);
} );

add_action( 'admin_init', function () {
	register_setting( 'lsishort_settings', 'lsishort_api_token', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	] );
} );

function lsishort_settings_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Link Shortener by iShort', 'link-shortener-ishort' ); ?></h1>
		<div class="notice notice-info inline" style="margin:12px 0;">
			<p>
				<?php
				printf(
					wp_kses(
						/* translators: %s: URL to iShort homepage */
						__( 'To use this plugin you need a free account at <a href="%s" target="_blank" rel="noopener">iShort.su</a> and an API token.', 'link-shortener-ishort' ),
						[ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ]
					),
					esc_url( 'https://ishort.su' )
				);
				?>
				<?php
				printf(
					wp_kses(
						/* translators: %s: URL to iShort API clients page */
						__( 'Get your token on the <a href="%s" target="_blank" rel="noopener">API clients page</a>.', 'link-shortener-ishort' ),
						[ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ]
					),
					esc_url( LSISHORT_SETTINGS_URL )
				);
				?>
			</p>
		</div>
		<form method="post" action="options.php">
			<?php settings_fields( 'lsishort_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="lsishort_api_token"><?php esc_html_e( 'API Token', 'link-shortener-ishort' ); ?></label>
					</th>
					<td>
						<input
							type="password"
							id="lsishort_api_token"
							name="lsishort_api_token"
							value="<?php echo esc_attr( get_option( 'lsishort_api_token', '' ) ); ?>"
							class="regular-text"
							autocomplete="off"
						/>
						<p class="description">
							<?php
							printf(
								wp_kses(
									/* translators: %s: URL to iShort API clients page */
									__( 'Get your token at <a href="%s" target="_blank" rel="noopener">ishort.su/user/api-clients</a>', 'link-shortener-ishort' ),
									[ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ]
								),
								esc_url( LSISHORT_SETTINGS_URL )
							);
							?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<hr>
		<h2><?php esc_html_e( 'Test Connection', 'link-shortener-ishort' ); ?></h2>
		<button id="lsishort-test-btn" class="button button-secondary">
			<?php esc_html_e( 'Test Connection', 'link-shortener-ishort' ); ?>
		</button>
		<span id="lsishort-test-result" style="margin-left:10px;"></span>
	</div>
	<?php
}

// -------------------------------------------------------------------------
// AJAX: shorten URL
// -------------------------------------------------------------------------

add_action( 'wp_ajax_lsishort_shorten', 'lsishort_ajax_shorten' );

function lsishort_ajax_shorten() {
	check_ajax_referer( 'lsishort_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( [ 'message' => __( 'Permission denied.', 'link-shortener-ishort' ) ], 403 );
	}

	$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
	if ( empty( $url ) ) {
		wp_send_json_error( [ 'message' => __( 'URL is required.', 'link-shortener-ishort' ) ] );
	}

	$token = get_option( 'lsishort_api_token', '' );
	if ( empty( $token ) ) {
		wp_send_json_error( [
			'message'      => __( 'API token is not configured.', 'link-shortener-ishort' ),
			'settings_url' => admin_url( 'options-general.php?page=link-shortener-ishort' ),
		] );
	}

	$locale = get_locale();

	$response = wp_remote_post( LSISHORT_API_URL, [
		'timeout' => 15,
		'headers' => [
			'Authorization'   => 'Bearer ' . $token,
			'Content-Type'    => 'application/json',
			'Accept'          => 'application/json',
			'Accept-Language' => $locale,
		],
		'body' => wp_json_encode( [ 'url' => $url ] ),
	] );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( [ 'message' => $response->get_error_message() ] );
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $code !== 200 && $code !== 201 ) {
		$message = isset( $body['message'] ) ? $body['message'] : __( 'API error.', 'link-shortener-ishort' );
		wp_send_json_error( [ 'message' => $message ] );
	}

	$short_url = isset( $body['data']['short_url'] ) ? $body['data']['short_url'] : '';
	if ( empty( $short_url ) ) {
		wp_send_json_error( [ 'message' => __( 'Unexpected API response.', 'link-shortener-ishort' ) ] );
	}

	wp_send_json_success( [ 'short_url' => $short_url ] );
}

// AJAX: test connection
add_action( 'wp_ajax_lsishort_test', 'lsishort_ajax_test' );

function lsishort_ajax_test() {
	check_ajax_referer( 'lsishort_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Permission denied.', 'link-shortener-ishort' ) ], 403 );
	}

	$token = get_option( 'lsishort_api_token', '' );
	if ( empty( $token ) ) {
		wp_send_json_error( [ 'message' => __( 'API token is not configured.', 'link-shortener-ishort' ) ] );
	}

	$response = wp_remote_post( LSISHORT_API_URL, [
		'timeout' => 10,
		'headers' => [
			'Authorization'   => 'Bearer ' . $token,
			'Content-Type'    => 'application/json',
			'Accept'          => 'application/json',
			'Accept-Language' => get_locale(),
		],
		'body' => wp_json_encode( [ 'url' => 'https://example.com' ] ),
	] );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( [ 'message' => $response->get_error_message() ] );
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( $code === 200 || $code === 201 ) {
		wp_send_json_success( [ 'message' => __( 'Connection successful!', 'link-shortener-ishort' ) ] );
	} elseif ( $code === 401 ) {
		wp_send_json_error( [ 'message' => __( 'Invalid token.', 'link-shortener-ishort' ) ] );
	} else {
		$body    = json_decode( wp_remote_retrieve_body( $response ), true );
		$message = isset( $body['message'] ) ? $body['message'] : __( 'Invalid token or connection failed.', 'link-shortener-ishort' );
		wp_send_json_error( [ 'message' => $message ] );
	}
}

// -------------------------------------------------------------------------
// Enqueue scripts
// -------------------------------------------------------------------------

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$on_settings = ( $hook === 'settings_page_link-shortener-ishort' );
	$on_editor   = in_array( $hook, [ 'post.php', 'post-new.php' ], true );

	if ( ! $on_settings && ! $on_editor ) {
		return;
	}

	wp_enqueue_style(
		'lsishort-admin',
		LSISHORT_PLUGIN_URL . 'assets/admin.css',
		[],
		LSISHORT_VERSION
	);

	wp_enqueue_script(
		'lsishort-admin',
		LSISHORT_PLUGIN_URL . 'assets/admin.js',
		[ 'jquery' ],
		LSISHORT_VERSION,
		true
	);

	wp_localize_script( 'lsishort-admin', 'lsishort', [
		'ajax_url'    => admin_url( 'admin-ajax.php' ),
		'nonce'       => wp_create_nonce( 'lsishort_nonce' ),
		'settings_url'=> admin_url( 'options-general.php?page=link-shortener-ishort' ),
		'has_token'   => ! empty( get_option( 'lsishort_api_token', '' ) ),
		'i18n'        => [
			'shortening'    => __( 'Shortening...', 'link-shortener-ishort' ),
			'no_token'      => __( 'API token is not configured.', 'link-shortener-ishort' ),
			'no_token_link' => __( 'Go to settings', 'link-shortener-ishort' ),
			'no_url'        => __( 'Please select a valid URL (http:// or https://).', 'link-shortener-ishort' ),
			'error'         => __( 'Error: ', 'link-shortener-ishort' ),
			'copied'        => __( 'Copied!', 'link-shortener-ishort' ),
			'copy'          => __( 'Copy', 'link-shortener-ishort' ),
			'draft_hint'    => __( 'Publish the post first.', 'link-shortener-ishort' ),
			'shorten_post'  => __( 'Shorten post URL', 'link-shortener-ishort' ),
			'short_url'     => __( 'Short URL:', 'link-shortener-ishort' ),
			'testing'       => __( 'Testing...', 'link-shortener-ishort' ),
		],
	] );
} );

// Gutenberg script
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_script(
		'lsishort-gutenberg',
		LSISHORT_PLUGIN_URL . 'assets/gutenberg.js',
		[ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-rich-text', 'wp-compose' ],
		LSISHORT_VERSION,
		true
	);

	wp_localize_script( 'lsishort-gutenberg', 'lsishort', [
		'ajax_url'     => admin_url( 'admin-ajax.php' ),
		'nonce'        => wp_create_nonce( 'lsishort_nonce' ),
		'settings_url' => admin_url( 'options-general.php?page=link-shortener-ishort' ),
		'has_token'    => ! empty( get_option( 'lsishort_api_token', '' ) ),
		'icon_url'     => LSISHORT_PLUGIN_URL . 'assets/icon.svg',
		'i18n'         => [
			'button_title'  => __( 'Shorten URL with iShort', 'link-shortener-ishort' ),
			'shortening'    => __( 'Shortening...', 'link-shortener-ishort' ),
			'no_token'      => __( 'API token is not configured. ', 'link-shortener-ishort' ),
			'no_token_link' => __( 'Go to settings.', 'link-shortener-ishort' ),
			'no_url'        => __( 'Please select a valid URL (http:// or https://).', 'link-shortener-ishort' ),
			'error'         => __( 'Error: ', 'link-shortener-ishort' ),
		],
	] );
} );

// -------------------------------------------------------------------------
// TinyMCE (Classic Editor)
// -------------------------------------------------------------------------

add_filter( 'mce_buttons', function ( $buttons ) {
	$buttons[] = 'lsishort';
	return $buttons;
} );

add_filter( 'mce_external_plugins', function ( $plugins ) {
	$plugins['lsishort'] = LSISHORT_PLUGIN_URL . 'assets/tinymce-plugin.js';
	return $plugins;
} );

// -------------------------------------------------------------------------
// Meta box
// -------------------------------------------------------------------------

add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'lsishort_meta_box',
		__( 'iShort — Short URL', 'link-shortener-ishort' ),
		'lsishort_meta_box_html',
		null,
		'side',
		'default'
	);
} );

function lsishort_meta_box_html( $post ) {
	$short_url   = get_post_meta( $post->ID, '_lsishort_short_url', true );
	$is_draft    = ! in_array( $post->post_status, [ 'publish', 'future' ], true );
	$post_url    = get_permalink( $post->ID );
	?>
	<div id="lsishort-meta-box">
		<?php if ( $short_url ) : ?>
			<p class="lsishort-result">
				<strong><?php esc_html_e( 'Short URL:', 'link-shortener-ishort' ); ?></strong><br>
				<a href="<?php echo esc_url( $short_url ); ?>" target="_blank" rel="noopener">
					<?php echo esc_html( $short_url ); ?>
				</a>
				<button type="button" class="button button-small lsishort-copy-btn" data-url="<?php echo esc_attr( $short_url ); ?>">
					<?php esc_html_e( 'Copy', 'link-shortener-ishort' ); ?>
				</button>
			</p>
		<?php endif; ?>

		<button
			type="button"
			id="lsishort-shorten-post-btn"
			class="button button-primary"
			data-post-url="<?php echo esc_attr( $post_url ? $post_url : '' ); ?>"
			<?php disabled( $is_draft ); ?>
		>
			<?php esc_html_e( 'Shorten post URL', 'link-shortener-ishort' ); ?>
		</button>

		<?php if ( $is_draft ) : ?>
			<p class="description"><?php esc_html_e( 'Publish the post first.', 'link-shortener-ishort' ); ?></p>
		<?php endif; ?>

		<span id="lsishort-meta-spinner" class="spinner" style="float:none;display:none;"></span>
		<div id="lsishort-meta-result"></div>
	</div>
	<?php
}

// Save short URL to post meta via AJAX
add_action( 'wp_ajax_lsishort_save_meta', function () {
	check_ajax_referer( 'lsishort_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( [], 403 );
	}

	$post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$short_url = isset( $_POST['short_url'] ) ? esc_url_raw( wp_unslash( $_POST['short_url'] ) ) : '';

	if ( $post_id && $short_url ) {
		update_post_meta( $post_id, '_lsishort_short_url', $short_url );
	}

	wp_send_json_success();
} );
