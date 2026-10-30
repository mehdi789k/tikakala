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

define( 'MOBILE_CHI_VERSION', '1.2.3' );

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
	// A parent counts as available only if its folder exists on disk with a
	// valid style.css — independent of the (possibly stale) theme registry.
	$found = '';
	foreach ( mobile_chi_parent_candidates() as $slug ) {
		if ( $slug !== basename( get_stylesheet_directory() ) && mobile_chi_parent_dir_exists( $slug ) ) {
			$found = $slug;
			break;
		}
	}
	$cached = ( '' !== $found );
	return $cached;
}

/* -------------------------------------------------------------------------
 * Robust parent detection + self-healing header.
 *
 *  - No WoodMart installed  -> strip "Template:" => valid standalone theme,
 *    WordPress NEVER lists it under «پوسته‌های خراب».
 *  - WoodMart installed     -> ensure "Template: woodmart" => real child.
 *
 * Runs at load (one DB option check per request) and on theme upload/switch.
 * ---------------------------------------------------------------------- */

/**
* Direct filesystem check for an installed parent theme folder with a
* valid style.css header. Independent of the WP theme registry cache, so
* freshly uploaded themes are detected immediately.
*
* @param string $slug Theme directory slug (e.g. 'woodmart').
* @return bool
*/
function mobile_chi_parent_dir_exists( $slug ) {
if ( '' === $slug || ! is_string( $slug ) ) {
return false;
}
$dir   = trailingslashit( WP_CONTENT_DIR ) . 'themes/' . sanitize_key( $slug );
$style = $dir . '/style.css';
if ( ! is_file( $style ) ) {
return false;
}
$contents = file_get_contents( $style, false, null, 0, 8192 ); // phpcs:ignore
if ( false === $contents ) {
return false;
}
return (bool) preg_match( '/^[ \t\/]*Theme\s*Name\s*:/mi', $contents );
}

/**
* Candidate parent slugs to look for (declared one first, then common ones).
*
* @return string[]
*/
function mobile_chi_parent_candidates() {
$candidates = array();

// Declared value straight from our own style.css header (fresh read).
$own = (string) file_get_contents( get_stylesheet_directory() . '/style.css', false, null, 0, 4096 ); // phpcs:ignore
		if ( preg_match( '/^Template:[ 	]*([A-Za-z0-9_-]+)/mi', $own, $m ) ) {
			$candidates[] = $m[1];
		}
// Registry value (may be cached/stale — harmless duplicate).
$declared = wp_get_theme()->get( 'Template' );
if ( $declared ) {
$candidates[] = $declared;
}
$candidates = array_merge( $candidates, array( 'woodmart', 'woodmart-lite' ) );

return array_values( array_unique( array_filter( $candidates ) ) );
}

