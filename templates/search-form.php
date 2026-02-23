<?php
/**
 * Search form template — matches Koha/Ibram layout
 *
 * Variables available from Shortcode_Handler / Search_Widget:
 *   $show_title, $show_libraries, $libraries, $opac_url, $layout
 *
 * @package BuscaKoha
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$layout = isset( $layout ) ? $layout : 'inline';
$is_modal = ( $layout === 'modal' );
?>

<?php if ( $is_modal ) : ?>
    <!-- Modal trigger button -->
    <button type="button" class="bk-modal-trigger" id="bk-modal-trigger"
            aria-haspopup="dialog"
            aria-label="<?php esc_attr_e( 'Abrir busca no acervo', 'busca-koha' ); ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <?php esc_html_e( 'Pesquisar no Acervo', 'busca-koha' ); ?>
    </button>

    <!-- Modal overlay -->
    <div class="bk-modal-overlay" id="bk-modal-overlay" role="dialog" aria-modal="true"
         aria-label="<?php esc_attr_e( 'Busca no acervo', 'busca-koha' ); ?>" aria-hidden="true">
        <div class="bk-modal-content">
            <button type="button" class="bk-modal-close" id="bk-modal-close"
                    aria-label="<?php esc_attr_e( 'Fechar', 'busca-koha' ); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
<?php endif; ?>

<div class="busca-koha-container">
    <div class="busca-koha-corpo">
        <div class="busca-koha-wrapper">

            <!-- Search box -->
            <div class="busca-caixa-1">
                <?php if ( $show_title ) : ?>
                    <div class="busca-titulo">
                        <p><?php esc_html_e( 'Pesquise em nosso acervo', 'busca-koha' ); ?></p>
                    </div>
                <?php endif; ?>

                <div class="busca-input-wrapper">
                    <div class="busca-campo">
                        <input type="search"
                               id="busca-koha-input"
                               class="busca-input"
                               placeholder="<?php esc_attr_e( 'O que você procura?', 'busca-koha' ); ?>"
                               autocomplete="off"
                               aria-label="<?php esc_attr_e( 'Campo de busca no acervo', 'busca-koha' ); ?>"
                               maxlength="256">
                        <button type="button"
                                class="busca-botao"
                                id="busca-koha-submit"
                                aria-label="<?php esc_attr_e( 'Buscar', 'busca-koha' ); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters & actions -->
            <div class="busca-caixa-2">
                <div class="busca-por">
                    <p><?php esc_html_e( 'Pesquisar por', 'busca-koha' ); ?></p>
                </div>

                <div class="busca-botao-wrapper">
                    <button class="botao-autoridade" type="button" id="bk-authority-btn"
                            aria-label="<?php esc_attr_e( 'Buscar autoridades', 'busca-koha' ); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?php esc_html_e( 'Autoridades', 'busca-koha' ); ?>
                    </button>
                </div>

                <?php if ( $show_libraries && ! empty( $libraries ) ) : ?>
                    <div class="busca-biblioteca-wrapper">
                        <label for="busca-biblioteca-select" class="busca-label">
                            <?php esc_html_e( 'Biblioteca:', 'busca-koha' ); ?>
                        </label>
                        <select id="busca-biblioteca-select" class="busca-select"
                                aria-label="<?php esc_attr_e( 'Filtrar por biblioteca', 'busca-koha' ); ?>">
                            <option value=""><?php esc_html_e( 'Todas as bibliotecas', 'busca-koha' ); ?></option>
                            <?php foreach ( $libraries as $bib ) : ?>
                                <option value="<?php echo esc_attr( $bib['code'] ); ?>">
                                    <?php echo esc_html( $bib['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Screen reader announcements -->
    <div class="screen-reader-text" aria-live="assertive" id="bk-sr-announcements"></div>
</div>

<?php if ( $is_modal ) : ?>
        </div><!-- .bk-modal-content -->
    </div><!-- .bk-modal-overlay -->
<?php endif; ?>
