<?php
/**
 * Search settings tab
 *
 * @package BuscaKoha
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bk-card">
    <h2 class="bk-card-title">
        <span class="dashicons dashicons-search"></span>
        <?php esc_html_e( 'Configurações de Busca', 'busca-koha' ); ?>
    </h2>
    <p class="bk-card-description">
        <?php esc_html_e( 'Defina como a busca se comporta: modo de operação, cache, limites e índices disponíveis.', 'busca-koha' ); ?>
    </p>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'bk_search_settings' );
        do_settings_sections( 'bk_search_settings' );
        ?>

        <hr>

        <h3><?php esc_html_e( 'Cache', 'busca-koha' ); ?></h3>
        <p class="description" style="margin-bottom:12px;">
            <?php esc_html_e( 'O cache armazena resultados de busca para evitar chamadas repetidas à API do Koha, melhorando a performance.', 'busca-koha' ); ?>
        </p>
        <button type="button" class="button button-secondary" id="bk-clear-cache">
            <span class="dashicons dashicons-trash"></span>
            <?php esc_html_e( 'Limpar Cache', 'busca-koha' ); ?>
        </button>
        <span id="bk-cache-status" class="bk-inline-status"></span>

        <hr>

        <?php submit_button( __( 'Salvar Configurações', 'busca-koha' ) ); ?>
    </form>
</div>
