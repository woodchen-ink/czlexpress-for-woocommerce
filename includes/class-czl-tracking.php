<?php
class CZL_Tracking {
    private $api;
    
    public function __construct() {
        $this->api = new CZL_API();
        
        // 添加定时任务钩子
        add_action('czl_update_tracking_cron', array($this, 'update_all_tracking_info'));
        
        // 如果定时任务未设置，则设置它
        if (!wp_next_scheduled('czl_update_tracking_cron')) {
            wp_schedule_event(time(), 'hourly', 'czl_update_tracking_cron');
        }
    }
    
    /**
     * 更新所有活跃运单的轨迹信息
     */
    public function update_all_tracking_info() {
        global $wpdb;
        
        // 获取所有需要更新的运单
        $shipments = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}czl_shipments 
            WHERE status NOT IN ('delivered', 'cancelled', 'failed')"
        );
        
        foreach ($shipments as $shipment) {
            $this->update_tracking_info($shipment->tracking_number);
        }
    }
    
    /**
     * 更新单个运单的轨迹信息
     */
    public function update_tracking_info($tracking_number) {
        global $wpdb;
        
        try {
            // 获取运单信息
            $shipment = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}czl_shipments WHERE tracking_number = %s",
                $tracking_number
            ));
            
            if (!$shipment) {
                return;
            }
            
            // 获取轨迹信息
            $tracking_info = $this->api->get_tracking($tracking_number);
            
            if (!empty($tracking_info['trackDetails'])) {
                foreach ($tracking_info['trackDetails'] as $detail) {
                    // 检查是否已存在该轨迹记录
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}czl_tracking_history 
                        WHERE shipment_id = %d AND track_date = %s AND track_content = %s",
                        $shipment->id,
                        $detail['track_date'],
                        $detail['track_content']
                    ));
                    
                    if (!$exists) {
                        // 插入新的轨迹记录
                        $wpdb->insert(
                            $wpdb->prefix . 'czl_tracking_history',
                            array(
                                'shipment_id' => $shipment->id,
                                'tracking_number' => $tracking_number,
                                'track_date' => $detail['track_date'],
                                'track_location' => $detail['track_location'],
                                'track_content' => $detail['track_content'],
                                'created_at' => current_time('mysql')
                            ),
                            array('%d', '%s', '%s', '%s', '%s', '%s')
                        );
                    }
                }
                
                // 更新运单状态
                $latest = reset($tracking_info['trackDetails']);
                $new_status = 'in_transit';
                
                if (strpos($latest['track_content'], '已签收') !== false || 
                    strpos($latest['track_content'], 'Delivered') !== false) {
                    $new_status = 'delivered';
                }
                
                // 更新运单状态
                $wpdb->update(
                    $wpdb->prefix . 'czl_shipments',
                    array('status' => $new_status),
                    array('id' => $shipment->id),
                    array('%s'),
                    array('%d')
                );
                
                // 更新WooCommerce订单状态
                $order = wc_get_order($shipment->order_id);
                if ($order) {
                    $order->update_status($new_status, __('Package status updated from tracking info', 'woo-czl-express'));
                }
            }
            
        } catch (Exception $e) {
            error_log('CZL Express Error: Failed to update tracking info - ' . $e->getMessage());
        }
    }
    
    /**
     * 在订单详情页显示跟踪信息
     */
    public static function display_tracking_info($order) {
        $tracking_number = get_post_meta($order->get_id(), '_czl_tracking_number', true);
        if (!$tracking_number) {
            return;
        }
        
        try {
            $api = new CZL_API();
            $tracking_info = $api->get_tracking($tracking_number);
            
            if (!empty($tracking_info)) {
                echo '<h2>' . __('物流跟踪信息', 'woo-czl-express') . '</h2>';
                echo '<div class="czl-tracking-info">';
                echo '<p><strong>' . __('运单号：', 'woo-czl-express') . '</strong>' . esc_html($tracking_number) . '</p>';
                
                if (!empty($tracking_info['trackDetails'])) {
                    echo '<table class="czl-tracking-details">';
                    echo '<thead><tr>';
                    echo '<th>' . __('时间', 'woo-czl-express') . '</th>';
                    echo '<th>' . __('地点', 'woo-czl-express') . '</th>';
                    echo '<th>' . __('状态', 'woo-czl-express') . '</th>';
                    echo '</tr></thead><tbody>';
                    
                    foreach ($tracking_info['trackDetails'] as $detail) {
                        echo '<tr>';
                        echo '<td>' . esc_html($detail['track_date']) . '</td>';
                        echo '<td>' . esc_html($detail['track_location']) . '</td>';
                        echo '<td>' . esc_html($detail['track_content']) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                }
                
                echo '</div>';
            }
        } catch (Exception $e) {
            error_log('CZL Express Tracking Error: ' . $e->getMessage());
        }
    }
    
    /**
     * 添加跟踪链接到订单邮件
     */
    public static function add_tracking_to_email($order, $sent_to_admin = false) {
        $tracking_number = get_post_meta($order->get_id(), '_czl_tracking_number', true);
        if ($tracking_number) {
            $tracking_url = 'https://exp.czl.net/track/?query=' . urlencode($tracking_number);
            echo '<p><strong>' . __('物流跟踪：', 'woo-czl-express') . '</strong>';
            echo '<a href="' . esc_url($tracking_url) . '" target="_blank">' . esc_html($tracking_number) . '</a></p>';
        }
    }
    
    /**
     * 在管理员订单页面显示轨迹信息
     */
    public static function display_admin_tracking_info($order) {
        $tracking_number = $order->get_meta('_czl_tracking_number');
        if (empty($tracking_number)) {
            return;
        }
        
        ?>
        <div class="czl-admin-tracking-info">
            <h3><?php _e('CZL Express 运单信息', 'woo-czl-express'); ?></h3>
            <p>
                <?php 
                printf(
                    __('运单号: %s', 'woo-czl-express'),
                    '<a href="https://exp.czl.net/track/?query=' . esc_attr($tracking_number) . '" target="_blank">' . 
                    esc_html($tracking_number) . '</a>'
                ); 
                ?>
            </p>
            
            <?php
            // 显示子单号
            $child_numbers = $order->get_meta('_czl_child_numbers');
            if (!empty($child_numbers)) {
                echo '<p><strong>' . __('子单号：', 'woo-czl-express') . '</strong> ' . 
                     implode(', ', array_map('esc_html', $child_numbers)) . '</p>';
            }
            
            // 显示参考号
            $reference_number = $order->get_meta('_czl_reference_number');
            if (!empty($reference_number)) {
                echo '<p><strong>' . __('参考号：', 'woo-czl-express') . '</strong> ' . 
                     esc_html($reference_number) . '</p>';
            }
            
            // 显示偏远信息
            $is_remote = $order->get_meta('_czl_is_remote');
            if (!empty($is_remote)) {
                $remote_text = '';
                switch ($is_remote) {
                    case 'Y':
                        $remote_text = __('偏远地区', 'woo-czl-express');
                        break;
                    case 'A':
                        $remote_text = __('FedEx偏远A级', 'woo-czl-express');
                        break;
                    case 'B':
                        $remote_text = __('FedEx偏远B级', 'woo-czl-express');
                        break;
                    case 'C':
                        $remote_text = __('FedEx偏远C级', 'woo-czl-express');
                        break;
                    case 'N':
                        $remote_text = __('非偏远地区', 'woo-czl-express');
                        break;
                }
                if ($remote_text) {
                    echo '<p><strong>' . __('地区类型：', 'woo-czl-express') . '</strong> ' . 
                         esc_html($remote_text) . '</p>';
                }
            }
            
            // 显示住宅信息
            $is_residential = $order->get_meta('_czl_is_residential');
            if ($is_residential === 'Y') {
                echo '<p><strong>' . __('地址类型：', 'woo-czl-express') . '</strong> ' . 
                     __('住宅地址', 'woo-czl-express') . '</p>';
            }
            
            // 显示轨迹信息
            $tracking_history = $order->get_meta('_czl_tracking_history');
            if (!empty($tracking_history['trackDetails'])) {
                ?>
                <div class="czl-tracking-details">
                    <h4><?php _e('最新轨迹', 'woo-czl-express'); ?></h4>
                    <?php
                    $latest = reset($tracking_history['trackDetails']);
                    ?>
                    <p>
                        <span class="tracking-date"><?php echo esc_html($latest['track_date']); ?></span>
                        <span class="tracking-content"><?php echo esc_html($latest['track_content']); ?></span>
                    </p>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }
}

// 初始化类
new CZL_Tracking(); 