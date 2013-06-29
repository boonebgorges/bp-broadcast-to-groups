<?php

/**
 * Main plugin class
 */

class BPBTG {
	public function __construct() {
		$this->setup_hooks();
	}

	protected function setup_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'transition_post_status', array( $this, 'post_activity' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	public function add_meta_box() {
		if ( ! $this->do_on_page() ) {
			return;
		}

		$screens = array(
			'post',
			'page'
		);

		foreach ( $screens as $screen ) {
			add_meta_box(
				'bp-broadcast-to-group',
				__( 'Broadcast to Groups', 'bpbtg' ),
				array( $this, 'meta_box_cb' ),
				$screen,
				'side',
				'default'
			);
		}
	}

	public function post_activity( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status ) {
			return;
		}

		if ( empty( $_POST['bpbtg-group'] ) ) {
			return;
		}

		$blog_id = get_current_blog_id();
		$post_id = $post->ID;

		foreach ( $_POST['bpbtg-group'] as $group_id ) {
			$group = groups_get_group( array( 'group_id' => $group_id ) );

			$user_id = (int) $post->post_author;
			$post_permalink   = get_permalink( $post_id );
			$activity_action  = sprintf( __( '%1$s wrote a new post, %2$s, on the site %3$s', 'buddypress' ), bp_core_get_userlink( (int) $post->post_author ), '<a href="' . $post_permalink . '">' . $post->post_title . '</a>', '<a href="' . get_blog_option( $blog_id, 'home' ) . '">' . get_blog_option( $blog_id, 'blogname' ) . '</a>' );
			$activity_content = $post->post_content;

			// Set hide_sitewide according to the group's privacy level
			$hide_sitewide = 'public' != $group->status;

			bp_activity_add( array(
				'user_id'           => (int) $post->post_author,
				'component'         => buddypress()->groups->id,
				'action'            => apply_filters( 'bp_blogs_activity_new_post_action', $activity_action, $post, $post_permalink ),
				'content'           => apply_filters( 'bp_blogs_activity_new_post_content', $activity_content, $post, $post_permalink ),
				'primary_link'      => apply_filters( 'bp_blogs_activity_new_post_primary_link', $post_permalink, $post_id ),
				'type'              => 'new_blog_post',
				'item_id'           => $group_id,
				'secondary_item_id' => $blog_id,
				'recorded_time'     => $post->post_modified_gmt,
				'hide_sitewide'     => $hide_sitewide,
			));
		}

		if ( ! empty( $_POST['bpbtg-save-as-default'] ) ) {
			update_option( 'bpbtg_default_groups', $_POST['bpbtg-group'] );
		}
	}

	protected function get_groups_for_user( $user_id ) {
		$groups = BP_Groups_Group::get( array(
			'user_id' => $user_id,
			'type' => 'alphabetical',
			'per_page' => false,
			'page' => 1,
			'show_hidden' => true,
		) );
		return isset( $groups['groups'] ) ? $groups['groups'] : array();
	}

	public function meta_box_cb() {
		$saved_groups = get_option( 'bpbtg_default_groups' );
		if ( ! $saved_groups ) {
			$saved_groups = array();
		}

		$save_as_default = ! empty( $saved_groups );

		$groups = $this->get_groups_for_user( get_current_user_id() );

		?>

		<div class="bpbtg-group-list">
			<p class="bpbtg-gloss"><?php _e( 'Published post will display in the activity feed of the selected groups:', 'bpbtg' ) ?></p>
			<select id="bpbtg-group" multiple="multiple" name="bpbtg-group[]">
				<?php foreach ( $groups as $group ) : ?>
					<?php $selected = in_array( $group->id, $saved_groups ) ?>
					<option value="<?php echo esc_attr( $group->id ) ?>" <?php selected( $selected ) ?>><?php echo esc_attr( $group->name ) ?></option>
				<?php endforeach ?>
			</select>
		</div>

		<div class="bpbtg-save-as-default-toggle">
			<input type="checkbox" <?php checked( $save_as_default ) ?> name="bpbtg-save-as-default" id="bpbtg-save-as-default" value="1" /> <label for="bpbtg-save-as-default"><?php _e( 'Save as default', 'bpbtg' ) ?></label>
		</div>
		<?php
	}

	/**
	 * @todo post type filters?
	 */
	protected function do_on_page() {
		global $pagenow;
		return 'post-new.php' === $pagenow;
	}

	public function enqueue_scripts() {
		if ( $this->do_on_page() ) {
			wp_register_script( 'bpbtg-chosen', plugins_url( 'bp-broadcast-to-groups/lib/chosen/chosen.jquery.min.js' ), array( 'jquery' ) );
			wp_register_script( 'bpbtg', plugins_url( 'bp-broadcast-to-groups/assets/js/bpbtg.js' ), array( 'jquery', 'bpbtg-chosen' ) );
			wp_enqueue_script( 'bpbtg' );
		}
	}

	public function enqueue_styles() {
		if ( $this->do_on_page() ) {
			wp_enqueue_style( 'bpbtg-chosen', plugins_url( 'bp-broadcast-to-groups/lib/chosen/chosen.css' ) );
		}
	}
}
