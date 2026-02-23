<?php
/**
 * Shortcode registration and rendering
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Frontend;

use BuscaKoha\Services\Library_Service;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Shortcode_Handler {

    /** @var Library_Service */
    private $library_service;

    public function __construct( Library_Service $libraries ) {
        $this->library_service = $libraries;
    }

    /**
     * Register shortcodes.
     */
    public function register(): void {
        add_shortcode( 'busca_koha', [ $this, 'render_search_form' ] );
        add_shortcode( 'koha_iframe', [ $this, 'render_iframe' ] );
    }

    /**
     * Render [busca_koha] shortcode.
     */
    public function render_search_form( $atts ): string {
        $atts = shortcode_atts( [
            // Legacy v3 attributes (Portuguese)
            'mostrar_titulo'      => 'true',
            'mostrar_bibliotecas' => 'true',
            // New attributes
            'show_title'          => null,
            'show_libraries'      => null,
            'layout'              => null,
        ], $atts, 'busca_koha' );

        // Resolve legacy vs new (new takes precedence)
        $show_title     = $atts['show_title'] !== null ? ( $atts['show_title'] !== 'false' ) : ( $atts['mostrar_titulo'] !== 'false' );
        $show_libraries = $atts['show_libraries'] !== null ? ( $atts['show_libraries'] !== 'false' ) : ( $atts['mostrar_bibliotecas'] !== 'false' );
        $layout         = $atts['layout'] !== null ? $atts['layout'] : get_option( 'bk_display_layout', 'inline' );

        $libraries = $this->library_service->get_libraries();
        $opac_url  = get_option( 'bk_koha_opac_url', 'https://bibliotecas-koha.museus.gov.br/cgi-bin/koha' );

        ob_start();
        include BUSCA_KOHA_PATH . 'templates/search-form.php';
        return ob_get_clean();
    }

    /**
     * Render [koha_iframe] shortcode.
     */
    public function render_iframe( $atts ): string {
        $atts = shortcode_atts( [
            'height' => '700',
        ], $atts, 'koha_iframe' );

        $opac = esc_url( get_option( 'bk_koha_opac_url', 'https://bibliotecas-koha.museus.gov.br/cgi-bin/koha' ) );
        $h    = absint( $atts['height'] );

        return '<div class="busca-koha-iframe-wrapper" style="width:100%;height:' . $h . 'px;">'
            . '<iframe src="' . $opac . '/opac-search.pl" width="100%" height="100%" '
            . 'frameborder="0" loading="lazy" title="' . esc_attr__( 'Busca no Acervo', 'busca-koha' ) . '"></iframe>'
            . '</div>';
    }

    /**
     * Get the template variables for rendering the search form.
     * Used by both the shortcode and the widget.
     */
    public static function get_form_vars( array $args = [] ): array {
        $show_title     = isset( $args['show_title'] ) ? (bool) $args['show_title'] : true;
        $show_libraries = isset( $args['show_libraries'] ) ? (bool) $args['show_libraries'] : true;
        $layout         = isset( $args['layout'] ) ? $args['layout'] : get_option( 'bk_display_layout', 'inline' );

        $plugin    = \BuscaKoha\Core\Plugin::get_instance();
        $lib_svc   = $plugin->get_service( 'library' );
        $libraries = $lib_svc ? $lib_svc->get_libraries() : [];
        $opac_url  = get_option( 'bk_koha_opac_url', 'https://bibliotecas-koha.museus.gov.br/cgi-bin/koha' );

        return compact( 'show_title', 'show_libraries', 'libraries', 'opac_url', 'layout' );
    }
}
