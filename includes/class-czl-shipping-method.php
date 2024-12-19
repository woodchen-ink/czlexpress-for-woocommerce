<?php
class WC_CZL_Shipping_Method extends WC_Shipping_Method {
    private $api;
    private $calculator;
    
    public function __construct($instance_id = 0) {
        parent::__construct($instance_id);
        
        $this->id = 'czl_express';
        $this->instance_id = absint($instance_id);
        $this->title = __('CZL Express', 'woo-czl-express');
        $this->method_title = __('CZL Express', 'woo-czl-express');
        $this->method_description = __('CZL Express shipping integration', 'woo-czl-express');
        
        $this->supports = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );
        
        $this->enabled = 'yes';
        
        $this->init();
        
        $this->api = new CZL_API();
        $this->calculator = new CZL_Rate_Calculator();
        
        // 添加前端资源
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function init() {
        $this->init_form_fields();
        $this->init_settings();
        
        $this->title = $this->get_option('title', $this->method_title);
        $this->enabled = $this->get_option('enabled', 'yes');
        
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }
    
    public function init_form_fields() {
        $this->instance_form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woo-czl-express'),
                'type' => 'checkbox',
                'label' => __('Enable this shipping method', 'woo-czl-express'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Method Title', 'woo-czl-express'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woo-czl-express'),
                'default' => __('CZL Express', 'woo-czl-express'),
                'desc_tip' => true
            ),
            'show_all_rates' => array(
                'title' => __('显示方式', 'woo-czl-express'),
                'type' => 'select',
                'description' => __('选择是显示分组运费还是显示所有具体线路', 'woo-czl-express'),
                'default' => 'no',
                'options' => array(
                    'no' => __('显示分组运费', 'woo-czl-express'),
                    'yes' => __('显示所有线路', 'woo-czl-express')
                )
            ),
            'sort_by' => array(
                'title' => __('排序方式', 'woo-czl-express'),
                'type' => 'select',
                'description' => __('选择运费显示的排序方式', 'woo-czl-express'),
                'default' => 'price',
                'options' => array(
                    'price' => __('按价格排序', 'woo-czl-express'),
                    'time' => __('按时效排序', 'woo-czl-express')
                )
            ),
            'exchange_rate' => array(
                'title' => __('汇率设置', 'woo-czl-express'),
                'type' => 'text',
                'description' => sprintf(
                    __('设置CNY到%s的汇率。例如：如果1CNY=%s0.14，输入0.14', 'woo-czl-express'),
                    get_woocommerce_currency(),
                    get_woocommerce_currency_symbol()
                ),
                'default' => $this->get_default_exchange_rate(),
                'desc_tip' => true
            )
        );
    }
    
    private function get_default_exchange_rate() {
        $currency = get_woocommerce_currency();
        $default_rates = array(
            'USD' => 0.14,
            'EUR' => 0.13,
            'GBP' => 0.11,
            // 添加其他常用货币...
        );
        return isset($default_rates[$currency]) ? $default_rates[$currency] : 1;
    }
    
    public function calculate_shipping($package = array()) {
        if ($this->enabled !== 'yes') {
            return;
        }
        
        try {
            error_log('CZL Express: Starting shipping calculation');
            
            $calculator = new CZL_Rate_Calculator();
            $rates = $calculator->calculate_shipping_rate($package);
            
            if (!empty($rates)) {
                foreach ($rates as $rate) {
                    $rate_id = $this->id . '_' . (isset($rate['product_id']) ? $rate['product_id'] : uniqid());
                    
                    $this->add_rate(array(
                        'id' => $rate_id,
                        'label' => $rate['method_title'],
                        'cost' => $rate['cost'],
                        'calc_tax' => 'per_order',
                        'meta_data' => array(
                            'product_id' => $rate['product_id'],
                            'delivery_time' => $rate['delivery_time'],
                            'original_name' => $rate['original_name'],
                            'is_group' => $rate['is_group'],
                            'group_name' => $rate['group_name'],
                            'original_amount' => $rate['original_amount']
                        )
                    ));
                }
                
                error_log('CZL Express: Added ' . count($rates) . ' shipping rates');
            } else {
                error_log('CZL Express: No shipping rates available');
            }
            
        } catch (Exception $e) {
            error_log('CZL Express Error: Failed to calculate shipping - ' . $e->getMessage());
        }
    }
    
    public function enqueue_scripts() {
        if (is_cart() || is_checkout()) {
            wp_enqueue_style(
                'czl-shipping-method',
                WOO_CZL_EXPRESS_URL . 'assets/css/shipping-method.css',
                array(),
                WOO_CZL_EXPRESS_VERSION
            );
        }
    }
    
    public function process_admin_options() {
        parent::process_admin_options();
        
        // 保存汇率设置
        $currency = get_woocommerce_currency();
        $rate = $this->get_option('exchange_rate');
        if (!empty($rate)) {
            update_option('czl_exchange_rate_' . $currency, $rate);
        }
    }
} 