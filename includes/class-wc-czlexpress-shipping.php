class WC_CZLExpress_Shipping {

    public function get_shipping_info($order_id) {
        $order = wc_get_order($order_id);
        
        if ($order) {
            return array(
                'weight' => $order->get_meta('_czlexpress_weight'),
                'remote_fee' => $order->get_meta('_czlexpress_remote_fee'),
                'shipping_method' => $order->get_meta('_czlexpress_shipping_method')
            );
        }
        
        return false;
    }
} 