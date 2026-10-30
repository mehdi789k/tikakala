<?php
/**
* Mobile Chi footer widget + trust badges.
*
* Reproduces the demo footer structure: brand column, link columns and a
* trust-badge row (enamad-style placeholders) above the copyright bar.
* Markup is injected through WoodMart hooks so the child theme stays
* upgrade-safe.
*
* @package mobile-chi
*/

defined( 'ABSPATH' ) || exit;

/**
* Register the trust-badges sidebar.
*/
function mobile_chi_footer_widgets_init() {
	register_sidebar(
	array(
	'name'          => __( 'نمادهای اعتماد موبایل چی', 'mobile-chi' ),
	'id'            => 'mobile_chi-trust-badges',
	'description'   => __( 'ویجت‌های متنی/تصویری (اینماد، ساماندهی و…) را اینجا قرار دهید.', 'mobile-chi' ),
	'before_widget' => '<div id="%1$s" class="tk-badge-widget %2$s">',
	'after_widget'  => '</div>',
	'before_title'  => '<h4 class="tk-badge-title">',
	'after_title'   => '</h4>',
)
);
}
add_action( 'widgets_init', 'mobile_chi_footer_widgets_init' );

/**
* Render the trust badges row.
*
* Hooked to woodmart_after_content at low priority so it lands near the
* footer area; falls back to wp_footer when WoodMart is not active.
*/
function mobile_chi_render_trust_badges() {
	if ( ! is_active_sidebar( 'mobile_chi-trust-badges' ) ) {
		return;
	}
	echo '<div class="mobile-chi-trust-badges container"><div class="tk-badges-inner">';
	dynamic_sidebar( 'mobile_chi-trust-badges' );
	echo '</div></div>';
}

// WoodMart exposes `woodmart_before_footer` when active; otherwise fall back to wp_footer at init time.
add_action(
'init',
function () {
	if ( function_exists( 'woodmart_theme_version' ) || has_filter( 'woodmart_before_footer' ) ) {
		add_action( 'woodmart_before_footer', 'mobile_chi_render_trust_badges', 5 );
		} else {
		add_action( 'wp_footer', 'mobile_chi_render_trust_badges', 5 );
	}
},
20
);
