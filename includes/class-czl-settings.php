<?php
class CZL_Settings {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function register_settings() {
        register_setting('czl_options_group', 'czl_api_url');
        register_setting('czl_options_group', 'czl_username');
        register_setting('czl_options_group', 'czl_password');
        register_setting('czl_options_group', 'czl_exchange_rate');
        register_setting('czl_options_group', 'czl_product_groups');
    }
    
    public function get_settings_fields() {
        return array(
            'basic' => array(
                array(
                    'name' => 'czl_api_url',
                    'label' => __('API URL', 'woo-czl-express'),
                    'type' => 'text',
                    'default' => '',
                ),
                array(
                    'name' => 'czl_username',
                    'label' => __('Username', 'woo-czl-express'),
                    'type' => 'text',
                    'default' => '',
                ),
                array(
                    'name' => 'czl_password',
                    'label' => __('Password', 'woo-czl-express'),
                    'type' => 'password',
                    'default' => '',
                ),
                array(
                    'name' => 'czl_exchange_rate',
                    'label' => __('Exchange Rate', 'woo-czl-express'),
                    'type' => 'number',
                    'default' => '1',
                    'step' => '0.0001',
                ),
            )
        );
    }
} 