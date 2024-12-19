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
            $order = wc_get_order($order_id);
            if (!$order) {
                throw new Exception('订单不存在');
            }
            
            // 获取运单号
            $tracking_number = $order->get_meta('_czl_tracking_number');
            $czl_order_id = $order->get_meta('_czl_order_id');
            
            if (empty($tracking_number) && empty($czl_order_id)) {
                throw new Exception('未找到运单号或订单号');
            }
            
            // 构建标签URL
            $base_url = 'https://tms.czl.net/printOrderLabel.htm';
            $params = array();
            
            if (!empty($tracking_number)) {
                $params['documentCode'] = $tracking_number;
            } else {
                $params['order_id'] = $czl_order_id;
            }
            
            $url = add_query_arg($params, $base_url);
            return $url;
            
        } catch (Exception $e) {
            error_log('CZL Express Error: Failed to get label URL - ' . $e->getMessage());
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
                    'name' => __('打印运单', 'woo-czl-express'),
                    'action' => 'czl_print_label'
                );
            }
        } catch (Exception $e) {
            error_log('CZL Express Error: ' . $e->getMessage());
        }
        return $actions;
    }
    
    /**
     * 处理标签打印请求
     */
    public static function handle_print_request() {
        if (!current_user_can('edit_shop_orders')) {
            wp_die(__('您没有权限执行此操作', 'woo-czl-express'));
        }
        
        check_admin_referer('czl_print_label');
        
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        if (!$order_id) {
            wp_die(__('订单ID无效', 'woo-czl-express'));
        }
        
        $label_url = self::get_label_url($order_id);
        if (!$label_url) {
            wp_die(__('未找到运单标签', 'woo-czl-express'));
        }
        
        wp_redirect($label_url);
        exit;
    }
}

// 在插件初始化时调用
add_action('init', array('CZL_Label', 'init')); 