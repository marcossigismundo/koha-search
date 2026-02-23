<?php
/**
 * Display/appearance tab
 *
 * @package BuscaKoha
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bk-card">
    <h2 class="bk-card-title">
        <span class="dashicons dashicons-art"></span>
        <?php esc_html_e( 'Aparência', 'busca-koha' ); ?>
    </h2>
    <p class="bk-card-description">
        <?php esc_html_e( 'Personalize a aparência do formulário de busca e dos resultados.', 'busca-koha' ); ?>
    </p>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'bk_display_settings' );
        do_settings_sections( 'bk_display_settings' );
        submit_button( __( 'Salvar Configurações', 'busca-koha' ) );
        ?>
    </form>
</div>
