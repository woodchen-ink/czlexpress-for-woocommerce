<?php
class WC_CZL_Shipping_Method extends WC_Shipping_Method {
    private $api;
    private $calculator;
    
    public function __construct($instance_id = 0) {
        parent::__construct($instance_id);
        
        $this->id = 'czl_express';
        $this->instance_id = absint($instance_id);
        $this->title = __('CZL Express', 'czlexpress-for-woocommerce');
        $this->method_title = __('CZL Express', 'czlexpress-for-woocommerce');
        $this->method_description = __('CZL Express shipping integration', 'czlexpress-for-woocommerce');
        
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
                'title' => __('Enable/Disable', 'czlexpress-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable this shipping method', 'czlexpress-for-woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Method Title', 'czlexpress-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'czlexpress-for-woocommerce'),
                'default' => __('CZL Express', 'czlexpress-for-woocommerce'),
                'desc_tip' => true
            ),
            'show_all_rates' => array(
                'title' => __('Display Mode', 'czlexpress-for-woocommerce'),
                'type' => 'select',
                'description' => __('Choose to display grouped shipping rates or all specific routes', 'czlexpress-for-woocommerce'),
                'default' => 'no',
                'options' => array(
                    'no' => __('Show Grouped Rates', 'czlexpress-for-woocommerce'),
                    'yes' => __('Show All Routes', 'czlexpress-for-woocommerce')
                )
            ),
            'sort_by' => array(
                'title' => __('Sort Order', 'czlexpress-for-woocommerce'),
                'type' => 'select',
                'description' => __('Choose how to sort shipping rates', 'czlexpress-for-woocommerce'),
                'default' => 'price',
                'options' => array(
                    'price' => __('Sort by Price', 'czlexpress-for-woocommerce'),
                    'time' => __('Sort by Delivery Time', 'czlexpress-for-woocommerce')
                )
            ),
            'exchange_rate' => array(
                'title' => __('Exchange Rate', 'czlexpress-for-woocommerce'),
                'type' => 'text',
                'description' => sprintf(
                    /* translators: 1: currency code, 2: currency symbol */
                    __('Set exchange rate from CNY to %1$s. Example: if 1 CNY = %2$s0.14, enter 0.14', 'czlexpress-for-woocommerce'),
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
            CZL_Logger::info('Starting shipping calculation');
            
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
                
                CZL_Logger::info('Added shipping rates', array('count' => count($rates)));
            } else {
                CZL_Logger::info('No shipping rates available');
            }
            
        } catch (Exception $e) {
            CZL_Logger::error('Failed to calculate shipping', array('error' => $e->getMessage()));
        }
    }
    
    public function enqueue_scripts() {
        if (is_cart() || is_checkout()) {
            wp_enqueue_style(
                'czl-shipping-method',
                CZL_EXPRESS_URL . 'assets/css/shipping-method.css',
                array(),
                CZL_EXPRESS_VERSION
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