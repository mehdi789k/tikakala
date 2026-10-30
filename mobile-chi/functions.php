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

/* -------------------------------------------------------------------------
 * Self-healing header: remove a stale "Template:" line when the declared
 * parent theme is NOT installed. Without this, WordPress lists the theme
 * under «پوسته‌های خراب» (broken themes) and it cannot be activated.
 * With WoodMart installed, the header stays intact and the theme behaves
 * as a proper child theme.
 * ---------------------------------------------------------------------- */

/**
* Make sure style.css header matches the actual availability of WoodMart.
*/
function mobile_chi_fix_header_on_setup() {
	if ( mobile_chi_has_parent() ) {
		return; // Parent available — keep Template: woodmart.
	}

	$style = get_stylesheet_directory() . '/style.css';
	if ( ! is_writable( $style ) ) {
		return;
	}

	$css = file_get_contents( $style ); // phpcs:ignore
	$css = preg_replace( '/^Template:[ \t]*woodmart.*$/mi', '', $css );

	// Bump the version marker so admins can see the patch happened.
	$css = str_replace( 'Version: 1.2.0', 'Version: 1.2.1', $css );

	file_put_contents( $style, $css ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
	wp_clean_themes_cache();
	delete_transient( 'theme_mods_' . get_stylesheet() );
}
add_action( 'after_setup_theme', 'mobile_chi_fix_header_on_setup', 3 );

/**
* Boot after activation: keep the theme usable even without WoodMart.
*
* When the declared parent is missing we remember that the theme runs in
* standalone mode (fallback templates below), instead of leaving WordPress
* with the «پوستهٔ مادر در دسترس نیست» broken-theme state.
*/
function mobile_chi_bootstrap() {
	mobile_chi_fix_header_on_setup();

	if ( mobile_chi_is_standalone() ) {
		set_theme_mod( 'mobile_chi_mode', 'standalone' );
		mobile_chi_fallback_dir(); // Pre-create fallback templates on activation.
	} else {
		set_theme_mod( 'mobile_chi_mode', 'child' );
	}
}
add_action( 'after_switch_theme', 'mobile_chi_bootstrap', 1 );

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
			<?php esc_html_e( 'قالب مادر WoodMart پیدا نشد؛ پوسته در حالت مستقل با قالب‌های جایگزین داخلی اجرا می‌شود و هیچ بخشی از سایت خطا نمی‌دهد. برای تجربه کامل دمو، WoodMart را نصب کنید (نمایش > پوسته‌ها > افزودن پوسته)؛ پس از نصب، موبایل چی خودکار به حالت فرزند برمی‌گردد.', 'mobile-chi' ); ?>
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

	// Search results template for standalone mode.
	$search = <<<'PHP_SEARCH'
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
echo '<h1 class="entry-title">' . sprintf( esc_html__( 'نتایج جستجو: %s', 'mobile-chi' ), esc_html( get_search_query() ) ) . '</h1>';
echo '<form role="search" method="get" class="mc-search-form" action="' . esc_url( home_url( '/' ) ) . '">
<input type="search" name="s" value="' . esc_attr( get_search_query() ) . '" />
<button type="submit">' . esc_html__( 'جستجو', 'mobile-chi' ) . '</button></form>';
if ( have_posts() ) :
	while ( have_posts() ) : the_post();
		echo '<article class="mc-entry"><a href="' . esc_url( get_permalink() ) . '"><h2>' . esc_html( get_the_title() ) . '</h2></a><p>' . esc_html( wp_trim_words( get_the_excerpt(), 30 ) ) . '</p></article>';
	endwhile;
	echo '<nav class="mc-pagination">' . paginate_links() . '</nav>';
else :
	echo '<p>' . esc_html__( 'نتیجه‌ای برای جستجوی شما یافت نشد.', 'mobile-chi' ) . '</p>';
endif;
get_footer();
PHP_SEARCH;

	// Simple product grid template used when WooCommerce is active but the
	// shop skin of WoodMart is unavailable (standalone mode).
	$shop = <<<'PHP_SHOP'
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
$mc_terms = array();
if ( taxonomy_exists( 'product_cat' ) ) {
	$mc_terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 8 ) );
}
if ( ! is_wp_error( $mc_terms ) && $mc_terms ) : ?>
<h2 class="tk-section-title"><span class="tk-accent"></span><?php esc_html_e( 'دسته‌بندی‌ها', 'mobile-chi' ); ?></h2>
<ul class="tk-grid tk-cols-4 mc-cat-grid">
<?php foreach ( $mc_terms as $mc_term ) : ?>
	<li class="product-category"><a href="<?php echo esc_url( get_term_link( $mc_term ) ); ?>">
		<?php if ( function_exists( 'woocommerce_thumbnail_image_width' ) || true ) { echo woocommerce_get_product_thumbnail(); } ?>
		<span><?php echo esc_html( $mc_term->name ); ?></span>
	</a></li>
<?php endforeach; ?>
</ul>
<?php endif;

$mc_args = array( 'post_type' => 'product', 'posts_per_page' => 16, 'orderby' => 'date', 'order' => 'DESC' );
$mc_q = new WP_Query( $mc_args );
if ( $mc_q->have_posts() ) : ?>
<h2 class="tk-section-title"><span class="tk-accent"></span><?php esc_html_e( 'جدیدترین محصولات', 'mobile-chi' ); ?></h2>
<ul class="products tk-grid tk-cols-4">
<?php while ( $mc_q->have_posts() ) : $mc_q->the_post(); wc_get_template_part( 'content', 'product' ); endwhile; ?>
</ul>
<?php wp_reset_postdata(); else : ?>
<p><?php esc_html_e( 'محصولی برای نمایش وجود ندارد.', 'mobile-chi' ); ?></p>
<?php endif;
get_footer();
PHP_SHOP;

	$templates = array(
		'header.php'   => $header,
		'footer.php'   => $footer,
		'index.php'    => $index,
		'page.php'     => $index,
		'singular.php' => $index,
		'archive.php'  => $index,
		'single.php'   => $index,
		'search.php'   => $search,
	);

	if ( class_exists( 'WooCommerce' ) ) {
		$templates['archive-product.php'] = $shop;
		$templates['taxonomy-product_cat.php'] = $shop;
	}

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
	'<style id="mobile-chi-css-vars">:root{--tk-primary:%1$s;--mc-primary:%1$s;}</style>',
	esc_attr( $primary )
);
}
add_action( 'wp_head', 'mobile_chi_child_output_css_vars', 99 );

/**
* Favicon for standalone mode (inline SVG with the demo primary color).
*/
function mobile_chi_child_site_icon() {
	if ( ! mobile_chi_is_standalone() ) {
		return; // WoodMart handles site icons in child mode.
	}
	$primary = rawurlencode( apply_filters( 'mobile_chi_primary_color', mobile_chi_get_primary_color() ) );
	$svg     = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='14' fill='%24" . $primary . "'/><text x='32' y='44' font-size='34' font-family='Tahoma,Arial' font-weight='bold' text-anchor='middle' fill='white'>M</text></svg>";
	echo '<link rel="icon" href="data:image/svg+xml,' . esc_attr( $svg ) . '" type="image/svg+xml" />' . "\n";
}
add_action( 'wp_head', 'mobile_chi_child_site_icon', 5 );

/**
* Internal module includes (Elementor widgets, footer badges).
*/
require_once get_stylesheet_directory() . '/inc/elementor-widgets.php';
require_once get_stylesheet_directory() . '/inc/footer-widgets.php';

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
