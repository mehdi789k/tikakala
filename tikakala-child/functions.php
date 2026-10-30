<?php
/**
* Tikakala Child Theme (WoodMart) functions.
*
* Styles/settings inspired by the analyzed demo:
* https://demo-serverha.ir/woodmart/tikakala/
*
* @package tikakala-child
*/

defined( 'ABSPATH' ) || exit;

define( 'TIKAKALA_CHILD_VERSION', '1.1.0' );

/**
* Load parent (WoodMart) + child styles.
*/
function tikakala_child_enqueue_assets() {
	// Parent stylesheet (WoodMart).
	wp_enqueue_style( 'tikakala-parent-style', get_template_directory_uri() . '/style.css', array(), TIKAKALA_CHILD_VERSION );

	// Child RTL stylesheet.
	$child_css = is_rtl() ? '/assets/css/tikakala-rtl.css' : '/assets/css/tikakala-ltr.css';
	wp_enqueue_style( 'tikakala-child-style', get_stylesheet_directory_uri() . $child_css, array( 'tikakala-parent-style' ), TIKAKALA_CHILD_VERSION );

	// Child JS (header sticky behaviour etc.).
	wp_enqueue_script( 'tikakala-child-script', get_stylesheet_directory_uri() . '/assets/js/tikakala.js', array(), TIKAKALA_CHILD_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'tikakala_child_enqueue_assets', 20 );

/**
* Register navigation menus used by the demo layout.
*/
function tikakala_child_register_menus() {
	register_nav_menus(
	array(
	'main-menu'       => __( 'منوی اصلی', 'tikakala-child' ),
	'top-bar-menu'    => __( 'منوی نوار بالا', 'tikakala-child' ),
	'footer-menu'     => __( 'منوی فوتر', 'tikakala-child' ),
	'mobile-menu'     => __( 'منوی موبایل', 'tikakala-child' ),
)
);
}
add_action( 'after_setup_theme', 'tikakala_child_register_menus' );

/**
* Register widget area used by the home template (hero/slider region).
*/
function tikakala_child_widgets_init() {
	register_sidebar(
	array(
	'name'          => __( 'منطقه اسلایدر خانه تیکا کالا', 'tikakala-child' ),
	'id'            => 'tikakala-slider',
	'description'   => __( 'ویجت اسلایدر WoodMart را اینجا قرار دهید.', 'tikakala-child' ),
	'before_widget' => '<div id="%1$s" class="widget %2$s">',
	'after_widget'  => '</div>',
	'before_title'  => '<h2 class="widget-title">',
	'after_title'   => '</h2>',
)
);
}
add_action( 'widgets_init', 'tikakala_child_widgets_init' );

/**
* Add body classes to help scope custom styles.
*/
function tikakala_child_body_classes( $classes ) {
	$classes[] = 'tikakala-child';
	if ( is_front_page() ) {
		$classes[] = 'tikakala-home';
	}
	return $classes;
}
add_filter( 'body_class', 'tikakala_child_body_classes' );

/**
* WooCommerce support flags (mirror demo feature set).
*/
function tikakala_child_woocommerce_support() {
	add_theme_support(
	'woocommerce',
	array(
	'thumbnail_image_width' => 450,
	'single_image_width'    => 800,
	'product_grid'          => array(
	'default_rows'    => 4,
	'min_rows'        => 1,
	'default_columns' => 4,
	'min_columns'     => 1,
	'max_columns'     => 6,
),
)
);
}
add_action( 'after_setup_theme', 'tikakala_child_woocommerce_support', 11 );

/**
* Product loop columns / count tuned for the demo grid.
*/
function tikakala_child_loop_columns() {
	return 4;
}
add_filter( 'loop_shop_columns', 'tikakala_child_loop_columns' );

function tikakala_child_products_per_page() {
	return 16;
}
add_filter( 'loop_shop_per_page', 'tikakala_child_products_per_page', 20 );

/**
* Helper: primary color used across the theme (matches --wd-primary-color in demo).
*/
function tikakala_get_primary_color() {
	return '#435fcb';
}

/**
* Output inline CSS variables so other templates can reference them.
*/
function tikakala_child_output_css_vars() {
	$primary = apply_filters( 'tikakala_primary_color', tikakala_get_primary_color() );
	printf(
	'<style id="tikakala-css-vars">:root{--tk-primary:%1$s;}</style>',
	esc_attr( $primary )
);
}
add_action( 'wp_head', 'tikakala_child_output_css_vars', 99 );

/**
* Internal module includes (Elementor widgets, footer badges).
*/
require_once get_stylesheet_directory() . '/inc/tikakala-elementor-widgets.php';
require_once get_stylesheet_directory() . '/inc/tikakala-footer.php';

/**
* Localize the child script with demo-derived settings.
*/
function tikakala_child_localize_script() {
	wp_localize_script(
	'tikakala-child-script',
	'TikakalaChild',
	array(
	'primaryColor' => apply_filters( 'tikakala_primary_color', tikakala_get_primary_color() ),
	'rtl'          => is_rtl(),
	'version'      => TIKAKALA_CHILD_VERSION,
)
);
}
add_action( 'wp_enqueue_scripts', 'tikakala_child_localize_script', 21 );

/**
* Optional Persian digit display for prices (demo uses Persian numerals).
* Enable via the "نمایش ارقام فارسی در قیمت‌ها" checkbox in Customizer.
*/
function tikakala_child_persian_prices( $price ) {
	if ( ! get_theme_mod( 'tikakala_persian_numbers', true ) ) {
		return $price;
	}
	return str_replace(
	array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
	array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
	$price
);
}
add_filter( 'woocommerce_in_cart_product_total', 'tikakala_child_persian_prices', 20 );
add_filter( 'woocommerce_get_price_html', 'tikakala_child_persian_prices', 20 );

/**
* Customizer section for Tikakala options.
*
* @param WP_Customize_Manager $wp_customize Customizer manager.
*/
function tikakala_child_customize_register( $wp_customize ) {
	$wp_customize->add_section(
	'tikakala_options',
	array(
	'title'    => __( 'تنظیمات تیکا کالا', 'tikakala-child' ),
	'priority' => 30,
)
);

	$wp_customize->add_setting(
	'tikakala_primary_color',
	array(
	'default'           => '#435fcb',
	'sanitize_callback' => 'sanitize_hex_color',
)
);
	$wp_customize->add_control(
	new WP_Customize_Color_Control(
	$wp_customize,
	'tikakala_primary_color',
	array(
	'label'   => __( 'رنگ اصلی (مطابق دمو)', 'tikakala-child' ),
	'section' => 'tikakala_options',
)
)
);

	$wp_customize->add_setting(
	'tikakala_persian_numbers',
	array(
	'default'           => true,
	'sanitize_callback' => 'rest_sanitize_boolean',
)
);
	$wp_customize->add_control(
	'tikakala_persian_numbers',
	array(
	'label'   => __( 'نمایش ارقام فارسی در قیمت‌ها', 'tikakala-child' ),
	'section' => 'tikakala_options',
	'type'    => 'checkbox',
)
);
}
add_action( 'customize_register', 'tikakala_child_customize_register' );

/**
* Let the Customizer color override the helper used for CSS vars.
*/
function tikakala_child_customizer_primary( $color ) {
	$value = get_theme_mod( 'tikakala_primary_color', '' );
	return $value ? $value : $color;
}
add_filter( 'tikakala_primary_color', 'tikakala_child_customizer_primary', 10, 1 );
