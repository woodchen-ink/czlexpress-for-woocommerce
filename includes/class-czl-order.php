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
                
                // 准备运单数据
                $shipment_data = array(
                    'buyerid' => $order->get_id(),
                    'consignee_address' => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
                    'consignee_city' => $order->get_shipping_city(),
                    'consignee_mobile' => $order->get_billing_phone(),
                    'consignee_name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                    'consignee_postcode' => $order->get_shipping_postcode(),
                    'consignee_state' => $order->get_shipping_state(),
                    'consignee_email' => $order->get_billing_email(),
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
                    
                    // 获取海关编码
                    $hs_code = $product->get_meta('_czl_hs_code');
                    if (empty($hs_code)) {
                        throw new Exception('产品 ' . $product->get_name() . ' 缺少海关编码');
                    }
                    
                    $shipment_data['orderInvoiceParam'][] = array(
                        'invoice_amount' => $item->get_total(),
                        'invoice_pcs' => $item->get_quantity(),
                        'invoice_title' => $item->get_name(),
                        'invoice_weight' => $product->get_weight() * $item->get_quantity(),
                        'item_id' => $product->get_id(),
                        'sku' => $product->get_sku(),
                        'hs_code' => $hs_code  // 添加海关编码
                    );
                }
                
                // 调用API创建运单
                $result = $this->api->create_shipment($shipment_data);
                
                if (!empty($result['tracking_number'])) {
                    // 更新订单元数据
                    $order->update_meta_data('_czl_tracking_number', $result['tracking_number']);
                    $order->update_meta_data('_czl_order_id', $result['order_id']);
                    $order->update_meta_data('_czl_reference_number', $result['reference_number']);
                    $order->update_meta_data('_czl_order_privatecode', $result['order_privatecode']);
                    $order->update_meta_data('_czl_order_transfercode', $result['order_transfercode']);
                    $order->update_meta_data('_czl_label_url', $result['label_url']);
                    
                    // 添加订单备注
                    $order->add_order_note(
                        sprintf(
                            __('CZL Express运单创建成功。
                            运单号: %s
                            订单号: %s
                            参考号: %s', 
                            'woo-czl-express'),
                            $result['tracking_number'],
                            $result['order_id'],
                            $result['reference_number']
                        ),
                        true
                    );
                    
                    // 更新订单状态
                    $order->update_status('shipping', __('运单已创建，包裹开始运输', 'woo-czl-express'));
                    
                    $order->save();
                }
                
                return $result;
                
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