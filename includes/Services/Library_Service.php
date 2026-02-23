<?php
/**
 * Library management service
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Library_Service {

    /** @var Koha_Client */
    private $client;

    /** @var Cache_Service */
    private $cache;

    /** @var array|null In-memory cache */
    private $libraries_cache = null;

    public function __construct( Koha_Client $client, Cache_Service $cache ) {
        $this->client = $client;
        $this->cache  = $cache;
    }

    /**
     * Get current libraries list.
     *
     * @return array Array of {name, code}
     */
    public function get_libraries(): array {
        if ( $this->libraries_cache !== null ) {
            return $this->libraries_cache;
        }

        $json = get_option( 'bk_libraries', '[]' );
        $list = json_decode( $json, true );

        if ( ! is_array( $list ) || empty( $list ) ) {
            $list = $this->get_default_libraries();
        }

        $this->libraries_cache = $list;
        return $list;
    }

    /**
     * Save libraries to database.
     */
    public function save_libraries( array $libraries ): bool {
        $clean = [];
        foreach ( $libraries as $lib ) {
            if ( ! empty( $lib['name'] ) && ! empty( $lib['code'] ) ) {
                $clean[] = [
                    'name' => sanitize_text_field( $lib['name'] ),
                    'code' => strtoupper( sanitize_key( $lib['code'] ) ),
                ];
            }
        }

        $this->libraries_cache = $clean;
        $this->cache->delete( 'libraries' );

        return update_option( 'bk_libraries', wp_json_encode( $clean, JSON_UNESCAPED_UNICODE ) );
    }

    /**
     * Import libraries from Koha REST API.
     *
     * @return array|\WP_Error
     */
    public function import_from_koha() {
        if ( ! $this->client->is_configured() ) {
            return new \WP_Error(
                'not_configured',
                __( 'A API do Koha não está configurada. Configure a URL na aba Conexão.', 'busca-koha' )
            );
        }

        $response = $this->client->get( 'libraries' );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $imported = [];
        foreach ( $response as $lib ) {
            $name = $lib['name'] ?? $lib['branchname'] ?? '';
            $code = $lib['library_id'] ?? $lib['branchcode'] ?? '';

            if ( ! empty( $name ) && ! empty( $code ) ) {
                $imported[] = [
                    'name' => sanitize_text_field( $name ),
                    'code' => strtoupper( sanitize_key( $code ) ),
                ];
            }
        }

        if ( empty( $imported ) ) {
            return new \WP_Error(
                'no_libraries',
                __( 'Nenhuma biblioteca encontrada na resposta da API.', 'busca-koha' )
            );
        }

        $this->save_libraries( $imported );
        update_option( 'bk_libraries_last_import', gmdate( 'c' ) );

        return $imported;
    }

    /**
     * Get last import timestamp.
     */
    public function get_last_import(): string {
        return get_option( 'bk_libraries_last_import', '' );
    }

    /**
     * Hardcoded default Ibram libraries.
     */
    public function get_default_libraries(): array {
        return [
            [ 'name' => 'Museu da Abolição',                   'code' => 'MAB' ],
            [ 'name' => 'Museu da República',                  'code' => 'MR' ],
            [ 'name' => 'Museu do Ouro',                       'code' => 'MDO' ],
            [ 'name' => 'Museu Histórico Nacional',            'code' => 'MHN' ],
            [ 'name' => 'Museu Nacional de Belas Artes',       'code' => 'MNBA' ],
            [ 'name' => 'Museu Regional de São João del-Rei',  'code' => 'MRSJDR' ],
            [ 'name' => 'Museu Victor Meirelles',              'code' => 'MVM' ],
            [ 'name' => 'Museu Villa-Lobos',                   'code' => 'MVL' ],
            [ 'name' => 'Museus Castro Maya',                  'code' => 'MCM' ],
            [ 'name' => 'Museu da Inconfidência',              'code' => 'MDINC' ],
            [ 'name' => 'Museu Imperial',                      'code' => 'MI' ],
            [ 'name' => 'Casa de Cláudio de Souza',            'code' => 'CS' ],
            [ 'name' => 'Casa Geyer',                          'code' => 'CG' ],
            [ 'name' => 'CEDOC',                               'code' => 'CENEDOM' ],
        ];
    }
}
