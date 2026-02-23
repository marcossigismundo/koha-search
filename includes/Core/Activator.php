<?php
/**
 * Plugin activation logic
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Activator {

    public static function activate(): void {
        self::check_requirements();
        self::create_default_options();
    }

    private static function check_requirements(): void {
        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            deactivate_plugins( BUSCA_KOHA_BASENAME );
            wp_die(
                esc_html__( 'Busca Koha requer PHP 7.4 ou superior.', 'busca-koha' ),
                esc_html__( 'Requisito não atendido', 'busca-koha' ),
                [ 'back_link' => true ]
            );
        }

        if ( version_compare( get_bloginfo( 'version' ), '5.9', '<' ) ) {
            deactivate_plugins( BUSCA_KOHA_BASENAME );
            wp_die(
                esc_html__( 'Busca Koha requer WordPress 5.9 ou superior.', 'busca-koha' ),
                esc_html__( 'Requisito não atendido', 'busca-koha' ),
                [ 'back_link' => true ]
            );
        }

        if ( ! function_exists( 'openssl_encrypt' ) ) {
            deactivate_plugins( BUSCA_KOHA_BASENAME );
            wp_die(
                esc_html__( 'Busca Koha requer a extensão OpenSSL do PHP.', 'busca-koha' ),
                esc_html__( 'Requisito não atendido', 'busca-koha' ),
                [ 'back_link' => true ]
            );
        }
    }

    private static function create_default_options(): void {
        $defaults = [
            'bk_koha_api_url'          => '',
            'bk_koha_opac_url'         => 'https://bibliotecas-koha.museus.gov.br/cgi-bin/koha',
            'bk_auth_type'             => 'none',
            'bk_oauth_client_id'       => '',
            'bk_oauth_client_secret'   => '',
            'bk_basic_auth_user'       => '',
            'bk_basic_auth_pass'       => '',
            'bk_libraries'             => '[]',
            'bk_libraries_last_import' => '',
            'bk_search_mode'           => 'redirect',
            'bk_cache_ttl'             => 3600,
            'bk_results_per_page'      => 20,
            'bk_rate_limit'            => 30,
            'bk_search_indexes'        => '["kw","ti","au","su","nb"]',
            'bk_default_search_index'  => 'kw',
            'bk_display_layout'        => 'inline',
            'bk_primary_color'         => '#366AAC',
            'bk_custom_css'            => '',
            'bk_version'               => BUSCA_KOHA_VERSION,
        ];

        foreach ( $defaults as $key => $value ) {
            if ( get_option( $key ) === false ) {
                add_option( $key, $value );
            }
        }
    }
}
