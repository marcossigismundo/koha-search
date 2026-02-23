<?php
/**
 * Frontend asset registration and performance hints
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Asset_Loader {

    /** @var string */
    private $version;

    public function __construct( string $version ) {
        $this->version = $version;
    }

    /**
     * Register all hooks.
     */
    public function register(): void {
        add_action( 'init', [ $this, 'register_assets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Register scripts and styles.
     */
    public function register_assets(): void {
        $script_args = [ 'strategy' => 'defer', 'in_footer' => true ];
        if ( version_compare( get_bloginfo( 'version' ), '6.3', '<' ) ) {
            $script_args = true;
        }

        wp_register_script(
            'busca-koha-public',
            BUSCA_KOHA_URL . 'assets/js/public.js',
            [],
            $this->version,
            $script_args
        );

        wp_register_style(
            'busca-koha-public',
            BUSCA_KOHA_URL . 'assets/css/public.css',
            [],
            $this->version
        );
    }

    /**
     * Conditionally enqueue on pages that use our shortcodes or widget.
     */
    public function enqueue_assets(): void {
        if ( ! $this->should_load() ) {
            return;
        }

        // Performance hints
        add_action( 'wp_head', [ $this, 'add_preconnect_hints' ], 1 );
        // Inline critical CSS
        add_action( 'wp_head', [ $this, 'inline_critical_css' ], 5 );
        // Custom CSS from settings
        add_action( 'wp_head', [ $this, 'inline_custom_css' ], 6 );

        wp_enqueue_script( 'busca-koha-public' );

        wp_localize_script( 'busca-koha-public', 'buscaKohaConfig', [
            'opacUrl' => get_option( 'bk_koha_opac_url', '' ),
        ] );
    }

    /**
     * DNS prefetch and preconnect hints.
     */
    public function add_preconnect_hints(): void {
        $opac = get_option( 'bk_koha_opac_url', '' );
        if ( empty( $opac ) ) {
            return;
        }

        $parsed = wp_parse_url( $opac );
        $origin = ( $parsed['scheme'] ?? 'https' ) . '://' . ( $parsed['host'] ?? '' );

        if ( empty( $parsed['host'] ) ) {
            return;
        }

        echo "<!-- Busca Koha - Performance -->\n";
        echo '<link rel="preconnect" href="' . esc_url( $origin ) . '" crossorigin>' . "\n";
        echo '<link rel="dns-prefetch" href="' . esc_url( $origin ) . '">' . "\n";
    }

    /**
     * Inline critical CSS for faster first paint.
     */
    public function inline_critical_css(): void {
        $css_file = BUSCA_KOHA_PATH . 'assets/css/public.css';
        if ( ! file_exists( $css_file ) ) {
            return;
        }

        $css = file_get_contents( $css_file );
        // Lightweight minification
        $css = preg_replace( '/\/\*[^*]*\*+([^\/][^*]*\*+)*\//', '', $css );
        $css = preg_replace( '/\s{2,}/', ' ', $css );
        $css = preg_replace( '/\s*([:;{},])\s*/', '$1', $css );
        $css = trim( $css );

        // Inject CSS custom properties
        $primary = get_option( 'bk_primary_color', '#366AAC' );
        $vars    = ":root{--bk-primary:{$primary};--bk-primary-light:{$primary}1a;}";

        echo '<style id="busca-koha-critical">' . $vars . $css . "</style>\n";
    }

    /**
     * Inline custom CSS from settings.
     */
    public function inline_custom_css(): void {
        $custom = get_option( 'bk_custom_css', '' );
        if ( ! empty( $custom ) ) {
            echo '<style id="busca-koha-custom">' . wp_strip_all_tags( $custom ) . "</style>\n";
        }
    }

    /**
     * Check if assets should load on this page.
     */
    private function should_load(): bool {
        // Always load on front page
        if ( is_front_page() ) {
            return true;
        }

        // Check for shortcode in post content
        $post    = get_post();
        $content = ( $post instanceof \WP_Post ) ? $post->post_content : '';

        if ( has_shortcode( $content, 'busca_koha' ) || has_shortcode( $content, 'koha_iframe' ) ) {
            return true;
        }

        // Check if widget is active
        if ( is_active_widget( false, false, 'busca_koha_widget', true ) ) {
            return true;
        }

        return false;
    }
}
