<?php
class CZL_Tracking {
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