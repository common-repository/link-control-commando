<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 
$search = isset($_POST['lla_search']) ? sanitize_text_field($_POST['lla_search']) : ''; 
?>
<h1><?php _e(LLA_LONG_NAME, LLA_NAMESPACE); ?> - Domains</h1>
<div class="wrap">
	<form method="post" action="">
<?php 
	global $wpdb;
	$class = __LLA_TABLE_DOMAINS__;
	require_once(__LLA_TABLE_DOMAINS__FILE__);
	$table = new $class();
	$table->prepare_items();
	$table->display(); 
?>
	</form>
</div>
