<?php
	if ( ! defined( 'ABSPATH' ) ) exit; 
	global $wpdb;
	$table_links = LLA_TABLE;
	$table_domains = LLA_DOMAINS_TABLE;
	$table_anchors = LLA_ANCHORS_TABLE;
	$table_unique_links = LLA_UNIQUE_LINKS_TABLE;
	$current_post_cursor = intval(self::get_option('post_cursor'));
	$post_count =  $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->posts} WHERE post_status = 'publish'" );
	$posts_remaining = $post_count - $current_post_cursor;
	$site_url = get_site_url();
	$this_domain = parse_url($site_url, PHP_URL_HOST);
	$total_count = $wpdb->get_var( "SELECT COUNT(id) FROM {$table_links}" );
	$internal_count = $wpdb->get_var( "SELECT COUNT(id) FROM {$table_links} WHERE domain = '$this_domain'" );
	$external_count = $total_count - $internal_count;
	$percent = intval(max(1,(($current_post_cursor/$post_count) * 100)));
	$link_count =  $wpdb->get_var( "SELECT COUNT(id) FROM {$table_links}" );
	$link_unique_count =  $wpdb->get_var( "SELECT COUNT(link) FROM {$table_unique_links} WHERE deleted <> 1" );
	$domain_count =  $wpdb->get_var( "SELECT COUNT(domain) FROM {$table_domains} WHERE deleted <> 1" );
	$anchor_unique_count =  $wpdb->get_var( "SELECT COUNT(anchor) FROM {$table_anchors}" );
	$no_follow_count =  $wpdb->get_var( "SELECT COUNT(id) FROM {$table_links} WHERE nofollow = 1" );
	$do_follow_count =  $wpdb->get_var( "SELECT COUNT(id) FROM {$table_links} WHERE nofollow <> 1" );
	$link_unique_count =  $wpdb->get_var( "SELECT COUNT(link) FROM {$table_unique_links} WHERE deleted <> 1" );
	$mfd_internal_count = $wpdb->get_var("SELECT count FROM {$table_domains} WHERE domain = '$this_domain' AND  deleted <> 1 ORDER BY count DESC LIMIT 0, 1");
	$mfd_external = $wpdb->get_row("SELECT domain, count FROM {$table_domains} WHERE domain <> '$this_domain' AND deleted <> 1 ORDER BY count DESC LIMIT 0, 1");
	$mfd_external_count = $mfd_external == null ? 0 : $mfd_external->count;
	$lfd_external = $wpdb->get_row("SELECT domain, count FROM {$table_domains} WHERE domain <> '$this_domain' AND deleted <> 1 ORDER BY count ASC LIMIT 0, 1");
	$lfd_external_count = $lfd_external == null ? 0 : $lfd_external->count;
	$highest_spam_score = $wpdb->get_row("SELECT domain, count, spam_score FROM {$table_domains} WHERE domain <> '$this_domain' AND spam_score > 1 AND deleted <> 1 ORDER BY spam_score DESC LIMIT 0, 1");
	$least_domain_authority = $wpdb->get_row("SELECT domain, count, domain_authority FROM {$table_domains} WHERE domain <> '$this_domain' AND domain_authority > 0.0 AND deleted <> 1 ORDER BY domain_authority ASC LIMIT 0, 1");
	$least_moz_rank = $wpdb->get_row("SELECT domain, count, mozrank_subdomain FROM {$table_domains} WHERE domain <> '$this_domain' AND mozrank_subdomain > 0.0 AND deleted <> 1 ORDER BY mozrank_subdomain ASC LIMIT 0, 1");
	$status200 =  $wpdb->get_var( "SELECT COUNT(http_status_code) FROM {$table_unique_links} WHERE http_status_code = 200 AND deleted <> 1" );
	$status301 =  $wpdb->get_var( "SELECT COUNT(http_status_code) FROM {$table_unique_links} WHERE http_status_code = 301 AND deleted <> 1" );
	$status302 =  $wpdb->get_var( "SELECT COUNT(http_status_code) FROM {$table_unique_links} WHERE http_status_code = 302 AND deleted <> 1" );
	$status404 =  $wpdb->get_var( "SELECT COUNT(http_status_code) FROM {$table_unique_links} WHERE http_status_code = 404 AND deleted <> 1" );
	$status_other =  $wpdb->get_var( "SELECT COUNT(http_status_code) FROM {$table_unique_links} ".
		"WHERE http_status_code > 0 AND http_status_code <> 404 AND http_status_code <> 302  AND http_status_code <> 301 AND http_status_code <> 200 AND deleted <> 1" );
	$status200_internal =  $wpdb->get_var( "SELECT COUNT(http_status_code) FROM {$table_unique_links} WHERE http_status_code = 200 AND is_internal = 1 AND deleted <> 1" );
	$status301_internal =  $wpdb->get_var( "SELECT COUNT(http_status_code) FROM {$table_unique_links} WHERE http_status_code = 301 AND is_internal = 1 AND deleted <> 1" );
	$status302_internal =  $wpdb->get_var( "SELECT COUNT(http_status_code) FROM {$table_unique_links} WHERE http_status_code = 302 AND is_internal = 1 AND deleted <> 1" );
	$status404_internal =  $wpdb->get_var( "SELECT COUNT(http_status_code) FROM {$table_unique_links} WHERE http_status_code = 404 AND is_internal = 1 AND deleted <> 1" );
	$status_other_internal =  $wpdb->get_var( "SELECT COUNT(http_status_code) FROM {$table_unique_links} ".
		"WHERE http_status_code > 0 AND http_status_code <> 404 AND http_status_code <> 302  AND http_status_code <> 301 AND http_status_code <> 200 AND is_internal = 1 AND deleted <> 1" );
	$status200 =  intval($status200);
	$status301 =  intval($status301);
	$status302 =  intval($status302);
	$status404 =  intval($status404);
	$status_other = intval($status_other); 
	$checked_total = $status200 + $status301 + $status302 + $status404 + $status_other;

	$broken_links_count =  $status404 + $status_other;
	$links_301_count =  $status301;
	$anchors = $wpdb->get_results("SELECT * FROM {$table_anchors} ORDER BY external_count DESC LIMIT 0, 100");
