<?php
/**
 * Search widget — displays the Koha search box in sidebars/widget areas
 *
 * @package BuscaKoha
 */

namespace BuscaKoha\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Search_Widget extends \WP_Widget {

    public function __construct() {
        parent::__construct(
            'busca_koha_widget',
            __( 'Busca Koha', 'busca-koha' ),
            [
                'description'                 => __( 'Caixa de busca no acervo do Koha', 'busca-koha' ),
                'customize_selective_refresh' => true,
            ]
        );
    }

    /**
     * Front-end display.
     */
    public function widget( $args, $instance ): void {
        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $instance['title'] ) ) . $args['after_title'];
        }

        // Ensure assets are loaded
        wp_enqueue_script( 'busca-koha-public' );
        wp_enqueue_style( 'busca-koha-public' );

        $show_title     = ! empty( $instance['show_title'] );
        $show_libraries = ! empty( $instance['show_libraries'] );

        $vars = Shortcode_Handler::get_form_vars( [
            'show_title'     => $show_title,
            'show_libraries' => $show_libraries,
        ] );

        // Extract vars for template
        extract( $vars );

        include BUSCA_KOHA_PATH . 'templates/search-form.php';

        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     */
    public function form( $instance ): void {
        $title          = $instance['title'] ?? '';
        $show_title     = isset( $instance['show_title'] ) ? (bool) $instance['show_title'] : true;
        $show_libraries = isset( $instance['show_libraries'] ) ? (bool) $instance['show_libraries'] : true;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Título do widget:', 'busca-koha' ); ?>
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <input type="checkbox"
                   id="<?php echo esc_attr( $this->get_field_id( 'show_title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_title' ) ); ?>"
                   value="1"
                   <?php checked( $show_title ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_title' ) ); ?>">
                <?php esc_html_e( 'Exibir "Pesquise em nosso acervo"', 'busca-koha' ); ?>
            </label>
        </p>
        <p>
            <input type="checkbox"
                   id="<?php echo esc_attr( $this->get_field_id( 'show_libraries' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'show_libraries' ) ); ?>"
                   value="1"
                   <?php checked( $show_libraries ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_libraries' ) ); ?>">
                <?php esc_html_e( 'Exibir filtro de bibliotecas', 'busca-koha' ); ?>
            </label>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values.
     */
    public function update( $new_instance, $old_instance ): array {
        return [
            'title'          => sanitize_text_field( $new_instance['title'] ?? '' ),
            'show_title'     => ! empty( $new_instance['show_title'] ),
            'show_libraries' => ! empty( $new_instance['show_libraries'] ),
        ];
    }
}
