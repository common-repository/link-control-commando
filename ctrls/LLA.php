<?php
if ( ! defined( 'ABSPATH' ) ) exit; 
class LLA extends LLAUtils{
	public function __construct(){
		add_filter( 'cron_schedules', array(get_class(), 'lla_every_five_minutes' )); 
		add_action( LLA_CRON_HOOK, array(get_class(), 'lla_start_cron') );
		$this->load_ajax_actions();
		$this->start_dashboard();
	}

	public static function lla_every_five_minutes( $schedules ) {
		$schedules['lla_five_minutes'] = array(
			'interval' => 300,
			'display' => __('Local Link Alpha Five Minutes')
		);
		return $schedules;
	}
	public static function lla_start_cron(){
		global $wpdb, $wp_version;

	    $table_links = LLA_TABLE;
	    $table_domains = LLA_DOMAINS_TABLE;
	    $table_anchors = LLA_ANCHORS_TABLE;
	    $table_post_links = LLA_POST_LINKS_TABLE;
	    $table_unique_links = LLA_UNIQUE_LINKS_TABLE;

		$current_page = intval(self::get_option("post_cursor"));
		$post_item = $wpdb->get_row( "SELECT ID, post_type, post_content FROM {$wpdb->posts} ".
				"WHERE post_status = 'publish' ".
				"ORDER BY ID ASC ".
				"LIMIT 1 OFFSET $current_page" 
		);

		$site_url = get_site_url();
		$site_domain = parse_url($site_url, PHP_URL_HOST);
		$i = $current_page;
		$j = 0;
		while($post_item != NULL &&  $j < 9){
			$j++;
			self::add_option("post_cursor", ++$i);
			$post_id = $post_item->ID;
			$post_type = $post_item->post_type;
			$post_permalink = get_permalink($post_id);
			//$wpdb->delete( 'table', array( 'post_id' => $post_id ));
	
			$args = array(
				'timeout' => 30,
			    'user-agent'  => LLA_USER_AGENT
			);
			$response = wp_remote_get( $post_permalink, $args );


			$o_link = untrailingslashit(strtok($post_permalink, '#'));
			$prsed = parse_url($o_link);
			unset($prsed['scheme']);
			$link = self::unparse_url($prsed);

			if ( !is_wp_error( $response ) ) {
				$html = $response['body'];

				$final_url = untrailingslashit($response['http_response']->get_response_object()->url);
				$f_link = untrailingslashit(strtok($final_url, '#'));
				$prsed = parse_url($f_link);
				unset($prsed['scheme']);
				$f_link = self::unparse_url($prsed);

				if(strcasecmp($link, $f_link) == 0){
					$code = wp_remote_retrieve_response_code($response);
				} else {
					$history = $response['http_response']->get_response_object()->history;
					$code = count($history) ? intval($history[0]->status_code) : 301;

					$unique_links_item = $wpdb->get_row("SELECT count FROM $table_unique_links WHERE link = '$f_link'");
					if($unique_links_item != null){
						$wpdb->update( 
							$table_unique_links, 
							array(
								'httped' => 1,
								'deleted' => 0,
								'canonical' => $f_link,
								'http_status_code' => 200
							), 
							array('link' => $link)
						);
					} else {
						$wpdb->insert(
							$table_unique_links, 
							array(
								'count' => 0,
								'httped' => 1,
								'deleted' => 0,
								'link' => $f_link,
								'is_internal' => 1,
								'canonical' => $f_link,
								'original_link' => $final_url,
								'http_status_code' => 200
							)
						);
					}
				}
			} else {
				$html = apply_filters('the_content', get_post_field('post_content', $post_id));
				$code = 404;
				$o_link = untrailingslashit(strtok($post_permalink, '#'));
				$prsed = parse_url($o_link);
				unset($prsed['scheme']);
				$f_link = self::unparse_url($prsed);
			}


			$unique_links_item = $wpdb->get_row("SELECT count FROM $table_unique_links WHERE link = '$link'");
			if($unique_links_item != null){
				$wpdb->update( 
					$table_unique_links, 
					array(
						'httped' => 1,
						'deleted' => 0,
						'canonical' => $f_link,
						'http_status_code' => $code
					), 
					array('link' => $link)
				);
			} else {
				$wpdb->insert(
					$table_unique_links, 
					array(
						'count' => 0,
						'httped' => 1,
						'deleted' => 0,
						'link' => $link,
						'is_internal' => 1,
						'canonical' => $f_link,
						'original_link' => $o_link,
						'http_status_code' => $code
					)
				);
			}

			$dom = new DOMDocument;
			@$dom->loadHTML( $html );
			foreach($dom->getElementsByTagName('a') as $node){
				$href = @(string)$node->getAttribute("href");
				$href = trim($href);
				if(strpos($href,"#")===0) continue;	
				if(stripos($href,"ftp:")===0) continue;
				if(stripos($href,"news:")===0) continue;
				if(stripos($href,"file:")===0) continue;	
				if(stripos($href,"wais:")===0) continue;
				if(stripos($href,"mailto:")===0) continue;
				if(stripos($href,"gopher:")===0) continue;
				if(stripos($href,"telnet:")===0) continue;
				if(stripos($href,"telnet:")===0) continue;
				if(stripos($href,"javascript:")===0) continue;
				if(untrailingslashit(strtok($href, '#')) == untrailingslashit(strtok($post_permalink, '#')) ) continue;

				$url = self::rel2abs($href, $post_permalink);
				$a = array();
				$a['href'] = $href;
				$a['anchor'] = @(string)$node->nodeValue;
				$a['rev'] = @(string)$node->getAttribute("rev");
				$a['rel'] = @(string)$node->getAttribute("rel");
				$a['name'] = @(string)$node->getAttribute("name");
				$a['type'] = @(string)$node->getAttribute("type");
				$a['media'] = @(string)$node->getAttribute("media");
				$a['shape'] = @(string)$node->getAttribute("shape");
				$a['title'] = @(string)$node->getAttribute("title");
				$a['target'] = @(string)$node->getAttribute("target");
				$a['coords'] = @(string)$node->getAttribute("coords");
				$a['charset'] = @(string)$node->getAttribute("charset");
				$a['download'] = @(string)$node->getAttribute("download");
				$a['hreflang'] = @(string)$node->getAttribute("hreflang");
						
				$domain = parse_url($url, PHP_URL_HOST);
				$tld = self::get_domain($url);
				$nofollow = isset($a['rel']) && stripos($a['rel'], 'nofollow') !== false ? 1 : 0;
				$time = time();
				$href_hash = self::link_href_hash($url);
				

				$wpdb->insert(
					$table_links,
					array(
						'tld' => $tld,
						'domain' => $domain,
						'permalink' => $url,
						'post_id' => $post_id,
						'nofollow' => $nofollow,
						'date_created' => $time,
						'date_modified' => $time,
						'post_type' => $post_type,
						'href_hash' => $href_hash,
						'href' => isset($a['href']) ? $a['href'] : '',
						'anchor' => isset($a['anchor']) ? $a['anchor'] : '',
						'anchor_title' => isset($a['title']) ? $a['title'] : '',
						'extras' => serialize($a)
					)
				);
				$domain_item = $wpdb->get_row("SELECT count FROM $table_domains WHERE domain = '$domain'");
				if($domain_item != null){
					$count = $domain_item->count + 1;
					$wpdb->update( 
						$table_domains, 
						array('count' => $count, 'deleted' => 0 ), 
						array( 'domain' => $domain )
					);
				} else {
					$wpdb->insert(
						$table_domains, 
						array(
							'deleted' => 0,
							'count' => 1,
							'domain' => $domain
						)
					);
				}
				$sanitized_anchor = sanitize_title($a['anchor']);
				$anchor_item = $wpdb->get_row("SELECT * FROM $table_anchors WHERE sanitized_anchor = '$sanitized_anchor'");
				if($anchor_item != null){
					$count = $anchor_item->count + 1;
					$internal_count = $anchor_item->internal_count;
					$external_count = $anchor_item->external_count;
					if(strcasecmp($domain, $site_domain) == 0){
						$internal_count = $internal_count + 1;
					} else {
						$external_count = $external_count + 1;
					}

					$wpdb->update( 
						$table_anchors, 
						array(
							'count' => $count,
							'internal_count' => $internal_count,
							'external_count' => $external_count
						), 
						array( 'sanitized_anchor' => $sanitized_anchor )
					);
				} else {
					$count = 1;
					$internal_count = 0;
					$external_count = 0;
					if(strcasecmp($domain, $site_domain) == 0){
						$internal_count = $internal_count + 1;
					} else {
						$external_count = $external_count + 1;
					}
					$anch = preg_replace('/\s+/', ' ', trim($a['anchor']));


					$wpdb->insert(
						$table_anchors, 
						array(
							'count' => 1,
							'anchor' => $anch,
							'internal_count' => $internal_count,
							'external_count' => $external_count,
							'sanitized_anchor' => $sanitized_anchor
						)
					);
				}
				$post_link_item = $wpdb->get_row("SELECT count FROM $table_post_links WHERE post_id = '$post_id'");
				if($post_link_item != null){
					$count = $post_link_item->count + 1;
					$wpdb->update( 
						$table_post_links, 
						array( 'count' => $count ), 
						array( 'post_id' => $post_id )
					);
				} else {
					$wpdb->insert(
						$table_post_links, 
						array(
							'count' => 1,
							'post_id' => $post_id
						)
					);
				}

				$url2 = strtok($url, '#');
				$o_link = untrailingslashit($url2);
				$prsed = parse_url($o_link);
				unset($prsed['scheme']);
				$link = self::unparse_url($prsed);
				$unique_links_item = $wpdb->get_row("SELECT count FROM $table_unique_links WHERE link = '$link'");
				if($unique_links_item != null){
					$count = $unique_links_item->count + 1;
					$wpdb->update( 
						$table_unique_links, 
						array( 'count' => $count, 'deleted' => 0 ), 
						array('link' => $link)
					);
				} else {
					if(strcasecmp($domain, $site_domain) == 0){
						$is_internal = 1;
					} else {
						$is_internal = 0;
					}
					$wpdb->insert(
						$table_unique_links, 
						array(
							'count' => 1,
							'deleted' => 0,
							'link' => $link,
							'original_link' => $url2,
							'is_internal' => $is_internal
						)
					);
				}
			}
			
			$current_page = $current_page + 1;
			$post_item = $wpdb->get_row( 
				"SELECT ID, post_type FROM {$wpdb->posts} ".
				"WHERE post_status = 'publish' ".
				"ORDER BY ID ASC ".
				"LIMIT 1 OFFSET $current_page" 
			);
		}
	}

