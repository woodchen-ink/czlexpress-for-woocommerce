<?php
class CZL_Label {
    private static $api;
    
    public static function init() {
        self::$api = new CZL_API();
    }
    
    /**
     * 获取标签URL
     */
    public static function get_label_url($order_id) {
        try {
            $czl_order_id = get_post_meta($order_id, '_czl_order_id', true);
            if (!$czl_order_id) {
                throw new Exception('未找到CZL订单号');
            }
            
            $api = new CZL_API();
            $url = $api->get_label($czl_order_id);
            
            if (empty($url)) {
                throw new Exception('获取标签URL失败');
            }
            
            return $url;
            
        } catch (Exception $e) {
            CZL_Logger::error('Failed to get label URL', array(
                'order_id' => $order_id,
                'error' => $e->getMessage()
            ));
            throw $e;
        }
    }
    
    /**
     * 打印运单标签
     */
    public static function print_label($order_id) {
        try {
            $url = self::get_label_url($order_id);
            
            if (empty($url)) {
                throw new Exception('标签URL为空');
            }
            
            // 保存标签URL
            update_post_meta($order_id, '_czl_label_url', $url);
            
            return $url;
            
        } catch (Exception $e) {
            CZL_Logger::error('Failed to print label', array(
                'order_id' => $order_id,
                'error' => $e->getMessage()
            ));
            throw $e;
        }
    }
    
    /**
     * 添加标签打印按钮
     */
    public static function add_print_actions($actions, $order) {
        try {
            $url = self::get_label_url($order->get_id());
            if ($url) {
                $actions['czl_print_label'] = array(
                    'url' => wp_nonce_url(admin_url('admin-ajax.php?action=czl_print_label&order_id=' . $order->get_id()), 'czl_print_label'),
                    'name' => __('打印运单', 'czlexpress-for-woocommerce'),
                    'action' => 'czl_print_label'
                );
            }
        } catch (Exception $e) {
            CZL_Logger::error('Label print action error', array(
                'order_id' => $order->get_id(),
                'error' => $e->getMessage()
            ));
        }
        return $actions;
    }
    
    /**
     * 处理标签打印请求
     */
    public static function handle_print_request() {
        if (!current_user_can('edit_shop_orders')) {
            wp_die(esc_html__('您没有权限执行此操作', 'czlexpress-for-woocommerce'));
        }
        
        check_admin_referer('czl_print_label');
        
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        if (!$order_id) {
            wp_die(esc_html__('订单ID无效', 'czlexpress-for-woocommerce'));
        }
        
        $label_url = self::get_label_url($order_id);
        if (!$label_url) {
            wp_die(esc_html__('未找到运单标签', 'czlexpress-for-woocommerce'));
        }
        
        wp_redirect($label_url);
        exit;
    }
}

// 在插件初始化时调用
add_action('init', array('CZL_Label', 'init')); 