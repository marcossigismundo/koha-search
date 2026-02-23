<?php
/**
 * Search REST endpoint
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\API;

use BuscaKoha\Services\Search_Service;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Search_Controller extends Base_REST_Controller {

    /** @var string */
    protected $rest_base = 'search';

    /** @var Search_Service */
    private $search_service;

    public function __construct( Search_Service $search_service ) {
        $this->search_service = $search_service;
    }

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'search' ],
                'permission_callback' => [ $this, 'search_permissions' ],
                'args'                => $this->get_search_args(),
            ],
        ] );
    }

    /**
     * @return true|\WP_Error
     */
    public function search_permissions( \WP_REST_Request $request ) {
        return $this->check_rate_limit( 'search' );
    }

    /**
     * Handle search request.
     */
    public function search( \WP_REST_Request $request ): \WP_REST_Response {
        $params = [
            'q'        => $request->get_param( 'q' ),
            'idx'      => $request->get_param( 'idx' ),
            'library'  => $request->get_param( 'library' ),
            'sort'     => $request->get_param( 'sort' ),
            'page'     => $request->get_param( 'page' ),
            'per_page' => $request->get_param( 'per_page' ),
        ];

        $result = $this->search_service->search( $params );

        if ( is_wp_error( $result ) ) {
            $data   = $result->get_error_data();
            $status = $data['status'] ?? 502;
            return new \WP_REST_Response( [
                'code'    => $result->get_error_code(),
                'message' => $result->get_error_message(),
            ], $status );
        }

        $response = $this->prepare_success( $result );

        $ttl = (int) get_option( 'bk_cache_ttl', 3600 );
        $response->header( 'Cache-Control', 'public, max-age=' . $ttl );
        $response->header( 'X-BK-Cache', ! empty( $result['cached'] ) ? 'HIT' : 'MISS' );

        return $response;
    }

    /**
     * Validation schema for search params.
     */
    public function get_search_args(): array {
        return [
            'q' => [
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function ( $value ) {
                    return is_string( $value ) && strlen( trim( $value ) ) >= 2;
                },
                'description'       => __( 'Termo de busca (mínimo 2 caracteres)', 'busca-koha' ),
            ],
            'idx' => [
                'required'          => false,
                'type'              => 'string',
                'default'           => get_option( 'bk_default_search_index', 'kw' ),
                'enum'              => [ 'kw', 'ti', 'au', 'su', 'nb', 'se', 'callnum' ],
                'sanitize_callback' => 'sanitize_key',
            ],
            'library' => [
                'required'          => false,
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'sort' => [
                'required'          => false,
                'type'              => 'string',
                'default'           => 'relevance',
                'enum'              => [ 'relevance', 'title_az', 'title_za', 'author_az', 'pubdate_dsc', 'pubdate_asc' ],
                'sanitize_callback' => 'sanitize_key',
            ],
            'page' => [
                'required'          => false,
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'required'          => false,
                'type'              => 'integer',
                'default'           => (int) get_option( 'bk_results_per_page', 20 ),
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
            ],
        ];
    }
}
