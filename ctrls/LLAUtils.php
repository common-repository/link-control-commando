<?php
if ( ! defined( 'ABSPATH' ) ) exit; 
class LLAUtils{
	public static function add_option( $option, $value){
		update_option(sprintf('%s%s', LLA_WP_OPTIONS, $option), $value);
	}
	public static function get_option( $option ){
		return get_option(sprintf('%s%s', LLA_WP_OPTIONS, $option));
	}
	private static function url_origin( $s, $use_forwarded_host = false ){
		$ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
		$sp       = strtolower( $s['SERVER_PROTOCOL'] );
		$protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
		$port     = $s['SERVER_PORT'];
		$port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
		$host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
		$host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
		return $protocol . '://' . $host;
	}

	public static function full_url( $s, $use_forwarded_host = false ){
    	return self::url_origin( $s, $use_forwarded_host ) . $s['REQUEST_URI'];
	}
	public static function rel2abs($rel, $base){
		if(strpos($rel,"//")===0){
			return "http:".$rel;
		}
		if(strpos($rel,"/")===0){
			return parse_url($base, PHP_URL_SCHEME).'://'.parse_url($base, PHP_URL_HOST).$rel;
		}
		/* return if  already absolute URL */
		if  (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
		/* queries and  anchors */
		if ((is_array($rel) && count($rel)) && ($rel[0]=='#'  || $rel[0]=='?')) return $base.$rel;
		/* parse base URL  and convert to local variables:
		$scheme, $host,  $path */
		extract(parse_url($base));
		/* remove  non-directory element from path */
		$path = isset($path) ?  preg_replace('#/[^/]*$#',  '', $path) : '';
		/* destroy path if  relative url points to root */
		if (is_array($rel) && count($rel) && $rel[0] ==  '/') $path = '';
		/* dirty absolute  URL */
		$abs =  "$host$path/$rel";
		/* replace '//' or  '/./' or '/foo/../' with '/' */
		$re =  array('#(/.?/)#', '#/(?!..)[^/]+/../#');
		//for($n=1; $n>0;  $abs=preg_replace($re, '/', $abs, -1, $n)) {}
		/* absolute URL is  ready! */
		return  $scheme.'://'.$abs;
	}
	public static function link_href_hash($link){
		$link = untrailingslashit(strtok($link, '#'));
		$prsed = parse_url($link);
		unset($prsed['scheme']);
		return md5(self::unparse_url($prsed));
	}
	public static function domain_href_hash($link){
		$domain = parse_url($link, PHP_URL_HOST);
		return md5($domain);
	}
	public static function get_domain($url){
		$pieces = parse_url($url);
		$domain = isset($pieces['host']) ? $pieces['host'] : '';
		if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
		    return $regs['domain'];
		}
		return false;
	}
	public static function unparse_url($parsed_url) { 
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
		$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
		$user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
		$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
		$pass     = ($user || $pass) ? "$pass@" : ''; 
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
		$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
		return "$scheme$user$pass$host$port$path$query$fragment"; 
	}
	public static function make_internal_url($link, $site_url){
		$link_host = strtolower(preg_replace('/^(www\.)/i', '', parse_url($link, PHP_URL_HOST)));
		$site_host = strtolower(preg_replace('/^(www\.)/i', '', parse_url($site_url, PHP_URL_HOST)));


		$parsed_link = parse_url($link);
		$parsed_link['scheme'] = parse_url($site_url, PHP_URL_SCHEME);
		$parsed_link['host'] = parse_url($site_url, PHP_URL_HOST);
		unset($parsed_link['port']);
		if($link_host != $site_host){
			$parsed_link['path'] = "/d/$link_host" . parse_url($link, PHP_URL_PATH);
		}
		return self::unparse_url($parsed_link);
	}
}
?>
