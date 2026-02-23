<?php
/**
 * Search service — queries Koha and transforms results
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Search_Service {

    /** @var Koha_Client */
    private $client;

    /** @var Cache_Service */
    private $cache;

    public function __construct( Koha_Client $client, Cache_Service $cache ) {
        $this->client = $client;
        $this->cache  = $cache;
    }

    /**
     * Execute a search.
     *
     * @param array $params {q, idx, library, sort, page, per_page}
     * @return array|\WP_Error
     */
    public function search( array $params ) {
        // If API not configured, return error suggesting redirect mode
        if ( ! $this->client->is_configured() ) {
            return new \WP_Error(
                'not_configured',
                __( 'A API do Koha não está configurada. Use o modo de redirecionamento ou configure a conexão.', 'busca-koha' ),
                [ 'status' => 503 ]
            );
        }

        $cache_key = $this->build_cache_key( $params );
        $cached    = $this->cache->get( $cache_key );

        if ( $cached !== false ) {
            $cached['cached'] = true;
            return $cached;
        }

        $query_params = $this->build_query( $params );
        $response     = $this->client->get( 'biblios', $query_params );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Normalize response — Koha may return items at top level or in a wrapper
        $items = isset( $response['results'] ) ? $response['results'] : $response;
        $total = isset( $response['total'] ) ? (int) $response['total'] : count( $items );

        $per_page = max( 1, (int) ( $params['per_page'] ?? 20 ) );
        $page     = max( 1, (int) ( $params['page'] ?? 1 ) );

        $result = [
            'results' => array_map( [ $this, 'transform_biblio' ], $items ),
            'total'   => $total,
            'page'    => $page,
            'pages'   => (int) ceil( $total / $per_page ),
            'query'   => sanitize_text_field( $params['q'] ?? '' ),
            'cached'  => false,
        ];

        $this->cache->set( $cache_key, $result );

        return $result;
    }

    /**
     * Build OPAC search URL for redirect mode (backwards compatibility).
     */
    public function get_opac_search_url( string $query, string $library = '', string $type = 'geral' ): string {
        $base = $this->client->get_opac_url();

        switch ( $type ) {
            case 'autoridade':
                if ( $query ) {
                    return $base . '/opac-authorities-home.pl?op=do_search&type=opac&operator=contains&value=' . rawurlencode( $query );
                }
                return $base . '/opac-authorities-home.pl';

            case 'instantanea':
            case 'geral':
            default:
                $url = $base . '/opac-search.pl?idx=kw%2Cwrdl&q=' . rawurlencode( $query ) . '&weight_search=1';
                if ( $library ) {
                    $url .= '&limit=branch:' . rawurlencode( $library );
                }
                return $url;
        }
    }

    /**
     * Return available search indexes for the frontend.
     */
    public function get_search_indexes(): array {
        $enabled = json_decode( get_option( 'bk_search_indexes', '["kw","ti","au","su","nb"]' ), true );

        $all = [
            'kw'      => __( 'Palavra-chave', 'busca-koha' ),
            'ti'      => __( 'Título', 'busca-koha' ),
            'au'      => __( 'Autor', 'busca-koha' ),
            'su'      => __( 'Assunto', 'busca-koha' ),
            'nb'      => __( 'ISBN', 'busca-koha' ),
            'se'      => __( 'Série', 'busca-koha' ),
            'callnum' => __( 'Número de chamada', 'busca-koha' ),
        ];

        $result = [];
        foreach ( $all as $value => $label ) {
            if ( in_array( $value, $enabled, true ) ) {
                $result[] = [ 'value' => $value, 'label' => $label ];
            }
        }

        return $result;
    }

    /**
     * Get available sort options.
     */
    public function get_sort_options(): array {
        return [
            [ 'value' => 'relevance',   'label' => __( 'Relevância', 'busca-koha' ) ],
            [ 'value' => 'title_az',    'label' => __( 'Título A-Z', 'busca-koha' ) ],
            [ 'value' => 'title_za',    'label' => __( 'Título Z-A', 'busca-koha' ) ],
            [ 'value' => 'author_az',   'label' => __( 'Autor A-Z', 'busca-koha' ) ],
            [ 'value' => 'pubdate_dsc', 'label' => __( 'Mais recente', 'busca-koha' ) ],
            [ 'value' => 'pubdate_asc', 'label' => __( 'Mais antigo', 'busca-koha' ) ],
        ];
    }

    /* ── Internal ────────────────────────────────────────────────────── */

    /**
     * Build Koha API query parameters from search params.
     */
    private function build_query( array $params ): array {
        $q        = sanitize_text_field( $params['q'] ?? '' );
        $idx      = sanitize_key( $params['idx'] ?? 'kw' );
        $library  = sanitize_text_field( $params['library'] ?? '' );
        $sort     = sanitize_key( $params['sort'] ?? 'relevance' );
        $page     = max( 1, (int) ( $params['page'] ?? 1 ) );
        $per_page = max( 1, min( 100, (int) ( $params['per_page'] ?? 20 ) ) );

        // Build the q parameter for Koha REST API
        $query_filter = [];
        switch ( $idx ) {
            case 'ti':
                $query_filter['title'] = [ '-like' => '%' . $q . '%' ];
                break;
            case 'au':
                $query_filter['author'] = [ '-like' => '%' . $q . '%' ];
                break;
            case 'su':
                // Subject search — use keyword as fallback
                $query_filter['title'] = [ '-like' => '%' . $q . '%' ];
                break;
            case 'nb':
                $query_filter['isbn'] = $q;
                break;
            default: // kw
                $query_filter['title'] = [ '-like' => '%' . $q . '%' ];
                break;
        }

        $api_params = [
            'q'         => wp_json_encode( $query_filter ),
            '_per_page' => $per_page,
            '_page'     => $page,
        ];

        // Sort
        $sort_map = [
            'title_az'    => '+title',
            'title_za'    => '-title',
            'author_az'   => '+author',
            'pubdate_dsc' => '-copyrightdate',
            'pubdate_asc' => '+copyrightdate',
        ];

        if ( isset( $sort_map[ $sort ] ) ) {
            $api_params['_order_by'] = $sort_map[ $sort ];
        }

        return $api_params;
    }

    /**
     * Transform a Koha biblio record to our standard format.
     */
    private function transform_biblio( array $record ): array {
        $biblio_id = $record['biblio_id'] ?? $record['biblionumber'] ?? 0;
        $opac_url  = $this->client->get_opac_url() . '/opac-detail.pl?biblionumber=' . intval( $biblio_id );

        return [
            'biblio_id'        => (int) $biblio_id,
            'title'            => sanitize_text_field( $record['title'] ?? '' ),
            'author'           => sanitize_text_field( $record['author'] ?? '' ),
            'publication_date' => sanitize_text_field( $record['copyrightdate'] ?? $record['publication_year'] ?? '' ),
            'publisher'        => sanitize_text_field( $record['publisher'] ?? $record['publishercode'] ?? '' ),
            'isbn'             => sanitize_text_field( $record['isbn'] ?? '' ),
            'subjects'         => $this->extract_subjects( $record ),
            'library'          => sanitize_text_field( $record['holding_library'] ?? $record['homebranch'] ?? '' ),
            'library_code'     => sanitize_key( $record['library_id'] ?? $record['branchcode'] ?? '' ),
            'notes'            => sanitize_text_field( $record['abstract'] ?? $record['notes'] ?? '' ),
            'opac_url'         => esc_url( $opac_url ),
        ];
    }

    /**
     * Extract subjects from a biblio record.
     */
    private function extract_subjects( array $record ): array {
        if ( isset( $record['subjects'] ) && is_array( $record['subjects'] ) ) {
            return array_map( 'sanitize_text_field', $record['subjects'] );
        }

        if ( isset( $record['subject'] ) && is_string( $record['subject'] ) ) {
            return array_map( 'trim', explode( ';', sanitize_text_field( $record['subject'] ) ) );
        }

        return [];
    }

    /**
     * Build deterministic cache key from params.
     */
    private function build_cache_key( array $params ): string {
        $normalized = [
            'q'        => $params['q'] ?? '',
            'idx'      => $params['idx'] ?? 'kw',
            'library'  => $params['library'] ?? '',
            'sort'     => $params['sort'] ?? 'relevance',
            'page'     => $params['page'] ?? 1,
            'per_page' => $params['per_page'] ?? 20,
        ];

        ksort( $normalized );

        return 'search_' . md5( wp_json_encode( $normalized ) );
    }
}
