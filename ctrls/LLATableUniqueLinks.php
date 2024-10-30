<?php
if ( ! defined( 'ABSPATH' ) ) exit; 
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class LLATableUniqueLinks extends WP_List_Table {
	private $total_items = 0;
	private $table_name = LLA_UNIQUE_LINKS_TABLE;

	public function __construct(){
		parent::__construct( array(
			'singular'  => 'Link',
			'plural'    => 'Links',
			'ajax'      => false
		) );
	}
	public function no_items() {
		_e(sprintf('No domains'), LLA_NAMESPACE);
	}
	public function column_default( $item, $column_name ){
    	switch( $column_name ){
    		case 'cb':
    			return '<input type="checkbox" />';
	        case 'link':
				return $item['link'];
	       	case 'count':
				return intval($item['count']);
	        default:
	            return '';
    	}
	}
	public function column_link($item){
		return sprintf(
			'<a href="%s" target="_blank">%s</a>', 
			$item['original_link'],
			strlen($item['original_link']) > 250 ? substr($item['original_link'], 0, 250 ). "&hellip;" : $item['original_link']
		);
	}
	public function column_count($item){
		return sprintf('<a href="?page=%s&%s=links&flag=bylink&bylink=%s">%s</a>',
			LLA_SLUG,
			LLA_SLUG,
			urlencode(untrailingslashit($item['original_link'])),
			intval($item['count'])
		);
	}

    public function get_bulk_actions(){
        $actions = array(
        );
        return $actions;
    }

	private function delete($links){
		global $wpdb;
		$table_name_strip = LLA_STRIP_LINKS_TABLE;
		$href_hashs = array();
		if(count($links)){
			foreach($links as $link){
				if($link == null ) continue;
				$href_hashs[] = md5($link);
				$wpdb->update(
					$this->table_name,
					array('status' => 0),
					array('link' => $link)
				);
			}
		}
		$href_hashs = "'" . implode("','", $href_hashs) . "'";
        $wpdb->query("DELETE FROM $table_name_strip WHERE href_hash IN($href_hashs)");
	}
	public function get_columns(){
		$columns = array(
			'link' => 'Link',
			'count' => 'Link count',
        	);
		return $columns;
	}  
	public function get_sortable_columns() {
		$sortable_columns = array(
			'link'   => array('link', false ),
			'count'   => array('count', false )
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
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'spam_score';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';
        // [REQUIRED] define $items array
        // notice that last argument is ARRAY_A, so we will retrieve array
		if(isset($_POST['lla_search_submit'])){
			$search = sanitize_text_field($_POST['lla_search']);
    		$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE link LIKE '%$search%' AND deleted <> 1 LIMIT %d, %d", 0, 50), ARRAY_A);	
			$total_items = $wpdb->get_var("SELECT COUNT(link) FROM $table_name WHERE link LIKE '%$search%' AND deleted <> 1");
			$this->total_items = $total_items;
		} else {
			$flag = isset($_REQUEST['flag']) ? sanitize_key($_REQUEST['flag']) : '';
			
			switch($flag){
				case 'internal':
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE is_internal = 1 AND deleted <> 1 ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(link) FROM $table_name  WHERE is_internal = 1 AND deleted <> 1");
					$this->total_items = $total_items;
					break;
				case 'external':
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE is_internal <> 1 AND deleted <> 1 ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(link) FROM $table_name  WHERE is_internal <> 1 AND deleted <> 1");
					$this->total_items = $total_items;
					break;
				case 'broken-301':
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE http_status_code = 301 AND deleted <> 1 ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(link) FROM $table_name  WHERE http_status_code = 301 AND deleted <> 1");
					$this->total_items = $total_items;
					break;
				case 'broken-404':
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE http_status_code = 404 AND deleted <> 1 ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(link) FROM $table_name  WHERE http_status_code = 404 AND deleted <> 1");
					$this->total_items = $total_items;
					break;
				default:
					$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE deleted <> 1 ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(link) FROM $table_name WHERE deleted <> 1");
					$this->total_items = $total_items;
					break;
			}
		}

        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
	}
}
?>
