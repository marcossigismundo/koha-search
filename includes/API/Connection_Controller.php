<?php
/**
 * Connection test REST endpoint
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\API;

use BuscaKoha\Services\Koha_Client;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Connection_Controller extends Base_REST_Controller {

    /** @var string */
    protected $rest_base = 'admin';

    /** @var Koha_Client */
    private $koha_client;

    public function __construct( Koha_Client $koha_client ) {
        $this->koha_client = $koha_client;
    }

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/test-connection', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'test_connection' ],
                'permission_callback' => [ $this, 'check_admin_permission' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/clear-cache', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'clear_cache' ],
                'permission_callback' => [ $this, 'check_admin_permission' ],
            ],
        ] );

    }

    /**
     * Test connection to Koha.
     */
    public function test_connection( \WP_REST_Request $request ): \WP_REST_Response {
        $result = $this->koha_client->test_connection();
        return $this->prepare_success( $result );
    }

    /**
     * Clear all plugin cache.
     */
    public function clear_cache( \WP_REST_Request $request ): \WP_REST_Response {
        $cache = \BuscaKoha\Core\Plugin::get_instance()->get_service( 'cache' );
        $cache->flush();

        return $this->prepare_success( [
            'success' => true,
            'message' => __( 'Cache limpo com sucesso.', 'busca-koha' ),
        ] );
    }
}
