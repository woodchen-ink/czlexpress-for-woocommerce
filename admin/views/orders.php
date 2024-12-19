<?php
if (!defined('ABSPATH')) {
    exit;
}

// 获取订单列表
$orders_query = new WC_Order_Query(array(
    'limit' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
    'status' => array('processing', 'completed', 'shipping', 'delivered')
));

$orders = $orders_query->get_orders();
?>

<div class="wrap">
    <h1><?php _e('CZL Express 订单管理', 'woo-czl-express'); ?></h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <select id="bulk-action-selector-top">
                <option value="-1"><?php _e('批量操作', 'woo-czl-express'); ?></option>
                <option value="create_shipment"><?php _e('创建运单', 'woo-czl-express'); ?></option>
            </select>
            <button class="button" id="doaction"><?php _e('应用', 'woo-czl-express'); ?></button>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td class="manage-column column-cb check-column">
                    <input type="checkbox" id="cb-select-all-1">
                </td>
                <th><?php _e('订单号', 'woo-czl-express'); ?></th>
                <th><?php _e('日期', 'woo-czl-express'); ?></th>
                <th><?php _e('状态', 'woo-czl-express'); ?></th>
                <th><?php _e('收件人', 'woo-czl-express'); ?></th>
                <th><?php _e('运单信息', 'woo-czl-express'); ?></th>
                <th><?php _e('操作', 'woo-czl-express'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <?php 
            $tracking_number = $order->get_meta('_czl_tracking_number');
            $czl_order_id = $order->get_meta('_czl_order_id');
            $reference_number = $order->get_meta('_czl_reference_number');
            
            $shipping_methods = $order->get_shipping_methods();
            $shipping_method = current($shipping_methods);
            $is_czl = $shipping_method && strpos($shipping_method->get_method_id(), 'czl_express') !== false;
            ?>
            <?php if ($is_czl): ?>
            <tr>
                <th scope="row" class="check-column">
                    <input type="checkbox" name="order_ids[]" value="<?php echo esc_attr($order->get_id()); ?>">
                </th>
                <td>
                    <a href="<?php echo esc_url($order->get_edit_order_url()); ?>" target="_blank">
                        #<?php echo esc_html($order->get_order_number()); ?>
                    </a>
                </td>
                <td><?php echo esc_html($order->get_date_created()->date_i18n('Y-m-d H:i:s')); ?></td>
                <td><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></td>
                <td>
                    <?php echo esc_html($order->get_formatted_shipping_full_name()); ?><br>
                    <?php echo esc_html($order->get_shipping_address_1()); ?>
                </td>
                <td>
                    <?php if ($tracking_number): ?>
                        <strong><?php _e('运单号:', 'woo-czl-express'); ?></strong> 
                        <a href="https://exp.czl.net/track/?query=<?php echo esc_attr($tracking_number); ?>" target="_blank">
                            <?php echo esc_html($tracking_number); ?>
                        </a><br>
                        <strong><?php _e('CZL订单号:', 'woo-czl-express'); ?></strong> 
                        <?php echo esc_html($czl_order_id); ?><br>
                        <strong><?php _e('参考号:', 'woo-czl-express'); ?></strong> 
                        <?php echo esc_html($reference_number); ?>
                    <?php else: ?>
                        <?php _e('未创建运单', 'woo-czl-express'); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($tracking_number)): ?>
                        <button type="button" class="button" onclick="printLabel(<?php echo $order->get_id(); ?>)">
                            <?php _e('打印标签', 'woo-czl-express'); ?>
                        </button>
                    <?php endif; ?>
                    <?php if (empty($tracking_number)): ?>
                        <button type="button" class="button" onclick="createShipment(<?php echo $order->get_id(); ?>)">
                            <?php _e('创建运单', 'woo-czl-express'); ?>
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // 单个订单创建运单
    $('.create-shipment').click(function() {
        var button = $(this);
        var orderId = button.data('order-id');
        
        button.prop('disabled', true).text('<?php _e('处理中...', 'woo-czl-express'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'czl_create_shipment',
                order_id: orderId,
                nonce: '<?php echo wp_create_nonce('czl_create_shipment'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                    button.prop('disabled', false).text('<?php _e('创建运单', 'woo-czl-express'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('请求失败，请重试', 'woo-czl-express'); ?>');
                button.prop('disabled', false).text('<?php _e('创建运单', 'woo-czl-express'); ?>');
            }
        });
    });
    
    // 批量创建运单
    $('#doaction').click(function(e) {
        e.preventDefault();
        
        var action = $('#bulk-action-selector-top').val();
        if (action !== 'create_shipment') {
            return;
        }
        
        var orderIds = [];
        $('input[name="order_ids[]"]:checked').each(function() {
            orderIds.push($(this).val());
        });
        
        if (orderIds.length === 0) {
            alert('<?php _e('请选择订单', 'woo-czl-express'); ?>');
            return;
        }
        
        if (!confirm('<?php _e('确定要为选中的订单创建运单吗？', 'woo-czl-express'); ?>')) {
            return;
        }
        
        $(this).prop('disabled', true).text('<?php _e('处理中...', 'woo-czl-express'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'czl_bulk_create_shipment',
                order_ids: orderIds,
                nonce: '<?php echo wp_create_nonce('czl_bulk_create_shipment'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('请求失败，请重试', 'woo-czl-express'); ?>');
            },
            complete: function() {
                $('#doaction').prop('disabled', false).text('<?php _e('应用', 'woo-czl-express'); ?>');
            }
        });
    });
});

// 打印标签功能
function printLabel(orderId) {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'czl_print_label',
            order_id: orderId,
            security: '<?php echo wp_create_nonce("czl-print-label"); ?>'
        },
        success: function(response) {
            if (response.success && response.data.url) {
                // 在新窗口打开标签
                window.open(response.data.url, '_blank');
            } else {
                alert(response.data.message || '获取标签失败');
            }
        },
        error: function() {
            alert('请求失败，请重试');
        }
    });
}
</script> 