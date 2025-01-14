<?php
/**
 * Plugin Name: CZL Express for WooCommerce
 * Plugin URI: https://github.com/woodchen-ink/czlexpress-for-woocommerce
 * Description: CZL Express shipping integration for WooCommerce. Provides real-time shipping rates, shipment creation, and package tracking for CZL Express delivery service.
 * Version: 1.0.0
 * Requires at least: 6.7
 * Requires PHP: 8.0
 * Author: CZL Express
 * Author URI: https://exp.czl.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: czlexpress-for-woocommerce
 * Domain Path: /languages
 * WC requires at least: 6.0.0
 * WC tested up to: 8.3.0
 *
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this plugin. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 防止重复加载
if (defined('CZL_EXPRESS_VERSION')) {
    return;
}

// 定义插件常量
define('CZL_EXPRESS_VERSION', '1.0.0');
define('CZL_EXPRESS_PATH', plugin_dir_path(__FILE__));
define('CZL_EXPRESS_URL', plugin_dir_url(__FILE__));

// 加载核心类文件
require_once CZL_EXPRESS_PATH . 'includes/class-czl-logger.php';

// 检查环境
function czl_express_check_environment() {
    // 检查WooCommerce是否已安装并激活
    if (!class_exists('WC_Shipping_Method')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                 esc_html__('CZL Express requires WooCommerce to be installed and activated', 'czlexpress-for-woocommerce') . 
                 '</p></div>';
        });
        return false;
    }
    
    // 检查WooCommerce版本
    if (version_compare(WC_VERSION, '6.0.0', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                 esc_html__('CZL Express requires WooCommerce 6.0.0 or higher', 'czlexpress-for-woocommerce') . 
                 '</p></div>';
        });
        return false;
    }
    
    return true;
}

// 在插件激活时创建数据表
register_activation_hook(__FILE__, 'czl_express_activate');

function czl_express_activate() {
    // 确保加载安装类
    require_once CZL_EXPRESS_PATH . 'includes/class-czl-install.php';
    
    // 创建数据表和默认选项
    CZL_Install::init();
    
    // 记录版本号
    update_option('czl_express_version', CZL_EXPRESS_VERSION);
}

// 在插件停用时清理
register_deactivation_hook(__FILE__, function() {
    // 清理定时任务
    wp_clear_scheduled_hook('czl_update_tracking_info');
});

// 声明支持HPOS
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// 初始化插件
function czl_express_init() {
    if (czl_express_check_environment()) {
        CZL_Logger::info('Plugin initialization started');
        
        // 加载必要的类文件
        require_once CZL_EXPRESS_PATH . 'includes/class-czlexpress.php';
        require_once CZL_EXPRESS_PATH . 'includes/class-czl-api.php';
        require_once CZL_EXPRESS_PATH . 'includes/class-czl-order.php';
        require_once CZL_EXPRESS_PATH . 'includes/class-czl-ajax.php';
        
        CZLExpress::instance();
        
        // 初始化AJAX处理器
        new CZL_Ajax();
        
        // 添加AJAX处理
        add_action('wp_ajax_czl_create_shipment', 'czl_ajax_create_shipment');
        add_action('wp_ajax_czl_update_tracking_number', 'czl_ajax_update_tracking_number');
        add_action('wp_ajax_czl_update_tracking_info', 'czl_ajax_update_tracking_info');
        
        // 添加前端脚本
        add_action('admin_enqueue_scripts', 'czl_enqueue_admin_scripts');
        
        // 注册自定义订单状态
        add_action('init', 'register_czl_order_statuses');
        
        // 在init钩子中加载翻译文件
        add_action('init', function() {
            load_plugin_textdomain(
                'czlexpress-for-woocommerce',
                false,
                dirname(plugin_basename(__FILE__)) . '/languages'
            );
        });
        
        CZL_Logger::info('Plugin initialization completed');
    }
}

// 注册自定义订单状态
function register_czl_order_statuses() {
    register_post_status('wc-in_transit', array(
        'label' => _x('In Transit', 'Order status', 'czlexpress-for-woocommerce'),
        'public' => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list' => true,
        'exclude_from_search' => false,
        /* translators: %s: number of orders */
        'label_count' => _n_noop(
            'In Transit <span class="count">(%s)</span>',
            'In Transit <span class="count">(%s)</span>',
            'czlexpress-for-woocommerce'
        )
    ));
    
    // 添加到WooCommerce订单状态列表
    add_filter('wc_order_statuses', function($order_statuses) {
        $new_statuses = array();
        
        // 在processing后面插入新状态
        foreach ($order_statuses as $key => $status) {
            $new_statuses[$key] = $status;
            if ($key === 'wc-processing') {
                $new_statuses['wc-in_transit'] = _x('In Transit', 'Order status', 'czlexpress-for-woocommerce');
            }
        }
        
        return $new_statuses;
    });
}

