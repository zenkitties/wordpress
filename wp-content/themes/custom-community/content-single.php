<?php
/**
 * @package cc2
 */

$show_title = true;
$display_page_title_props = get_theme_mod('display_page_title', array() );
$author_image_settings = get_theme_mod('show_author_image' );
$author_avatar = false;
$post_class = get_post_class();


//new __debug( array('settings' => $author_image_settings, 'global settings' => get_theme_mod('show_author_image', array() ) )  );

// search
if( isset( $display_page_title_props['posts'] ) && $display_page_title_props['posts'] != 1 ) {
	$show_title = false;
}

if( $author_image_settings['single_post'] != false ) {
	$author_avatar = cc2_get_author_image();
		
	$post_class[] = 'has-author-avatar';
}


?>

<article id="post-<?php the_ID(); ?>" <?php post_class( $post_class ); ?>>
	
	<header class="page-header">
		<?php if( !empty($show_title) ) : ?>
		<h1 class="page-title"><?php the_title(); ?></h1>
		<?php endif; ?>

		<div class="entry-meta">
			<?php if( !empty($author_avatar) ) : ?>
			<div class="entry-meta-author pull-left">
				<?php echo ( !empty($author_avatar['linked_image']) ? $author_avatar['linked_image'] : $author_avatar['image'] ); ?>
			</div>
			<?php endif; ?>		
			
			<?php _tk_posted_on(); ?>
		</div><!-- .entry-meta -->
	
	
	</header><!-- .entry-header -->

	

	<div class="entry-content">
		<?php the_content(); ?>
		<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . __( 'Pages:', '_tk' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->

	<footer class="entry-meta">
		<?php
			/* translators: used between list items, there is a space after the comma */
			$category_list = get_the_category_list( __( ', ', '_tk' ) );

			/* translators: used between list items, there is a space after the comma */
			$tag_list = get_the_tag_list( '', __( ', ', '_tk' ) );

			if ( ! _tk_categorized_blog() ) {
				// This blog only has 1 category so we just need to worry about tags in the meta text
				if ( '' != $tag_list ) {
					$meta_text = __( 'This entry was tagged %2$s. Bookmark the <a href="%3$s" title="Permalink to %4$s" rel="bookmark">permalink</a>.', '_tk' );
				} else {
					$meta_text = __( 'Bookmark the <a href="%3$s" title="Permalink to %4$s" rel="bookmark">permalink</a>.', '_tk' );
				}

			} else {
				// But this blog has loads of categories so we should probably display them here
				if ( '' != $tag_list ) {
					$meta_text = __( 'This entry was posted in %1$s and tagged %2$s. Bookmark the <a href="%3$s" title="Permalink to %4$s" rel="bookmark">permalink</a>.', '_tk' );
				} else {
					$meta_text = __( 'This entry was posted in %1$s. Bookmark the <a href="%3$s" title="Permalink to %4$s" rel="bookmark">permalink</a>.', '_tk' );
				}

			} // end check for categories on this blog

			printf(
				$meta_text,
				$category_list,
				$tag_list,
				get_permalink(),
				the_title_attribute( 'echo=0' )
			);
		?>

		<?php edit_post_link( __( 'Edit', '_tk' ), '<span class="edit-link">', '</span>' ); ?>
	</footer><!-- .entry-meta -->
</article><!-- #post-## -->
