<?php
/**
 * Plugin Name: WP MCP Abilities
 * Description: Registers core WordPress management abilities for the MCP Adapter plugin.
 * Version:     1.1.0-diag
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Author:      Daniel Boring
 * License:     MIT
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_notices', function () {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		echo '<div class="notice notice-error"><p><strong>WP MCP Abilities</strong> requires the <a href="https://wordpress.org/plugins/mcp-adapter/">MCP Adapter</a> plugin to be installed and active.</p></div>';
	}
} );

add_action( 'wp_abilities_api_init', function () {
	// Inline test: if this ability appears in /diag, the callback runs and
	// wp_register_ability() works. If it's missing, the callback never fires.
	wp_register_ability( 'wp-mcp/test-ping', array(
		'label'               => 'Test Ping',
		'description'         => 'Diagnostic ability.',
		'category'            => 'core',
		'execute_callback'    => function () { return array( 'ok' => true ); },
		'permission_callback' => function () { return true; },
		'meta'                => array( 'mcp' => array( 'public' => true ) ),
	) );

	require_once __DIR__ . '/includes/class-posts.php';
	require_once __DIR__ . '/includes/class-taxonomy.php';
	require_once __DIR__ . '/includes/class-comments.php';
	require_once __DIR__ . '/includes/class-health.php';
	require_once __DIR__ . '/includes/class-security.php';
	require_once __DIR__ . '/includes/class-seo.php';

	WP_MCP_Posts::register();
	WP_MCP_Taxonomy::register();
	WP_MCP_Comments::register();
	WP_MCP_Health::register();
	WP_MCP_Security::register();
	WP_MCP_SEO::register();
} );

// Diagnostic REST endpoint — DELETE BEFORE PRODUCTION.
// GET /wp-json/wp-mcp/v1/diag  (requires authentication)
add_action( 'rest_api_init', function () {
	register_rest_route( 'wp-mcp/v1', '/diag', array(
		'methods'             => 'GET',
		'callback'            => function () {
			$abilities    = function_exists( 'wp_get_abilities' ) ? wp_get_abilities() : array();
			$ability_list = array();
			foreach ( $abilities as $a ) {
				$meta           = $a->get_meta();
				$ability_list[] = array(
					'name'       => $a->get_name(),
					'mcp_public' => $meta['mcp']['public'] ?? false,
					'mcp_type'   => $meta['mcp']['type'] ?? 'tool',
				);
			}
				// Inspect how many callbacks are registered on wp_abilities_api_init.
			global $wp_filter;
			$hook_callbacks = array();
			if ( isset( $wp_filter['wp_abilities_api_init'] ) ) {
				foreach ( $wp_filter['wp_abilities_api_init']->callbacks as $priority => $cbs ) {
					foreach ( $cbs as $cb ) {
						$fn = $cb['function'];
						if ( is_array( $fn ) ) {
							$fn = ( is_object( $fn[0] ) ? get_class( $fn[0] ) : $fn[0] ) . '::' . $fn[1];
						} elseif ( $fn instanceof Closure ) {
							$fn = 'Closure';
						}
						$hook_callbacks[] = "priority={$priority} fn={$fn}";
					}
				}
			}

			return array(
				'wp_abilities_api_init_fired'     => (bool) did_action( 'wp_abilities_api_init' ),
				'wp_register_ability_exists'      => function_exists( 'wp_register_ability' ),
				'wp_get_abilities_exists'         => function_exists( 'wp_get_abilities' ),
				'wp_abilities_api_init_callbacks' => $hook_callbacks,
				'ability_count'                   => count( $ability_list ),
				'abilities'                       => $ability_list,
			);
		},
		'permission_callback' => function () {
			return is_user_logged_in();
		},
	) );
} );
