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
            'label' => __('中文品名', 'woo-czl-express'),
            'desc_tip' => true,
            'description' => __('输入产品的中文名称，用于物流申报', 'woo-czl-express')
        ));
        
        // 海关编码字段
        woocommerce_wp_text_input(array(
            'id' => '_czl_hs_code',
            'label' => __('海关编码 (HS Code)', 'woo-czl-express'),
            'desc_tip' => true,
            'description' => __('输入产品的海关编码 (HS Code)', 'woo-czl-express')
        ));
        
        // 用途字段
        woocommerce_wp_text_input(array(
            'id' => '_czl_usage',
            'label' => __('用途', 'woo-czl-express'),
            'desc_tip' => true,
            'description' => __('输入产品的用途，例如：日常使用、装饰等', 'woo-czl-express')
        ));
        
        // 材质字段
        woocommerce_wp_text_input(array(
            'id' => '_czl_material',
            'label' => __('材质', 'woo-czl-express'),
            'desc_tip' => true,
            'description' => __('输入产品的材质，例如：塑料、金属、布料等', 'woo-czl-express')
        ));
        
        echo '</div>';
    }
    
    /**
     * 保存自定义字段
     */
    public function save_custom_fields($post_id) {
        // 保存中文品名
        $name_cn = isset($_POST['_czl_name_cn']) ? sanitize_text_field($_POST['_czl_name_cn']) : '';
        update_post_meta($post_id, '_czl_name_cn', $name_cn);
        
        // 保存海关编码
        $hs_code = isset($_POST['_czl_hs_code']) ? sanitize_text_field($_POST['_czl_hs_code']) : '';
        update_post_meta($post_id, '_czl_hs_code', $hs_code);
        
        // 保存用途
        $usage = isset($_POST['_czl_usage']) ? sanitize_text_field($_POST['_czl_usage']) : '';
        update_post_meta($post_id, '_czl_usage', $usage);
        
        // 保存材质
        $material = isset($_POST['_czl_material']) ? sanitize_text_field($_POST['_czl_material']) : '';
        update_post_meta($post_id, '_czl_material', $material);
    }
} 