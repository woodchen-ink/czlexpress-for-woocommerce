<?php
// 如果没有通过WordPress调用，则退出
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 删除数据表
global $wpdb;

// 删除所有相关的数据表
$tables = array(
    'czl_shipments',
    'czl_tracking_history',
    'czl_shipping_rules',
    'czl_product_groups'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
}

// 删除所有相关的选项
$options = array(
    'czl_express_api_url',
    'czl_express_api_key',
    'czl_express_api_secret',
    'czl_express_test_mode',
    'czl_express_version',
    'czl_last_tracking_sync',
    'czl_express_db_version',
    'czl_product_groups',
    'czl_username',
    'czl_password',
    'czl_rate_adjustment'
);

foreach ($options as $option) {
    delete_option($option);
}

// 删除所有订单的相关元数据
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_czl_%'");

// 清理定时任务
wp_clear_scheduled_hook('czl_sync_tracking_numbers_hook');
wp_clear_scheduled_hook('czl_update_tracking_info'); 