class WC_CZLExpress_Orders_List {

    public function get_orders($args = array()) {
        // 使用新的订单查询API
        $query = new \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableQuery($args);
        return $query->get_orders();
    }
    
    public function get_order_items($order_id) {
        $order = wc_get_order($order_id);
        return $order ? $order->get_items() : array();
    }
} 