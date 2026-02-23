<?php
/**
 * Libraries REST endpoints
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\API;

use BuscaKoha\Services\Library_Service;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Libraries_Controller extends Base_REST_Controller {

    /** @var string */
    protected $rest_base = 'libraries';

    /** @var Library_Service */
    private $library_service;

    public function __construct( Library_Service $library_service ) {
        $this->library_service = $library_service;
    }

    public function register_routes(): void {
        // GET /libraries — public
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_libraries' ],
                'permission_callback' => '__return_true',
            ],
        ] );

        // POST /libraries/import — admin only
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/import', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'import_libraries' ],
                'permission_callback' => [ $this, 'check_admin_permission' ],
            ],
        ] );

        // POST /libraries/save — admin only
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/save', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'save_libraries' ],
                'permission_callback' => [ $this, 'check_admin_permission' ],
                'args'                => [
                    'libraries' => [
                        'required' => true,
                        'type'     => 'array',
                    ],
                ],
            ],
        ] );

        // POST /libraries/defaults — admin only
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/defaults', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'load_defaults' ],
                'permission_callback' => [ $this, 'check_admin_permission' ],
            ],
        ] );
    }

    /**
     * Get cached libraries list.
     */
    public function get_libraries( \WP_REST_Request $request ): \WP_REST_Response {
        $libraries   = $this->library_service->get_libraries();
        $last_import = $this->library_service->get_last_import();

        $response = $this->prepare_success( [
            'libraries'   => $libraries,
            'count'       => count( $libraries ),
            'last_import' => $last_import,
        ] );

        $response->header( 'Cache-Control', 'public, max-age=86400' );

        return $response;
    }

    /**
     * Import libraries from Koha API.
     */
    public function import_libraries( \WP_REST_Request $request ): \WP_REST_Response {
        $result = $this->library_service->import_from_koha();

        if ( is_wp_error( $result ) ) {
            return new \WP_REST_Response( [
                'code'    => $result->get_error_code(),
                'message' => $result->get_error_message(),
            ], 502 );
        }

        return $this->prepare_success( [
            'success'   => true,
            'imported'  => count( $result ),
            'libraries' => $result,
        ] );
    }

    /**
     * Save libraries list.
     */
    public function save_libraries( \WP_REST_Request $request ): \WP_REST_Response {
        $libraries = $request->get_param( 'libraries' );

        if ( ! is_array( $libraries ) ) {
            return new \WP_REST_Response( [
                'code'    => 'invalid_data',
                'message' => __( 'Dados inválidos.', 'busca-koha' ),
            ], 400 );
        }

        $this->library_service->save_libraries( $libraries );

        return $this->prepare_success( [
            'success'   => true,
            'libraries' => $this->library_service->get_libraries(),
        ] );
    }

    /**
     * Load default Ibram libraries.
     */
    public function load_defaults( \WP_REST_Request $request ): \WP_REST_Response {
        $defaults = $this->library_service->get_default_libraries();
        $this->library_service->save_libraries( $defaults );

        return $this->prepare_success( [
            'success'   => true,
            'libraries' => $defaults,
        ] );
    }
}
