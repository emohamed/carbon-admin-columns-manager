<?php 

class Carbon_Admin_Column {
	/**
	 * Column Label
	 *
	 * @var string
	 */
	protected $label;

	/**
	 * Column name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The field that will be used for ordeing the posts. Null value will
	 * disable the ordering capability.
	 * 
	 * @var string $sort_field
	 */
	protected $sort_field;

	/**
	 * An instance of Main Carbon Columns Container
	 *
	 * @var object $manager
	 */
	protected $manager;

	/**
	 * @var string $meta_key
	 */
	public $meta_key = null;

	/**
	 * Callback that will be used for rendering columns 
	 * values in WP admin listing screen. By default, this 
	 * will print  custom field value associated with 
	 * the column name.
	 */
	public $callback;

	/**
	 * Instance of Carbon_Admin_Column_Callback_Helper
	 * @see new Carbon_Admin_Column_Callback_Helper()
	 */
	protected $callback_helper = null;

	static function create($label, $name = null) {
		if ( !$label ) {
			wp_die( 'Column label is required.' );
		}

		return new self($label, $name);
	}

	private function __construct($label, $name) {
		$this->label = $label;

		if ( empty($name) ) {
			$name = 'carbon-' . preg_replace('~[^a-zA-Z0-9.]~', '', $label);
		}
		$this->set_column_name($name);

		$this->callback = array($this, "get_meta_value");

		return $this;
	}

	public function set_column_name($name) {
		$this->name = $name;

		return $this;
	}

	public function get_column_name() {
		return $this->name;
	}

	public function set_field($meta_key) {
		$this->meta_key = $meta_key;

		return $this;
	}

	public function get_field() {
		if ( !empty($this->callback_helper) ) {
			return $this->callback_helper->get_field();
		}

		return $this->meta_key;
	}

	public function set_callback($callback) {
		if ( !is_callable($callback) ) {
			trigger_error( "Callback must be callable function. ", E_USER_WARNING);
			return false;
		}

		$this->callback = $callback;

		return $this;
	}

	public function get_callback() {
		if ( !empty($this->callback_helper) ) {
			return $this->callback_helper->get_callback();
		}

		return $this->callback;
	}

	public function set_column_callback_helper($callback_helper) {
		$this->callback_helper = $callback_helper;

		return $this;
	}

	public function get_column_label() {
		return $this->label;
	}

	public function set_sort_field($sort_field=null) {
		$this->sort_field = $sort_field;

		return $this;
	}

	public function get_sort_field() {
		$sort_field = $this->sort_field;

		if ( !$sort_field ) {
			$sort_field = $this->get_column_name();
		}

		return $sort_field;
	}

	public function is_callback() {
		return $this->is_callback===true;
	}

	public function set_manager( Carbon_Admin_Columns_Manager $manager ) {
		$this->manager = $manager;

		return $this;
	}

	/**
	 * Setup hooks for columns list, columns values and sortable flags.
	 */
	public function init() {
		// The type of objects that will be affected -- e.g. specific 
		// post types or taxonomies
		$object_types = $this->manager->object_types;
		$admin_screen = $this->manager->admin_screen_type;

		foreach ($object_types as $object_type) {
			// Filter the columns list
			add_filter(
				$this->manager->get_cols_list_filter_name( $object_type ),
				array($this, 'register_column'),
				15
			);

			// Filter the columns content for each row
			add_action(
				$this->manager->get_col_content_filter_name( $object_type ),
				array($this, 'init_' . $admin_screen . '_callback'),
				15,
				3
			);

			if ( $this->sort_field ) {
				// If necessary, filter sortable flags. 
				add_filter(
					$this->manager->get_sortable_filter_name( $object_type ),
					array($this, 'init_column_sortable')
				);
			}
		}

		return true;
	}

	/**
	 * Add this column to registered columns
	 * @param array $columns Columns registered so far
	 */
	public function register_column($columns) {
		$columns[ $this->name ] = $this->label;

		return $columns;
	}

	public function init_column_sortable($columns) {
		$columns[ $this->get_column_name() ] = $this->sort_field;

		return $columns;  
	}

	public function init_user_columns_callback($null, $column_name, $user_id) {
		return $this->init_column_callback(
			$this->get_column_name(),
			$user_id
		);
	}

	public function init_taxonomy_columns_callback($null, $column_name, $term_id) {
		echo $this->init_column_callback(
			$column_name,
			$term_id
		);
	}

	public function init_post_columns_callback($column_name, $post_id) {
		echo $this->init_column_callback(
			$column_name,
			$post_id
		);
	}

	public function init_column_callback( $column_name, $object_id ) {
		$this_column_name = $this->get_column_name();

		# check whether this is the right column
		if ( $this_column_name !== $column_name ) {
			return;
		}

		if ( !empty($this->callback_helper) ) {
			$this->callback_helper->increase_callback_request_number();

			# prevent multiple callback function calling
			if ( $this->callback_helper->get_callback_request_number() % $this->callback_helper->get_total_columns() !== 0 ) {
				return;
			}
		}

		$results = call_user_func($this->get_callback(), $object_id);

		return $results;
	}

	function get_meta_value($object_id) {
		return $this->manager->get_meta_value(
			$object_id,
			$this->get_field()
		);
	}
}