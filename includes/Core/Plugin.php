<?php
/**
 * Singleton orchestrator
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {

    /** @var Plugin|null */
    private static $instance = null;

    /** @var string */
    private $version;

    /** @var array */
    private $services = [];

    /** @var bool */
    private $initialized = false;

    public static function get_instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->version = BUSCA_KOHA_VERSION;
    }

    public function run(): void {
        if ( $this->initialized ) {
            return;
        }
        $this->initialized = true;

        // Run migration if needed
        if ( Migrator::needs_migration() ) {
            Migrator::migrate();
        }

        $this->register_hooks();
        $this->init_services();

        if ( is_admin() ) {
            $this->init_admin();
        }

        $this->init_rest_api();
        $this->init_frontend();

        do_action( 'busca_koha_loaded', $this );
    }

    /* ── Hooks ───────────────────────────────────────────────────────── */

    private function register_hooks(): void {
        add_filter(
            'plugin_action_links_' . BUSCA_KOHA_BASENAME,
            [ $this, 'add_action_links' ]
        );

        add_filter( 'plugin_row_meta', [ $this, 'add_row_meta_links' ], 10, 2 );
    }

    /**
     * @param array $links
     * @return array
     */
    public function add_action_links( array $links ): array {
        $url = admin_url( 'admin.php?page=busca-koha' );
        array_unshift(
            $links,
            sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Configurações', 'busca-koha' ) )
        );
        return $links;
    }

    /**
     * @param array  $links
     * @param string $file
     * @return array
     */
    public function add_row_meta_links( array $links, string $file ): array {
        if ( $file === BUSCA_KOHA_BASENAME ) {
            $links[] = sprintf(
                '<a href="%s" target="_blank" rel="noopener">%s</a>',
                'https://api.koha-community.org/',
                esc_html__( 'Documentação Koha API', 'busca-koha' )
            );
        }
        return $links;
    }

    /* ── Services ────────────────────────────────────────────────────── */

    private function init_services(): void {
        $cache = new \BuscaKoha\Services\Cache_Service(
            (int) get_option( 'bk_cache_ttl', 3600 )
        );
        $this->register_service( 'cache', $cache );

        $koha_client = new \BuscaKoha\Services\Koha_Client( $cache );
        $this->register_service( 'koha_client', $koha_client );

        $library = new \BuscaKoha\Services\Library_Service( $koha_client, $cache );
        $this->register_service( 'library', $library );

        $search = new \BuscaKoha\Services\Search_Service( $koha_client, $cache );
        $this->register_service( 'search', $search );
    }

    /* ── Admin ───────────────────────────────────────────────────────── */

    private function init_admin(): void {
        $admin = new \BuscaKoha\Admin\Admin_Controller( $this->version );
        $admin->init();
    }

    /* ── REST API ────────────────────────────────────────────────────── */

    private function init_rest_api(): void {
        add_action( 'rest_api_init', function () {
            $search = new \BuscaKoha\API\Search_Controller(
                $this->get_service( 'search' )
            );
            $search->register_routes();

            $libraries = new \BuscaKoha\API\Libraries_Controller(
                $this->get_service( 'library' )
            );
            $libraries->register_routes();

            $connection = new \BuscaKoha\API\Connection_Controller(
                $this->get_service( 'koha_client' )
            );
            $connection->register_routes();
        } );
    }

    /* ── Frontend ────────────────────────────────────────────────────── */

    private function init_frontend(): void {
        $assets = new \BuscaKoha\Frontend\Asset_Loader( $this->version );
        $assets->register();

        $shortcodes = new \BuscaKoha\Frontend\Shortcode_Handler(
            $this->get_service( 'library' )
        );
        $shortcodes->register();

        // Register widget
        add_action( 'widgets_init', function () {
            register_widget( \BuscaKoha\Frontend\Search_Widget::class );
        } );
    }

    /* ── Service container ───────────────────────────────────────────── */

    public function register_service( string $name, $service ): void {
        $this->services[ $name ] = $service;
    }

    public function get_service( string $name ) {
        return $this->services[ $name ] ?? null;
    }

    public function get_version(): string {
        return $this->version;
    }

    private function __clone() {}

    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton' );
    }
}
