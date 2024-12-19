<?php
class WooCzlExpress {
    private static $instance = null;
    private $order_handler;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        register_activation_hook(WOO_CZL_EXPRESS_PATH . 'woo-commerce-czlexpress.php', array('CZL_Install', 'init'));
        $this->init();
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_czl_test_connection', array($this, 'handle_test_connection'));
        
        // 添加自定义订单状态
        add_action('init', array($this, 'register_custom_order_statuses'));
        add_filter('wc_order_statuses', array($this, 'add_custom_order_statuses'));
        
        // 添加订单状态自动更新
        add_action('wp_ajax_czl_update_order_status', array($this, 'update_order_status'));
        add_action('wp_ajax_nopriv_czl_update_order_status', array($this, 'update_order_status'));
        
        // 添加订单详情页面的轨迹显示
        add_action('woocommerce_order_details_after_order_table', array('CZL_Tracking', 'display_tracking_info'));
        add_action('woocommerce_admin_order_data_after_shipping_address', array('CZL_Tracking', 'display_admin_tracking_info'));
        
        // 添加定时任务
        add_action('czl_update_tracking_info', array($this, 'schedule_tracking_updates'));
        
        // 注册定时任务
        if (!wp_next_scheduled('czl_update_tracking_info')) {
            wp_schedule_event(time(), 'hourly', 'czl_update_tracking_info');
        }
    }
    
    private function init() {
        // 加载依赖文件
        $this->load_dependencies();
        
        // 初始化产品字段
        new CZL_Product_Fields();
        
        // 初始化钩子
        $this->init_hooks();
        
        // 添加菜单
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    private function load_dependencies() {
        require_once WOO_CZL_EXPRESS_PATH . 'includes/class-czl-api.php';
        require_once WOO_CZL_EXPRESS_PATH . 'includes/class-czl-rate-calculator.php';
        require_once WOO_CZL_EXPRESS_PATH . 'includes/class-czl-shipping-method.php';
        require_once WOO_CZL_EXPRESS_PATH . 'includes/class-czl-install.php';
        require_once WOO_CZL_EXPRESS_PATH . 'includes/class-czl-settings.php';
        require_once WOO_CZL_EXPRESS_PATH . 'includes/class-czl-order-handler.php';
        require_once WOO_CZL_EXPRESS_PATH . 'includes/class-czl-order-data.php';
        require_once WOO_CZL_EXPRESS_PATH . 'includes/class-czl-label.php';
        require_once WOO_CZL_EXPRESS_PATH . 'includes/class-czl-tracking.php';
        require_once WOO_CZL_EXPRESS_PATH . 'includes/class-czl-product-fields.php';
        require_once WOO_CZL_EXPRESS_PATH . 'includes/class-czl-order.php';
    }
    
    private function init_hooks() {
        add_action('woocommerce_shipping_init', array($this, 'init_shipping_method'));
        add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_method'));
        
        // 订单处理钩子
        $order_handler = new CZL_Order_Handler();
        add_action('woocommerce_order_status_processing', array($order_handler, 'create_shipment'));
        add_action('woocommerce_order_status_cancelled', array($order_handler, 'cancel_shipment'));
        
        // 运单标签钩子
        add_filter('woocommerce_order_actions', array('CZL_Label', 'add_print_actions'), 10, 2);
        add_action('wp_ajax_czl_print_label', array('CZL_Label', 'handle_print_request'));
        
        // 跟踪信息显示钩子
        add_action('woocommerce_order_details_after_order_table', array('CZL_Tracking', 'display_tracking_info'));
        add_action('woocommerce_admin_order_data_after_shipping_address', array('CZL_Tracking', 'display_admin_tracking_info'));
        
        // 添加订单操作
        add_filter('woocommerce_order_actions', array($this, 'add_order_actions'), 10, 2);
        
        // 添加AJAX处理
        add_action('wp_ajax_czl_create_shipment', array($this, 'handle_create_shipment'));
        add_action('wp_ajax_czl_bulk_create_shipment', array($this, 'handle_bulk_create_shipment'));
    }
    
    public function init_shipping_method() {
        if (!class_exists('WC_CZL_Shipping_Method')) {
            require_once WOO_CZL_EXPRESS_PATH . 'includes/class-czl-shipping-method.php';
        }
    }
    
    public function add_shipping_method($methods) {
        if (class_exists('WC_CZL_Shipping_Method')) {
            $methods['czl_express'] = 'WC_CZL_Shipping_Method';
        }
        return $methods;
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('CZL Express', 'woo-czl-express'),
            __('CZL Express', 'woo-czl-express'),
            'manage_woocommerce',
            'woo-czl-express',
            array($this, 'render_settings_page'),
            'dashicons-airplane'
        );
        
        add_submenu_page(
            'woo-czl-express',
            __('基本设置', 'woo-czl-express'),
            __('基本设置', 'woo-czl-express'),
            'manage_woocommerce',
            'woo-czl-express',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'woo-czl-express',
            __('订单管理', 'woo-czl-express'),
            __('订单管理', 'woo-czl-express'),
            'manage_woocommerce',
            'czl-express-orders',
            array($this, 'render_orders_page')
        );
        
        add_submenu_page(
            'woo-czl-express',
            __('产品分组', 'woo-czl-express'),
            __('产品分组', 'woo-czl-express'),
            'manage_options',
            'czl-product-groups',
            array($this, 'render_product_groups_page')
        );
        
        add_submenu_page(
            'woo-czl-express',
            __('汇率设置', 'woo-czl-express'),
            __('汇率设置', 'woo-czl-express'),
            'manage_options',
            'czl-exchange-rates',
            array($this, 'render_exchange_rates_page')
        );
    }
    
    public function render_product_groups_page() {
        // 处理表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
            check_admin_referer('czl_save_product_groups', 'czl_product_groups_nonce')) {
            
            $groups = array();
            if (!empty($_POST['groups'])) {
                foreach ($_POST['groups'] as $key => $group) {
                    if (empty($group['groupName'])) continue;
                    
                    $prefixes = array_filter(array_map('trim', explode("\n", $group['prefixes'])));
                    if (empty($prefixes)) continue;
                    
                    $groups[sanitize_key($group['groupName'])] = array(
                        'enabled' => !empty($group['enabled']),
                        'groupName' => sanitize_text_field($group['groupName']),
                        'prefixes' => array_map('sanitize_text_field', $prefixes)
                    );
                }
            }
            
            update_option('czl_product_groups', $groups);
            add_settings_error('czl_messages', 'czl_message', __('产品分组设置已保存', 'woo-czl-express'), 'updated');
        }
        
        // 显示设置页面
        require_once WOO_CZL_EXPRESS_PATH . 'admin/views/product-groups.php';
    }
    
    public function admin_page() {
        require_once WOO_CZL_EXPRESS_PATH . 'admin/views/admin-page.php';
    }
    
    /**
     * 添加订单操作按钮
     */
    public function add_order_actions($actions, $order) {
        // 检查是否已创建运单
        $tracking_number = get_post_meta($order->get_id(), '_czl_tracking_number', true);
        
        if ($tracking_number) {
            $actions['czl_cancel_shipment'] = __('取消CZL运单', 'woo-czl-express');
        } else {
            $actions['czl_create_shipment'] = __('创建CZL运单', 'woo-czl-express');
        }
        
        return $actions;
    }
    
    public function register_settings() {
        // 注册设置组
        register_setting(
            'czl_options_group',  // 设置组名称
            'czl_username'        // 用户名选项
        );
        
        register_setting(
            'czl_options_group',
            'czl_password'
        );
        
        register_setting(
            'czl_options_group',
            'czl_rate_adjustment'
        );
        
        // 添加设置分节
        add_settings_section(
            'czl_api_settings',
            __('API设置', 'woo-czl-express'),
            null,
            'czl_options'
        );
        
        // 添加设置字段
        add_settings_field(
            'czl_username',
            __('用户名', 'woo-czl-express'),
            array($this, 'username_field_callback'),
            'czl_options',
            'czl_api_settings'
        );
        
        add_settings_field(
            'czl_password',
            __('密码', 'woo-czl-express'),
            array($this, 'password_field_callback'),
            'czl_options',
            'czl_api_settings'
        );
        
        // 添加运费调整设置分节
        add_settings_section(
            'czl_rate_settings',
            __('运费设置', 'woo-czl-express'),
            null,
            'czl_options'
        );
        
        add_settings_field(
            'czl_rate_adjustment',
            __('运费调整公式', 'woo-czl-express'),
            array($this, 'rate_adjustment_field_callback'),
            'czl_options',
            'czl_rate_settings'
        );
    }
    
    public function username_field_callback() {
        $value = get_option('czl_username');
        echo '<input type="text" name="czl_username" value="' . esc_attr($value) . '" />';
    }
    
    public function password_field_callback() {
        $value = get_option('czl_password');
        echo '<input type="password" name="czl_password" value="' . esc_attr($value) . '" />';
    }
    
    public function rate_adjustment_field_callback() {
        $value = get_option('czl_rate_adjustment');
        ?>
        <input type="text" name="czl_rate_adjustment" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">
            <?php _e('设置运费调整公式，支持百分比和固定金额。例如：', 'woo-czl-express'); ?><br>
            - 10% + 10 (运费乘以1.1后加10)<br>
            - 20% (运费乘以1.2)<br>
            - +15 (运费加15)<br>
            <?php _e('留空表示不调整运费', 'woo-czl-express'); ?>
        </p>
        <?php
    }
    
    public function handle_test_connection() {
        check_ajax_referer('czl_test_api', 'nonce');
        
        $username = get_option('czl_username');
        $password = get_option('czl_password');
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(array(
                'message' => __('请先配置API账号和密码', 'woo-czl-express')
            ));
        }
        
        // 测试API连接
        $response = wp_remote_get('https://tms-api-go.czl.net/api/countries', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password)
            )
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message()
            ));
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!empty($data) && isset($data['code']) && $data['code'] == 200) {
            wp_send_json_success(array(
                'message' => __('API连接测试成功', 'woo-czl-express')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('API连接测试失败，请检查账号密码是否正确', 'woo-czl-express')
            ));
        }
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // 显示设置页面
        require_once WOO_CZL_EXPRESS_PATH . 'admin/views/settings.php';
    }
    
    public function render_exchange_rates_page() {
        // 处理表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
            check_admin_referer('czl_save_exchange_rates', 'czl_exchange_rates_nonce')) {
            
            // 删除所有现有汇率
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'czl_exchange_rate_%'");
            
            // 保存新的汇率
            if (!empty($_POST['rates'])) {
                foreach ($_POST['rates'] as $data) {
                    if (empty($data['currency']) || !isset($data['rate'])) continue;
                    
                    $currency = sanitize_text_field($data['currency']);
                    $rate = (float)$data['rate'];
                    
                    if ($rate > 0) {
                        update_option('czl_exchange_rate_' . $currency, $rate);
                    }
                }
            }
            
            add_settings_error('czl_messages', 'czl_message', __('汇率设置已保存', 'woo-czl-express'), 'updated');
        }
        
        // 显示设置页面
        require_once WOO_CZL_EXPRESS_PATH . 'admin/views/exchange-rates.php';
    }
    
    /**
     * 注册自定义订单状态
     */
    public function register_custom_order_statuses() {
        register_post_status('wc-in_transit', array(
            'label' => _x('In Transit', 'Order status', 'woo-czl-express'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('In Transit <span class="count">(%s)</span>',
                'In Transit <span class="count">(%s)</span>', 'woo-czl-express')
        ));
        
        register_post_status('wc-delivered', array(
            'label' => _x('Delivered', 'Order status', 'woo-czl-express'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Delivered <span class="count">(%s)</span>',
                'Delivered <span class="count">(%s)</span>', 'woo-czl-express')
        ));
    }
    
    /**
     * 添加自定义订单状态到WooCommerce状态列表
     */
    public function add_custom_order_statuses($order_statuses) {
        $new_statuses = array(
            'wc-in_transit' => _x('In Transit', 'Order status', 'woo-czl-express'),
            'wc-delivered' => _x('Delivered', 'Order status', 'woo-czl-express')
        );
        
        return array_merge($order_statuses, $new_statuses);
    }
    
    /**
     * 在订单详情页显示轨迹信息
     */
    public function display_tracking_info($order) {
        $tracking_number = $order->get_meta('_czl_tracking_number');
        if (empty($tracking_number)) {
            return;
        }
        
        $tracking_history = $order->get_meta('_czl_tracking_history');
        if (empty($tracking_history)) {
            return;
        }
        
        ?>
        <h2><?php _e('物流轨迹', 'woo-czl-express'); ?></h2>
        <div class="czl-tracking-info">
            <p>
                <?php 
                printf(
                    __('运单号: %s', 'woo-czl-express'),
                    '<a href="https://exp.czl.net/track/?query=' . esc_attr($tracking_number) . '" target="_blank">' . 
                    esc_html($tracking_number) . '</a>'
                ); 
                ?>
            </p>
            <ul class="czl-tracking-history">
                <?php foreach ($tracking_history['trackDetails'] as $track): ?>
                <li>
                    <span class="tracking-date"><?php echo esc_html($track['track_date']); ?></span>
                    <span class="tracking-content"><?php echo esc_html($track['track_content']); ?></span>
                    <?php if (!empty($track['track_location'])): ?>
                    <span class="tracking-location"><?php echo esc_html($track['track_location']); ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
    
    /**
     * 定时更新所有运输中订单的轨迹
     */
    public function schedule_tracking_updates() {
        $orders = wc_get_orders(array(
            'status' => array('shipping'),
            'limit' => -1,
            'meta_key' => '_czl_tracking_number',
            'meta_compare' => 'EXISTS'
        ));
        
        foreach ($orders as $order) {
            $this->order_handler->update_tracking_info($order->get_id());
        }
    }
    
    /**
     * 在管理员订单页面显示轨迹信息
     */
    public function display_admin_tracking_info($order) {
        $tracking_number = $order->get_meta('_czl_tracking_number');
        if (empty($tracking_number)) {
            return;
        }
        
        ?>
        <div class="czl-admin-tracking-info">
            <h3><?php _e('CZL Express 运单信息', 'woo-czl-express'); ?></h3>
            <p>
                <?php 
                printf(
                    __('运单号: %s', 'woo-czl-express'),
                    '<a href="https://exp.czl.net/track/?query=' . esc_attr($tracking_number) . '" target="_blank">' . 
                    esc_html($tracking_number) . '</a>'
                ); 
                ?>
            </p>
        </div>
        <?php
    }
    
    public function handle_create_shipment() {
        try {
            // 验证nonce
            check_ajax_referer('czl_create_shipment', 'nonce');
            
            // 检查权限
            if (!current_user_can('manage_woocommerce')) {
                throw new Exception(__('权限不足', 'woo-czl-express'));
            }
            
            // 验证订单ID
            $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
            if (!$order_id) {
                throw new Exception(__('无效的订单ID', 'woo-czl-express'));
            }
            
            // 获取WooCommerce订单对象
            $order = wc_get_order($order_id);
            if (!$order) {
                throw new Exception('订单不存在');
            }
            
            // 创建运单
            $czl_order = new CZL_Order();
            $result = $czl_order->create_shipment($order_id);
            
            if (!empty($result['tracking_number'])) {
                wp_send_json_success(array(
                    'message' => __('运单创建成功', 'woo-czl-express'),
                    'tracking_number' => $result['tracking_number']
                ));
            } else {
                throw new Exception('运单创建失败');
            }
            
        } catch (Exception $e) {
            error_log('CZL Express Error: ' . $e->getMessage());
            if (isset($order)) {
                $order->add_order_note(
                    sprintf(
                        __('CZL Express运单创建失败: %s', 'woo-czl-express'),
                        $e->getMessage()
                    ),
                    true
                );
            }
            wp_send_json_error(array(
                'message' => $e->getMessage() // 直接返回错误信息
            ));
        }
    }
    
    public function handle_bulk_create_shipment() {
        check_ajax_referer('czl_bulk_create_shipment', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('权限不足', 'woo-czl-express')));
        }
        
        $order_ids = isset($_POST['order_ids']) ? array_map('intval', $_POST['order_ids']) : array();
        if (empty($order_ids)) {
            wp_send_json_error(array('message' => __('请选择订单', 'woo-czl-express')));
        }
        
        $success = 0;
        $failed = 0;
        $failed_orders = array();
        $czl_order = new CZL_Order();
        
        foreach ($order_ids as $order_id) {
            try {
                $order = wc_get_order($order_id);
                if (!$order) {
                    throw new Exception('订单不存在');
                }
                
                $result = $czl_order->create_shipment($order_id);
                
                if (!empty($result)) {
                    // 更新WooCommerce订单元数据
                    $order->update_meta_data('_czl_tracking_number', $result['tracking_number']);
                    $order->update_meta_data('_czl_order_id', $result['order_id']);
                    
                    // 保存子单号
                    if (!empty($result['childList'])) {
                        $child_numbers = array_map(function($child) {
                            return $child['child_number'];
                        }, $result['childList']);
                        $order->update_meta_data('_czl_child_numbers', $child_numbers);
                    }
                    
                    // 添加订单备注
                    $order->add_order_note(
                        sprintf(
                            __('CZL Express运单创建成功。运单号: %s', 'woo-czl-express'),
                            $result['tracking_number']
                        ),
                        true
                    );
                    
                    // 更新订单状态为运输中
                    $order->update_status('shipping', __('运单已创建，包裹开始运输', 'woo-czl-express'));
                    
                    // 保存更改
                    $order->save();
                    
                    $success++;
                } else {
                    throw new Exception('运单创建失败');
                }
                
            } catch (Exception $e) {
                error_log('CZL Express Error: Failed to create shipment for order ' . $order_id . ' - ' . $e->getMessage());
                if (isset($order)) {
                    $order->add_order_note(
                        sprintf(
                            __('CZL Express运单创建失败: %s', 'woo-czl-express'),
                            $e->getMessage()
                        ),
                        true
                    );
                }
                $failed++;
                $failed_orders[] = $order_id;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('处理完成。成功：%d，失败：%d', 'woo-czl-express'),
                $success,
                $failed
            ),
            'failed_orders' => $failed_orders
        ));
    }
    
    /**
     * 渲染订单管理页面
     */
    public function render_orders_page() {
        // 检查权限
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('您没有足够的权限访问此页面', 'woo-czl-express'));
        }
        
        // 加载订单列表页面模板
        require_once WOO_CZL_EXPRESS_PATH . 'admin/views/orders.php';
    }
} 