<?php
/**
* Mobile Chi (WoodMart) — موبایل چی functions.
*
* Styles/settings extracted from the analyzed demo layout:
* https://demo-serverha.ir/woodmart/tikakala/
*
* The theme works in two modes:
*  1. Child mode      – WoodMart (or another parent theme) is installed.
*  2. Standalone mode – no parent theme found; the theme falls back to its
*     own minimal templates so WP never shows a broken-theme notice.
*
* @package mobile-chi
*/

defined( 'ABSPATH' ) || exit;

define( 'MOBILE_CHI_VERSION', '1.2.0' );

/* -------------------------------------------------------------------------
 * Parent-theme detection & standalone fallback
 * ---------------------------------------------------------------------- */

/**
* Find an installed parent theme to run on top of.
*
* Checks the "Template:" header first, then common alternatives
* (woodmart / woodmart-lite). Returns '' when running standalone.
*
* @return string Parent theme directory slug or empty string.
*/
function mobile_chi_find_parent() {
	$theme = wp_get_theme();
	$stack = array();

	$template = $theme->get( 'Template' );
	if ( $template ) {
		$stack[] = $template;
	}
	$stack[] = 'woodmart';
	$stack[] = 'woodmart-lite';

	foreach ( $stack as $slug ) {
		if ( $slug === $theme->get_stylesheet() ) {
			continue; // Never treat ourselves as the parent.
		}
		if ( wp_get_theme( $slug )->exists() ) {
			return $slug;
		}
	}

	return '';
}

/**
* Is a real parent theme (e.g. WoodMart) available?
*
* @return bool
*/
function mobile_chi_has_parent() {
	static $cached = null;
	if ( null !== $cached ) {
		return $cached;
	}
	$cached = ( '' !== mobile_chi_find_parent() );
	return $cached;
}

/**
* Boot after activation: keep the theme usable even without WoodMart.
*
* When the declared parent is missing we remember that the theme runs in
* standalone mode (fallback templates below), instead of leaving WordPress
* with the «پوستهٔ مادر در دسترس نیست» broken-theme state.
*/
function mobile_chi_bootstrap() {
	if ( mobile_chi_has_parent() && ! get_option( 'mobile_chi_force_standalone', false ) ) {
		set_theme_mod( 'mobile_chi_mode', 'child' );
		} else {
		set_theme_mod( 'mobile_chi_mode', 'standalone' );
		mobile_chi_fallback_dir(); // Pre-create fallback templates on activation.
	}
}
add_action( "after_switch_theme", "mobile_chi_bootstrap", 9 );

