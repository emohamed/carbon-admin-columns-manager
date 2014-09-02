<?php

/**
 * Plugin Name: CRB Columns Manager
 */
class Carbon_Admin_Columns_Manager {
	
	/**
	 * Target name
	 *
	 * The target name might be taxonomies or post types
	 *
	 * @see set_target()
	 * @see get_target()
	 * @var array|string $targets
	 */
	protected $targets;
	/**
	 * One of the following: 
	 *  - post_columns
	 *  - user_columns 
	 *  - taxonomy_columns
	 *
	 * This is used for filters names manage_edit-**
	 * See http://codex.wordpress.org/Plugin_API/Filter_Reference/manage_edit-post_type_columns
	 */
	public $admin_screen_type;

	/**
	 * Sepcify columns for removal.
	 * The value might be a string or an array with columns
	 *
	 * @see remove()
	 * @var array|string $columns_to_remove
	 */
	protected $columns_to_remove;

	static function modify_post_type_columns( $post_types ) {
		return new Carbon_Admin_Columns_Manager_Post_Columns($post_types);
	}

	static function modify_users_columns() {
		return new Carbon_Admin_Columns_Manager_User_Columns();
	}

	static function modify_taxonomy_columns( $taxonomies ) {
		return new Carbon_Admin_Columns_Manager_Taxonomy_Columns($taxonomies);
	}

	private function __construct($targets='') {
		$this->set_target($targets);
	}

	public function set_target($targets) {
		$this->targets = (array) $targets;

		return $this;
	}

	public function get_targets() {
		return $this->targets;
	}

	public function unset_admin_columns($columns) {
		foreach ( $this->columns_to_remove as $column_name ) {
			unset( $columns[$column_name] );
		}

		return $columns;
	}
}