<?php
/**
 * Libraries tab
 *
 * @package BuscaKoha
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$library_service = \BuscaKoha\Core\Plugin::get_instance()->get_service( 'library' );
$libraries       = $library_service->get_libraries();
$last_import     = $library_service->get_last_import();
?>

<div class="bk-card">
    <h2 class="bk-card-title">
        <span class="dashicons dashicons-building"></span>
        <?php esc_html_e( 'Gerenciar Bibliotecas', 'busca-koha' ); ?>
    </h2>
    <p class="bk-card-description">
        <?php esc_html_e( 'Configure as bibliotecas disponíveis no filtro de busca. Você pode importar automaticamente da API do Koha ou gerenciar manualmente.', 'busca-koha' ); ?>
    </p>

    <div class="bk-button-row" style="margin-bottom:16px;">
        <button type="button" class="button button-secondary" id="bk-import-libraries">
            <span class="dashicons dashicons-download"></span>
            <?php esc_html_e( 'Importar do Koha', 'busca-koha' ); ?>
        </button>
        <button type="button" class="button button-secondary" id="bk-add-library">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e( 'Adicionar Biblioteca', 'busca-koha' ); ?>
        </button>
        <button type="button" class="button button-secondary" id="bk-load-defaults">
            <span class="dashicons dashicons-undo"></span>
            <?php esc_html_e( 'Restaurar Padrão', 'busca-koha' ); ?>
        </button>

        <?php if ( $last_import ) : ?>
            <span class="bk-last-import">
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: %s: date/time */
                        __( 'Última importação: %s', 'busca-koha' ),
                        wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_import ) )
                    )
                );
                ?>
            </span>
        <?php endif; ?>
    </div>

    <form method="post" action="options.php" id="bk-libraries-form">
        <?php settings_fields( 'bk_connection_settings' ); ?>
        <input type="hidden" name="bk_libraries_json_data" id="bk-libraries-json" value="">

        <table class="widefat bk-libraries-table" id="bk-libraries-table">
            <thead>
                <tr>
                    <th class="bk-col-code"><?php esc_html_e( 'Código', 'busca-koha' ); ?></th>
                    <th class="bk-col-name"><?php esc_html_e( 'Nome', 'busca-koha' ); ?></th>
                    <th class="bk-col-actions"><?php esc_html_e( 'Ações', 'busca-koha' ); ?></th>
                </tr>
            </thead>
            <tbody id="bk-libraries-body">
                <?php foreach ( $libraries as $lib ) : ?>
                    <tr class="bk-library-row" data-code="<?php echo esc_attr( $lib['code'] ); ?>" data-name="<?php echo esc_attr( $lib['name'] ); ?>">
                        <td class="bk-col-code">
                            <code><?php echo esc_html( $lib['code'] ); ?></code>
                        </td>
                        <td class="bk-col-name">
                            <?php echo esc_html( $lib['name'] ); ?>
                        </td>
                        <td class="bk-col-actions">
                            <button type="button" class="button button-small bk-edit-library" title="<?php esc_attr_e( 'Editar', 'busca-koha' ); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="button button-small bk-delete-library" title="<?php esc_attr_e( 'Remover', 'busca-koha' ); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="bk-libraries-count">
            <?php
            echo esc_html(
                sprintf(
                    /* translators: %d: number of libraries */
                    _n( '%d biblioteca cadastrada', '%d bibliotecas cadastradas', count( $libraries ), 'busca-koha' ),
                    count( $libraries )
                )
            );
            ?>
        </p>

        <div class="bk-button-row">
            <button type="button" class="button button-primary" id="bk-save-libraries">
                <?php esc_html_e( 'Salvar Bibliotecas', 'busca-koha' ); ?>
            </button>
        </div>
    </form>
</div>
