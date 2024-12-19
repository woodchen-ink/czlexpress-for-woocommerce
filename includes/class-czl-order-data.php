<?php
class CZL_Order_Data {
    private $order;
    private $customer_id;
    private $customer_userid;
    
    public function __construct($order) {
        $this->order = $order;
        
        // 获取认证信息
        $auth = get_option('czl_auth_info', array());
        $this->customer_id = !empty($auth['customer_id']) ? $auth['customer_id'] : '';
        $this->customer_userid = !empty($auth['customer_userid']) ? $auth['customer_userid'] : '';
    }
    
    /**
     * 准备创建运单的数据
     */
    public function prepare() {
        $shipping_address = $this->order->get_shipping_address_1();
        if ($this->order->get_shipping_address_2()) {
            $shipping_address .= ' ' . $this->order->get_shipping_address_2();
        }
        
        // 基础订单数据
        $data = array(
            'buyerid' => '',
            'consignee_address' => $shipping_address,
            'order_piece' => 1, // 默认1件
            'consignee_city' => $this->order->get_shipping_city(),
            'consignee_mobile' => $this->order->get_shipping_phone(),
            'order_returnsign' => 'N',
            'consignee_name' => $this->order->get_shipping_first_name() . ' ' . $this->order->get_shipping_last_name(),
            'trade_type' => 'ZYXT',
            'consignee_postcode' => $this->order->get_shipping_postcode(),
            'consignee_state' => $this->order->get_shipping_state(),
            'consignee_telephone' => $this->order->get_shipping_phone(),
            'country' => $this->order->get_shipping_country(),
            'customer_id' => $this->customer_id,
            'customer_userid' => $this->customer_userid,
            'order_customerinvoicecode' => $this->order->get_order_number(),
            'product_id' => $this->get_shipping_product_id(),
            'consignee_email' => $this->order->get_billing_email(),
            'consignee_companyname' => $this->order->get_shipping_company(),
            'order_cargoamount' => $this->order->get_total(),
            'orderInvoiceParam' => $this->prepare_items()
        );
        
        return $data;
    }
    
    /**
     * 准备商品信息
     */
    private function prepare_items() {
        $items = array();
        $order_items = $this->order->get_items();
        
        foreach ($order_items as $item) {
            $product = $item->get_product();
            
            $items[] = array(
                'invoice_amount' => $item->get_total(),
                'invoice_pcs' => $item->get_quantity(),
                'invoice_title' => $product->get_meta('_czl_name_en') ?: $product->get_name(),
                'invoice_weight' => $product->get_weight(),
                'sku' => $product->get_name(),
                'hs_code' => $product->get_meta('_czl_hs_code')
            );
        }
        
        return $items;
    }
    
    /**
     * 获取配送方式ID
     */
    private function get_shipping_product_id() {
        $shipping_methods = $this->order->get_shipping_methods();
        foreach ($shipping_methods as $shipping_method) {
            if (strpos($shipping_method->get_method_id(), 'czl_express_') === 0) {
                return str_replace('czl_express_', '', $shipping_method->get_method_id());
            }
        }
        return '';
    }
} 