/**
* Core routine that keeps the style.css header in sync with reality.
*
* @return string The mode applied: 'child' or 'standalone'.
*/
function mobile_chi_sync_header_with_reality() {
$style    = get_stylesheet_directory() . '/style.css';
$has_real = '';
foreach ( mobile_chi_parent_candidates() as $slug ) {
if ( $slug !== basename( get_stylesheet_directory() ) && mobile_chi_parent_dir_exists( $slug ) ) {
$has_real = $slug;
break;
}
}

$mode = $has_real ? 'child' : 'standalone';

if ( ! wp_is_writable( dirname( $style ) ) || ! is_writable( $style ) ) {
return $mode; // Read-only FS: runtime logic still works via fallbacks.
}

$css = file_get_contents( $style ); // phpcs:ignore
if ( false === $css ) {
return $mode;
}

$changed = false;
if ( 'standalone' === $mode ) {
$patched = preg_replace( '/^Template:[ \t]*[A-Za-z0-9_-]+.*$/mi', '', $css, 1, $count );
if ( $count > 0 ) {
$css     = $patched;
$changed = true;
}
} else {
// Make sure the correct Template line exists / points to the found parent.
if ( preg_match( '/^Template:[ \t]*([A-Za-z0-9_-]+)/mi', $css, $m ) ) {
if ( $m[1] !== $has_real ) {
$css     = preg_replace( '/^Template:[ \t]*[A-Za-z0-9_-]+.*$/mi', 'Template: ' . $has_real, $css, 1 );
$changed = true;
}
} else {
// Re-insert the Template line right before Version:.
$css     = preg_replace( '/^(Version:)/mi', "Template: {$has_real}\n$1", $css, 1 );
$changed = true;
}
}

if ( $changed ) {
file_put_contents( $style, $css ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
wp_clean_themes_cache();
delete_transient( 'theme_transients' );
}

return $mode;
}

/**
* Cheap bootstrap: re-sync only when the stored mode disagrees with the
* filesystem, or when we have never run before. This makes the broken-theme
* notice disappear after a single page refresh — even without activating
* the theme — because functions.php of installed themes loads on admin
* screens too (wp_get_themes scans them), and directly on front end.
*/
function mobile_chi_maybe_sync_header() {
$saved = get_option( 'mobile_chi_runtime_mode' );
$style = get_stylesheet_directory() . '/style.css';

// If header already has no Template line, standalone mode needs no work.
$needs_check = ( false === $saved );
if ( ! $needs_check ) {
$has_template = (bool) preg_match( '/^Template:[ \t]*[A-Za-z0-9_-]+/mi', (string) file_get_contents( $style, false, null, 0, 4096 ) ); // phpcs:ignore
$parent_now = '';
foreach ( mobile_chi_parent_candidates() as $slug ) {
if ( $slug !== basename( get_stylesheet_directory() ) && mobile_chi_parent_dir_exists( $slug ) ) {
$parent_now = $slug;
break;
}
}
$real_mode = $parent_now ? 'child' : 'standalone';
// Stored mode disagrees with reality, or header state is inconsistent → resync.
if ( $saved !== $real_mode || ( 'child' === $real_mode && ! $has_template ) ) {
$needs_check = true;
}
}

if ( $needs_check ) {
update_option( 'mobile_chi_runtime_mode', mobile_chi_sync_header_with_reality(), false );
}
}
mobile_chi_maybe_sync_header();
add_action( 'load-themes.php', 'mobile_chi_sync_header_with_reality' );
add_action( 'upgrader_process_complete', 'mobile_chi_sync_header_with_reality' );
add_action( 'switch_theme', 'mobile_chi_sync_header_with_reality' );

/**
* Boot after activation: prepare standalone fallbacks if needed.
*/
function mobile_chi_bootstrap() {
	delete_option( 'mobile_chi_runtime_mode' ); // Force fresh detection on activation.
mobile_chi_sync_header_with_reality();
update_option( 'mobile_chi_runtime_mode', mobile_chi_is_standalone() ? 'standalone' : 'child', false );

if ( mobile_chi_is_standalone() ) {
set_theme_mod( 'mobile_chi_mode', 'standalone' );
mobile_chi_fallback_dir(); // Pre-create fallback templates on activation.
} else {
set_theme_mod( 'mobile_chi_mode', 'child' );
}
}
add_action( 'after_switch_theme', 'mobile_chi_bootstrap', 1 );

/**
* Manual mode controls for site admins (no FTP needed):
*
*   ?mc-mode=child       -> force WoodMart child mode (Template line restored)
*   ?mc-mode=standalone  -> force standalone mode (Template line removed)
*
* Only usable by users with manage_themes capability.
*/
function mobile_chi_handle_mode_switch() {
if ( ! isset( $_GET['mc-mode'], $_GET['mc-nonce'] ) || ! is_user_logged_in() || ! current_user_can( 'manage_themes' ) ) { // phpcs:ignore
return;
}
if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['mc-nonce'] ) ), 'mobile_chi_switch_mode' ) ) {
return;
}
$wanted = sanitize_key( sanitize_text_field( wp_unslash( $_GET['mc-mode'] ) ) ); // phpcs:ignore
if ( ! in_array( $wanted, array( 'child', 'standalone' ), true ) ) {
return;
}

if ( 'child' === $wanted ) {
$found = '';
foreach ( mobile_chi_parent_candidates() as $slug ) {
if ( $slug !== basename( get_stylesheet_directory() ) && mobile_chi_parent_dir_exists( $slug ) ) {
$found = $slug;
break;
}
}
if ( '' === $found ) {
add_action( 'admin_notices', 'mobile_chi_no_parent_error_notice' );
return;
}
update_option( 'mobile_chi_force_standalone', 0 );
mobile_chi_sync_header_with_reality();
wp_safe_redirect( add_query_arg( 'mc-switched', 'child', admin_url( 'themes.php' ) ) );
exit;
}

update_option( 'mobile_chi_force_standalone', 1 );
mobile_chi_sync_header_with_reality();
mobile_chi_fallback_dir();
wp_safe_redirect( add_query_arg( 'mc-switched', 'standalone', admin_url( 'themes.php' ) ) );
exit;
}
add_action( 'admin_init', 'mobile_chi_handle_mode_switch' );

/**
* Error notice when child mode was requested but no parent theme exists.
*/
function mobile_chi_no_parent_error_notice() {
echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'موبایل چی:', 'mobile-chi' ) . '</strong> ' . esc_html__( 'قالب مادر WoodMart نصب نیست؛ ابتدا آن را نصب کنید، سپس دوباره حالت فرزند را انتخاب نمایید.', 'mobile-chi' ) . '</p></div>';
}

