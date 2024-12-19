class WC_CZLExpress_Order {
    
    public function save_tracking_info($order_id, $tracking_number, $label_url) {
        // 使用新的HPOS API保存跟踪信息
        $order = wc_get_order($order_id);
        
        if ($order) {
            // 使用新的元数据API
            $order->update_meta_data('_czlexpress_tracking_number', $tracking_number);
            $order->update_meta_data('_czlexpress_label_url', $label_url);
            $order->save();
        }
    }

    public function get_tracking_info($order_id) {
        $order = wc_get_order($order_id);
        
        if ($order) {
            return array(
                'tracking_number' => $order->get_meta('_czlexpress_tracking_number'),
                'label_url' => $order->get_meta('_czlexpress_label_url')
            );
        }
        
        return false;
    }
} 