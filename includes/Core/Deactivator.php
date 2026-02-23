<?php
/**
 * Plugin deactivation logic
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Deactivator {

    public static function deactivate(): void {
        self::clear_transients();
    }

    public static function clear_transients(): void {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_bk\_%'
                OR option_name LIKE '_transient_timeout_bk\_%'"
        );

        wp_cache_flush();
    }
}
