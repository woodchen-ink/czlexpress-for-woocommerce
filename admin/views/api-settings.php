<?php
defined('ABSPATH') || exit;

$settings = array(
    array(
        'title' => __('API Settings', 'czlexpress-for-woocommerce'),
        'type' => 'title',
        'id' => 'czl_api_settings'
    ),
    array(
        'title' => __('API URL', 'czlexpress-for-woocommerce'),
        'type' => 'text',
        'id' => 'czl_api_url',
        'desc' => __('Enter the CZL Express API URL', 'czlexpress-for-woocommerce'),
        'default' => ''
    ),
    array(
        'title' => __('Username', 'czlexpress-for-woocommerce'),
        'type' => 'text',
        'id' => 'czl_username',
        'desc' => __('Enter your CZL Express API username', 'czlexpress-for-woocommerce'),
        'default' => ''
    ),
    array(
        'title' => __('Password', 'czlexpress-for-woocommerce'),
        'type' => 'password',
        'id' => 'czl_password',
        'desc' => __('Enter your CZL Express API password', 'czlexpress-for-woocommerce'),
        'default' => ''
    ),
    array(
        'title' => __('Warehouse Settings', 'czlexpress-for-woocommerce'),
        'type' => 'title',
        'id' => 'czl_warehouse_settings'
    ),
    array(
        'title' => __('Province', 'czlexpress-for-woocommerce'),
        'type' => 'text',
        'id' => 'czl_warehouse_province',
        'desc' => __('Enter warehouse province', 'czlexpress-for-woocommerce'),
        'default' => ''
    ),
    array(
        'title' => __('City', 'czlexpress-for-woocommerce'),
        'type' => 'text',
        'id' => 'czl_warehouse_city',
        'desc' => __('Enter warehouse city', 'czlexpress-for-woocommerce'),
        'default' => ''
    ),
    array(
        'title' => __('Address', 'czlexpress-for-woocommerce'),
        'type' => 'textarea',
        'id' => 'czl_warehouse_address',
        'desc' => __('Enter warehouse address', 'czlexpress-for-woocommerce'),
        'default' => ''
    ),
    array('type' => 'sectionend', 'id' => 'czl_api_settings'),
);

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('czl_api_options');
        do_settings_sections('czl_api_options');
        
        WC_Admin_Settings::output_fields($settings);
        
        submit_button();
        ?>
    </form>
    
    <div class="czl-api-test">
        <h2><?php _e('API Connection Test', 'czlexpress-for-woocommerce'); ?></h2>
        <button type="button" class="button" id="czl-test-connection">
            <?php _e('Test Connection', 'czlexpress-for-woocommerce'); ?>
        </button>
        <button type="button" class="button" id="czl-test-shipping-rate">
            <?php _e('Test Shipping Rate', 'czlexpress-for-woocommerce'); ?>
        </button>
        <div id="czl-test-result"></div>
    </div>
</div>

<script>
jQuery(function($) {
    $('#czl-test-connection').on('click', function() {
        var $button = $(this);
        var $result = $('#czl-test-result');
        
        $button.prop('disabled', true);
        $result.html('<?php _e('Testing...', 'czlexpress-for-woocommerce'); ?>');
        
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
        $result.html('<?php _e('Testing...', 'czlexpress-for-woocommerce'); ?>');
        
        $.post(ajaxurl, {
            action: 'czl_test_shipping_rate',
            nonce: '<?php echo wp_create_nonce('czl_test_api'); ?>'
        }, function(response) {
            $button.prop('disabled', false);
            if (response.success) {
                var html = '<div class="notice notice-success"><p><?php _e('Shipping rates retrieved successfully:', 'czlexpress-for-woocommerce'); ?></p>';
                html += '<pre>' + wp.escapeHtml(JSON.stringify(response.data, null, 2)) + '</pre></div>';
                $result.html(html);
            } else {
                $result.html('<div class="notice notice-error"><p>' + wp.escapeHtml(response.data.message) + '</p></div>');
            }
        });
    });
});
</script> 