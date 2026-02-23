<?php
/**
 * Transient-based caching service
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cache_Service {

    /** @var string */
    private $prefix = 'bk_';

    /** @var int */
    private $default_ttl;

    /** @var array In-memory request cache */
    private $memory = [];

    public function __construct( int $default_ttl = 3600 ) {
        $this->default_ttl = $default_ttl;
    }

    /**
     * Get a cached value.
     *
     * @param string $key
     * @return mixed|false
     */
    public function get( string $key ) {
        $full_key = $this->make_key( $key );

        if ( isset( $this->memory[ $full_key ] ) ) {
            return $this->memory[ $full_key ];
        }

        $value = get_transient( $full_key );

        if ( $value !== false ) {
            $this->memory[ $full_key ] = $value;
        }

        return $value;
    }

    /**
     * Store a value in cache.
     */
    public function set( string $key, $value, ?int $ttl = null ): bool {
        $full_key = $this->make_key( $key );
        $ttl      = $ttl ?? $this->default_ttl;

        $this->memory[ $full_key ] = $value;

        return set_transient( $full_key, $value, $ttl );
    }

    /**
     * Delete a cached value.
     */
    public function delete( string $key ): bool {
        $full_key = $this->make_key( $key );
        unset( $this->memory[ $full_key ] );

        return delete_transient( $full_key );
    }

    /**
     * Flush all plugin transients.
     */
    public function flush(): bool {
        global $wpdb;

        $this->memory = [];

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_{$this->prefix}%'
                OR option_name LIKE '_transient_timeout_{$this->prefix}%'"
        );

        return true;
    }

    /**
     * Build a cache key with prefix, ensuring it stays under 172 chars.
     */
    private function make_key( string $key ): string {
        $full = $this->prefix . $key;

        if ( strlen( $full ) > 150 ) {
            $full = $this->prefix . md5( $key );
        }

        return $full;
    }

    public function get_default_ttl(): int {
        return $this->default_ttl;
    }
}
