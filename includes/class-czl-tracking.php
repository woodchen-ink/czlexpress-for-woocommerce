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
        try {
            $api = new CZL_API();
            $tracking_info = $api->get_tracking_info($tracking_number);
            
            if (!$tracking_info['success']) {
                throw new Exception($tracking_info['message']);
            }
            
            // 更新订单状态
            global $wpdb;
            $shipment = $wpdb->get_row($wpdb->prepare(
                "SELECT order_id FROM {$wpdb->prefix}czl_shipments WHERE tracking_number = %s",
                $tracking_number
            ));
            
            if ($shipment && $shipment->order_id) {
                $order = wc_get_order($shipment->order_id);
                if ($order) {
                    // 获取最新的轨迹信息
                    $latest_track_content = $tracking_info['data']['track_content'];
                    $latest_track_location = $tracking_info['data']['track_location'];
                    $latest_track_time = $tracking_info['data']['track_time'];
                    
                    // 获取订单的备注
                    $notes = wc_get_order_notes(['order_id' => $shipment->order_id, 'limit' => 1]);
                    
                    // 检查是否需要添加新备注
                    $should_add_note = true;
                    if (!empty($notes)) {
                        $last_note = $notes[0];
                        // 检查最后一条备注是否包含相同的轨迹信息
                        if (strpos($last_note->content, $latest_track_content) !== false &&
                            strpos($last_note->content, $latest_track_location) !== false &&
                            strpos($last_note->content, $latest_track_time) !== false) {
                            $should_add_note = false;
                        }
                    }
                    
                    if ($should_add_note) {
                        // 添加轨迹信息作为订单备注（设置为公开可见）
                        $note = sprintf(
                            "📦 Package Update\n\n" .
                            "Status: %s\n" .
                            "Location: %s\n" .
                            "Time: %s\n\n" .
                            "Track your package: https://exp.czl.net/track/?query=%s",
                            $latest_track_content,
                            $latest_track_location,
                            $latest_track_time,
                            $tracking_number
                        );
                        $order->add_order_note($note, 1); // 1表示对客户可见
                    }
                    
                    // 如果是已签收状态，更新订单状态
                    if ($tracking_info['data']['status'] === 'delivered') {
                        $order->update_status('completed', '📦 Package delivered, order completed automatically');
                    }
                }
            }
            
            return $tracking_info;
            
        } catch (Exception $e) {
            CZL_Logger::error('Failed to update tracking info', array(
                'tracking_number' => $tracking_number,
                'error' => $e->getMessage()
            ));
            throw $e;
        }
    }
    
    /**
     * 更新订单状态
     */
    public function update_order_status($order_id, $tracking_info) {
        try {
            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }
            
            $status = $tracking_info['status'];
            $content = $tracking_info['track_content'];
            $location = $tracking_info['track_location'];
            $time = $tracking_info['track_time'];
            
            // 添加订单备注
            $order->add_order_note(sprintf(
                /* translators: 1: status 2: location 3: time 4: details */
                __('包裹状态: %1$s, 位置: %2$s, 时间: %3$s, 详情: %4$s', 'czlexpress-for-woocommerce'),
                $status,
                $location,
                $time,
                $content
            ));
            
            // 更新订单状态
            switch ($status) {
                case 'delivered':
                    $order->update_status('completed');
                    break;
                case 'in_transit':
                    $order->update_status('in_transit');
                    break;
                case 'picked_up':
                    $order->update_status('processing');
                    break;
            }
            
        } catch (Exception $e) {
            CZL_Logger::error('Tracking error', array(
                'order_id' => $order_id,
                'error' => $e->getMessage()
            ));
            throw $e;
        }
    }
    
    /**
     * 清除运单相关的所有缓存
     * 
     * @param int $shipment_id 运单ID
     * @param string $tracking_number 运单号
     * @param int $order_id 订单ID
     */
    public function clear_tracking_cache($shipment_id, $tracking_number, $order_id) {
        // 清除运单缓存
        wp_cache_delete('czl_shipment_' . $shipment_id);
        wp_cache_delete('czl_tracking_' . $tracking_number);
        
        // 清除订单相关缓存
        wp_cache_delete('czl_order_shipments_' . $order_id);
        wp_cache_delete('czl_order_tracking_' . $order_id);
        
        // 清除轨迹历史缓存
        wp_cache_delete('czl_tracking_history_' . $shipment_id);
        wp_cache_delete('czl_latest_tracking_' . $tracking_number);
        
        // 清除运单列表缓存
        wp_cache_delete('czl_active_shipments');
        wp_cache_delete('czl_pending_shipments');
        
        // 触发缓存清除动作，允许其他部分清除相关缓存
        do_action('czl_tracking_cache_cleared', $shipment_id, $tracking_number, $order_id);
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
                echo '<h2>' . esc_html__('物流跟踪信息', 'czlexpress-for-woocommerce') . '</h2>';
                echo '<div class="czl-tracking-info">';
                echo '<p><strong>' . esc_html__('运单号：', 'czlexpress-for-woocommerce') . '</strong>' . esc_html($tracking_number) . '</p>';
                
                if (!empty($tracking_info['trackDetails'])) {
                    echo '<table class="czl-tracking-details">';
                    echo '<thead><tr>';
                    echo '<th>' . esc_html__('时间', 'czlexpress-for-woocommerce') . '</th>';
                    echo '<th>' . esc_html__('地点', 'czlexpress-for-woocommerce') . '</th>';
                    echo '<th>' . esc_html__('状态', 'czlexpress-for-woocommerce') . '</th>';
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
            CZL_Logger::error('Tracking display error', array(
                'tracking_number' => $tracking_number,
                'error' => $e->getMessage()
            ));
        }
    }
    
    /**
     * 添加跟踪链接到订单邮件
     */
    public static function add_tracking_to_email($order, $sent_to_admin = false) {
        $tracking_number = get_post_meta($order->get_id(), '_czl_tracking_number', true);
        if ($tracking_number) {
            $tracking_url = 'https://exp.czl.net/track/?query=' . urlencode($tracking_number);
            echo '<p><strong>' . esc_html__('物流跟踪：', 'czlexpress-for-woocommerce') . '</strong>';
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
            <h3><?php esc_html_e('CZL Express 运单信息', 'czlexpress-for-woocommerce'); ?></h3>
            <p>
                <?php 
                printf(
                    esc_html__('运单号: %s', 'czlexpress-for-woocommerce'),
                    '<a href="https://exp.czl.net/track/?query=' . esc_attr($tracking_number) . '" target="_blank">' . 
                    esc_html($tracking_number) . '</a>'
                ); 
                ?>
            </p>
            
            <?php
            // 显示子单号
            $child_numbers = $order->get_meta('_czl_child_numbers');
            if (!empty($child_numbers)) {
                echo '<p><strong>' . esc_html__('子单号：', 'czlexpress-for-woocommerce') . '</strong> ' . 
                     implode(', ', array_map('esc_html', $child_numbers)) . '</p>';
            }
            
            // 显示参考号
            $reference_number = $order->get_meta('_czl_reference_number');
            if (!empty($reference_number)) {
                echo '<p><strong>' . esc_html__('参考号：', 'czlexpress-for-woocommerce') . '</strong> ' . 
                     esc_html($reference_number) . '</p>';
            }
            
            // 显示偏远信息
            $is_remote = $order->get_meta('_czl_is_remote');
            if (!empty($is_remote)) {
                $remote_text = '';
                switch ($is_remote) {
                    case 'Y':
                        $remote_text = esc_html__('偏远地区', 'czlexpress-for-woocommerce');
                        break;
                    case 'A':
                        $remote_text = esc_html__('FedEx偏远A级', 'czlexpress-for-woocommerce');
                        break;
                    case 'B':
                        $remote_text = esc_html__('FedEx偏远B级', 'czlexpress-for-woocommerce');
                        break;
                    case 'C':
                        $remote_text = esc_html__('FedEx偏远C级', 'czlexpress-for-woocommerce');
                        break;
                    case 'N':
                        $remote_text = esc_html__('非偏远地区', 'czlexpress-for-woocommerce');
                        break;
                }
                if ($remote_text) {
                    echo '<p><strong>' . esc_html__('地区类型：', 'czlexpress-for-woocommerce') . '</strong> ' . 
                         esc_html($remote_text) . '</p>';
                }
            }
            
            // 显示住宅信息
            $is_residential = $order->get_meta('_czl_is_residential');
            if ($is_residential === 'Y') {
                echo '<p><strong>' . esc_html__('地址类型：', 'czlexpress-for-woocommerce') . '</strong> ' . 
                     esc_html__('住宅地址', 'czlexpress-for-woocommerce') . '</p>';
            }
            
            // 显示轨迹信息
            $tracking_history = $order->get_meta('_czl_tracking_history');
            if (!empty($tracking_history['trackDetails'])) {
                ?>
                <div class="czl-tracking-details">
                    <h4><?php esc_html_e('最新轨迹', 'czlexpress-for-woocommerce'); ?></h4>
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