<?php
/**
 * Plugin Name: Busca Koha
 * Plugin URI:  https://github.com/ibram/koha-search
 * Description: Sistema de busca integrado com Koha ILS — Rede de Bibliotecas do Ibram
 * Version:     4.0.0
 * Author:      CTINF / Ibram
 * Author URI:  https://github.com/ibram
 * Text Domain: busca-koha
 * Domain Path: /languages
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ─── Constants ──────────────────────────────────────────────────────── */

define( 'BUSCA_KOHA_VERSION', '4.0.0' );
define( 'BUSCA_KOHA_FILE', __FILE__ );
define( 'BUSCA_KOHA_PATH', plugin_dir_path( __FILE__ ) );
define( 'BUSCA_KOHA_URL', plugin_dir_url( __FILE__ ) );
define( 'BUSCA_KOHA_BASENAME', plugin_basename( __FILE__ ) );

/* ─── PSR-4 Autoloader ───────────────────────────────────────────────── */

spl_autoload_register( function ( $class ) {
    $prefix = 'BuscaKoha\\';
    $len    = strlen( $prefix );

    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative = substr( $class, $len );
    $file     = BUSCA_KOHA_PATH . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

/* ─── Activation / Deactivation ──────────────────────────────────────── */

register_activation_hook( __FILE__, [ 'BuscaKoha\\Core\\Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'BuscaKoha\\Core\\Deactivator', 'deactivate' ] );

/* ─── Bootstrap ──────────────────────────────────────────────────────── */

add_action( 'plugins_loaded', function () {
    load_plugin_textdomain( 'busca-koha', false, dirname( BUSCA_KOHA_BASENAME ) . '/languages' );

    $plugin = \BuscaKoha\Core\Plugin::get_instance();
    $plugin->run();
} );
