<?php
/**
* Tikakala Elementor widgets.
*
* Registers home-page widgets that reproduce the structure observed in the
* demo layout (https://demo-serverha.ir/woodmart/tikakala/):
*   - Tikakala Hero Slider (wraps WoodMart slider shortcode)
*   - Tikakala Categories  (product category grid, 4 columns like the demo)
*   - Tikakala Products    (latest/sale/best-selling product grid)
*
* All widgets degrade gracefully when WooCommerce/Elementor are absent.
*
* @package tikakala-child
*/

defined( 'ABSPATH' ) || exit;

/**
* Check whether Elementor is active.
*
* @return bool
*/
function tikakala_is_elementor_active() {
	return did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Widget_Base' );
}

if ( ! tikakala_is_elementor_active() ) {
	return;
}

/**
* Register our Elementor category.
*
* @param \Elementor\Elements_Manager $elements_manager Elements manager.
*/
function tikakala_register_elementor_category( $elements_manager ) {
	$elements_manager->add_category(
	'tikakala',
	array(
	'title' => __( 'تیکا کالا', 'tikakala-child' ),
	'icon'  => 'fa fa-plug',
),
	1
);
}
add_action( 'elementor/elements/categories_registered', 'tikakala_register_elementor_category' );

/**
* Base class shared by all Tikakala widgets.
*/
abstract class Tikakala_Widget_Base extends \Elementor\Widget_Base {

	/**
	* Demo primary color (#435fcb extracted from --wd-primary-color).
	*
	* @return string
	*/
	protected function demo_primary_color() {
		return '#435fcb';
	}

	/**
	* Shared style controls.
	*/
	protected function register_style_controls() {
		$this->start_controls_section(
		'tk_section_style_title',
		array(
		'label' => __( 'عنوان بخش', 'tikakala-child' ),
		'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
	)
	);

		$this->add_control(
		'tk_title_color',
		array(
		'label'     => __( 'رنگ عنوان', 'tikakala-child' ),
		'type'      => \Elementor\Controls_Manager::COLOR,
		'default'   => '#111111',
		'selectors' => array(
		'{{WRAPPER}} .tk-section-title h2' => 'color: {{VALUE}};',
	),
	)
	);

		$this->add_control(
		'tk_accent_color',
		array(
		'label'     => __( 'رنگ تأکیدی', 'tikakala-child' ),
		'type'      => \Elementor\Controls_Manager::COLOR,
		'default'   => $this->demo_primary_color(),
		'selectors' => array(
		'{{WRAPPER}} .tk-section-title .tk-accent' => 'background-color: {{VALUE}};',
	),
	)
	);

		$this->end_controls_section();
	}

	/**
	* Render a section heading styled like the demo.
	*
	* @param string $title Heading text.
	*/
	protected function render_section_title( $title ) {
		if ( '' === $title ) {
			return;
		}
		echo '<div class="tk-section-title wd-heading-title"><span class="tk-accent"></span><h2>' . esc_html( $title ) . '</h2></div>';
	}
}

/**
* Hero / Slider widget.
*/
class Tikakala_Hero_Widget extends Tikakala_Widget_Base {

	public function get_name() {
		return 'tikakala-hero';
	}

