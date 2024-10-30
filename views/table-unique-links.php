<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 
$search = isset($_POST['lla_search']) ? sanitize_text_field($_POST['lla_search']) : ''; 
?>
<h1><?php _e(LLA_LONG_NAME, LLA_NAMESPACE); ?></h1>
<div class="wrap">
	<form style="float:right" method="post" action="">
		<table>
			<tr>
				<td><p class="description">Search by domain:<input type="text" name="lla_search" value="<?php echo $search?>" placeholder="Enter search term&hellip;"></p></td>
				<td><input type="submit" id="lla_search_submit" name="lla_search_submit" class="button" value="Search"></td>
			</tr>
		</table>
	</form>
	<form method="post" action="">
<?php 
	global $wpdb;
	$class = __LLA_TABLE_UNIQUE_LINKS__;
	require_once(__LLA_TABLE_UNIQUE_LINKS__FILE__);
	$table = new $class();
	$table->prepare_items();
	$table->display(); 
?>
	</form>
</div>
