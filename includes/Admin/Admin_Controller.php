<?php
/**
 * Admin controller — menu, assets, tab routing
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Controller {

    /** @var string */
    private $version;

    /** @var string */
    private $hook_suffix = '';

    public function __construct( string $version ) {
        $this->version = $version;
    }

    /**
     * Register admin hooks.
     */
    public function init(): void {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Add top-level menu with dashicons-book icon.
     */
    public function add_menu(): void {
        $this->hook_suffix = add_menu_page(
            __( 'Busca Koha', 'busca-koha' ),
            __( 'Busca Koha', 'busca-koha' ),
            'manage_options',
            'busca-koha',
            [ $this, 'render_page' ],
            'dashicons-book-alt',
            81
        );

        // Sub-menu items for each tab
        add_submenu_page(
            'busca-koha',
            __( 'Conexão', 'busca-koha' ),
            __( 'Conexão', 'busca-koha' ),
            'manage_options',
            'busca-koha&tab=connection',
            [ $this, 'render_page' ]
        );

        add_submenu_page(
            'busca-koha',
            __( 'Bibliotecas', 'busca-koha' ),
            __( 'Bibliotecas', 'busca-koha' ),
            'manage_options',
            'busca-koha&tab=libraries',
            [ $this, 'render_page' ]
        );

        add_submenu_page(
            'busca-koha',
            __( 'Busca', 'busca-koha' ),
            __( 'Busca', 'busca-koha' ),
            'manage_options',
            'busca-koha&tab=search',
            [ $this, 'render_page' ]
        );

        add_submenu_page(
            'busca-koha',
            __( 'Aparência', 'busca-koha' ),
            __( 'Aparência', 'busca-koha' ),
            'manage_options',
            'busca-koha&tab=display',
            [ $this, 'render_page' ]
        );

        add_submenu_page(
            'busca-koha',
            __( 'Ajuda', 'busca-koha' ),
            __( 'Ajuda', 'busca-koha' ),
            'manage_options',
            'busca-koha&tab=help',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Register settings via Settings API.
     */
    public function register_settings(): void {
        $registry = new Settings_Registry();
        $registry->register();
    }

    /**
     * Conditionally load admin CSS/JS only on plugin pages.
     */
    public function enqueue_assets( string $hook ): void {
        // Check if we're on any of our plugin pages
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'busca-koha' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'busca-koha-admin',
            BUSCA_KOHA_URL . 'assets/css/admin.css',
            [],
            $this->version
        );

        wp_enqueue_script(
            'busca-koha-admin',
            BUSCA_KOHA_URL . 'assets/js/admin.js',
            [ 'wp-api-fetch' ],
            $this->version,
            true
        );

        wp_localize_script( 'busca-koha-admin', 'bkAdmin', [
            'restUrl'   => rest_url( 'busca-koha/v1/' ),
            'restNonce' => wp_create_nonce( 'wp_rest' ),
            'i18n'      => [
                'testingConnection' => __( 'Testando conexão...', 'busca-koha' ),
                'connectionSuccess' => __( 'Conexão bem-sucedida!', 'busca-koha' ),
                'connectionFailed'  => __( 'Falha na conexão.', 'busca-koha' ),
                'importing'         => __( 'Importando bibliotecas...', 'busca-koha' ),
                'importSuccess'     => __( 'Bibliotecas importadas com sucesso!', 'busca-koha' ),
                'importFailed'      => __( 'Falha na importação.', 'busca-koha' ),
                'confirmDelete'     => __( 'Remover esta biblioteca?', 'busca-koha' ),
                'confirmImport'     => __( 'Importar bibliotecas do Koha? A lista atual será substituída.', 'busca-koha' ),
                'confirmFlush'      => __( 'Limpar todo o cache do plugin?', 'busca-koha' ),
                'cacheCleared'      => __( 'Cache limpo com sucesso!', 'busca-koha' ),
                'saving'            => __( 'Salvando...', 'busca-koha' ),
                'saved'             => __( 'Salvo!', 'busca-koha' ),
                'stepDns'           => __( 'Resolução DNS', 'busca-koha' ),
                'stepHttp'          => __( 'Conexão HTTP/TLS', 'busca-koha' ),
                'stepApi'           => __( 'Resposta da API', 'busca-koha' ),
                'stepAuth'          => __( 'Autenticação', 'busca-koha' ),
            ],
        ] );

        // Color picker for display tab
        if ( $this->get_current_tab() === 'display' ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );
        }
    }

    /**
     * Render settings page with tab navigation.
     */
    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Acesso negado.', 'busca-koha' ) );
        }

        include BUSCA_KOHA_PATH . 'includes/Admin/views/page-settings.php';
    }

    /**
     * Get current active tab.
     */
    public function get_current_tab(): string {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'connection';
        $valid = [ 'connection', 'libraries', 'search', 'display', 'help' ];
        return in_array( $tab, $valid, true ) ? $tab : 'connection';
    }
}
