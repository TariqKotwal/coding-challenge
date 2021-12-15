<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_types = get_post_types( [ 'public' => true ] );
		$class_name = $attributes['className'];
		ob_start();
		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<h2>Post Counts</h2>
			<ul>
			<?php
			foreach ( $post_types as $post_type_slug ) :
				$post_type_object = get_post_type_object( $post_type_slug );
				$query            = new WP_Query(
					[
						'post_type'              => $post_type_slug,
						'post_status'            => 'any',
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'fields'                 => 'ids',
					]
				);
				?>
				<li>
				<?php
				$label_string = ( 1 === $query->found_posts ) ? $post_type_object->labels->singular_name : $post_type_object->labels->name;
				/* translators: %d: total posts */
				echo sprintf( esc_html( _n( 'There is %1$d %2$s.', 'There are %1$d %2$s.', $query->found_posts, 'site-counts' ) ), esc_html( $query->found_posts ), esc_html( $label_string ) );
				?>
				</li>
			<?php endforeach; ?>
			</ul><p><?php echo ( ! empty( $_GET['post_id'] ) && is_numeric( $_GET['post_id'] ) ) ? 'The current post ID is ' . esc_html( sanitize_text_field( $_GET['post_id'] ) ) . '.' : ''; ?></p>

			<?php
			$query = new WP_Query(
				[
					'post_type'              => [ 'post', 'page' ],
					'post_status'            => 'any',
					'date_query'             => [
						[
							'hour'    => 9,
							'compare' => '>=',
						],
						[
							'hour'    => 17,
							'compare' => '<=',
						],
					],
					'tag'                    => 'foo',
					'category_name'          => 'baz',
					'posts_per_page'         => 6,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);
			$max_posts = ( $query->found_posts > 5 ) ? 5 : $query->found_posts;
			if ( $query->found_posts ) :
				?>
				<h2>
					<?php
					/* translators: %d: total posts */
					echo sprintf( esc_html( _n( '%d post with the tag of foo and the category of baz', '%d posts with the tag of foo and the category of baz', $max_posts, 'site-counts' ) ), esc_html( $max_posts ) );
					?>
				</h2>
				<ul>
				<?php
				$current_post_id = get_the_ID();
				$cntr            = 0;
				foreach ( $query->posts as $post ) :
					if ( $post->ID !== $current_post_id && $cntr < $max_posts ) {
						$cntr++;
						?>
					<li>
						<?php
						echo esc_html( $post->post_title );
						?>
					</li>
						<?php
					}
				endforeach;
			endif;
			?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}
}
