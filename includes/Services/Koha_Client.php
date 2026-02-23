<?php
/**
 * HTTP client for Koha REST API
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Services;

use BuscaKoha\Core\Encryption;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Koha_Client {

    /** @var Cache_Service */
    private $cache;

    /** @var string */
    private $api_url;

    /** @var string */
    private $opac_url;

    /** @var string none|oauth2|basic */
    private $auth_type;

    public function __construct( Cache_Service $cache ) {
        $this->cache     = $cache;
        $this->api_url   = rtrim( get_option( 'bk_koha_api_url', '' ), '/' );
        $this->opac_url  = rtrim( get_option( 'bk_koha_opac_url', 'https://bibliotecas-koha.museus.gov.br/cgi-bin/koha' ), '/' );
        $this->auth_type = get_option( 'bk_auth_type', 'none' );
    }

    /**
     * Check if the API URL is configured.
     */
    public function is_configured(): bool {
        return ! empty( $this->api_url );
    }

    /**
     * Get OPAC base URL.
     */
    public function get_opac_url(): string {
        return $this->opac_url;
    }

    /**
     * GET request to Koha API.
     *
     * @param string $endpoint e.g. 'biblios' or 'libraries'
     * @param array  $params   Query parameters
     * @return array|\WP_Error
     */
    public function get( string $endpoint, array $params = [] ) {
        $url = $this->api_url . '/' . ltrim( $endpoint, '/' );

        if ( ! empty( $params ) ) {
            $url = add_query_arg( $params, $url );
        }

        return $this->request( 'GET', $url );
    }

    /**
     * POST request to Koha API.
     *
     * @param string $endpoint
     * @param array  $body
     * @return array|\WP_Error
     */
    public function post( string $endpoint, array $body = [] ) {
        $url = $this->api_url . '/' . ltrim( $endpoint, '/' );

        return $this->request( 'POST', $url, $body );
    }

    /**
     * Test connection step-by-step.
     *
     * Tests both the OPAC URL (always) and the API URL (if configured).
     *
     * @return array{success: bool, steps: array, total_time_ms: int}
     */
    public function test_connection(): array {
        $steps = [];
        $start = microtime( true );

        // Determine which URL to test
        $test_api  = ! empty( $this->api_url );
        $test_host = '';

        if ( $test_api ) {
            $parsed    = wp_parse_url( $this->api_url );
            $test_host = $parsed['host'] ?? '';
        } else {
            $parsed    = wp_parse_url( $this->opac_url );
            $test_host = $parsed['host'] ?? '';
        }

        if ( empty( $test_host ) ) {
            return [
                'success'      => false,
                'steps'        => [ [
                    'step'    => 'config',
                    'status'  => 'error',
                    'message' => __( 'Nenhuma URL configurada (API ou OPAC).', 'busca-koha' ),
                    'time_ms' => 0,
                ] ],
                'total_time_ms' => 0,
            ];
        }

        // Step 1: DNS resolution
        $t      = microtime( true );
        $dns_ok = gethostbyname( $test_host ) !== $test_host;
        $steps[] = [
            'step'    => 'dns',
            'status'  => $dns_ok ? 'ok' : 'error',
            'message' => $dns_ok
                ? sprintf( __( 'DNS resolvido: %s', 'busca-koha' ), $test_host )
                : sprintf( __( 'Falha ao resolver DNS: %s', 'busca-koha' ), $test_host ),
            'time_ms' => round( ( microtime( true ) - $t ) * 1000 ),
        ];

        if ( ! $dns_ok ) {
            return [
                'success'       => false,
                'steps'         => $steps,
                'total_time_ms' => round( ( microtime( true ) - $start ) * 1000 ),
            ];
        }

        // Step 2: HTTP/TLS — test OPAC URL
        // Try HTTPS first, then fallback to HTTP (some internal networks block 443)
        $t             = microtime( true );
        $https_timeout = false;
        $response      = wp_remote_get( $this->opac_url, [
            'timeout'   => 10,
            'sslverify' => false,
        ] );

        $http_ok  = ! is_wp_error( $response );
        $http_msg = '';

        if ( ! $http_ok ) {
            $https_timeout = ( strpos( $response->get_error_message(), 'timed out' ) !== false
                            || strpos( $response->get_error_message(), 'cURL error 28' ) !== false );

            // Try HTTP fallback if HTTPS failed
            $http_url  = preg_replace( '/^https:/i', 'http:', $this->opac_url );
            $response2 = wp_remote_get( $http_url, [
                'timeout'     => 10,
                'sslverify'   => false,
                'redirection' => 0,
            ] );

            $http_ok = ! is_wp_error( $response2 );
            if ( $http_ok ) {
                $code     = wp_remote_retrieve_response_code( $response2 );
                $http_msg = sprintf( __( 'OPAC acessível via HTTP (%d). HTTPS com timeout — verifique a porta 443/firewall.', 'busca-koha' ), $code );
            } else {
                $http_msg = sprintf( __( 'Falha ao acessar OPAC: %s', 'busca-koha' ), $response->get_error_message() );
            }
        } else {
            $http_msg = sprintf( __( 'OPAC acessível (HTTP %d).', 'busca-koha' ), wp_remote_retrieve_response_code( $response ) );
        }

        $steps[] = [
            'step'    => 'http',
            'status'  => $http_ok ? 'ok' : 'error',
            'message' => $http_msg,
            'time_ms' => round( ( microtime( true ) - $t ) * 1000 ),
        ];

        // Step 3: API REST (only if configured)
        // Check if API and OPAC share the same host — skip if HTTPS already timed out
        $same_host = false;
        if ( $test_api ) {
            $api_parsed  = wp_parse_url( $this->api_url );
            $opac_parsed = wp_parse_url( $this->opac_url );
            $same_host   = ( $api_parsed['host'] ?? '' ) === ( $opac_parsed['host'] ?? '' );
        }

        if ( $test_api && $https_timeout && $same_host ) {
            // Skip — same host already timed out on HTTPS
            $steps[] = [
                'step'    => 'api',
                'status'  => 'error',
                'message' => __( 'API no mesmo host — porta 443 inacessível (mesmo problema do OPAC).', 'busca-koha' ),
                'time_ms' => 0,
            ];
        } elseif ( $test_api ) {
            $t           = microtime( true );
            $api_response = wp_remote_get( $this->api_url, [
                'timeout'   => 10,
                'sslverify' => false,
                'headers'   => [ 'Accept' => 'application/json' ],
            ] );

            $api_ok = ! is_wp_error( $api_response );
            if ( $api_ok ) {
                $status_code = wp_remote_retrieve_response_code( $api_response );
                $steps[] = [
                    'step'    => 'api',
                    'status'  => ( $status_code >= 200 && $status_code < 500 ) ? 'ok' : 'error',
                    'message' => sprintf( __( 'API respondeu com código %d.', 'busca-koha' ), $status_code ),
                    'time_ms' => round( ( microtime( true ) - $t ) * 1000 ),
                ];
            } else {
                $steps[] = [
                    'step'    => 'api',
                    'status'  => 'error',
                    'message' => sprintf( __( 'API inacessível: %s', 'busca-koha' ), $api_response->get_error_message() ),
                    'time_ms' => round( ( microtime( true ) - $t ) * 1000 ),
                ];
            }
        } else {
            $steps[] = [
                'step'    => 'api',
                'status'  => 'ok',
                'message' => __( 'API REST não configurada (modo redirecionamento).', 'busca-koha' ),
                'time_ms' => 0,
            ];
        }

        // Step 4: Authentication (only if API + auth configured)
        if ( $test_api && $this->auth_type !== 'none' ) {
            $t     = microtime( true );
            $token = $this->get_auth_headers();

            if ( is_wp_error( $token ) ) {
                $steps[] = [
                    'step'    => 'auth',
                    'status'  => 'error',
                    'message' => sprintf( __( 'Falha na autenticação: %s', 'busca-koha' ), $token->get_error_message() ),
                    'time_ms' => round( ( microtime( true ) - $t ) * 1000 ),
                ];
            } else {
                $steps[] = [
                    'step'    => 'auth',
                    'status'  => 'ok',
                    'message' => sprintf( __( 'Autenticação %s bem-sucedida.', 'busca-koha' ), strtoupper( $this->auth_type ) ),
                    'time_ms' => round( ( microtime( true ) - $t ) * 1000 ),
                ];
            }
        } else {
            $steps[] = [
                'step'    => 'auth',
                'status'  => 'ok',
                'message' => $test_api
                    ? __( 'Sem autenticação configurada.', 'busca-koha' )
                    : __( 'Não necessária (modo redirecionamento).', 'busca-koha' ),
                'time_ms' => 0,
            ];
        }

        // Success if OPAC is accessible (main requirement for redirect mode)
        $all_ok = $http_ok;

        return [
            'success'       => $all_ok,
            'steps'         => $steps,
            'total_time_ms' => round( ( microtime( true ) - $start ) * 1000 ),
        ];
    }

    /* ── Internal ────────────────────────────────────────────────────── */

    /**
     * Execute an HTTP request with auth.
     *
     * @param string $method
     * @param string $url
     * @param array  $body
     * @return array|\WP_Error
     */
    private function request( string $method, string $url, array $body = [] ) {
        $headers = $this->get_auth_headers();

        if ( is_wp_error( $headers ) ) {
            return $headers;
        }

        $headers['Accept']       = 'application/json';
        $headers['Content-Type'] = 'application/json';

        $args = [
            'method'    => $method,
            'headers'   => $headers,
            'timeout'   => 15,
            'sslverify' => true,
        ];

        if ( $method !== 'GET' && ! empty( $body ) ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $raw  = wp_remote_retrieve_body( $response );
        $data = json_decode( $raw, true );

        if ( $code >= 400 ) {
            $msg = $data['error'] ?? $data['message'] ?? $raw;
            return new \WP_Error(
                'koha_api_error',
                sprintf(
                    /* translators: 1: HTTP status code, 2: error message */
                    __( 'Erro da API Koha (%1$d): %2$s', 'busca-koha' ),
                    $code,
                    $msg
                ),
                [ 'status' => $code ]
            );
        }

        return is_array( $data ) ? $data : [];
    }

    /**
     * Get authentication headers based on configured auth type.
     *
     * @return array|\WP_Error
     */
    private function get_auth_headers() {
        $headers = [];

        switch ( $this->auth_type ) {
            case 'oauth2':
                $token = $this->get_oauth_token();
                if ( is_wp_error( $token ) ) {
                    return $token;
                }
                $headers['Authorization'] = 'Bearer ' . $token;
                break;

            case 'basic':
                $user = get_option( 'bk_basic_auth_user', '' );
                $pass = Encryption::decrypt( get_option( 'bk_basic_auth_pass', '' ) );
                if ( ! empty( $user ) ) {
                    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
                    $headers['Authorization'] = 'Basic ' . base64_encode( $user . ':' . $pass );
                }
                break;
        }

        return $headers;
    }

    /**
     * Obtain OAuth2 token via client credentials grant.
     *
     * @return string|\WP_Error
     */
    private function get_oauth_token() {
        $cached = $this->cache->get( 'oauth_token' );
        if ( $cached !== false ) {
            return $cached;
        }

        $client_id     = get_option( 'bk_oauth_client_id', '' );
        $client_secret = Encryption::decrypt( get_option( 'bk_oauth_client_secret', '' ) );

        if ( empty( $client_id ) || empty( $client_secret ) ) {
            return new \WP_Error( 'oauth_missing', __( 'Credenciais OAuth2 não configuradas.', 'busca-koha' ) );
        }

        $response = wp_remote_post( $this->api_url . '/oauth/token', [
            'body'      => [
                'grant_type'    => 'client_credentials',
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
            ],
            'timeout'   => 15,
            'sslverify' => true,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['access_token'] ) ) {
            $msg = $body['error_description'] ?? $body['error'] ?? __( 'Resposta inválida do servidor OAuth2.', 'busca-koha' );
            return new \WP_Error( 'oauth_failed', $msg );
        }

        $ttl = max( ( $body['expires_in'] ?? 3600 ) - 60, 60 );
        $this->cache->set( 'oauth_token', $body['access_token'], $ttl );

        return $body['access_token'];
    }
}
