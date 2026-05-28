<?php
/**
 * Plugin Name: WP MCP Abilities
 * Description: Registers core WordPress management abilities for the MCP Adapter plugin.
 * Version:     1.0.5-diag
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

function wp_mcp_abilities_register() {
	$hook = current_action() ?: 'unknown';
	error_log( "WP_MCP_ABILITIES: wp_mcp_abilities_register() called on hook={$hook}" );

	if ( ! function_exists( 'wp_register_ability' ) ) {
		error_log( 'WP_MCP_ABILITIES: wp_register_ability() does not exist — aborting' );
		return;
	}

	if ( did_action( 'wp_mcp_abilities_registered' ) ) {
		error_log( 'WP_MCP_ABILITIES: already registered — skipping duplicate call' );
		return;
	}
	do_action( 'wp_mcp_abilities_registered' );

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

	$probe = wp_get_ability( 'wp-mcp/list-posts' );
	error_log( 'WP_MCP_ABILITIES: wp-mcp/list-posts registered=' . ( $probe ? 'YES' : 'NO' ) );
}

// Primary: correct hook per WP 6.9 Abilities API docs.
add_action( 'wp_abilities_api_init', 'wp_mcp_abilities_register' );

// Fallback: after MCP Adapter initialises (priority 15), before requests process.
add_action( 'rest_api_init', 'wp_mcp_abilities_register', 20 );

// Extra fallback: WP-CLI context (mcp-adapter uses init priority 20 for CLI).
add_action( 'init', 'wp_mcp_abilities_register', 30 );

error_log( 'WP_MCP_ABILITIES: plugin file loaded, hooks registered' );
