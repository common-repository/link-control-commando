<?php
if ( ! defined( 'ABSPATH' ) ) exit; 
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class LLATableLinks extends WP_List_Table {
	private $total_items = 0;
	private $table_name = LLA_TABLE;

	public function __construct(){
		parent::__construct( array(
			'singular'  => 'Link',
			'plural'    => 'Links',
			'ajax'      => false
		) );
	}
	public function no_items() {
		_e(sprintf('No links'));
	}
	public function column_default( $item, $column_name ){
    	switch( $column_name ){
	       	case 'post_id':
				return $item['post_id'];
	       	case 'permalink':
				return $item['permalink'];
	       	case 'anchor':
				return $item['anchor'];
	        default:
	            return '';
    	}
	}
	public function column_post_id($item){
		$post_permalink = get_permalink($item['post_id']);
		return sprintf('<a href="%s" target="_blank">%s</a>', $post_permalink, $post_permalink);
	}
	public function column_permalink($item){
		return sprintf('<a href="%s" target="_blank">%s</a>', esc_url($item['permalink']), esc_url($item['permalink']));
	}
	public function column_anchor($item){
		return $item['anchor'];
	}

    public function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="lla_id[]" value="%s" />',
            $item['id']
        );
    }
    public function get_bulk_actions(){
        $actions = array(
        );
        return $actions;
    }
	private function delete($ids){
		global $wpdb;
		$table_name_strip = LLA_STRIP_LINKS_TABLE;
		$href_hashs = array();
		if(count($ids)){
			foreach($ids as $id){
				$item = $wpdb->get_row("SELECT href_hash FROM {$this->table_name} WHERE id = $id");
				if($item == null ) continue;
				$href_hashs[] = $item->href_hash;
				$wpdb->update(
					$this->table_name,
					array('status' => 0),
					array('id' => $id)
				);
			}
		}
		$href_hashs = "'" . implode("','", $href_hashs) . "'";
        $wpdb->query("DELETE FROM $table_name_strip WHERE href_hash IN($href_hashs)");
	}
	private function get_or_create($id, $status){
		global $wpdb;
		$item = $wpdb->get_row("SELECT href_hash FROM {$this->table_name} WHERE id = $id");
		if($item == null ) return;
		
		$table_name_strip = LLA_STRIP_LINKS_TABLE;
		$href_hash = $item->href_hash;
		$strip_item = $wpdb->get_row("SELECT href_hash FROM $table_name_strip WHERE href_hash = '$href_hash'");
		if($strip_item != null){
			$wpdb->update( 
				$table_name_strip, 
				array( 'status' => $status ), 
				array('href_hash' => $href_hash)
			);
		} else {
			$wpdb->insert(
				$table_name_strip, 
				array(
					'status' => $status,
					'href_hash' => $href_hash
				)
			);
		}
		$wpdb->update(
			$this->table_name,
			array('status' => $status),
			array('id' => $id)
		);
	}
	public function get_columns(){
		$columns = array(
			'post_id' => 'Post',
			'permalink' => 'Link',
			'anchor' => 'Anchor text',
        	);
		return $columns;
	}  
	public function get_sortable_columns() {
		$sortable_columns = array(
			'post'   => array('post', false ),
			'anchor'   => array('anchor', false ),
			'permalink'   => array('permalink', false )
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
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'date_modified';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';
        // [REQUIRED] define $items array
        // notice that last argument is ARRAY_A, so we will retrieve array
		if(isset($_POST['lla_search_submit'])){
			$search = sanitize_text_field($_POST['lla_search']);
    		$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE permalink LIKE '%$search%' LIMIT %d, %d", 0, 50), ARRAY_A);	

		    $total_items = $wpdb->get_var("SELECT COUNT(domain) FROM $table_name WHERE permalink LIKE '%$search%'");
			$this->total_items = $total_items;
		} else {
			$flag = isset($_REQUEST['flag']) ? sanitize_key($_REQUEST['flag']) : '';
			
			switch($flag){
				case 'internal':
					$site_url = get_site_url();
					$this_domain = parse_url($site_url, PHP_URL_HOST);
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE domain = '$this_domain' ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(domain) FROM $table_name WHERE domain = '$this_domain'");
					$this->total_items = $total_items;
					break;
				case 'external':
					$site_url = get_site_url();
					$this_domain = parse_url($site_url, PHP_URL_HOST);
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE domain <> '$this_domain' ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(domain) FROM $table_name WHERE domain <> '$this_domain'");
					$this->total_items = $total_items;
					break;
				case 'dofollow':
					$site_url = get_site_url();
					$this_domain = parse_url($site_url, PHP_URL_HOST);
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE nofollow <> 1 ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(domain) FROM $table_name WHERE nofollow <> 1");
					$this->total_items = $total_items;
					break;
				case 'bylink':
					$link = urldecode($_REQUEST['bylink']);
					$href_hash = LLAUtils::link_href_hash($link);
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE href_hash = '$href_hash' ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(domain) FROM $table_name WHERE href_hash = '$href_hash'");
					$this->total_items = $total_items;
					break;
				case 'post':
					$post_id = intval($_REQUEST['post_id']);
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = '$post_id' ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(domain) FROM $table_name WHERE post_id = '$post_id'");
					$this->total_items = $total_items;
					break;
				case 'anchor':
					$anchor = urldecode($_REQUEST['anchor']);
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE anchor = '$anchor' ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(domain) FROM $table_name WHERE anchor = '$anchor'");
					$this->total_items = $total_items;
					break;
				case 'domain':
					$domain = urldecode($_REQUEST['domain']);
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE domain = '$domain' ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(domain) FROM $table_name WHERE domain = '$domain'");
					$this->total_items = $total_items;
					break;
				case 'nofollow':
					$site_url = get_site_url();
					$this_domain = parse_url($site_url, PHP_URL_HOST);
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE nofollow = 1 ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(domain) FROM $table_name WHERE nofollow = 1");
					$this->total_items = $total_items;
					break;
				default:
        			$this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
					$total_items = $wpdb->get_var("SELECT COUNT(domain) FROM $table_name");
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
