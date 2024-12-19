<?php
if (!defined('ABSPATH')) {
    exit;
}

// 加载必要的脚本和样式
wp_enqueue_script('jquery');
wp_enqueue_style('thickbox');
wp_enqueue_script('thickbox');
wp_enqueue_script('media-upload');

// 使用WooCommerce的API获取订单
$orders_query = new WC_Order_Query(array(
    'limit' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
    'status' => array('processing', 'completed', 'on-hold', 'in_transit', 'delivered')
));

$orders = $orders_query->get_orders();

// 获取运单信息
global $wpdb;
$shipments = array();
$shipment_results = $wpdb->get_results("
    SELECT order_id, tracking_number, czl_order_id, reference_number, status as shipment_status, label_url
    FROM {$wpdb->prefix}czl_shipments
");
foreach ($shipment_results as $shipment) {
    $shipments[$shipment->order_id] = $shipment;
}

// 添加必要的JavaScript变量
wp_enqueue_script('jquery');
?>
<script type="text/javascript">
    var czl_ajax = {
        ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('czl_ajax_nonce'); ?>',
        creating_text: '<?php _e('正在创建运单...', 'woo-czl-express'); ?>',
        success_text: '<?php _e('运单创建成功', 'woo-czl-express'); ?>',
        error_text: '<?php _e('运单创建失败', 'woo-czl-express'); ?>'
    };
</script>

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
                <th><?php _e('配送方式', 'woo-czl-express'); ?></th>
                <th><?php _e('运单信息', 'woo-czl-express'); ?></th>
                <th><?php _e('操作', 'woo-czl-express'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (empty($orders)) {
                echo '<tr><td colspan="8">' . __('没有找到订单', 'woo-czl-express') . '</td></tr>';
            } else {
                foreach ($orders as $order): 
                    $order_id = $order->get_id();
                    $shipment = isset($shipments[$order_id]) ? $shipments[$order_id] : null;
                    
                    // 获取配送方式
                    $shipping_methods = $order->get_shipping_methods();
                    $shipping_method = current($shipping_methods);
                    $shipping_method_title = $shipping_method ? $shipping_method->get_method_title() : '';
                ?>
                <tr>
                    <td class="check-column">
                        <input type="checkbox" name="order_ids[]" value="<?php echo esc_attr($order_id); ?>">
                    </td>
                    <td>
                        <a href="<?php echo esc_url($order->get_edit_order_url()); ?>" target="_blank">
                            #<?php echo esc_html($order->get_order_number()); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></td>
                    <td>
                        <?php 
                        $status_name = wc_get_order_status_name($order->get_status());
                        $status_class = sanitize_html_class('status-' . $order->get_status());
                        echo "<mark class='order-status {$status_class}'><span>" . esc_html($status_name) . "</span></mark>";
                        ?>
                    </td>
                    <td>
                        <?php echo esc_html($order->get_formatted_shipping_full_name()); ?><br>
                        <?php echo esc_html($order->get_shipping_address_1()); ?><br>
                        <?php echo esc_html($order->get_shipping_city() . ', ' . $order->get_shipping_country()); ?>
                    </td>
                    <td>
                        <?php echo esc_html($shipping_method_title); ?>
                    </td>
                    <td>
                        <?php if ($shipment && !empty($shipment->tracking_number)): ?>
                            <strong><?php _e('运单号:', 'woo-czl-express'); ?></strong> 
                            <a href="https://exp.czl.net/track/?query=<?php echo esc_attr($shipment->tracking_number); ?>" target="_blank">
                                <?php echo esc_html($shipment->tracking_number); ?>
                            </a>
                            <button type="button" class="button button-small edit-tracking-btn" 
                                    data-order-id="<?php echo $order_id; ?>"
                                    data-tracking-number="<?php echo esc_attr($shipment->tracking_number); ?>"
                                    style="margin-left: 5px;">
                                <span class="dashicons dashicons-edit" style="font-size: 16px; height: 16px; width: 16px;"></span>
                            </button><br>
                            <strong><?php _e('CZL订单号:', 'woo-czl-express'); ?></strong> 
                            <?php echo esc_html($shipment->czl_order_id); ?><br>
                            <strong><?php _e('参考号:', 'woo-czl-express'); ?></strong> 
                            <?php echo esc_html($shipment->reference_number); ?><br>
                            <strong><?php _e('运单状态:', 'woo-czl-express'); ?></strong> 
                            <?php echo esc_html($shipment->shipment_status); ?>
                        <?php else: ?>
                            <?php _e('未创建运单', 'woo-czl-express'); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$shipment || empty($shipment->tracking_number)): ?>
                            <button type="button" class="button czl-create-btn" 
                                    data-order-id="<?php echo $order_id; ?>">
                                <?php _e('创建运单', 'woo-czl-express'); ?>
                            </button>
                        <?php elseif (!empty($shipment->czl_order_id)): ?>
                            <button type="button" class="button" 
                                    onclick="printLabel('<?php echo $shipment->czl_order_id; ?>')">
                                <?php _e('打印标签', 'woo-czl-express'); ?>
                            </button>
                            <button type="button" class="button update-tracking-btn" 
                                    data-order-id="<?php echo $order_id; ?>">
                                <?php _e('更新轨迹', 'woo-czl-express'); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach;
            }
            ?>
        </tbody>
    </table>
</div>

<!-- 添加修改跟踪单号的对话框 -->
<div id="edit-tracking-dialog" class="hidden">
    <div style="padding:20px;">
        <div class="form-field" style="margin-bottom:15px;">
            <label for="new-tracking-number" style="display:block;margin-bottom:8px;font-weight:600;">
                <?php _e('新跟踪单号:', 'woo-czl-express'); ?>
            </label>
            <input type="text" id="new-tracking-number" style="width:100%;padding:5px;">
            <input type="hidden" id="edit-order-id" value="">
        </div>
        <div style="text-align:right;margin-top:20px;">
            <button type="button" class="button" onclick="self.parent.tb_remove();" style="margin-right:5px;">
                <?php _e('取消', 'woo-czl-express'); ?>
            </button>
            <button type="button" class="button button-primary" id="save-tracking-number">
                <?php _e('保存', 'woo-czl-express'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // 处理编辑按钮点击
    $('.edit-tracking-btn').on('click', function(e) {
        e.preventDefault();
        var orderId = $(this).data('order-id');
        var currentTracking = $(this).data('tracking-number');
        
        // 设置当前订单ID和跟踪单号
        $('#edit-order-id').val(orderId);
        $('#new-tracking-number').val(currentTracking || '');
        
        // 打开对话框
        tb_show(
            '<?php _e('修改跟踪单号', 'woo-czl-express'); ?>', 
            '#TB_inline?width=300&height=180&inlineId=edit-tracking-dialog'
        );
        
        // 调整对话框样式
        $('#TB_window').css({
            'height': 'auto',
            'width': '300px',
            'margin-left': '-150px',
            'top': '50%',
            'margin-top': '-90px'
        });
        
        // 聚焦输入框
        setTimeout(function() {
            $('#new-tracking-number').focus().select();
        }, 100);
    });
    
    // 处理保存按钮点击
    $('#save-tracking-number').on('click', function() {
        var orderId = $('#edit-order-id').val();
        var newTrackingNumber = $('#new-tracking-number').val().trim();
        
        if (!newTrackingNumber) {
            alert('请输入新的跟踪单号');
            return;
        }
        
        // 发送AJAX请求更新跟踪单号
        $.ajax({
            url: czl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'czl_update_tracking_number',
                nonce: czl_ajax.nonce,
                order_id: orderId,
                tracking_number: newTrackingNumber
            },
            success: function(response) {
                if (response.success) {
                    alert('跟踪单号更新成功');
                    self.parent.tb_remove();
                    window.location.reload();
                } else {
                    alert(response.data || '更新失败');
                }
            },
            error: function() {
                alert('请求失败，请重试');
            }
        });
    });
    
    // 处理更新轨迹按钮点击
    $(document).on('click', '.update-tracking-btn', function() {
        var $button = $(this);
        var orderId = $button.data('order-id');
        
        $button.prop('disabled', true).text('更新中...');
        
        $.ajax({
            url: czl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'czl_update_tracking_info',
                nonce: czl_ajax.nonce,
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    alert('轨迹更新成功');
                    window.location.reload();
                } else {
                    alert(response.data || '更新失败');
                    $button.prop('disabled', false).text('更新轨迹');
                }
            },
            error: function() {
                alert('请求失败，请重试');
                $button.prop('disabled', false).text('更新轨迹');
            }
        });
    });
    
    // 处理创建运单按钮点击
    $(document).on('click', '.czl-create-btn', function() {
        var $button = $(this);
        var orderId = $button.data('order-id');
        
        // 如果按钮已经在处理中，则返回
        if ($button.hasClass('processing')) {
            return;
        }
        
        // 显示处理中状态
        $button.addClass('processing').text(czl_ajax.creating_text);
        
        // 发送AJAX请求
        $.ajax({
            url: czl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'czl_create_shipment',
                nonce: czl_ajax.nonce,
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    // 显示成功消息
                    $button.removeClass('processing').addClass('success')
                           .text(czl_ajax.success_text);
                    
                    // 2秒后刷新页面
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    // 显示错误消息
                    $button.removeClass('processing').addClass('error')
                           .text(response.data);
                    
                    // 3秒后恢复按钮状态
                    setTimeout(function() {
                        $button.removeClass('error')
                               .text('创建运单');
                    }, 3000);
                }
            },
            error: function() {
                // 显示错误消息
                $button.removeClass('processing').addClass('error')
                       .text(czl_ajax.error_text);
                
                // 3秒后恢复按钮状态
                setTimeout(function() {
                    $button.removeClass('error')
                           .text('创建运单');
                }, 3000);
            }
        });
    });
    
    // 添加按钮样式
    $('<style>')
        .text(`
            .czl-create-btn {
                position: relative;
            }
            .czl-create-btn.processing {
                pointer-events: none;
                opacity: 0.7;
            }
            .czl-create-btn.success {
                background-color: #7ad03a !important;
                color: #fff !important;
            }
            .czl-create-btn.error {
                background-color: #dc3232 !important;
                color: #fff !important;
            }
        `)
        .appendTo('head');
});

// 打印标签功能
function printLabel(orderId) {
    // 构建标签URL
    var labelUrl = 'https://tms-label.czl.net/order/FastRpt/PDF_NEW.aspx?Format=lbl_sub一票多件161810499441.frx&PrintType=1&order_id=' + orderId;
    
    // 在新窗口打开标签
    window.open(labelUrl, '_blank');
}
</script> 