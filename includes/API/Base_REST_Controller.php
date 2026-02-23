<?php
/**
 * Abstract base REST controller
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Base_REST_Controller extends \WP_REST_Controller {

    /** @var string */
    protected $namespace = 'busca-koha/v1';

    /**
     * Prepare a success response.
     */
    protected function prepare_success( array $data, int $status = 200 ): \WP_REST_Response {
        return new \WP_REST_Response( $data, $status );
    }

    /**
     * Prepare an error response.
     */
    protected function prepare_error( string $message, string $code = 'error', int $status = 400 ): \WP_Error {
        return new \WP_Error( $code, $message, [ 'status' => $status ] );
    }

    /**
     * Check admin permission.
     */
    public function check_admin_permission( \WP_REST_Request $request ): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Check rate limit for an action.
     *
     * @return true|\WP_Error
     */
    public function check_rate_limit( string $action, int $limit = 0, int $window = 60 ) {
        if ( $limit <= 0 ) {
            $limit = (int) get_option( 'bk_rate_limit', 30 );
        }

        $ip   = $this->get_client_ip();
        $key  = 'bk_rate_' . md5( $action . $ip );
        $count = (int) get_transient( $key );

        if ( $count >= $limit ) {
            return new \WP_Error(
                'rate_limit_exceeded',
                __( 'Limite de requisições excedido. Tente novamente em alguns segundos.', 'busca-koha' ),
                [ 'status' => 429 ]
            );
        }

        set_transient( $key, $count + 1, $window );

        return true;
    }

    /**
     * Get client IP address safely.
     */
    private function get_client_ip(): string {
        $ip = '';

        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
            $ip  = trim( $ips[0] );
        }

        if ( empty( $ip ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        return $ip ?: '0.0.0.0';
    }
}
