<?php
/**
 * Plugin Name: WooCommerce CZL Express Shipping
 * Plugin URI: https://your-domain.com/
 * Description: CZL Express shipping integration for WooCommerce
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://your-domain.com/
 * Text Domain: woo-czl-express
 * Domain Path: /languages
 * Requires PHP: 7.0
 * WC requires at least: 6.0.0
 * WC tested up to: 8.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// 定义插件常量
define('WOO_CZL_EXPRESS_VERSION', '1.0.0');
define('WOO_CZL_EXPRESS_PATH', plugin_dir_path(__FILE__));
define('WOO_CZL_EXPRESS_URL', plugin_dir_url(__FILE__));

// 声明支持HPOS
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// 检查环境
function wc_czlexpress_check_environment() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                 __('CZL Express requires WooCommerce to be installed and active', 'woocommerce-czlexpress') . 
                 '</p></div>';
        });
        return false;
    }

    if (version_compare(WC_VERSION, '6.0.0', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . 
                 __('CZL Express requires WooCommerce 6.0.0 or higher', 'woocommerce-czlexpress') . 
                 '</p></div>';
        });
        return false;
    }
    
    return true;
}

// 初始化插件
function woo_czl_express_init() {
    if (wc_czlexpress_check_environment()) {
        require_once WOO_CZL_EXPRESS_PATH . 'includes/class-woo-czl-express.php';
        WooCzlExpress::instance();
    }
}

add_action('plugins_loaded', 'woo_czl_express_init');

add_action('woocommerce_order_status_processing', array('CZL_Order', 'create_shipment'));