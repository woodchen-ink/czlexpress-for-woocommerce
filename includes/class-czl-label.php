<?php
class CZL_Label {
    /**
     * 获取标签URL
     */
    public static function get_label_url($order_id) {
        $czl_order_id = get_post_meta($order_id, '_czl_order_id', true);
        if (!$czl_order_id) {
            return false;
        }
        
        $api = new CZL_API();
        return $api->get_label($czl_order_id);
    }
    
    /**
     * 添加标签打印按钮
     */
    public static function add_print_actions($actions, $order) {
        if (self::get_label_url($order->get_id())) {
            $actions['czl_print_label'] = array(
                'url' => wp_nonce_url(admin_url('admin-ajax.php?action=czl_print_label&order_id=' . $order->get_id()), 'czl_print_label'),
                'name' => __('打印运单', 'woo-czl-express'),
                'action' => 'czl_print_label'
            );
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