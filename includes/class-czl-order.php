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
            try {
                $order = wc_get_order($order_id);
                if (!$order) {
                    throw new Exception('订单不存在');
                }
                
                // 检查是否已有运单
                $tracking_number = $order->get_meta('_czl_tracking_number');
                if (!empty($tracking_number)) {
                    throw new Exception('订单已存在运单号');
                }
                
                // 获取运输方式信息
                $shipping_methods = $order->get_shipping_methods();
                $shipping_method = current($shipping_methods);
                
                // 从配送方式元数据中获取product_id
                $product_id = $shipping_method->get_meta('product_id');
                if (empty($product_id)) {
                    throw new Exception('未找到运输方式ID');
                }
                
                // 在create_shipment方法中添加手机号和联系电话
                $phone = $order->get_billing_phone();
                // 清理电话号码，只保留数字
                $phone = preg_replace('/[^0-9]/', '', $phone);
                
                // 准备运单数据
                $shipment_data = array(
                    'buyerid' => $order->get_id(),
                    'consignee_address' => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
                    'consignee_city' => $order->get_shipping_city(),
                    'consignee_mobile' => $order->get_billing_phone(),      // 使用原始手机号
                    'consignee_telephone' => $order->get_billing_phone(),   // 使用原始手机号作为联系电话
                    'consignee_name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                    'consignee_postcode' => $order->get_shipping_postcode(),
                    'consignee_state' => $order->get_shipping_state(),
                    'country' => $order->get_shipping_country(),
                    'order_piece' => $this->get_order_items_count($order),
                    'product_id' => $product_id,
                    'trade_type' => 'ZYXT',
                    'weight' => $this->get_order_weight($order),
                    'orderInvoiceParam' => array()
                );
                
                // 添加发票信息
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if (!$product) {
                        continue;
                    }
                    
                    // 获取中文品名
                    $name_cn = $product->get_meta('_czl_name_cn');
                    if (empty($name_cn)) {
                        throw new Exception('产品 ' . $product->get_name() . ' 缺少中文品名');
                    }
                    
                    // 获取海关编码
                    $hs_code = $product->get_meta('_czl_hs_code');
                    if (empty($hs_code)) {
                        throw new Exception('产品 ' . $product->get_name() . ' 缺少海关编码');
                    }
                    
                    // 获取用途和材质
                    $usage = $product->get_meta('_czl_usage');
                    if (empty($usage)) {
                        throw new Exception('产品 ' . $product->get_name() . ' 缺少用途信息');
                    }
                    
                    $material = $product->get_meta('_czl_material');
                    if (empty($material)) {
                        throw new Exception('产品 ' . $product->get_name() . ' 缺少材质信息');
                    }
                    
                    $shipment_data['orderInvoiceParam'][] = array(
                        'invoice_amount' => $item->get_total(),
                        'invoice_pcs' => $item->get_quantity(),
                        'invoice_title' => $item->get_name(),     // 英文品名（原商品名）
                        'invoice_weight' => $product->get_weight() * $item->get_quantity(),
                        'sku' => $name_cn,                        // 中文品名
                        'hs_code' => $hs_code,                    // 海关编码
                        'invoice_material' => $material,          // 材质
                        'invoice_purpose' => $usage,              // 用途
                        'item_id' => $product->get_id(),
                        'sku_code' => $product->get_sku()         // SKU作为配货信息
                    );
                }
                
                // 调用API创建运单
                $result = $this->api->create_shipment($shipment_data);
                
                // 记录API响应
                error_log('CZL Express: Create shipment response - ' . print_r($result, true));
                
                // 检查是否有订单号，即使API返回失败
                if (!empty($result['order_id'])) {
                    // 更新订单元数据
                    $order->update_meta_data('_czl_order_id', $result['order_id']);
                    
                    // 如果有跟踪号，也保存下来
                    if (!empty($result['tracking_number'])) {
                        $order->update_meta_data('_czl_tracking_number', $result['tracking_number']);
                    }
                    
                    // 保存偏远地区信息（如果有）
                    if (isset($result['is_remote'])) {
                        $order->update_meta_data('_czl_is_remote', $result['is_remote']);
                    }
                    if (isset($result['is_residential'])) {
                        $order->update_meta_data('_czl_is_residential', $result['is_residential']);
                    }
                    
                    // 如果API返回失败但有订单号，记录错误信息
                    if ($result['ack'] !== 'true') {
                        $error_msg = isset($result['message']) ? urldecode($result['message']) : 'Unknown error';
                        $order->add_order_note(sprintf(
                            __('Shipment partially created. Order ID: %s. Error: %s', 'woo-czl-express'),
                            $result['order_id'],
                            $error_msg
                        ));
                        $order->update_status('on-hold', __('Shipment needs manual processing', 'woo-czl-express'));
                    } else {
                        $order->update_status('in_transit', __('Package in transit', 'woo-czl-express'));
                    }
                    
                    $order->save();
                    
                    // 即使API返回失败，只要有订单号就返回结果
                    return $result;
                } else {
                    throw new Exception('创建运单失败：未获取到订单号');
                }
                
            } catch (Exception $e) {
                error_log('CZL Express Error: ' . $e->getMessage());
                throw $e;
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
                $items[] = array(
                    'invoice_amount' => $item->get_total(),
                    'invoice_pcs' => $item->get_quantity(),
                    'invoice_title' => $item->get_name(),
                    'invoice_weight' => $product ? ($product->get_weight() * $item->get_quantity()) : 0.1,
                    'item_id' => $product ? $product->get_id() : '',
                    'sku' => $product ? $product->get_sku() : ''
                );
            }
            return $items;
        }
        
        /**
         * 更新订单轨迹信息
         */
        public function update_tracking_info($order_id) {
            try {
                $order = wc_get_order($order_id);
                if (!$order) {
                    return;
                }
                
                $tracking_number = $order->get_meta('_czl_tracking_number');
                if (empty($tracking_number)) {
                    return;
                }
                
                // 获取轨迹信息
                $tracking_info = $this->api->get_tracking_info($tracking_number);
                if (empty($tracking_info)) {
                    return;
                }
                
                // 保存轨迹信息
                $order->update_meta_data('_czl_tracking_history', $tracking_info);
                
                // 根据最新轨迹更新订单状态
                $latest_status = end($tracking_info['trackDetails']);
                if ($latest_status) {
                    // 检查是否已签收
                    if (strpos($latest_status['track_content'], '已签收') !== false || 
                        strpos($latest_status['track_content'], 'Delivered') !== false) {
                        $order->update_status('delivered', __('包裹已送达', 'woo-czl-express'));
                    } else {
                        $order->update_status('shipping', __('包裹运输中', 'woo-czl-express'));
                    }
                }
                
                $order->save();
                
            } catch (Exception $e) {
                error_log('CZL Express Error: Failed to update tracking info - ' . $e->getMessage());
            }
        }
    }
} 