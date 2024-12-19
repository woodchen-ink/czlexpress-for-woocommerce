<?php
defined('ABSPATH') || exit;

$settings = array(
    array(
        'title' => __('API Settings', 'woo-czl-express'),
        'type' => 'title',
        'id' => 'czl_api_settings'
    ),
    array(
        'title' => __('API URL', 'woo-czl-express'),
        'type' => 'text',
        'id' => 'czl_api_url',
        'desc' => __('Enter the CZL Express API URL', 'woo-czl-express'),
        'default' => ''
    ),
    array(
        'title' => __('Username', 'woo-czl-express'),
        'type' => 'text',
        'id' => 'czl_username',
        'desc' => __('Enter your CZL Express API username', 'woo-czl-express'),
        'default' => ''
    ),
    array(
        'title' => __('Password', 'woo-czl-express'),
        'type' => 'password',
        'id' => 'czl_password',
        'desc' => __('Enter your CZL Express API password', 'woo-czl-express'),
        'default' => ''
    ),
    array(
        'title' => __('Warehouse Settings', 'woo-czl-express'),
        'type' => 'title',
        'id' => 'czl_warehouse_settings'
    ),
    array(
        'title' => __('Province', 'woo-czl-express'),
        'type' => 'text',
        'id' => 'czl_warehouse_province',
        'desc' => __('Enter warehouse province', 'woo-czl-express'),
        'default' => ''
    ),
    array(
        'title' => __('City', 'woo-czl-express'),
        'type' => 'text',
        'id' => 'czl_warehouse_city',
        'desc' => __('Enter warehouse city', 'woo-czl-express'),
        'default' => ''
    ),
    array(
        'title' => __('Address', 'woo-czl-express'),
        'type' => 'textarea',
        'id' => 'czl_warehouse_address',
        'desc' => __('Enter warehouse address', 'woo-czl-express'),
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
        <h2><?php _e('API Connection Test', 'woo-czl-express'); ?></h2>
        <button type="button" class="button" id="czl-test-connection">
            <?php _e('Test Connection', 'woo-czl-express'); ?>
        </button>
        <button type="button" class="button" id="czl-test-shipping-rate">
            <?php _e('Test Shipping Rate', 'woo-czl-express'); ?>
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
        $result.html('<?php _e('Testing...', 'woo-czl-express'); ?>');
        
        $.post(ajaxurl, {
            action: 'czl_test_connection',
            nonce: '<?php echo wp_create_nonce('czl_test_api'); ?>'
        }, function(response) {
            $button.prop('disabled', false);
            if (response.success) {
                $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
            } else {
                $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
            }
        });
    });
    
    $('#czl-test-shipping-rate').on('click', function() {
        var $button = $(this);
        var $result = $('#czl-test-result');
        
        $button.prop('disabled', true);
        $result.html('<?php _e('Testing...', 'woo-czl-express'); ?>');
        
        $.post(ajaxurl, {
            action: 'czl_test_shipping_rate',
            nonce: '<?php echo wp_create_nonce('czl_test_api'); ?>'
        }, function(response) {
            $button.prop('disabled', false);
            if (response.success) {
                var html = '<div class="notice notice-success"><p><?php _e('Shipping rates retrieved successfully:', 'woo-czl-express'); ?></p>';
                html += '<pre>' + JSON.stringify(response.data, null, 2) + '</pre></div>';
                $result.html(html);
            } else {
                $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
            }
        });
    });
});
</script> 