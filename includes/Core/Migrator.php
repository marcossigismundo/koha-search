<?php
/**
 * v3.x -> v4.x data migration
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Migrator {

    public static function needs_migration(): bool {
        $stored = get_option( 'bk_version', '' );

        // Fresh install — no migration needed but run activator defaults
        if ( $stored === '' && get_option( 'koha_base_url' ) === false ) {
            return false;
        }

        // v3.x data exists but no v4 version stored
        if ( $stored === '' && get_option( 'koha_base_url' ) !== false ) {
            return true;
        }

        // Version mismatch
        return version_compare( $stored, BUSCA_KOHA_VERSION, '<' );
    }

    public static function migrate(): void {
        $stored = get_option( 'bk_version', '' );

        // Migrate from v3.x
        if ( $stored === '' ) {
            self::migrate_from_v3();
        }

        update_option( 'bk_version', BUSCA_KOHA_VERSION );
    }

    private static function migrate_from_v3(): void {
        // Migrate OPAC URL
        $old_url = get_option( 'koha_base_url', '' );
        if ( ! empty( $old_url ) ) {
            update_option( 'bk_koha_opac_url', esc_url_raw( $old_url ) );
        }

        // Migrate libraries
        $old_json = get_option( 'koha_bibliotecas_json', '' );
        if ( ! empty( $old_json ) ) {
            $libraries = json_decode( $old_json, true );
            if ( is_array( $libraries ) ) {
                $migrated = [];
                foreach ( $libraries as $lib ) {
                    if ( ! empty( $lib['nome'] ) && ! empty( $lib['codigo'] ) ) {
                        $migrated[] = [
                            'name' => sanitize_text_field( $lib['nome'] ),
                            'code' => strtoupper( sanitize_key( $lib['codigo'] ) ),
                        ];
                    }
                }
                update_option( 'bk_libraries', wp_json_encode( $migrated, JSON_UNESCAPED_UNICODE ) );
            }
        }

        // Existing installs keep redirect mode for backward compat
        update_option( 'bk_search_mode', 'redirect' );

        // Mark migration source
        update_option( 'bk_migrated_version', '3.1.0' );

        // Clean up legacy options
        delete_option( 'koha_base_url' );
        delete_option( 'koha_bibliotecas_json' );
    }
}
