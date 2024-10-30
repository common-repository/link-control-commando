<?php
if ( ! defined( 'ABSPATH' ) ) exit; 
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class LLATableAnchor extends WP_List_Table {
	private $total_items = 0;
	private $table_name = LLA_ANCHORS_TABLE;

	public function __construct(){
		parent::__construct( array(
			'singular'  => 'Anchor text',
			'plural'    => 'Anchor text',
			'ajax'      => false
		) );
	}
	public function no_items() {
		_e(sprintf('No links'), LLA_NAMESPACE);
	}
	public function column_default( $item, $column_name ){
    	switch( $column_name ){
	       	case 'anchor':
				return $item['anchor'];
	       	case 'external_count':
				return $item['external_count'];
	       	case 'internal_count':
				return $item['internal_count'];
	       	case 'count':
				return $item['count'];
	        default:
	            return '';
    	}
	}
	public function column_external_count($item){
		return sprintf(
			'<a href="%s" target="_blank">%s</a>',
			add_query_arg(array('flag' => 'external' ,'anchor' => $item['sanitized_anchor'])), 
			$item['external_count']
		);
	}
	public function column_internal_count($item){
		return sprintf(
			'<a href="%s" target="_blank">%s</a>',
			add_query_arg(array('flag' => 'internal' ,'anchor' => $item['sanitized_anchor'])), 
			$item['internal_count']
		);
	}
	public function column_count($item){
		return sprintf(
			'<a href="?page=%s&%s=links&flag=anchor&anchor=%s">%s</a>',
			LLA_SLUG, 
			LLA_SLUG, 
			urlencode($item['anchor']),
			intval($item['internal_count']) + intval($item['external_count'])
		);
	}
	public function get_columns(){
		$columns = array(
			'anchor' => 'Anchor',
			'count' => 'Total Link count'
		);
		return $columns;
	}  
	public function get_sortable_columns() {
		$sortable_columns = array(
			'count'   => array('count', false ),
			'internal_count'   => array('internal_count', false ),
			'external_count'   => array('external_count', false ),
		);
		return $sortable_columns;
	}
	public function get_item_count(){
		return $this->total_items;
	}
	public function prepare_items(){
        global $wpdb;
        $per_page = 20;
        $table_name = $this->table_name;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
		$paged = $paged * $per_page;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'count';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';
        // [REQUIRED] define $items array
        // notice that last argument is ARRAY_A, so we will retrieve array

		$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
		$total_items = $wpdb->get_var("SELECT COUNT(sanitized_anchor) FROM $table_name");
		$this->total_items = $total_items;
        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
	}
}
?>