// 注册前端脚本
function czl_enqueue_admin_scripts($hook) {
    // 在WooCommerce订单页面和CZL Express订单页面加载脚本
    if (!in_array($hook, array('woocommerce_page_wc-orders', 'toplevel_page_czl-express-orders'))) {
        return;
    }
    
    // 加载 Thickbox
    add_thickbox();
    
    // 加载自定义脚本
    wp_enqueue_script(
        'czl-admin-script',
        CZL_EXPRESS_URL . 'assets/js/admin.js',
        array('jquery'),
        CZL_EXPRESS_VERSION,
        true
    );
    
    wp_localize_script('czl-admin-script', 'czl_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('czl_ajax_nonce'),
        'creating_text' => 'Creating shipment...',
        'success_text' => 'Shipment created successfully',
        'error_text' => 'Failed to create shipment'
    ));
}

// AJAX处理函数
function czl_ajax_create_shipment() {
    check_ajax_referer('czl_ajax_nonce', 'nonce');
    
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error('Permission denied');
    }
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }
    
    try {
        $czl_order = new CZL_Order();
        $result = $czl_order->create_shipment($order_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Shipment created successfully'
            ));
        } else {
            wp_send_json_error('Failed to create shipment');
        }
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

// 更新跟踪单号的AJAX处理函数
function czl_ajax_update_tracking_number() {
    check_ajax_referer('czl_ajax_nonce', 'nonce');
    
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(array('message' => '权限不足'));
    }
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $tracking_number = isset($_POST['tracking_number']) ? sanitize_text_field($_POST['tracking_number']) : '';
    
    if (!$order_id || !$tracking_number) {
        wp_send_json_error(array('message' => '参数无效'));
    }
    
    try {
        global $wpdb;
        
        // 获取运单信息
        $shipment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}czl_shipments WHERE order_id = %d",
            $order_id
        ));
        
        if (!$shipment) {
            throw new Exception('运单不存在');
        }
        
        // 更新数据库中的跟踪单号
        $updated = $wpdb->update(
            $wpdb->prefix . 'czl_shipments',
            array('tracking_number' => $tracking_number),
            array('order_id' => $order_id),
            array('%s'),
            array('%d')
        );
        
        if ($updated === false) {
            throw new Exception('数据库更新失败');
        }
        
        // 更新订单元数据
        $order = wc_get_order($order_id);
        if ($order) {
            $order->update_meta_data('_czl_tracking_number', $tracking_number);
            $order->save();
            
            // 添加订单备注
            $order->add_order_note(sprintf(
                '运单号已更新为: %s',
                $tracking_number
            ));
        }

        // 清除相关缓存
        $tracking = new CZL_Tracking();
        $tracking->clear_tracking_cache($shipment->id, $tracking_number, $order_id);
        
        wp_send_json_success(array(
            'message' => '运单号更新成功',
            'tracking_number' => $tracking_number
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

// 更新轨迹信息的AJAX处理函数
function czl_ajax_update_tracking_info() {
    try {
        // 验证nonce
        if (!check_ajax_referer('czl_update_tracking_info', 'nonce', false)) {
            throw new Exception('无效的请求');
        }
        
        // 验证权限
        if (!current_user_can('edit_shop_orders')) {
            throw new Exception('权限不足');
        }
        
        $tracking_number = isset($_POST['tracking_number']) ? sanitize_text_field($_POST['tracking_number']) : '';
        if (empty($tracking_number)) {
            throw new Exception('运单号不能为空');
        }
        
        $tracking = new CZL_Tracking();
        $result = $tracking->update_tracking_info($tracking_number);
        
        wp_send_json_success($result);
        
    } catch (Exception $e) {
        CZL_Logger::error('Error updating tracking info', array('error' => $e->getMessage()));
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

// 异步处理轨迹更新
function czl_do_update_tracking_info($order_id) {
    try {
        $czl_order = new CZL_Order();
        $czl_order->update_tracking_info($order_id);
    } catch (Exception $e) {
        CZL_Logger::error('Error updating tracking info', array(
            'order_id' => $order_id,
            'error' => $e->getMessage()
        ));
    }
}
add_action('czl_do_update_tracking_info', 'czl_do_update_tracking_info');

add_action('plugins_loaded', 'czl_express_init');

// 自动创建运单的钩子
add_action('woocommerce_order_status_processing', function($order_id) {
    $czl_order = new CZL_Order();
    try {
        $czl_order->create_shipment($order_id);
    } catch (Exception $e) {
        CZL_Logger::error('Auto create shipment failed', array(
            'order_id' => $order_id,
            'error' => $e->getMessage()
        ));
    }
});

// 添加自定义Cron间隔
add_filter('cron_schedules', 'czl_add_cron_interval');
function czl_add_cron_interval($schedules) { 
    // 添加每30分钟执行一次的间隔
    $schedules['czl_thirty_minutes'] = array(
        'interval' => 1800, // 30分钟 = 1800秒
        'display'  => esc_html__('Every 30 minutes', 'czlexpress-for-woocommerce')
    );
    return $schedules;
}

// 添加定时任务处理函数
function czl_sync_tracking_numbers() {
    global $wpdb;
    
    // 获取最后同步时间
    $last_sync = get_option('czl_last_tracking_sync', 0);
    $current_time = time();
    
    // 如果距离上次同步不到25分钟,则跳过
    if (($current_time - $last_sync) < 1500) {
        return;
    }
    
    // 限制每次处理的订单数量
    $limit = 10;
    
    // 获取需要同步的订单
    $shipments = $wpdb->get_results($wpdb->prepare("
        SELECT order_id, tracking_number, czl_order_id 
        FROM {$wpdb->prefix}czl_shipments 
        WHERE czl_order_id IS NOT NULL
        AND (last_sync_time IS NULL OR last_sync_time < DATE_SUB(NOW(), INTERVAL 25 MINUTE))
        LIMIT %d
    ", $limit));
    
    if (empty($shipments)) {
        return;
    }
    
    try {
        $api = new CZL_API();
        
        foreach ($shipments as $shipment) {
            // 为每个订单安排单独的更新事件
            wp_schedule_single_event(
                time() + rand(1, 300), // 随机延迟1-300秒
                'czl_do_sync_single_tracking',
                array($shipment->order_id, $shipment->tracking_number, $shipment->czl_order_id)
            );
        }
        
        // 更新最后同步时间
        update_option('czl_last_tracking_sync', $current_time);
        
        CZL_Logger::info('Tracking sync scheduled', array(
            'shipment_count' => count($shipments)
        ));
        
    } catch (Exception $e) {
        CZL_Logger::error('Tracking number sync failed', array(
            'error' => $e->getMessage()
        ));
    }
}

// 处理单个订单的同步
function czl_do_sync_single_tracking($order_id, $current_tracking, $czl_order_id) {
    try {
        global $wpdb;
        $api = new CZL_API();
        
        // 获取最新的跟踪单号
        $response = $api->get_tracking_number($czl_order_id);
        
        // 更新最后同步时间
        $wpdb->update(
            $wpdb->prefix . 'czl_shipments',
            array('last_sync_time' => current_time('mysql')),
            array('order_id' => $order_id),
            array('%s'),
            array('%d')
        );
        
        if (!empty($response['tracking_number']) && $response['tracking_number'] !== $current_tracking) {
            // 更新跟踪单号
            $wpdb->update(
                $wpdb->prefix . 'czl_shipments',
                array('tracking_number' => $response['tracking_number']),
                array('order_id' => $order_id),
                array('%s'),
                array('%d')
            );
            
            // 更新订单信息
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_meta_data('_czl_tracking_number', $response['tracking_number']);
                $order->save();
                
                $tracking_link = sprintf(
                    '<a href="https://exp.czl.net/track/?query=%s" target="_blank">%s</a>',
                    $response['tracking_number'],
                    __('查看物流', 'czlexpress-for-woocommerce')
                );
                
                $order->add_order_note(
                    sprintf(
                        /* translators: 1: tracking number 2: tracking link */
                        __('运单号已更新为: %1$s\n%2$s', 'czlexpress-for-woocommerce'),
                        $response['tracking_number'],
                        $tracking_link
                    ),
                    true
                );
            }
        }
    } catch (Exception $e) {
        CZL_Logger::error('Failed to sync tracking number', array(
            'order_id' => $order_id,
            'error' => $e->getMessage()
        ));
    }
}
add_action('czl_do_sync_single_tracking', 'czl_do_sync_single_tracking', 10, 3);

// 注册定时任务钩子
add_action('czl_sync_tracking_numbers_hook', 'czl_sync_tracking_numbers');