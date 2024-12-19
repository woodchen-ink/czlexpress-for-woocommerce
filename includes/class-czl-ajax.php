<?php
class CZL_Ajax {
    public function __construct() {
        add_action('wp_ajax_czl_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_czl_test_shipping_rate', array($this, 'test_shipping_rate'));
    }
    
    public function test_connection() {
        check_ajax_referer('czl_test_api', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'woo-czl-express')));
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
            wp_send_json_error(array('message' => __('Permission denied', 'woo-czl-express')));
        }
        
        $tester = new CZL_API_Test();
        $result = $tester->test_shipping_rate();
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result);
        }
    }
} 