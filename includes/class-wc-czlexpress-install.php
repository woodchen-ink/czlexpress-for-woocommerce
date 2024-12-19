class WC_CZLExpress_Install {

    public function migrate_to_hpos() {
        global $wpdb;
        
        // 获取所有需要迁移的订单
        $orders = $wpdb->get_results("
            SELECT post_id, meta_key, meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '_czlexpress_%'
        ");
        
        foreach ($orders as $order_data) {
            $order = wc_get_order($order_data->post_id);
            if ($order) {
                // 迁移元数据到新系统
                $order->update_meta_data($order_data->meta_key, $order_data->meta_value);
                $order->save();
            }
        }
    }
} 