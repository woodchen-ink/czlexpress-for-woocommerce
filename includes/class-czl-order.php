<?php
// 确保没有命名空间冲突
if (!class_exists('CZL_Order')) {
    class CZL_Order {
        private $api;
        
        public function __construct() {
            $this->api = new CZL_API();
        }
        
        /**
         * 创建运单
         */
        public function create_shipment($order_id) {
            global $wpdb;
            
            try {
                error_log('CZL Express: Starting create shipment for order ' . $order_id);
                
                // 更新订单备注以显示处理状态
                $order = wc_get_order($order_id);
                if (!$order) {
                    throw new Exception('订单未找到');
                }
                
                // 添加处理中的状态提示
                $order->add_order_note('正在创建运单，请稍候...');
                $order->save();
                
                // 检查是否已有运单
                $existing_shipment = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}czl_shipments WHERE order_id = %d",
                    $order_id
                ));
                
                if ($existing_shipment) {
                    $order->add_order_note('Shipment already exists for this order');
                    throw new Exception('Shipment already exists for this order');
                }
                
                // 获取运输方式信息
                $shipping_methods = $order->get_shipping_methods();
                $shipping_method = current($shipping_methods);
                $product_id = $shipping_method->get_meta('product_id');
                
                if (empty($product_id)) {
                    throw new Exception('Shipping method not found');
                }
                
                // 准备运单数据
                $shipment_data = array(
                    'buyerid' => strval($order->get_id()),
                    'consignee_address' => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
                    'consignee_city' => $order->get_shipping_city(),
                    'consignee_mobile' => $order->get_billing_phone(),
                    'consignee_telephone' => $order->get_billing_phone(),
                    'consignee_name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                    'consignee_postcode' => $order->get_shipping_postcode(),
                    'consignee_state' => $order->get_shipping_state(),
                    'country' => $order->get_shipping_country(),
                    'order_piece' => strval($this->get_order_items_count($order)),
                    'product_id' => strval($product_id),
                    'trade_type' => 'ZYXT',
                    'weight' => strval($this->get_order_weight($order)),
                    'length' => '10',
                    'width' => '10',
                    'height' => '10',
                    'orderInvoiceParam' => $this->prepare_invoice_items($order)
                );
                
                // 调用API创建运单
                $result = $this->api->create_shipment($shipment_data);
                error_log('CZL Express: API response - ' . print_r($result, true));
                
                // 检查API响应状态
                if ($result['ack'] !== 'true') {
                    $error_msg = isset($result['message']) && !empty($result['message']) 
                        ? urldecode($result['message']) 
                        : '创建运单失败，请稍后重试或联系客服';
                    
                    // 如果有订单号但API返回失败，记录警告并继续
                    if (!empty($result['order_id']) || !empty($result['tracking_number'])) {
                        $order->add_order_note(sprintf(
                            'Shipment Creation Warning: %s (Order ID: %s, Tracking Number: %s)',
                            $error_msg,
                            $result['order_id'],
                            $result['tracking_number']
                        ));
                    } else {
                        // 如果没有订单号，抛出异常
                        throw new Exception($error_msg);
                    }
                }

                // 检查是否有订单号或跟踪号
                if (!empty($result['order_id']) || !empty($result['tracking_number'])) {
                    // 保存运单信息到数据库
                    $wpdb->insert(
                        $wpdb->prefix . 'czl_shipments',
                        array(
                            'order_id' => $order_id,
                            'tracking_number' => $result['tracking_number'],
                            'czl_order_id' => $result['order_id'],
                            'reference_number' => $result['reference_number'],
                            'is_remote' => $result['is_remote'],
                            'is_residential' => $result['is_residential'],
                            'shipping_method' => $shipping_method->get_method_id(),
                            'status' => $result['ack'] === 'true' ? 'pending' : 'warning',
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
                    );
                    
                    // 更新订单元数据
                    $order->update_meta_data('_czl_tracking_number', $result['tracking_number']);
                    $order->update_meta_data('_czl_order_id', $result['order_id']);
                    $order->update_meta_data('_czl_reference_number', $result['reference_number']);
                    $order->save();
                    
                    // 添加成功提示
                    $order->add_order_note(sprintf(
                        'Shipment created successfully (Order ID: %s, Tracking Number: %s)',
                        $result['order_id'],
                        $result['tracking_number']
                    ));
                    
                    return true;
                } else {
                    throw new Exception('API未返回运单号或跟踪号');
                }
                
            } catch (Exception $e) {
                error_log('CZL Express Error: Create shipment failed - ' . esc_html($e->getMessage()));
                error_log('CZL Express Error Stack Trace: ' . esc_html($e->getTraceAsString()));
                
                // 添加错误提示到订单备注
                if (isset($order)) {
                    $order->add_order_note('运单创建失败: ' . esc_html($e->getMessage()));
                }
                
                // 抛出异常以便上层处理
                throw new Exception(esc_html($e->getMessage()));
            }
        }
        
        /**
         * 获取订单总重量
         */
        private function get_order_weight($order) {
            $total_weight = 0;
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && $product->get_weight()) {
                    $total_weight += ($product->get_weight() * $item->get_quantity());
                }
            }
            return max(0.1, $total_weight); // 最小重量0.1kg
        }
        
        private function get_order_items_count($order) {
            $count = 0;
            foreach ($order->get_items() as $item) {
                $count += $item->get_quantity();
            }
            return max(1, $count);
        }
        
        private function prepare_invoice_items($order) {
            $items = array();
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if (!$product) {
                    continue;
                }
                
                // 获取产品尺寸，确保是数字类型
                $length = floatval($product->get_length() ?: 10);
                $width = floatval($product->get_width() ?: 10);
                $height = floatval($product->get_height() ?: 10);
                
                $items[] = array(
                    'invoice_amount' => strval($item->get_total()),  // 转为字符串
                    'invoice_pcs' => intval($item->get_quantity()),  // 确保是整数
                    'invoice_title' => $item->get_name(),
                    'invoice_weight' => floatval($product->get_weight() * $item->get_quantity()),  // 确保是数字
                    'sku' => $product->get_meta('_czl_name_cn'),
                    'hs_code' => $product->get_meta('_czl_hs_code'),
                    'invoice_material' => $product->get_meta('_czl_material'),
                    'invoice_purpose' => $product->get_meta('_czl_usage'),
                    'item_id' => strval($product->get_id()),  // 转为字符串
                    'sku_code' => $product->get_sku(),
                    'length' => strval($length),  // 转为字符串
                    'width' => strval($width),    // 转为字符串
                    'height' => strval($height)   // 转为字符串
                );
            }
            return $items;
        }
        
        /**
         * 更新运单轨迹信息
         */
        public function update_tracking_info($order_id) {
            global $wpdb;
            
            // 获取运单信息
            $shipment = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}czl_shipments WHERE order_id = %d",
                $order_id
            ));
            
            if (!$shipment || empty($shipment->tracking_number)) {
                throw new Exception('未找到运单信息');
            }
            
            // 获取轨迹信息
            $api = new CZL_API();
            $tracking_info = $api->get_tracking_info($shipment->tracking_number);
            error_log('CZL Express: Tracking API response - ' . print_r($tracking_info, true));
            
            // 处理API响应
            if (!empty($tracking_info) && isset($tracking_info['success']) && $tracking_info['success'] == 1) {
                // 检查API返回的状态
                $api_status = isset($tracking_info['data']['status']) ? strtolower(trim($tracking_info['data']['status'])) : '';
                $is_delivered = ($api_status === 'delivered');
                
                // 如果API状态不是delivered，再检查轨迹详情
                if (!$is_delivered && isset($tracking_info[0]['data'][0]['trackDetails']) && is_array($tracking_info[0]['data'][0]['trackDetails'])) {
                    $latest_track = $tracking_info[0]['data'][0]['trackDetails'][0];
                    $track_signdate = isset($latest_track['track_signdate']) ? trim($latest_track['track_signdate']) : '';
                    $track_signperson = isset($latest_track['track_signperson']) ? trim($latest_track['track_signperson']) : '';
                    
                    // 如果有签收时间和签收人，也认为是已签收
                    if (!empty($track_signdate) && !empty($track_signperson)) {
                        $is_delivered = true;
                    }
                }
                
                // 更新运单表状态
                $wpdb->update(
                    $wpdb->prefix . 'czl_shipments',
                    array(
                        'status' => $is_delivered ? 'delivered' : 'in_transit',
                        'last_sync_time' => current_time('mysql')
                    ),
                    array('order_id' => $order_id),
                    array('%s', '%s'),
                    array('%d')
                );
                
                // 获取订单对象
                $order = wc_get_order($order_id);
                if ($order) {
                    // 根据签收状态更新订单状态
                    if ($is_delivered && $order->get_status() !== 'completed') {
                        $order->update_status('completed', sprintf('包裹已签收（%s），订单自动完成', 
                            $tracking_info['data']['track_content']
                        ));
                    }
                    
                    try {
                        // 构建轨迹信息
                        $note_content = array();
                        $note_content[] = 'Package Status Update:';
                        
                        if (!empty($tracking_info['data']['track_content'])) {
                            $note_content[] = sprintf('Status: %s', $tracking_info['data']['track_content']);
                        }
                        
                        if (!empty($tracking_info['data']['track_location'])) {
                            $note_content[] = sprintf('Location: %s', $tracking_info['data']['track_location']);
                        }
                        
                        if (!empty($tracking_info['data']['track_time'])) {
                            $note_content[] = sprintf('Time: %s', $tracking_info['data']['track_time']);
                        }
                        
                        $note_content[] = sprintf('Tracking Number: %s', $shipment->tracking_number);
                        $note_content[] = '';
                        $note_content[] = sprintf('Track your package: https://exp.czl.net/track/?query=%s', $shipment->tracking_number);
                        
                        // 添加新的轨迹信息
                        $note = implode("\n", array_filter($note_content));
                        $comment_id = $order->add_order_note($note, 1);
                        
                        if (!$comment_id) {
                            throw new Exception('Failed to add order note');
                        }
                        
                        $order->save();
                        return true;
                        
                    } catch (Exception $e) {
                        error_log('CZL Express: Error updating tracking info - ' . $e->getMessage());
                        throw $e;
                    }
                }
            }
            
            return false;
        }
    }
} 