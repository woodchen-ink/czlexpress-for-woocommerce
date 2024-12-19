<?php
class CZL_Install {
    public static function init() {
        self::create_tables();
        self::create_options();
    }
    
    /**
     * 创建数据表
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}czl_shipments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            tracking_number varchar(50),
            czl_order_id varchar(50),
            reference_number varchar(50),
            is_remote varchar(10),
            is_residential varchar(10),
            shipping_method varchar(50),
            status varchar(20),
            last_sync_time datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY tracking_number (tracking_number)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public static function create_options() {
        add_option('czl_express_api_url', 'https://api.czl.net');
        add_option('czl_express_api_key', '');
        add_option('czl_express_api_secret', '');
        add_option('czl_express_test_mode', 'yes');
        add_option('czl_last_tracking_sync', 0);
        add_option('czl_product_groups', array());
    }
    
    public static function deactivate() {
        // 只清理定时任务
        wp_clear_scheduled_hook('czl_sync_tracking_numbers_hook');
    }
    
    public static function uninstall() {
        global $wpdb;
        
        // 只有在卸载时才删除数据
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }
        
        // 删除数据表
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}czl_shipments");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}czl_tracking_history");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}czl_shipping_rules");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}czl_product_groups");
        
        // 删除选项
        delete_option('czl_express_api_url');
        delete_option('czl_express_api_key');
        delete_option('czl_express_api_secret');
        delete_option('czl_express_test_mode');
        delete_option('czl_express_version');
        delete_option('czl_last_tracking_sync');
        delete_option('czl_express_db_version');
        delete_option('czl_product_groups');
    }
} 