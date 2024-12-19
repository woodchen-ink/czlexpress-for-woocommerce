<?php
class CZL_Product_Fields {
    public function __construct() {
        add_action('woocommerce_product_options_shipping', array($this, 'add_custom_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_custom_fields'));
    }
    
    /**
     * 添加自定义字段
     */
    public function add_custom_fields() {
        echo '<div class="options_group">';
        
        woocommerce_wp_text_input(array(
            'id' => '_czl_name_en',
            'label' => __('英文品名', 'woo-czl-express'),
            'description' => __('用于国际物流申报', 'woo-czl-express'),
            'desc_tip' => true,
        ));
        
        woocommerce_wp_text_input(array(
            'id' => '_czl_hs_code',
            'label' => __('HS编码', 'woo-czl-express'),
            'description' => __('海关商品编码', 'woo-czl-express'),
            'desc_tip' => true,
        ));
        
        echo '</div>';
    }
    
    /**
     * 保存自定义字段
     */
    public function save_custom_fields($post_id) {
        $name_en = isset($_POST['_czl_name_en']) ? sanitize_text_field($_POST['_czl_name_en']) : '';
        $hs_code = isset($_POST['_czl_hs_code']) ? sanitize_text_field($_POST['_czl_hs_code']) : '';
        
        update_post_meta($post_id, '_czl_name_en', $name_en);
        update_post_meta($post_id, '_czl_hs_code', $hs_code);
    }
} 