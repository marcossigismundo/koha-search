<?php
/**
 * WordPress Settings API registration
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Admin;

use BuscaKoha\Core\Encryption;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Settings_Registry {

    /**
     * Register all settings, sections, and fields.
     */
    public function register(): void {
        $this->register_connection_settings();
        $this->register_search_settings();
        $this->register_display_settings();
    }

    /* ── Connection Tab ──────────────────────────────────────────────── */

    private function register_connection_settings(): void {
        $group   = 'bk_connection_settings';
        $section = 'bk_connection_section';

        add_settings_section( $section, '', '__return_false', $group );

        // API URL
        register_setting( $group, 'bk_koha_api_url', [
            'sanitize_callback' => 'esc_url_raw',
            'default'           => '',
        ] );
        add_settings_field( 'bk_koha_api_url', __( 'URL da API REST', 'busca-koha' ), function () {
            $val = get_option( 'bk_koha_api_url', '' );
            echo '<input type="url" name="bk_koha_api_url" value="' . esc_attr( $val ) . '" class="regular-text" placeholder="https://seu-koha.exemplo.com/api/v1">';
            echo '<p class="description">' . esc_html__( 'Endereço base da API REST do Koha (ex: https://bibliotecas-koha.museus.gov.br/api/v1)', 'busca-koha' ) . '</p>';
        }, $group, $section );

        // OPAC URL
        register_setting( $group, 'bk_koha_opac_url', [
            'sanitize_callback' => 'esc_url_raw',
            'default'           => 'https://bibliotecas-koha.museus.gov.br/cgi-bin/koha',
        ] );
        add_settings_field( 'bk_koha_opac_url', __( 'URL do OPAC', 'busca-koha' ), function () {
            $val = get_option( 'bk_koha_opac_url', 'https://bibliotecas-koha.museus.gov.br/cgi-bin/koha' );
            echo '<input type="url" name="bk_koha_opac_url" value="' . esc_attr( $val ) . '" class="regular-text">';
            echo '<p class="description">' . esc_html__( 'URL do OPAC público do Koha. Usado no modo de redirecionamento e nos links dos resultados.', 'busca-koha' ) . '</p>';
        }, $group, $section );

        // Auth type
        register_setting( $group, 'bk_auth_type', [
            'sanitize_callback' => function ( $v ) {
                return in_array( $v, [ 'none', 'oauth2', 'basic' ], true ) ? $v : 'none';
            },
            'default'           => 'none',
        ] );
        add_settings_field( 'bk_auth_type', __( 'Autenticação', 'busca-koha' ), function () {
            $val   = get_option( 'bk_auth_type', 'none' );
            $types = [
                'none'   => __( 'Nenhuma (acesso público)', 'busca-koha' ),
                'oauth2' => __( 'OAuth2 (Client Credentials)', 'busca-koha' ),
                'basic'  => __( 'Basic Auth', 'busca-koha' ),
            ];
            echo '<fieldset>';
            foreach ( $types as $key => $label ) {
                echo '<label style="display:block;margin-bottom:6px;">';
                echo '<input type="radio" name="bk_auth_type" value="' . esc_attr( $key ) . '"' . checked( $val, $key, false ) . '> ';
                echo esc_html( $label ) . '</label>';
            }
            echo '</fieldset>';
            echo '<p class="description">' . esc_html__( 'OAuth2 é recomendado. A opção deve corresponder à configuração do seu servidor Koha.', 'busca-koha' ) . '</p>';
        }, $group, $section );

        // OAuth2 Client ID
        register_setting( $group, 'bk_oauth_client_id', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );
        add_settings_field( 'bk_oauth_client_id', __( 'Client ID (OAuth2)', 'busca-koha' ), function () {
            $val = get_option( 'bk_oauth_client_id', '' );
            echo '<input type="text" name="bk_oauth_client_id" value="' . esc_attr( $val ) . '" class="regular-text" autocomplete="off">';
            echo '<p class="description bk-auth-hint bk-auth-oauth2">' . esc_html__( 'Gerado em: Koha Staff → Patron → More → Manage API keys', 'busca-koha' ) . '</p>';
        }, $group, $section );

        // OAuth2 Client Secret
        register_setting( $group, 'bk_oauth_client_secret', [
            'sanitize_callback' => function ( $v ) {
                if ( empty( $v ) || $v === '********' ) {
                    return get_option( 'bk_oauth_client_secret', '' );
                }
                return Encryption::encrypt( sanitize_text_field( $v ) );
            },
            'default'           => '',
        ] );
        add_settings_field( 'bk_oauth_client_secret', __( 'Client Secret (OAuth2)', 'busca-koha' ), function () {
            $has = ! empty( get_option( 'bk_oauth_client_secret', '' ) );
            echo '<input type="password" name="bk_oauth_client_secret" value="' . ( $has ? '********' : '' ) . '" class="regular-text" autocomplete="new-password">';
            echo '<p class="description bk-auth-hint bk-auth-oauth2">' . esc_html__( 'O secret é armazenado de forma criptografada. Deixe vazio para manter o valor atual.', 'busca-koha' ) . '</p>';
        }, $group, $section );

        // Basic Auth User
        register_setting( $group, 'bk_basic_auth_user', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );
        add_settings_field( 'bk_basic_auth_user', __( 'Usuário (Basic Auth)', 'busca-koha' ), function () {
            $val = get_option( 'bk_basic_auth_user', '' );
            echo '<input type="text" name="bk_basic_auth_user" value="' . esc_attr( $val ) . '" class="regular-text" autocomplete="off">';
        }, $group, $section );

        // Basic Auth Password
        register_setting( $group, 'bk_basic_auth_pass', [
            'sanitize_callback' => function ( $v ) {
                if ( empty( $v ) || $v === '********' ) {
                    return get_option( 'bk_basic_auth_pass', '' );
                }
                return Encryption::encrypt( sanitize_text_field( $v ) );
            },
            'default'           => '',
        ] );
        add_settings_field( 'bk_basic_auth_pass', __( 'Senha (Basic Auth)', 'busca-koha' ), function () {
            $has = ! empty( get_option( 'bk_basic_auth_pass', '' ) );
            echo '<input type="password" name="bk_basic_auth_pass" value="' . ( $has ? '********' : '' ) . '" class="regular-text" autocomplete="new-password">';
            echo '<p class="description bk-auth-hint bk-auth-basic">' . esc_html__( 'Requer que RESTBasicAuth esteja habilitado no Koha e servidor Plack.', 'busca-koha' ) . '</p>';
        }, $group, $section );
    }

    /* ── Search Tab ──────────────────────────────────────────────────── */

    private function register_search_settings(): void {
        $group   = 'bk_search_settings';
        $section = 'bk_search_section';

        add_settings_section( $section, '', '__return_false', $group );

        // Search mode
        register_setting( $group, 'bk_search_mode', [
            'sanitize_callback' => function ( $v ) {
                return in_array( $v, [ 'ajax', 'redirect' ], true ) ? $v : 'ajax';
            },
            'default'           => 'ajax',
        ] );
        add_settings_field( 'bk_search_mode', __( 'Modo de busca', 'busca-koha' ), function () {
            $val   = get_option( 'bk_search_mode', 'ajax' );
            $modes = [
                'ajax'     => __( 'AJAX — Exibe resultados na própria página (requer API configurada)', 'busca-koha' ),
                'redirect' => __( 'Redirecionamento — Envia o usuário ao OPAC do Koha (compatibilidade v3)', 'busca-koha' ),
            ];
            echo '<fieldset>';
            foreach ( $modes as $key => $label ) {
                echo '<label style="display:block;margin-bottom:6px;">';
                echo '<input type="radio" name="bk_search_mode" value="' . esc_attr( $key ) . '"' . checked( $val, $key, false ) . '> ';
                echo esc_html( $label ) . '</label>';
            }
            echo '</fieldset>';
        }, $group, $section );

        // Results per page
        register_setting( $group, 'bk_results_per_page', [
            'sanitize_callback' => function ( $v ) { return max( 1, min( 100, absint( $v ) ) ); },
            'default'           => 20,
        ] );
        add_settings_field( 'bk_results_per_page', __( 'Resultados por página', 'busca-koha' ), function () {
            $val = get_option( 'bk_results_per_page', 20 );
            echo '<input type="number" name="bk_results_per_page" value="' . esc_attr( $val ) . '" min="1" max="100" step="1" class="small-text">';
        }, $group, $section );

        // Cache TTL
        register_setting( $group, 'bk_cache_ttl', [
            'sanitize_callback' => function ( $v ) { return max( 0, absint( $v ) ); },
            'default'           => 3600,
        ] );
        add_settings_field( 'bk_cache_ttl', __( 'Tempo de cache (segundos)', 'busca-koha' ), function () {
            $val = get_option( 'bk_cache_ttl', 3600 );
            echo '<input type="number" name="bk_cache_ttl" value="' . esc_attr( $val ) . '" min="0" step="60" class="small-text">';
            echo '<p class="description">' . esc_html__( '0 = sem cache. Recomendado: 3600 (1 hora).', 'busca-koha' ) . '</p>';
        }, $group, $section );

        // Rate limit
        register_setting( $group, 'bk_rate_limit', [
            'sanitize_callback' => function ( $v ) { return max( 1, absint( $v ) ); },
            'default'           => 30,
        ] );
        add_settings_field( 'bk_rate_limit', __( 'Limite de buscas/min por IP', 'busca-koha' ), function () {
            $val = get_option( 'bk_rate_limit', 30 );
            echo '<input type="number" name="bk_rate_limit" value="' . esc_attr( $val ) . '" min="1" max="1000" class="small-text">';
        }, $group, $section );

        // Search indexes
        register_setting( $group, 'bk_search_indexes', [
            'sanitize_callback' => function ( $v ) {
                if ( is_array( $v ) ) {
                    $valid = [ 'kw', 'ti', 'au', 'su', 'nb', 'se', 'callnum' ];
                    $clean = array_values( array_intersect( $v, $valid ) );
                    return wp_json_encode( $clean );
                }
                return get_option( 'bk_search_indexes', '["kw","ti","au","su","nb"]' );
            },
            'default'           => '["kw","ti","au","su","nb"]',
        ] );
        add_settings_field( 'bk_search_indexes', __( 'Índices de busca', 'busca-koha' ), function () {
            $enabled = json_decode( get_option( 'bk_search_indexes', '["kw","ti","au","su","nb"]' ), true );
            $indexes = [
                'kw'      => __( 'Palavra-chave', 'busca-koha' ),
                'ti'      => __( 'Título', 'busca-koha' ),
                'au'      => __( 'Autor', 'busca-koha' ),
                'su'      => __( 'Assunto', 'busca-koha' ),
                'nb'      => __( 'ISBN', 'busca-koha' ),
                'se'      => __( 'Série', 'busca-koha' ),
                'callnum' => __( 'Número de chamada', 'busca-koha' ),
            ];
            echo '<fieldset>';
            foreach ( $indexes as $key => $label ) {
                $checked = in_array( $key, $enabled, true ) ? ' checked' : '';
                echo '<label style="display:block;margin-bottom:4px;">';
                echo '<input type="checkbox" name="bk_search_indexes[]" value="' . esc_attr( $key ) . '"' . $checked . '> ';
                echo esc_html( $label ) . ' <code>' . esc_html( $key ) . '</code></label>';
            }
            echo '</fieldset>';
        }, $group, $section );

        // Default search index
        register_setting( $group, 'bk_default_search_index', [
            'sanitize_callback' => 'sanitize_key',
            'default'           => 'kw',
        ] );
        add_settings_field( 'bk_default_search_index', __( 'Índice padrão', 'busca-koha' ), function () {
            $val   = get_option( 'bk_default_search_index', 'kw' );
            $items = [
                'kw' => __( 'Palavra-chave', 'busca-koha' ),
                'ti' => __( 'Título', 'busca-koha' ),
                'au' => __( 'Autor', 'busca-koha' ),
                'su' => __( 'Assunto', 'busca-koha' ),
                'nb' => __( 'ISBN', 'busca-koha' ),
            ];
            echo '<select name="bk_default_search_index">';
            foreach ( $items as $key => $label ) {
                echo '<option value="' . esc_attr( $key ) . '"' . selected( $val, $key, false ) . '>' . esc_html( $label ) . '</option>';
            }
            echo '</select>';
        }, $group, $section );
    }

    /* ── Display Tab ─────────────────────────────────────────────────── */

    private function register_display_settings(): void {
        $group   = 'bk_display_settings';
        $section = 'bk_display_section';

        add_settings_section( $section, '', '__return_false', $group );

        // Layout
        register_setting( $group, 'bk_display_layout', [
            'sanitize_callback' => function ( $v ) {
                return in_array( $v, [ 'inline', 'modal' ], true ) ? $v : 'inline';
            },
            'default'           => 'inline',
        ] );
        add_settings_field( 'bk_display_layout', __( 'Layout do formulário', 'busca-koha' ), function () {
            $val     = get_option( 'bk_display_layout', 'inline' );
            $layouts = [
                'inline' => __( 'Inline — formulário visível diretamente na página', 'busca-koha' ),
                'modal'  => __( 'Modal — botão que abre o formulário em janela sobreposta', 'busca-koha' ),
            ];
            echo '<fieldset>';
            foreach ( $layouts as $key => $label ) {
                echo '<label style="display:block;margin-bottom:6px;">';
                echo '<input type="radio" name="bk_display_layout" value="' . esc_attr( $key ) . '"' . checked( $val, $key, false ) . '> ';
                echo esc_html( $label ) . '</label>';
            }
            echo '</fieldset>';
        }, $group, $section );

        // Show title default
        register_setting( $group, 'bk_show_title', [
            'sanitize_callback' => function ( $v ) { return (bool) $v; },
            'default'           => true,
        ] );
        add_settings_field( 'bk_show_title', __( 'Exibir título', 'busca-koha' ), function () {
            $val = get_option( 'bk_show_title', true );
            echo '<label><input type="checkbox" name="bk_show_title" value="1"' . checked( $val, true, false ) . '> ';
            echo esc_html__( 'Exibir "Pesquise em nosso acervo" acima do campo', 'busca-koha' ) . '</label>';
        }, $group, $section );

        // Show library filter default
        register_setting( $group, 'bk_show_library_filter', [
            'sanitize_callback' => function ( $v ) { return (bool) $v; },
            'default'           => true,
        ] );
        add_settings_field( 'bk_show_library_filter', __( 'Exibir filtro de bibliotecas', 'busca-koha' ), function () {
            $val = get_option( 'bk_show_library_filter', true );
            echo '<label><input type="checkbox" name="bk_show_library_filter" value="1"' . checked( $val, true, false ) . '> ';
            echo esc_html__( 'Exibir dropdown de bibliotecas no formulário', 'busca-koha' ) . '</label>';
        }, $group, $section );

        // Show advanced filters
        register_setting( $group, 'bk_show_advanced_filters', [
            'sanitize_callback' => function ( $v ) { return (bool) $v; },
            'default'           => false,
        ] );
        add_settings_field( 'bk_show_advanced_filters', __( 'Filtros avançados', 'busca-koha' ), function () {
            $val = get_option( 'bk_show_advanced_filters', false );
            echo '<label><input type="checkbox" name="bk_show_advanced_filters" value="1"' . checked( $val, true, false ) . '> ';
            echo esc_html__( 'Exibir filtros de índice (título, autor, assunto) e ordenação', 'busca-koha' ) . '</label>';
        }, $group, $section );

        // Primary color
        register_setting( $group, 'bk_primary_color', [
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#366AAC',
        ] );
        add_settings_field( 'bk_primary_color', __( 'Cor principal', 'busca-koha' ), function () {
            $val = get_option( 'bk_primary_color', '#366AAC' );
            echo '<input type="text" name="bk_primary_color" value="' . esc_attr( $val ) . '" class="bk-color-picker" data-default-color="#366AAC">';
        }, $group, $section );

        // Custom CSS
        register_setting( $group, 'bk_custom_css', [
            'sanitize_callback' => 'wp_strip_all_tags',
            'default'           => '',
        ] );
        add_settings_field( 'bk_custom_css', __( 'CSS customizado', 'busca-koha' ), function () {
            $val = get_option( 'bk_custom_css', '' );
            echo '<textarea name="bk_custom_css" rows="8" cols="60" class="large-text code">' . esc_textarea( $val ) . '</textarea>';
            echo '<p class="description">' . esc_html__( 'CSS adicional aplicado ao formulário de busca e resultados.', 'busca-koha' ) . '</p>';
        }, $group, $section );
    }
}
