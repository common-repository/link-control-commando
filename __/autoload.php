<?php
if ( ! defined( 'ABSPATH' ) ) exit; 

global $wpdb;
$slug =  "lla0020";
define("LLA_DIR_NAME", dirname(dirname(plugin_basename(__FILE__)) ));
define("__LLA_PLUGIN_DIR__", trailingslashit(trailingslashit(WP_PLUGIN_DIR).LLA_DIR_NAME));
define("__LLA_PLUGIN_URL__", trailingslashit(plugins_url('', dirname(plugin_basename(__FILE__)) )));

define("__LLA_DIR_CTRLS__", trailingslashit(__LLA_PLUGIN_DIR__."ctrls"));
define("__LLA_DIR_VIEWS__", trailingslashit(__LLA_PLUGIN_DIR__."views"));
define("__LLA_DIR_MODELS__", trailingslashit(__LLA_PLUGIN_DIR__."models"));

define("LLA_SLUG", $slug);
define("LLA_VERSION", "0.0.6");
define("LLA_SHORT_NAME", "LLA");
define("LLA_DATABASE_VERSION", "");
define("LLA_NAMESPACE", "_".LLA_SLUG."_");
define("LLA_LONG_NAME", "Link Control Commando");
define("LLA_CRON_HOOK", LLA_NAMESPACE."hook");
define("LLA_DEFAULT_CAPABILITY", "edit_pages");
define("LLA_USER_AGENT", 'WordPress/5.0; Plugin(Local Link Alpha)');

define("LLA_TABLE", sprintf('%s%s',$wpdb->prefix,LLA_SLUG ));
define("LLA_DOMAINS_TABLE", sprintf('%s_%s',LLA_TABLE, "domains" ));
define("LLA_ANCHORS_TABLE", sprintf('%s_%s',LLA_TABLE, "anchors" ));
define("LLA_STRIP_LINKS_TABLE", sprintf('%s_%s',LLA_TABLE, "strip" ));
define("LLA_POST_LINKS_TABLE", sprintf('%s_%s',LLA_TABLE, "post_links" ));
define("LLA_UNIQUE_LINKS_TABLE", sprintf('%s_%s',LLA_TABLE, "unique_links" ));

define("LLA_WP_OPTIONS", LLA_NAMESPACE."_options".LLA_DATABASE_VERSION);
define("LLA_SNAPSHOT_TABLE", sprintf('%s%s_snapshots',$wpdb->prefix,LLA_SLUG ));

define("__LLA__", LLA_SHORT_NAME);
define("__LLA_FILE__",  __LLA_DIR_CTRLS__.__LLA__.".php");

define("__LLA_JS__", __LLA__."JS");
define("__LLA_JS_FILE__",  __LLA_DIR_CTRLS__.__LLA_JS__.".php");

define("__LLA_TABLE_POST__", __LLA__."TablePost");
define("__LLA_TABLE_POST__FILE__",  __LLA_DIR_CTRLS__.__LLA_TABLE_POST__.".php");
define("__LLA_TABLE_ANCHOR__", __LLA__."TableAnchor");
define("__LLA_TABLE_ANCHOR__FILE__",  __LLA_DIR_CTRLS__.__LLA_TABLE_ANCHOR__.".php");
define("__LLA_TABLE_LINKS__", __LLA__."TableLinks");
define("__LLA_TABLE_LINKS__FILE__",  __LLA_DIR_CTRLS__.__LLA_TABLE_LINKS__.".php");
define("__LLA_TABLE_DOMAINS__", __LLA__."TableDomains");
define("__LLA_TABLE_DOMAINS__FILE__",  __LLA_DIR_CTRLS__.__LLA_TABLE_DOMAINS__.".php");
define("__LLA_TABLE_UNIQUE_LINKS__", __LLA__."TableUniqueLinks");
define("__LLA_TABLE_UNIQUE_LINKS__FILE__",  __LLA_DIR_CTRLS__.__LLA_TABLE_UNIQUE_LINKS__.".php");

define("__LLA_CSS__", __LLA__."CSS");
define("__LLA_CSS_FILE__",  __LLA_DIR_CTRLS__.__LLA_CSS__.".php");

define("__LLA_UTILS__", __LLA__."Utils");
define("__LLA_UTILS_FILE__",  __LLA_DIR_CTRLS__.__LLA_UTILS__.".php");

define("__LLA_DASHBOARD__", __LLA__."Dashboard");
define("__LLA_DASHBOARD_FILE__",  __LLA_DIR_CTRLS__.__LLA_DASHBOARD__.".php");

define("__LLA_AJAX_ACTIONS__", __LLA__."AjaxActions");
define("__LLA_AJAX_ACTIONS_FILE__",  __LLA_DIR_CTRLS__.__LLA_AJAX_ACTIONS__.".php");

require_once(__LLA_UTILS_FILE__);
require_once(__LLA_FILE__);
$___LLA___ = __LLA__;
new $___LLA___();
register_activation_hook(__LLA_BASE_FILE__, array($___LLA___, 'install'));
register_deactivation_hook(__LLA_BASE_FILE__, array($___LLA___, 'uninstall'));
?>
