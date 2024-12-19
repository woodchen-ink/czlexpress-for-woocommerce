<?php
class CZL_Install {
    public static function init() {
        self::create_tables();
        self::create_options();
    }
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 创建运单表
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}czl_shipments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            tracking_number varchar(50) NOT NULL,
            label_url varchar(255) DEFAULT NULL,
            shipping_method varchar(100) NOT NULL,
            status varchar(50) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY tracking_number (tracking_number)
        ) $charset_collate;";
        
        // 创建运费规则表
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}czl_shipping_rules (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            shipping_method varchar(100) NOT NULL,
            czl_product_code varchar(100) NOT NULL,
            price_adjustment varchar(255) DEFAULT NULL,
            status int(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public static function create_options() {
        // 添加默认配置选项
        add_option('czl_api_url', '');
        add_option('czl_username', '');
        add_option('czl_password', '');
        add_option('czl_exchange_rate', '1');
        add_option('czl_product_groups', '');
    }
} 