/**
* Success notice after a manual mode switch.
*/
function mobile_chi_switched_notice() {
if ( empty( $_GET['mc-switched'] ) ) { // phpcs:ignore
return;
}
$mode = sanitize_key( sanitize_text_field( wp_unslash( $_GET['mc-switched'] ) ) ); // phpcs:ignore
$msg  = 'child' === $mode
? __( 'پوسته به حالت «فرزند WoodMart» تغییر کرد و خط Template در هدر بازمی‌گردد.', 'mobile-chi' )
: __( 'پوسته به حالت «مستقل» تغییر کرد؛ قالب‌های جایگزین داخلی فعال هستند.', 'mobile-chi' );
echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'موبایل چی:', 'mobile-chi' ) . '</strong> ' . esc_html( $msg ) . '</p></div>';
}
add_action( 'admin_notices', 'mobile_chi_switched_notice' );

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
<?php echo mobile_chi_mode_switch_links(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
</div>
<?php
}
add_action( 'admin_notices', 'mobile_chi_mode_notice' );

/**
* Render switch-mode links (nonce protected) for admins.
*
* @return string HTML fragment or empty string without capability.
*/
function mobile_chi_mode_switch_links() {
if ( ! current_user_can( 'manage_themes' ) ) {
return '';
}
$nonce = wp_create_nonce( 'mobile_chi_switch_mode' );
$child = add_query_arg( array( 'mc-mode' => 'child', 'mc-nonce' => $nonce ), admin_url( 'themes.php' ) );
$alone = add_query_arg( array( 'mc-mode' => 'standalone', 'mc-nonce' => $nonce ), admin_url( 'themes.php' ) );
return ' <a href="' . esc_url( $child ) . '" class="button button-small">' . esc_html__( 'تبدیل به حالت فرزند WoodMart', 'mobile-chi' ) . '</a>'
. ' <a href="' . esc_url( $alone ) . '" class="button button-small">' . esc_html__( 'حالت مستقل', 'mobile-chi' ) . '</a>';
}

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

	// Home / front page fallback with demo-like sections.
	$front = <<<'PHP_FRONT'
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
echo '<section class="mc-hero"><div class="container"><h1 class="entry-title">' . esc_html( get_bloginfo( 'name' ) ) . '</h1><p>' . esc_html( get_bloginfo( 'description' ) ) . '</p></div></section>';
if ( class_exists( 'WooCommerce' ) ) {
        echo '<div class="container">';
        if ( taxonomy_exists( 'product_cat' ) ) {
                $mc_terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 8 ) );
                if ( ! is_wp_error( $mc_terms ) && $mc_terms ) {
                        echo '<h2 class="tk-section-title"><span class="tk-accent"></span>' . esc_html__( 'دسته‌بندی‌ها', 'mobile-chi' ) . '</h2><ul class="tk-grid tk-cols-4 mc-cat-grid">';
                        foreach ( $mc_terms as $mc_term ) {
                                echo '<li class="product-category"><a href="' . esc_url( get_term_link( $mc_term ) ) . '"><span>' . esc_html( $mc_term->name ) . '</span></a></li>';
                        }
                        echo '</ul>';
                }
        }
        $mc_q = new WP_Query( array( 'post_type' => 'product', 'posts_per_page' => 8, 'orderby' => 'date', 'order' => 'DESC' ) );
        if ( $mc_q->have_posts() ) {
                echo '<h2 class="tk-section-title"><span class="tk-accent"></span>' . esc_html__( 'جدیدترین محصولات', 'mobile-chi' ) . '</h2><ul class="products tk-grid tk-cols-4">';
                while ( $mc_q->have_posts() ) { $mc_q->the_post(); wc_get_template_part( 'content', 'product' ); }
                echo '</ul>';
                wp_reset_postdata();
        }
        echo '</div>';
}
if ( have_posts() ) {
        echo '<div class="container">';
        while ( have_posts() ) : the_post();
                echo '<article class="mc-entry"><h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2><p>' . esc_html( wp_trim_words( get_the_excerpt(), 30 ) ) . '</p></article>';
        endwhile;
        echo '</div>';
}
get_footer();
PHP_FRONT;

	// 404 fallback page.
	$notfound = <<<'PHP_404'
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
echo '<div class="container mc-404"><h1 class="entry-title">' . esc_html__( 'صفحه پیدا نشد (۴۰۴)', 'mobile-chi' ) . '</h1><p>' . esc_html__( 'متأسفانه صفحه‌ای که به آن مراجعه کردید وجود ندارد. از منو یا جستجو استفاده کنید.', 'mobile-chi' ) . '</p>';
echo '<form role="search" method="get" class="mc-search-form" action="' . esc_url( home_url( '/' ) ) . '"><input type="search" name="s" placeholder="' . esc_attr__( 'جستجو در سایت…', 'mobile-chi' ) . '" /><button type="submit">' . esc_html__( 'جستجو', 'mobile-chi' ) . '</button></form></div>';
get_footer();
PHP_404;

	// Single product fallback (standalone WooCommerce).
	$single_product = <<<'PHP_SP'
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
while ( have_posts() ) : the_post();
        global $product;
        echo '<div class="container mc-single-product">';
        the_title( '<h1 class="entry-title">', '</h1>' );
        if ( $product && is_a( $product, 'WC_Product' ) ) {
                echo '<div class="mc-sp-price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
                if ( has_post_thumbnail() ) { echo '<div class="mc-sp-img">' . get_the_post_thumbnail( null, 'large' ) . '</div>'; }
                the_content();
                echo '<form class="cart mc-add-cart" method="post"><button type="submit" name="add-to-cart" value="' . esc_attr( get_the_ID() ) . '">' . esc_html__( 'افزودن به سبد خرید', 'mobile-chi' ) . '</button></form>';
        } else {
                the_content();
        }
        echo '</div>';
