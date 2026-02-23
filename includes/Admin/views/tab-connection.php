<?php
/**
 * Connection tab
 *
 * @package BuscaKoha
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="bk-card">
    <h2 class="bk-card-title">
        <span class="dashicons dashicons-admin-links"></span>
        <?php esc_html_e( 'Conexão com o Koha', 'busca-koha' ); ?>
    </h2>
    <p class="bk-card-description">
        <?php esc_html_e( 'Configure a URL e as credenciais de acesso à API REST do Koha. Essas informações são necessárias para o modo de busca AJAX.', 'busca-koha' ); ?>
    </p>

    <form method="post" action="options.php" id="bk-connection-form">
        <?php
        settings_fields( 'bk_connection_settings' );
        do_settings_sections( 'bk_connection_settings' );
        ?>

        <div class="bk-button-row">
            <?php submit_button( __( 'Salvar Configurações', 'busca-koha' ), 'primary', 'submit', false ); ?>
            <button type="button" class="button button-secondary" id="bk-test-connection">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e( 'Testar Conexão', 'busca-koha' ); ?>
            </button>
        </div>
    </form>
</div>

<!-- Connection test modal -->
<div id="bk-test-modal" class="bk-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Teste de conexão', 'busca-koha' ); ?>">
    <div class="bk-modal-overlay"></div>
    <div class="bk-modal-content">
        <div class="bk-modal-header">
            <h3><?php esc_html_e( 'Testando Conexão com o Koha', 'busca-koha' ); ?></h3>
            <button type="button" class="bk-modal-close" aria-label="<?php esc_attr_e( 'Fechar', 'busca-koha' ); ?>">&times;</button>
        </div>
        <div class="bk-modal-body">
            <p class="bk-test-description">
                <?php esc_html_e( 'Verificando cada etapa da conexão com o servidor Koha...', 'busca-koha' ); ?>
            </p>
            <div class="bk-test-steps" id="bk-test-steps">
                <!-- Steps injected by JS -->
            </div>
            <div class="bk-test-summary" id="bk-test-summary" style="display:none;"></div>
        </div>
        <div class="bk-modal-footer">
            <button type="button" class="button button-primary bk-modal-close-btn"><?php esc_html_e( 'Fechar', 'busca-koha' ); ?></button>
        </div>
    </div>
</div>
