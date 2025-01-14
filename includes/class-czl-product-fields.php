<?php
class CZL_Product_Fields {
    public function __construct() {
        add_action('woocommerce_product_options_shipping', array($this, 'add_custom_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_custom_fields'));
    }
    
    /**
     * 添加自定义字段到产品编辑页面
     */
    public function add_custom_fields() {
        echo '<div class="options_group">';
        
        // 中文品名字段
        woocommerce_wp_text_input(array(
            'id' => '_czl_name_cn',
            'label' => __('Chinese Name', 'czlexpress-for-woocommerce'),
            'desc_tip' => true,
            'description' => __('Enter the Chinese name of the product', 'czlexpress-for-woocommerce')
        ));
        
        // 海关编码字段
        woocommerce_wp_text_input(array(
            'id' => '_czl_hs_code',
            'label' => __('HS Code', 'czlexpress-for-woocommerce'),
            'desc_tip' => true,
            'description' => __('Enter the HS code for customs declaration', 'czlexpress-for-woocommerce')
        ));
        
        // 用途字段
        woocommerce_wp_text_input(array(
            'id' => '_czl_usage',
            'label' => __('Usage', 'czlexpress-for-woocommerce'),
            'desc_tip' => true,
            'description' => __('Enter the product usage, e.g., daily use, decoration, etc.', 'czlexpress-for-woocommerce')
        ));
        
        // 材质字段
        woocommerce_wp_text_input(array(
            'id' => '_czl_material',
            'label' => __('Material', 'czlexpress-for-woocommerce'),
            'desc_tip' => true,
            'description' => __('Enter the product material, e.g., plastic, metal, fabric, etc.', 'czlexpress-for-woocommerce')
        ));
        
        echo '</div>';
    }
    
    /**
     * 保存自定义字段
     */
    public function save_custom_fields($post_id) {
        // 验证nonce
        if (!isset($_POST['czl_product_fields_nonce']) || 
            !wp_verify_nonce($_POST['czl_product_fields_nonce'], 'czl_save_product_fields')) {
            return;
        }
        
        // 检查自动保存
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // 检查权限
        if (!current_user_can('edit_product', $post_id)) {
            return;
        }
        
        // 中文品名
        if (isset($_POST['_czl_name_cn'])) {
            update_post_meta(
                $post_id,
                '_czl_name_cn',
                sanitize_text_field(wp_unslash($_POST['_czl_name_cn']))
            );
        }
        
        // HS编码
        if (isset($_POST['_czl_hs_code'])) {
            update_post_meta(
                $post_id,
                '_czl_hs_code',
                sanitize_text_field(wp_unslash($_POST['_czl_hs_code']))
            );
        }
        
        // 用途
        if (isset($_POST['_czl_usage'])) {
            update_post_meta(
                $post_id,
                '_czl_usage',
                sanitize_text_field(wp_unslash($_POST['_czl_usage']))
            );
        }
        
        // 材质
        if (isset($_POST['_czl_material'])) {
            update_post_meta(
                $post_id,
                '_czl_material',
                sanitize_text_field(wp_unslash($_POST['_czl_material']))
            );
        }
    }
} 