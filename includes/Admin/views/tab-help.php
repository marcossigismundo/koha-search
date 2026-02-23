<?php
/**
 * Help tab
 *
 * @package BuscaKoha
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$mode = get_option( 'bk_search_mode', 'ajax' );
?>

<div class="bk-card">
    <h2 class="bk-card-title">
        <span class="dashicons dashicons-editor-help"></span>
        <?php esc_html_e( 'Ajuda & Documentação', 'busca-koha' ); ?>
    </h2>

    <div class="bk-help-grid">
        <!-- Shortcodes -->
        <div class="bk-help-section">
            <h3><?php esc_html_e( 'Shortcodes', 'busca-koha' ); ?></h3>

            <div class="bk-help-item">
                <h4><code>[busca_koha]</code></h4>
                <p><?php esc_html_e( 'Exibe o formulário de busca no acervo. Este é o shortcode principal.', 'busca-koha' ); ?></p>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Parâmetro', 'busca-koha' ); ?></th>
                            <th><?php esc_html_e( 'Valores', 'busca-koha' ); ?></th>
                            <th><?php esc_html_e( 'Descrição', 'busca-koha' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>mostrar_titulo</code></td>
                            <td><code>true</code> | <code>false</code></td>
                            <td><?php esc_html_e( 'Exibe/oculta o título "Pesquise em nosso acervo"', 'busca-koha' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>mostrar_bibliotecas</code></td>
                            <td><code>true</code> | <code>false</code></td>
                            <td><?php esc_html_e( 'Exibe/oculta o filtro de bibliotecas', 'busca-koha' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>layout</code></td>
                            <td><code>inline</code> | <code>modal</code></td>
                            <td><?php esc_html_e( 'Modo de exibição dos resultados', 'busca-koha' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>modo</code></td>
                            <td><code>ajax</code> | <code>redirect</code></td>
                            <td><?php esc_html_e( 'Modo de busca (AJAX ou redirecionamento)', 'busca-koha' ); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bk-help-item">
                <h4><code>[koha_iframe]</code></h4>
                <p><?php esc_html_e( 'Incorpora a página completa do OPAC do Koha em um iframe.', 'busca-koha' ); ?></p>
            </div>
        </div>

        <!-- REST API -->
        <div class="bk-help-section">
            <h3><?php esc_html_e( 'API REST', 'busca-koha' ); ?></h3>
            <p class="description"><?php esc_html_e( 'Endpoints disponíveis para integração externa:', 'busca-koha' ); ?></p>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Método', 'busca-koha' ); ?></th>
                        <th><?php esc_html_e( 'Endpoint', 'busca-koha' ); ?></th>
                        <th><?php esc_html_e( 'Permissão', 'busca-koha' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>POST</code></td>
                        <td><code>/wp-json/busca-koha/v1/search</code></td>
                        <td><?php esc_html_e( 'Pública (rate-limited)', 'busca-koha' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>GET</code></td>
                        <td><code>/wp-json/busca-koha/v1/libraries</code></td>
                        <td><?php esc_html_e( 'Pública', 'busca-koha' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>POST</code></td>
                        <td><code>/wp-json/busca-koha/v1/libraries/import</code></td>
                        <td><?php esc_html_e( 'Administrador', 'busca-koha' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>POST</code></td>
                        <td><code>/wp-json/busca-koha/v1/admin/test-connection</code></td>
                        <td><?php esc_html_e( 'Administrador', 'busca-koha' ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- System info -->
        <div class="bk-help-section">
            <h3><?php esc_html_e( 'Informações do Sistema', 'busca-koha' ); ?></h3>
            <table class="widefat striped">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e( 'Versão do Plugin', 'busca-koha' ); ?></strong></td>
                        <td><?php echo esc_html( BUSCA_KOHA_VERSION ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'WordPress', 'busca-koha' ); ?></strong></td>
                        <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'PHP', 'busca-koha' ); ?></strong></td>
                        <td><?php echo esc_html( PHP_VERSION ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Modo de busca', 'busca-koha' ); ?></strong></td>
                        <td><?php echo esc_html( $mode === 'ajax' ? __( 'AJAX', 'busca-koha' ) : __( 'Redirecionamento', 'busca-koha' ) ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'OpenSSL', 'busca-koha' ); ?></strong></td>
                        <td><?php echo function_exists( 'openssl_encrypt' ) ? '<span class="bk-status-ok">&#10003;</span>' : '<span class="bk-status-error">&#10007;</span>'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Links -->
        <div class="bk-help-section">
            <h3><?php esc_html_e( 'Links Úteis', 'busca-koha' ); ?></h3>
            <ul class="bk-links-list">
                <li>
                    <a href="https://api.koha-community.org/" target="_blank" rel="noopener">
                        <span class="dashicons dashicons-external"></span>
                        <?php esc_html_e( 'Documentação da API REST do Koha', 'busca-koha' ); ?>
                    </a>
                </li>
                <li>
                    <a href="https://wiki.koha-community.org/wiki/Using_the_Koha_REST_API" target="_blank" rel="noopener">
                        <span class="dashicons dashicons-external"></span>
                        <?php esc_html_e( 'Guia de uso da API REST', 'busca-koha' ); ?>
                    </a>
                </li>
                <li>
                    <a href="https://koha-community.org/" target="_blank" rel="noopener">
                        <span class="dashicons dashicons-external"></span>
                        <?php esc_html_e( 'Comunidade Koha', 'busca-koha' ); ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
