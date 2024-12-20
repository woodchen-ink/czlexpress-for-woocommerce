<?php
class CZL_Ajax {
    public function __construct() {
        add_action('wp_ajax_czl_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_czl_test_shipping_rate', array($this, 'test_shipping_rate'));
        add_action('wp_ajax_czl_print_label', array($this, 'handle_print_label'));
    }
    
    public function test_connection() {
        check_ajax_referer('czl_test_api', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'czlexpress-for-woocommerce')));
        }
        
        $tester = new CZL_API_Test();
        $result = $tester->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function test_shipping_rate() {
        check_ajax_referer('czl_test_api', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'czlexpress-for-woocommerce')));
        }
        
        $tester = new CZL_API_Test();
        $result = $tester->test_shipping_rate();
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function handle_print_label() {
        try {
            check_ajax_referer('czl-print-label', 'security');
            
            if (!current_user_can('manage_woocommerce')) {
                throw new Exception('权限不足');
            }
            
            $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
            if (!$order_id) {
                throw new Exception('订单ID无效');
            }
            
            $label = new CZL_Label();
            $url = $label->get_label_url($order_id);
            
            wp_send_json_success(array('url' => $url));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
} 