?>
<h1><?php _e(LLA_LONG_NAME, LLA_NAMESPACE); ?></h1>
<div class="postbox lla-postbox">
	<h2 class="hndle ui-sortable-handle" style="padding:0 10px;">Stats</h2>
	<div class="inside">
<?php if($current_post_cursor < $post_count): ?>
		<p class="description">Status: <b>processing</b><br><small>Expected time to completion <?php esc_html_e(self::secsToStr(intval($posts_remaining/9) * 300)); ?></small></p>
		<div id="myProgress">
		  <div id="myBar" style="width:<?php esc_html_e($percent); ?>%"></div>
		</div>
		<p class="description"><b><?php esc_html_e( $current_post_cursor );  ?></b> posts of <b><?php esc_html_e( $post_count ); ?></b>  processed</p>
<?php endif; ?>
		<p class="description">- Number of links discovered: <b><?php esc_html_e($link_count); ?></b>&nbsp;&nbsp;&nbsp;<a href="<?php esc_html_e( add_query_arg(array(LLA_SLUG => 'links'))); ?>" class="lla-blue-button">View&nbsp;&gt;</a></p>
		<p class="description">- Number of <b>internal links</b> discovered: <b><?php esc_html_e( $internal_count ); ?></b>&nbsp;&nbsp;&nbsp;<a href="<?php esc_html_e( add_query_arg(array(LLA_SLUG => 'unique-links', 'flag' => 'internal')) ); ?>" class="lla-blue-button">View&nbsp;&gt;</a></p>
		<p class="description">- Number of <b>external links</b> discovered: <b><?php esc_html_e( $external_count ); ?></b>&nbsp;&nbsp;&nbsp;<a href="<?php esc_html_e( add_query_arg(array(LLA_SLUG => 'unique-links', 'flag' => 'external')) ); ?>" class="lla-blue-button">View&nbsp;&gt;</a></p> 
		<p class="description">- Number of <b>dofollow links</b> discovered: <b><?php esc_html_e( $do_follow_count ); ?></b>&nbsp;&nbsp;&nbsp;<a href="<?php esc_html_e( add_query_arg(array(LLA_SLUG => 'links', 'flag' => 'dofollow')) ); ?>" class="lla-blue-button">View&nbsp;&gt;</a></p>
		<p class="description">- Number of <b>nofollow links</b> discovered: <b><?php esc_html_e( $no_follow_count ); ?></b>&nbsp;&nbsp;&nbsp;<a href="<?php esc_html_e( add_query_arg(array(LLA_SLUG => 'links', 'flag' => 'nofollow')) ); ?>" class="lla-blue-button">View&nbsp;&gt;</a></p> 
		<p class="description">- Number <b>unique links</b> discovered: <b><?php esc_html_e( $link_unique_count ); ?></b>&nbsp;&nbsp;&nbsp;<a href="<?php esc_html_e( add_query_arg(array(LLA_SLUG => 'unique-links')) ); ?>" class="lla-blue-button">View&nbsp;&gt;</a></p>
		<p class="description">- Number of anchor texts discovered: <b><?php esc_html_e( $anchor_unique_count ); ?></b>&nbsp;&nbsp;&nbsp;<a href="<?php esc_html_e( add_query_arg(array(LLA_SLUG => 'anchors')) ); ?>" class="lla-blue-button">View&nbsp;&gt;</a></p>
		<p class="description">- Number of linked domains discovered: <b><?php esc_html_e( $domain_count ); ?></b>&nbsp;&nbsp;&nbsp;<a href="<?php esc_html_e( add_query_arg(array(LLA_SLUG => 'domains')) ); ?>" class="lla-blue-button">View&nbsp;&gt;</a></p>
		<p class="description">- Number of <span class="lla-orange">301 redirects</span> discovered: <b><?php esc_html_e( $links_301_count ); ?></b>&nbsp;&nbsp;&nbsp;<a href="<?php esc_html_e( add_query_arg(array(LLA_SLUG => 'unique-links', 'flag' => 'broken-301'))); ?>" class="lla-blue-button">View&nbsp;&gt;</a></p>
		<p class="description">- Number of <span class="lla-red">broken links</span> discovered: <b><?php esc_html_e( $broken_links_count ); ?></b>&nbsp;&nbsp;&nbsp;<a href="<?php esc_html_e( add_query_arg(array(LLA_SLUG => 'unique-links', 'flag' => 'broken-404')) ); ?>" class="lla-blue-button">View&nbsp;&gt;</a></p>
		<p class="description">- Average unique links per post: <b><?php esc_html_e( ($link_unique_count > 0 && $current_post_cursor > 0) ? ceil($link_unique_count/$current_post_cursor) : 0 ); ?></b>&nbsp;&nbsp;&nbsp;<a href="<?php esc_html_e( add_query_arg(array(LLA_SLUG => 'posts'))); ?>" class="lla-blue-button">View&nbsp;&gt;</a></p>
	</div>
	<?php if($current_post_cursor > 0): ?><p class="description text-align-right"><a class="button button-primary" href="<?php esc_html_e( add_query_arg(array('lla_rescan' => true)) ); ?>">Reset Stats & Re-scan posts</a>&nbsp;or&nbsp;<a class="button button-primary" href="<?php esc_html_e( add_query_arg(array('lla_clear_rescan' => true))); ?>">Clear data & Re-scan posts</a></p><?php endif; ?>
</div>