	public function get_title() {
		return __( 'اسلایدر تیکا کالا', 'tikakala-child' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_categories() {
		return array( 'tikakala' );
	}

	protected function register_controls() {
		$this->start_controls_section(
		'tk_section_hero',
		array( 'label' => __( 'اسلایدر', 'tikakala-child' ) )
	);

		$this->add_control(
		'tk_slider_id',
		array(
		'label'   => __( 'شناسه اسلایدر WoodMart', 'tikakala-child' ),
		'type'    => \Elementor\Controls_Manager::TEXT,
		'default' => '',
	)
	);

		$this->add_control(
		'tk_fallback_text',
		array(
		'label'   => __( 'متن جایگزین (بدون اسلایدر)', 'tikakala-child' ),
		'type'    => \Elementor\Controls_Manager::TEXTAREA,
		'default' => __( 'تیکا کالا؛ فروشگاه اینترنتی', 'tikakala-child' ),
	)
	);

		$this->end_controls_section();

		$this->register_style_controls();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$id       = isset( $settings['tk_slider_id'] ) ? trim( $settings['tk_slider_id'] ) : '';

		echo '<section class="tikakala-hero-widget">';
		if ( $id && shortcode_exists( 'woodmart_slider' ) ) {
			echo do_shortcode( '[woodmart_slider id="' . esc_attr( $id ) . '"]' );
			} else {
			echo '<div class="tikakala-hero-fallback" style="background:var(--tk-primary-bg,#ebeffa);padding:60px 20px;text-align:center;">'
			. '<h1 style="margin:0;">' . esc_html( $settings['tk_fallback_text'] ) . '</h1></div>';
		}
		echo '</section>';
	}
}

/**
* Product categories widget.
*/
class Tikakala_Categories_Widget extends Tikakala_Widget_Base {

	public function get_name() {
		return 'tikakala-categories';
	}

	public function get_title() {
		return __( 'دسته‌بندی محصولات تیکا کالا', 'tikakala-child' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return array( 'tikakala' );
	}

	protected function register_controls() {
		$this->start_controls_section(
		'tk_section_cats',
		array( 'label' => __( 'دسته‌بندی‌ها', 'tikakala-child' ) )
	);

		$this->add_control(
		'tk_title',
		array(
		'label'   => __( 'عنوان بخش', 'tikakala-child' ),
		'type'    => \Elementor\Controls_Manager::TEXT,
		'default' => __( 'دسته‌بندی‌ها', 'tikakala-child' ),
	)
	);

		$this->add_control(
		'tk_count',
		array(
		'label'   => __( 'تعداد دسته‌بندی‌ها', 'tikakala-child' ),
		'type'    => \Elementor\Controls_Manager::NUMBER,
		'default' => 8,
		'min'     => 1,
		'max'     => 24,
	)
	);

		$this->add_control(
		'tk_columns',
		array(
		'label'   => __( 'تعداد ستون‌ها', 'tikakala-child' ),
		'type'    => \Elementor\Controls_Manager::SELECT,
		'default' => '4',
		'options' => array(
		'3' => '3',
		'4' => '4',
		'5' => '5',
		'6' => '6',
	),
	)
	);

		$this->end_controls_section();

		$this->register_style_controls();
	}

	protected function render() {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();
		$cats     = get_terms(
		array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'number'     => intval( $settings['tk_count'] ),
	)
	);

		if ( is_wp_error( $cats ) || empty( $cats ) ) {
			return;
		}

		echo '<section class="tikakala-cats-widget">';
		$this->render_section_title( $settings['tk_title'] );
		echo '<ul class="products tk-grid tk-cols-' . esc_attr( $settings['tk_columns'] ) . '">';
		foreach ( $cats as $cat ) {
			echo '<li class="product-category">';
			echo '<a href="' . esc_url( get_term_link( $cat ) ) . '">';
			$thumb_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
			if ( $thumb_id ) {
				echo wp_kses_post( wp_get_attachment_image( $thumb_id, 'medium' ) );
			}
			echo '<strong>' . esc_html( $cat->name ) . '</strong>';
			echo '</a></li>';
		}
		echo '</ul></section>';
	}
}

/**
* Products widget (latest / sale / best selling).
*/
class Tikakala_Products_Widget extends Tikakala_Widget_Base {

	public function get_name() {
		return 'tikakala-products';
	}

	public function get_title() {
		return __( 'محصولات تیکا کالا', 'tikakala-child' );
	}

	public function get_icon() {
		return 'eicon-products';
	}

	public function get_categories() {
		return array( 'tikakala' );
	}

	protected function register_controls() {
		$this->start_controls_section(
		'tk_section_products',
		array( 'label' => __( 'محصولات', 'tikakala-child' ) )
	);

		$this->add_control(
		'tk_title',
		array(
		'label'   => __( 'عنوان بخش', 'tikakala-child' ),
		'type'    => \Elementor\Controls_Manager::TEXT,
		'default' => __( 'جدیدترین محصولات', 'tikakala-child' ),
	)
	);

		$this->add_control(
		'tk_source',
		array(
		'label'   => __( 'منبع محصولات', 'tikakala-child' ),
		'type'    => \Elementor\Controls_Manager::SELECT,
		'default' => 'latest',
		'options' => array(
		'latest' => __( 'جدیدترین', 'tikakala-child' ),
		'sale'   => __( 'تخفیف‌دار', 'tikakala-child' ),
		'best'   => __( 'پرفروش‌ترین', 'tikakala-child' ),
	),
	)
	);

		$this->add_control(
		'tk_count',
		array(
		'label'   => __( 'تعداد', 'tikakala-child' ),
		'type'    => \Elementor\Controls_Manager::NUMBER,
		'default' => 8,
		'min'     => 4,
		'max'     => 24,
	)
	);

		$this->end_controls_section();

		$this->register_style_controls();
	}

	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();
		$args     = array(
		'post_type'      => 'product',
		'posts_per_page' => intval( $settings['tk_count'] ),
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

		switch ( $settings['tk_source'] ) {
			case 'sale':
			$sale_ids     = wc_get_product_ids_on_sale();
			$args['post__in'] = $sale_ids;
			if ( empty( $sale_ids ) ) {
				return;
			}
			break;
			case 'best':
			$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
			$args['orderby']  = 'meta_value_num';
			break;
		}

		$loop = new WP_Query( $args );
		if ( ! $loop->have_posts() ) {
			return;
		}

		echo '<section class="tikakala-products-widget">';
		$this->render_section_title( $settings['tk_title'] );
		woocommerce_product_loop_start( false );
		while ( $loop->have_posts() ) {
			$loop->the_post();
			wc_get_template_part( 'content', 'product' );
		}
		woocommerce_product_loop_end( false );
		wp_reset_postdata();
		echo '</section>';
	}
}

/**
* Register all Tikakala widgets.
*
* @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
*/
function tikakala_register_elementor_widgets( $widgets_manager ) {
	$widgets_manager->register( new \Tikakala_Hero_Widget() );
	$widgets_manager->register( new \Tikakala_Categories_Widget() );
	$widgets_manager->register( new \Tikakala_Products_Widget() );
}
add_action( 'elementor/widgets/register', 'tikakala_register_elementor_widgets' );
