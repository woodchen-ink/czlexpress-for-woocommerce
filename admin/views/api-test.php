<?php
defined('ABSPATH') || exit;
?>

<div class="czl-api-test">
    <h2><?php _e('API连接测试', 'czlexpress-for-woocommerce'); ?></h2>
    
    <p class="description">
        <?php _e('点击下面的按钮测试与CZL Express API的连接。', 'czlexpress-for-woocommerce'); ?>
    </p>
    
    <div class="test-buttons">
        <button type="button" class="button" id="czl-test-connection">
            <?php _e('测试连接', 'czlexpress-for-woocommerce'); ?>
        </button>
        
        <button type="button" class="button" id="czl-test-shipping-rate">
            <?php _e('测试运费查询', 'czlexpress-for-woocommerce'); ?>
        </button>
    </div>
    
    <div id="czl-test-result"></div>
</div>

<script>
jQuery(function($) {
    $('#czl-test-connection').on('click', function() {
        var $button = $(this);
        var $result = $('#czl-test-result');
        
        $button.prop('disabled', true);
        $result.html('<?php _e('测试中...', 'czlexpress-for-woocommerce'); ?>');
        
        $.post(ajaxurl, {
            action: 'czl_test_connection',
            nonce: '<?php echo wp_create_nonce('czl_test_api'); ?>'
        }, function(response) {
            $button.prop('disabled', false);
            if (response.success) {
                $result.html('<div class="notice notice-success"><p>' + wp.escapeHtml(response.data.message) + '</p></div>');
            } else {
                $result.html('<div class="notice notice-error"><p>' + wp.escapeHtml(response.data.message) + '</p></div>');
            }
        });
    });
    
    $('#czl-test-shipping-rate').on('click', function() {
        var $button = $(this);
        var $result = $('#czl-test-result');
        
        $button.prop('disabled', true);
        $result.html('<?php _e('测试中...', 'czlexpress-for-woocommerce'); ?>');
        
        $.post(ajaxurl, {
            action: 'czl_test_shipping_rate',
            nonce: '<?php echo wp_create_nonce('czl_test_api'); ?>'
        }, function(response) {
            $button.prop('disabled', false);
            if (response.success) {
                var html = '<div class="notice notice-success"><p><?php _e('运费查询成功：', 'czlexpress-for-woocommerce'); ?></p>';
                html += '<pre>' + wp.escapeHtml(JSON.stringify(response.data, null, 2)) + '</pre></div>';
                $result.html(html);
            } else {
                $result.html('<div class="notice notice-error"><p>' + wp.escapeHtml(response.data.message) + '</p></div>');
            }
        });
    });
});
</script> 