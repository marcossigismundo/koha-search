<?php
/**
 * AES-256-CBC encryption for credentials
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Encryption {

    private const METHOD = 'aes-256-cbc';
    private const SEPARATOR = '::';

    /**
     * Encrypt a plaintext string.
     */
    public static function encrypt( string $plaintext ): string {
        if ( $plaintext === '' ) {
            return '';
        }

        $key = self::get_key();
        $iv  = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::METHOD ) );

        $encrypted = openssl_encrypt( $plaintext, self::METHOD, $key, 0, $iv );

        if ( $encrypted === false ) {
            return '';
        }

        return base64_encode( $iv . self::SEPARATOR . $encrypted );
    }

    /**
     * Decrypt a ciphertext string.
     */
    public static function decrypt( string $ciphertext ): string {
        if ( $ciphertext === '' ) {
            return '';
        }

        $decoded = base64_decode( $ciphertext, true );
        if ( $decoded === false ) {
            return '';
        }

        $parts = explode( self::SEPARATOR, $decoded, 2 );
        if ( count( $parts ) !== 2 ) {
            return '';
        }

        $key       = self::get_key();
        $iv        = $parts[0];
        $encrypted = $parts[1];

        $decrypted = openssl_decrypt( $encrypted, self::METHOD, $key, 0, $iv );

        return $decrypted !== false ? $decrypted : '';
    }

    /**
     * Derive encryption key from WordPress salts.
     */
    private static function get_key(): string {
        $salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'busca-koha-default-key';
        $salt .= defined( 'AUTH_SALT' ) ? AUTH_SALT : 'busca-koha-default-salt';

        return hash( 'sha256', $salt, true );
    }
}