endwhile;
get_footer();
PHP_SP;

	// Cart / checkout / account pages render their own shortcode content.
	$wc_page = <<<'PHP_WCP'
<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
echo '<div class="container mc-wc-page">';
the_title( '<h1 class="entry-title">', '</h1>' );
if ( have_posts() ) { while ( have_posts() ) : the_post(); the_content(); endwhile; }
echo '</div>';
get_footer();
PHP_WCP;

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

	$templates['front-page.php'] = $front;
	$templates['404.php']       = $notfound;

	if ( class_exists( 'WooCommerce' ) ) {
		$templates['archive-product.php']               = $shop;
		$templates['taxonomy-product_cat.php']          = $shop;
		$templates['single-product.php']                = $single_product;
		$templates['woocommerce-cart-fallback.php']     = $wc_page;
		$templates['woocommerce-checkout-fallback.php'] = $wc_page;
		$templates['woocommerce-account-fallback.php']  = $wc_page;
	}

	$marker = '/* mobile-chi-fallback ' . MOBILE_CHI_VERSION . ' */';

	foreach ( $templates as $file => $content ) {
		$path = trailingslashit( $dir ) . $file;
		// Write new files and refresh ones left over from an older version.
		if ( ! file_exists( $path ) || false === strpos( (string) file_get_contents( $path ), $marker ) ) { // phpcs:ignore
			file_put_contents( $path, $marker . "\n" . $content . "\n" ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
		}
	}

	return $dir;
}

/**
* Map any WP request to one of our fallback template file names.
*
* @param string $base Current template basename (unused; kept for filters).
* @return string Fallback template basename.
*/
function mobile_chi_map_fallback_base( $base ) {
if ( function_exists( 'is_product' ) && is_singular( 'product' ) ) {
return 'single-product.php';
}
if ( function_exists( 'is_cart' ) && is_cart() ) {
return 'woocommerce-cart-fallback.php';
}
if ( function_exists( 'is_checkout' ) && is_checkout() ) {
return 'woocommerce-checkout-fallback.php';
}
if ( function_exists( 'is_account_page' ) && is_account_page() ) {
return 'woocommerce-account-fallback.php';
}
if ( is_404() ) {
return '404.php';
}
if ( is_search() ) {
return 'search.php';
}
if ( is_archive() || is_post_type_archive() || is_tax() ) {
if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
return 'taxonomy-product_cat.php';
}
if ( function_exists( 'is_shop' ) && is_shop() ) {
return 'archive-product.php';
}
return 'archive.php';
}
if ( is_front_page() || is_home() ) {
return 'front-page.php';
}
if ( is_singular( 'page' ) ) {
return 'page.php';
}
if ( is_singular() ) {
return 'single.php';
}
return 'index.php';
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

$dir    = mobile_chi_fallback_dir();
$base   = mobile_chi_map_fallback_base( basename( $template ) );
$chosen = trailingslashit( $dir ) . $base;

if ( ! file_exists( $chosen ) || 0 !== validate_file( $chosen ) ) {
$chosen = trailingslashit( $dir ) . 'index.php';
}

return file_exists( $chosen ) && 0 === validate_file( $chosen ) ? $chosen : $template;
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
	// NOTE: 'title-tag' is reserved for parent themes in child mode;
	// requesting it here would trigger _doing_it_wrong(). The standalone
	// fallback header prints <title> via wp_head through core defaults.
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
