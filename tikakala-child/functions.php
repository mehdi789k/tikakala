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

define( 'TIKAKALA_CHILD_VERSION', '1.0.0' );

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
