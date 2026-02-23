<?php
/**
 * Uninstall handler — remove all plugin data
 *
 * @package BuscaKoha
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

global $wpdb;

// Remove all plugin options (bk_* prefix)
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'bk\_%'"
);

// Remove all transients
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_bk\_%'
        OR option_name LIKE '_transient_timeout_bk\_%'"
);

// Remove legacy v3 options if still present
delete_option( 'koha_base_url' );
delete_option( 'koha_bibliotecas_json' );

wp_cache_flush();
