<?php
/**
* Tikakala footer widget + trust badges.
*
* Reproduces the demo footer structure: brand column, link columns and a
* trust-badge row (enamad-style placeholders) above the copyright bar.
* Markup is injected through WoodMart hooks so the child theme stays
* upgrade-safe.
*
* @package tikakala-child
*/

defined( 'ABSPATH' ) || exit;

/**
* Register the trust-badges sidebar.
*/
function tikakala_footer_widgets_init() {
	register_sidebar(
	array(
	'name'          => __( 'نمادهای اعتماد تیکا کالا', 'tikakala-child' ),
	'id'            => 'tikakala-trust-badges',
	'description'   => __( 'ویجت‌های متنی/تصویری (اینماد، ساماندهی و…) را اینجا قرار دهید.', 'tikakala-child' ),
	'before_widget' => '<div id="%1$s" class="tk-badge-widget %2$s">',
	'after_widget'  => '</div>',
	'before_title'  => '<h4 class="tk-badge-title">',
	'after_title'   => '</h4>',
)
);
}
add_action( 'widgets_init', 'tikakala_footer_widgets_init' );

/**
* Render the trust badges row.
*
* Hooked to woodmart_after_content at low priority so it lands near the
* footer area; falls back to wp_footer when WoodMart is not active.
*/
function tikakala_render_trust_badges() {
	if ( ! is_active_sidebar( 'tikakala-trust-badges' ) ) {
		return;
	}
	echo '<div class="tikakala-trust-badges container"><div class="tk-badges-inner">';
	dynamic_sidebar( 'tikakala-trust-badges' );
	echo '</div></div>';
}

// WoodMart exposes `woodmart_before_footer` when active; otherwise fall back to wp_footer at init time.
add_action(
'init',
function () {
	if ( function_exists( 'woodmart_theme_version' ) || has_filter( 'woodmart_before_footer' ) ) {
		add_action( 'woodmart_before_footer', 'tikakala_render_trust_badges', 5 );
		} else {
		add_action( 'wp_footer', 'tikakala_render_trust_badges', 5 );
	}
},
20
);
