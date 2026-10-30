<?php
/**
 * Template Name: Tikakala Home
 * Description: صفحه اصلی با چیدمان شبیه دمو تیکا کالا (اسلایدر، دسته‌بندی‌ها، محصولات).
 *
 * Requires WoodMart + WooCommerce. Falls back gracefully if they are missing.
 *
 * @package tikakala-child
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="tikakala-home-wrap container">

	<?php
	/* -------------------------------------------------
	 * 1) Hero / Slider area.
	 * Uses WoodMart's slider widget shortcode if available,
	 * otherwise shows a simple placeholder banner.
	 * ------------------------------------------------- */
	if ( is_active_sidebar( 'tikakala-slider' ) ) {
		dynamic_sidebar( 'tikakala-slider' );
	} else {
		?>
		<section class="tikakala-hero" style="background:var(--tk-primary-bg);padding:40px;text-align:center;margin-bottom:30px;">
			<h1><?php esc_html_e( 'تیکا کالا', 'tikakala-child' ); ?></h1>
			<p><?php esc_html_e( 'منطقه اسلایدر اصلی – از برگه‌ها > خانهک > ویجت اسلایدر استفاده کنید.', 'tikakala-child' ); ?></p>
		</section>
		<?php
	}
	?>

	<?php
	/* -------------------------------------------------
	 * 2) Product categories grid (like demo cat design).
	 * ------------------------------------------------- */
	$cats = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => 8,
		)
	);

	if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) :
		?>
		<section class="tikakala-cats product-grid cols-4 row-gap">
			<h2 class="wd-heading-title"><?php esc_html_e( 'دسته‌بندی‌ها', 'tikakala-child' ); ?></h2>
			<ul class="products">
				<?php foreach ( $cats as $cat ) : ?>
					<li class="product-category">
						<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
							<?php
							if ( function_exists( 'woocommerce_thumbnail' ) ) {
								$thumb_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
								if ( $thumb_id ) {
									echo wp_kses_post( wp_get_attachment_image( $thumb_id, 'medium' ) );
								}
							}
							?>
							<strong><?php echo esc_html( $cat->name ); ?></strong>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	endif;
	?>

	<?php
	/* -------------------------------------------------
	 * 3) Latest products loop (4 columns like the demo).
	 * ------------------------------------------------- */
	if ( class_exists( 'WooCommerce' ) ) :
		$args  = array(
			'post_type'      => 'product',
			'posts_per_page' => 16,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		$loop  = new WP_Query( $args );
		if ( $loop->have_posts() ) :
			?>
			<section class="tikakala-latest product-grid cols-4">
				<h2 class="wd-heading-title"><?php esc_html_e( 'جدیدترین محصولات', 'tikakala-child' ); ?></h2>
				<?php woocommerce_product_loop_start(); ?>
				<?php while ( $loop->have_posts() ) : $loop->the_post(); ?>
					<?php wc_get_template_part( 'content', 'product' ); ?>
				<?php endwhile; ?>
				<?php woocommerce_product_loop_end(); ?>
				<?php wp_reset_postdata(); ?>
			</section>
			<?php
		endif;
	endif;
	?>

</div><!-- .tikakala-home-wrap -->

<?php
get_footer();
