<?php
/**
 * Main settings page shell with tab navigation
 *
 * @package BuscaKoha
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$controller  = new \BuscaKoha\Admin\Admin_Controller( BUSCA_KOHA_VERSION );
$current_tab = $controller->get_current_tab();

$tabs = [
    'connection' => [ 'label' => __( 'Conexão', 'busca-koha' ),     'icon' => 'dashicons-admin-links' ],
    'libraries'  => [ 'label' => __( 'Bibliotecas', 'busca-koha' ), 'icon' => 'dashicons-building' ],
    'search'     => [ 'label' => __( 'Busca', 'busca-koha' ),       'icon' => 'dashicons-search' ],
    'display'    => [ 'label' => __( 'Aparência', 'busca-koha' ),   'icon' => 'dashicons-art' ],
    'help'       => [ 'label' => __( 'Ajuda', 'busca-koha' ),       'icon' => 'dashicons-editor-help' ],
];
?>

<div class="wrap bk-admin-wrap">
    <h1 class="bk-admin-title">
        <span class="dashicons dashicons-book-alt"></span>
        <?php esc_html_e( 'Busca Koha', 'busca-koha' ); ?>
        <span class="bk-version"><?php echo esc_html( 'v' . BUSCA_KOHA_VERSION ); ?></span>
    </h1>

    <nav class="nav-tab-wrapper bk-tabs">
        <?php foreach ( $tabs as $slug => $tab ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=busca-koha&tab=' . $slug ) ); ?>"
               class="nav-tab <?php echo $slug === $current_tab ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
                <?php echo esc_html( $tab['label'] ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="bk-tab-content">
        <?php
        $view_file = BUSCA_KOHA_PATH . 'includes/Admin/views/tab-' . $current_tab . '.php';
        if ( file_exists( $view_file ) ) {
            include $view_file;
        }
        ?>
    </div>
</div>