	public static function install(){
		global $wpdb;
		self::add_option("installed", TRUE);

		$links_table = LLA_TABLE;
		$__links_table__ = "CREATE TABLE IF NOT EXISTS $links_table (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			date_modified INT UNSIGNED NOT NULL,
			date_created INT UNSIGNED NOT NULL,
			post_type VARCHAR(25) DEFAULT NULL,
			http_status INT UNSIGNED DEFAULT 0,
			fetch_time INT UNSIGNED DEFAULT 0,
			href_hash VARCHAR(100) DEFAULT '',
			anchor VARCHAR(2048) DEFAULT '',
			anchor_title VARCHAR(2048) DEFAULT '',
			link_status VARCHAR(25) DEFAULT 'queued',
			permalink VARCHAR(2048) DEFAULT '',
			domain VARCHAR(255) DEFAULT '',
			post_id INT UNSIGNED DEFAULT 0,
			status INT UNSIGNED DEFAULT 0,
			nofollow TINYINT(1) DEFAULT 0,
			noindex TINYINT(1) DEFAULT 0,
			href VARCHAR(2048) DEFAULT '',
			tld VARCHAR(255) DEFAULT '',
			extras text
		);";
		
		$domains_table = LLA_DOMAINS_TABLE;
		$__domains_table__ = "CREATE TABLE IF NOT EXISTS $domains_table (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			mozrank_subdomain DECIMAL(20, 15) DEFAULT 0.0,
			inbound_links_equity INT UNSIGNED DEFAULT 0,
			domain_authority INT UNSIGNED DEFAULT 0,
			page_authority INT UNSIGNED DEFAULT 0,
			http_status_code INT UNSIGNED DEFAULT 0,
			mozrank_url DECIMAL(20, 15) DEFAULT 0.0,
			inbound_links INT UNSIGNED DEFAULT 0,
			spam_score INT UNSIGNED DEFAULT 0,
			status INT UNSIGNED DEFAULT 0,
			domain VARCHAR(255) NOT NULL,
			count INT UNSIGNED DEFAULT 0,
			mozzed TINYINT(1) DEFAULT 0,
			httped TINYINT(1) DEFAULT 0,
			deleted TINYINT(1) DEFAULT 0
		);";

		$anchor_table = LLA_ANCHORS_TABLE;
		$__anchor_table__ = "CREATE TABLE IF NOT EXISTS $anchor_table (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			sanitized_anchor VARCHAR(255) NOT NULL,
			external_count INT UNSIGNED DEFAULT 0,
			internal_count INT UNSIGNED DEFAULT 0,
			anchor VARCHAR(255) NOT NULL,
			count INT UNSIGNED DEFAULT 0
		);";

		$post_links_table = LLA_POST_LINKS_TABLE;
		$__post_links_table__ = "CREATE TABLE IF NOT EXISTS $post_links_table (
			post_id INT UNSIGNED PRIMARY KEY,
			count INT UNSIGNED DEFAULT 0
		);";

		$unique_links_table = LLA_UNIQUE_LINKS_TABLE;
		$__unique_links_table__ = "CREATE TABLE IF NOT EXISTS $unique_links_table (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			mozrank_subdomain DECIMAL(20, 15) DEFAULT 0.0,
			inbound_links_equity INT UNSIGNED DEFAULT 0,
			domain_authority INT UNSIGNED DEFAULT 0,
			page_authority INT UNSIGNED DEFAULT 0,
			http_status_code INT UNSIGNED DEFAULT 0,
			mozrank_url DECIMAL(20, 15) DEFAULT 0.0,
			spam_score INT UNSIGNED DEFAULT 0,
			inbound_links INT UNSIGNED DEFAULT 0,
			original_link VARCHAR(2048) NOT NULL,
			is_internal TINYINT(1) DEFAULT 0,
			status INT UNSIGNED DEFAULT 0,
			count INT UNSIGNED DEFAULT 0,
			link VARCHAR(2048) NOT NULL,
			mozzed TINYINT(1) DEFAULT 0,
			httped TINYINT(1) DEFAULT 0,
			deleted TINYINT(1) DEFAULT 0,
			canonical VARCHAR(2048)
		);";

		$strip_links_table = LLA_STRIP_LINKS_TABLE;
		$__strip_links_table__ = "CREATE TABLE IF NOT EXISTS $strip_links_table (
			href_hash VARCHAR(50) PRIMARY KEY,
			status INT UNSIGNED DEFAULT 0
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $__unique_links_table__ );
		dbDelta( $__strip_links_table__ );
		dbDelta( $__post_links_table__ );
		dbDelta( $__domains_table__ );
		dbDelta( $__anchor_table__ );
		dbDelta( $__links_table__ );

		wp_schedule_event(time(), 'lla_five_minutes', LLA_CRON_HOOK);
	}
	public static function uninstall(){
		self::add_option("installed", FALSE);
		wp_clear_scheduled_hook(LLA_CRON_HOOK);
	}
	private function load_ajax_actions(){
		require_once(__LLA_AJAX_ACTIONS_FILE__);
		$class_name = __LLA_AJAX_ACTIONS__;
		$class = new $class_name();
	}
	private function start_dashboard(){
		require_once(__LLA_DASHBOARD_FILE__);
		$class_name = __LLA_DASHBOARD__;
		$class = new $class_name();
	}
}
?>