/**
* Admin notice explaining the current mode (dismissible).
*/
function mobile_chi_mode_notice() {
	if ( ! mobile_chi_is_standalone() ) {
		return;
	}
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<strong><?php esc_html_e( 'موبایل چی:', 'mobile-chi' ); ?></strong>
			<?php esc_html_e( 'قالب مادر WoodMart پیدا نشد؛ پوسته در حالت مستقل با قالب‌های جایگزین ساده اجرا می‌شود. برای تجربه کامل دمو، WoodMart را نصب کنید (نمایش > پوسته‌ها > افزودن پوسته).', 'mobile-chi' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'mobile_chi_mode_notice' );

/**
* Allow the user to run this theme fully standalone even when WoodMart IS
* installed (e.g. to test the fallback templates). Option lives inside the
* Customizer section «تنظیمات موبایل چی».
*
* @return bool True when forced standalone via option, or when no parent exists.
*/
function mobile_chi_is_standalone() {
	if ( get_option( 'mobile_chi_force_standalone', false ) ) {
		return true;
	}
	return ! mobile_chi_has_parent();
}

/**
* Minimal fallback templates for standalone mode.
*
* Written once into uploads/mobile-chi-fallback and routed through the
* template_include filter, so the theme folder itself stays clean.
*
* @return string Absolute path of the fallback directory.
*/
function mobile_chi_fallback_dir() {
	$uploads = wp_upload_dir();
	$dir     = trailingslashit( $uploads['basedir'] ) . 'mobile-chi-fallback';

	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}

	$header = <<<'PHP_HEADER'
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="whb-top-bar whb-main-header mc-topbar"><div class="container mc-topbar-inner">
<a class="mc-logo-link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span class="mc-logo-text"><?php bloginfo( 'name' ); ?></span></a>
<nav class="mc-nav"><?php wp_nav_menu( array( 'theme_location' => 'main-menu', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ) ); ?></nav>
</div></header>
<main id="main" class="mc-main"><div class="container">
PHP_HEADER;

	$footer = <<<'PHP_FOOTER'
</div></main>
<footer class="mc-footer"><div class="container"><div class="mc-footer-cols">
<div><h4><?php bloginfo( 'name' ); ?></h4><p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p></div>
<div><h4><?php esc_html_e( 'دسترسی سریع', 'mobile-chi' ); ?></h4><?php wp_nav_menu( array( 'theme_location' => 'footer-menu', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ) ); ?></div>
</div><div class="mc-copyright">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?> &mdash; <?php esc_html_e( 'قدرت گرفته از موبایل چی', 'mobile-chi' ); ?></div></div></footer>
<?php wp_footer(); ?>
</body>
</html>
PHP_FOOTER;

	$index = <<<'PHP_INDEX'
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
if ( have_posts() ) :
	while ( have_posts() ) : the_post();
		echo '<article class="mc-entry">';
		the_title( '<h1 class="entry-title">', '</h1>' );
		the_content();
		echo '</article>';
	endwhile;
	else :
	echo '<p>' . esc_html__( 'محتوایی یافت نشد.', 'mobile-chi' ) . '</p>';
	endif;
get_footer();
PHP_INDEX;

	$templates = array(
		'header.php'  => $header,
		'footer.php'  => $footer,
		'index.php'   => $index,
		'page.php'    => $index,
		'singular.php' => $index,
		'archive.php' => $index,
		'single.php'  => $index,
	);

	foreach ( $templates as $file => $content ) {
		$path = trailingslashit( $dir ) . $file;
		if ( ! file_exists( $path ) ) {
			file_put_contents( $path, $content . "\n" ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
		}
	}

	return $dir;
}

/**
* Route WP to the fallback templates when running standalone.
*
* @param string $template Resolved template path.
* @return string
*/
function mobile_chi_template_loader( $template ) {
	if ( ! mobile_chi_is_standalone() ) {
		return $template; // WoodMart handles front-end templates.
	}

	$base     = basename( $template );
	$fallback = trailingslashit( mobile_chi_fallback_dir() ) . $base;

	if ( file_exists( $fallback ) && 0 === validate_file( $fallback ) ) {
		return $fallback;
	}

	$index = trailingslashit( mobile_chi_fallback_dir() ) . 'index.php';
	return file_exists( $index ) ? $index : $template;
}
add_filter( 'template_include', 'mobile_chi_template_loader', 99 );

/**
* Load stylesheets (parent style when available) + child assets.
*/
function mobile_chi_child_enqueue_assets() {
	$deps = array();

	// Parent stylesheet (WoodMart) — only when a real parent exists and we run as a child.
	if ( mobile_chi_has_parent() && ! mobile_chi_is_standalone() ) {
		wp_enqueue_style( 'mobile-chi-parent-style', get_template_directory_uri() . '/style.css', array(), MOBILE_CHI_VERSION );
		$deps[] = 'mobile-chi-parent-style';
	}

	// Base styles of this theme (colors, fonts, RTL helpers).
	wp_enqueue_style( 'mobile-chi-base', get_stylesheet_directory_uri() . '/style.css', $deps, MOBILE_CHI_VERSION );

	// Directional stylesheet.
	$child_css = is_rtl() ? '/assets/css/mobile-chi-rtl.css' : '/assets/css/mobile-chi-ltr.css';
	wp_enqueue_style( 'mobile-chi-style', get_stylesheet_directory_uri() . $child_css, array( 'mobile-chi-base' ), MOBILE_CHI_VERSION );

	// Child JS (header sticky behaviour etc.).
	wp_enqueue_script( 'mobile-chi-script', get_stylesheet_directory_uri() . '/assets/js/mobile-chi.js', array(), MOBILE_CHI_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'mobile_chi_child_enqueue_assets', 20 );

/**
* Register navigation menus used by the demo layout.
*/
function mobile_chi_child_register_menus() {
	register_nav_menus(
	array(
	'main-menu'       => __( 'منوی اصلی', 'mobile-chi' ),
	'top-bar-menu'    => __( 'منوی نوار بالا', 'mobile-chi' ),
	'footer-menu'     => __( 'منوی فوتر', 'mobile-chi' ),
	'mobile-menu'     => __( 'منوی موبایل', 'mobile-chi' ),
)
);
}
add_action( 'after_setup_theme', 'mobile_chi_child_register_menus' );

/**
* Register widget area used by the home template (hero/slider region).
*/
function mobile_chi_child_widgets_init() {
	register_sidebar(
	array(
	'name'          => __( 'منطقه اسلایدر خانه موبایل چی', 'mobile-chi' ),
	'id'            => 'mobile_chi-slider',
	'description'   => __( 'ویجت اسلایدر WoodMart را اینجا قرار دهید.', 'mobile-chi' ),
	'before_widget' => '<div id="%1$s" class="widget %2$s">',
	'after_widget'  => '</div>',
	'before_title'  => '<h2 class="widget-title">',
	'after_title'   => '</h2>',
)
);
}
add_action( 'widgets_init', 'mobile_chi_child_widgets_init' );

/**
* Add body classes to help scope custom styles.
*/
function mobile_chi_child_body_classes( $classes ) {
	$classes[] = 'mobile-chi';
	if ( is_front_page() ) {
		$classes[] = 'mobile_chi-home';
	}
	if ( mobile_chi_is_standalone() ) {
		$classes[] = 'mc-standalone';
	} else {
		$classes[] = 'mc-with-parent';
	}
	return $classes;
}
add_filter( 'body_class', 'mobile_chi_child_body_classes' );

/**
* Standalone safety net: when running without WoodMart we add the theme
* supports that the parent would normally provide (title-tag, post
* thumbnails, custom logo…). This keeps the fallback templates fully
* functional and prevents fatal errors on sites missing the parent theme.
*/
function mobile_chi_standalone_theme_supports() {
	if ( ! mobile_chi_is_standalone() ) {
		return; // Parent theme declares its own supports.
	}
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
}
add_action( 'after_setup_theme', 'mobile_chi_standalone_theme_supports', 5 );

/**
* WooCommerce support flags (mirror demo feature set).
*/
function mobile_chi_child_woocommerce_support() {
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
add_action( 'after_setup_theme', 'mobile_chi_child_woocommerce_support', 11 );

/**
* Product loop columns / count tuned for the demo grid.
*/
function mobile_chi_child_loop_columns() {
	return 4;
}
add_filter( 'loop_shop_columns', 'mobile_chi_child_loop_columns' );

function mobile_chi_child_products_per_page() {
	return 16;
}
add_filter( 'loop_shop_per_page', 'mobile_chi_child_products_per_page', 20 );

/**
* Helper: primary color used across the theme (matches --wd-primary-color in demo).
*/
function mobile_chi_get_primary_color() {
	return '#435fcb';
}

/**
* Output inline CSS variables so other templates can reference them.
*/
function mobile_chi_child_output_css_vars() {
	$primary = apply_filters( 'mobile_chi_primary_color', mobile_chi_get_primary_color() );
	printf(
	'<style id="mobile-chi-css-vars">:root{--tk-primary:%1$s;}</style>',
	esc_attr( $primary )
);
}
add_action( 'wp_head', 'mobile_chi_child_output_css_vars', 99 );

/**
* Internal module includes (Elementor widgets, footer badges).
*/
require_once get_stylesheet_directory() . '/inc/mobile-chi-elementor-widgets.php';
require_once get_stylesheet_directory() . '/inc/mobile-chi-footer.php';

/**
* Localize the child script with demo-derived settings.
*/
function mobile_chi_child_localize_script() {
	wp_localize_script(
	'mobile-chi-script',
	'MobileChi',
	array(
	'primaryColor' => apply_filters( 'mobile_chi_primary_color', mobile_chi_get_primary_color() ),
	'rtl'          => is_rtl(),
	'version'      => MOBILE_CHI_VERSION,
)
);
}
add_action( 'wp_enqueue_scripts', 'mobile_chi_child_localize_script', 21 );

/**
* Optional Persian digit display for prices (demo uses Persian numerals).
* Enable via the "نمایش ارقام فارسی در قیمت‌ها" checkbox in Customizer.
*/
function mobile_chi_child_persian_prices( $price ) {
	if ( ! get_theme_mod( 'mobile_chi_persian_numbers', true ) ) {
		return $price;
	}
	return str_replace(
	array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
	array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
	$price
);
}
add_filter( 'woocommerce_in_cart_product_total', 'mobile_chi_child_persian_prices', 20 );
add_filter( 'woocommerce_get_price_html', 'mobile_chi_child_persian_prices', 20 );

/**
* Customizer section for Mobile Chi options.
*
* @param WP_Customize_Manager $wp_customize Customizer manager.
*/
function mobile_chi_child_customize_register( $wp_customize ) {
	$wp_customize->add_section(
	'mobile_chi_options',
	array(
	'title'    => __( 'تنظیمات موبایل چی', 'mobile-chi' ),
	'priority' => 30,
)
);

	$wp_customize->add_setting(
	'mobile_chi_primary_color',
	array(
	'default'           => '#435fcb',
	'sanitize_callback' => 'sanitize_hex_color',
)
);
	$wp_customize->add_control(
	new WP_Customize_Color_Control(
	$wp_customize,
	'mobile_chi_primary_color',
	array(
	'label'   => __( 'رنگ اصلی (مطابق دمو)', 'mobile-chi' ),
	'section' => 'mobile_chi_options',
)
)
);

	$wp_customize->add_setting(
	'mobile_chi_persian_numbers',
	array(
	'default'           => true,
	'sanitize_callback' => 'rest_sanitize_boolean',
)
);
	$wp_customize->add_control(
	'mobile_chi_persian_numbers',
	array(
	'label'   => __( 'نمایش ارقام فارسی در قیمت‌ها', 'mobile-chi' ),
	'section' => 'mobile_chi_options',
	'type'    => 'checkbox',
)
);
/* Standalone mode switch (useful when WoodMart is not installed). */
$wp_customize->add_setting(
'mobile_chi_force_standalone',
array(
'default'           => false,
'sanitize_callback' => 'rest_sanitize_boolean',
'type'              => 'option',
)
);
$wp_customize->add_control(
'mobile_chi_force_standalone',
array(
'label'       => __( 'اجرای مستقل بدون قالب مادر', 'mobile-chi' ),
'description' => __( 'در صورت فعال بودن، پوسته با قالب‌های جایگزین داخلی اجرا می‌شود حتی اگر WoodMart نصب باشد.', 'mobile-chi' ),
'section'     => 'mobile_chi_options',
'type'        => 'checkbox',
)
);
}
add_action( 'customize_register', 'mobile_chi_child_customize_register' );

/**
* Let the Customizer color override the helper used for CSS vars.
*/
function mobile_chi_child_customizer_primary( $color ) {
	$value = get_theme_mod( 'mobile_chi_primary_color', '' );
	return $value ? $value : $color;
}
add_filter( 'mobile_chi_primary_color', 'mobile_chi_child_customizer_primary', 10, 1 );
