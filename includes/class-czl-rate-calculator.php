<?php
class CZL_Rate_Calculator {
    private $api;
    private $product_groups;
    private $exchange_rate;
    
    public function __construct() {
        $this->api = new CZL_API();
        $this->product_groups = $this->get_product_groups();
        $this->exchange_rate = $this->get_exchange_rate();
    }
    
    /**
     * 获取产品分组配置
     */
    private function get_product_groups() {
        // 获取产品分组配置
        $groups = get_option('czl_product_groups', array());
        
        // 如果没有配置，使用默认分组
        if (empty($groups)) {
            $groups = array(
                'ups_blue' => array(
                    'enabled' => true,
                    'groupName' => 'UPS Expedited',
                    'prefixes' => array('UPS 蓝单')
                ),
                'ups_red' => array(
                    'enabled' => true,
                    'groupName' => 'UPS Saver',
                    'prefixes' => array('UPS 红单')
                ),
                'fedex_ie' => array(
                    'enabled' => true,
                    'groupName' => 'FEDEX IE',
                    'prefixes' => array('FEDEX IE')
                ),
                'fedex_ip' => array(
                    'enabled' => true,
                    'groupName' => 'FEDEX IP',
                    'prefixes' => array('FEDEX IP')
                ),
                'dhl' => array(
                    'enabled' => true,
                    'groupName' => 'DHL',
                    'prefixes' => array('DHL')
                ),
                'europe_normal' => array(
                    'enabled' => true,
                    'groupName' => 'European and American general package tax line',
                    'prefixes' => array(
                        '欧美经济专线(普货)',
                        '欧美标准专线(普货)',
                        '欧洲经济专线(普货)',
                        '欧洲标准专线(普货)'
                    )
                ),
                'europe_b' => array(
                    'enabled' => true,
                    'groupName' => 'European and American B-class tax line',
                    'prefixes' => array(
                        '欧美经济专线(B类)',
                        '欧美标准专线(B类)',
                        '欧洲经济专线(B类)',
                        '欧洲标准专线(B类)'
                    )
                ),
                'europe_battery' => array(
                    'enabled' => true,
                    'groupName' => 'European and American battery tax line',
                    'prefixes' => array(
                        '欧美经济专线(带电)',
                        '欧美标准专线(带电)',
                        '欧洲经济专线(带电)',
                        '欧洲标准专线(带电)'
                    )
                ),
                'dubai_dhl' => array(
                    'enabled' => true,
                    'groupName' => 'Dubai DHL',
                    'prefixes' => array('迪拜DHL')
                ),
                'dubai_ups' => array(
                    'enabled' => true,
                    'groupName' => 'Dubai UPS',
                    'prefixes' => array('迪拜UPS')
                ),
                'dubai_fedex' => array(
                    'enabled' => true,
                    'groupName' => 'Dubai FEDEX',
                    'prefixes' => array('迪拜FEDEX')
                ),
                'post' => array(
                    'enabled' => true,
                    'groupName' => 'Post',
                    'prefixes' => array('E特快', 'EMS')
                ),
                'czl_uae' => array(
                    'enabled' => true,
                    'groupName' => 'CZL UAE Line',
                    'prefixes' => array('CZL阿联酋')
                )
            );
            update_option('czl_product_groups', $groups);
        }
        
        // 只返回启用的分组
        return array_filter($groups, function($group) {
            return !empty($group['enabled']);
        });
    }
    
    /**
     * 获取汇率
     */
    private function get_exchange_rate() {
        // 获取WooCommerce货币设置
        $wc_currency = get_woocommerce_currency();
        
        if ($wc_currency === 'CNY') {
            return 1;
        }
        
        // 尝试从设置获取自定义汇率
        $custom_rate = get_option('czl_exchange_rate_' . $wc_currency);
        if (!empty($custom_rate)) {
            return floatval($custom_rate);
        }
        
        // 如果没有自定义汇率，使用WooCommerce的汇率转换
        if (function_exists('wc_get_price_in_currency')) {
            return wc_get_price_in_currency(1, $wc_currency);
        }
        
        // 如果都没有，返回默认汇率
        $default_rates = array(
            'USD' => 0.14,  // 1 CNY = 0.14 USD
            'EUR' => 0.13,  // 1 CNY = 0.13 EUR
            'GBP' => 0.11,  // 1 CNY = 0.11 GBP
            // 添加其他常用货币...
        );
        
        return isset($default_rates[$wc_currency]) ? $default_rates[$wc_currency] : 1;
    }
    
    /**
     * 转换货币
     */
    private function convert_currency($amount) {
        return $amount * $this->exchange_rate;
    }
    
    private function adjust_shipping_rate($rate) {
        $adjustment = get_option('czl_rate_adjustment', '');
        if (empty($adjustment)) {
            return $rate;
        }
        
        try {
            // 解析调整公式
            $formula = strtolower(str_replace(' ', '', $adjustment));
            $original_rate = $rate;
            
            // 处理百分比
            if (strpos($formula, '%') !== false) {
                preg_match('/(\d+)%/', $formula, $matches);
                if (!empty($matches[1])) {
                    $percentage = floatval($matches[1]) / 100;
                    $rate = $rate * (1 + $percentage);
                }
                $formula = preg_replace('/\d+%/', '', $formula);
            }
            
            // 处理固定金额
            if (preg_match('/([+-])(\d+)/', $formula, $matches)) {
                $operator = $matches[1];
                $amount = floatval($matches[2]);
                $rate = $operator === '+' ? $rate + $amount : $rate - $amount;
            }
            
            error_log(sprintf(
                'CZL Express: Adjusted rate from %f to %f using formula: %s',
                $original_rate,
                $rate,
                $adjustment
            ));
            
            return max(0, $rate);
        } catch (Exception $e) {
            error_log('CZL Express: Rate adjustment error - ' . $e->getMessage());
            return $rate;
        }
    }
    
    public function calculate_shipping_rate($package) {
        try {
            error_log('CZL Express: Calculating shipping rate for package: ' . print_r($package, true));
            
            // 基本验证
            if (empty($package['destination']['country'])) {
                error_log('CZL Express: Empty destination country');
                return array();
            }
            
            // 获取包裹信息
            $weight = 0;
            $length = 0;
            $width = 0;
            $height = 0;
            
            foreach ($package['contents'] as $item) {
                $product = $item['data'];
                $quantity = $item['quantity'];
                
                // 累加重量
                $item_weight = (float)$product->get_weight();
                if ($item_weight > 0) {
                    $weight += $item_weight * $quantity;
                }
                
                // 获取最大尺寸
                $item_length = (float)$product->get_length();
                $item_width = (float)$product->get_width();
                $item_height = (float)$product->get_height();
                
                $length = max($length, $item_length);
                $width = max($width, $item_width);
                $height = max($height, $item_height);
            }
            
            // 调用API获取运费
            $api_params = array(
                'weight' => $weight > 0 ? $weight : 0.1, // 默认最小重量0.1kg
                'country' => $package['destination']['country'],
                'postcode' => $package['destination']['postcode'],
                'length' => $length > 0 ? $length : 1,
                'width' => $width > 0 ? $width : 1,
                'height' => $height > 0 ? $height : 1,
                'cargoType' => 'P'
            );
            
            error_log('CZL Express: API params: ' . print_r($api_params, true));
            
            // 调用API获取运费
            $api_rates = $this->api->get_shipping_rate($api_params);
            error_log('CZL Express: API response: ' . print_r($api_rates, true));
            
            if (empty($api_rates)) {
                return array();
            }
            
            // 按产品分组处理运费
            $grouped_rates = array();
            $all_rates = array(); // 存储所有线路
            
            foreach ($api_rates as $rate) {
                $product_name = $rate['product_name'];
                $group_found = false;
                
                // 调整运费
                $adjusted_amount = $this->adjust_shipping_rate(floatval($rate['total_amount']));
                
                // 添加单独的线路选项
                $rate_id = sanitize_title($product_name);
                $all_rates[$rate_id] = array(
                    'id' => 'czl_express_' . $rate_id,
                    'label' => sprintf(
                        '%s (%s)',
                        $product_name,
                        $this->translate_delivery_time($rate['product_aging'])
                    ),
                    'cost' => $this->convert_currency($adjusted_amount),
                    'calc_tax' => 'per_order',
                    'meta_data' => array(
                        'product_id' => $rate['product_id'],
                        'delivery_time' => $rate['product_aging'],
                        'is_group' => false,
                        'original_amount' => $rate['total_amount'],
                        'adjusted_amount' => $adjusted_amount
                    )
                );
                
                // 查找匹配的分组
                foreach ($this->product_groups as $group_key => $group) {
                    foreach ($group['prefixes'] as $prefix) {
                        if (strpos($product_name, $prefix) === 0) {
                            $group_id = sanitize_title($group['groupName']);
                            if (!isset($grouped_rates[$group_id]) || 
                                $rate['total_amount'] < $grouped_rates[$group_id]['meta_data']['original_amount']) {
                                $grouped_rates[$group_id] = array(
                                    'id' => 'czl_express_group_' . $group_id,
                                    'label' => sprintf(
                                        '%s (%s)',
                                        $group['groupName'],
                                        $this->translate_delivery_time($rate['product_aging'])
                                    ),
                                    'cost' => $this->convert_currency(floatval($rate['total_amount'])),
                                    'calc_tax' => 'per_order',
                                    'meta_data' => array(
                                        'product_id' => $rate['product_id'],
                                        'delivery_time' => $rate['product_aging'],
                                        'original_name' => $product_name,
                                        'is_group' => true,
                                        'group_name' => $group['groupName'],
                                        'original_amount' => $rate['total_amount']
                                    )
                                );
                            }
                            $group_found = true;
                            break;
                        }
                    }
                    if ($group_found) break;
                }
            }
            
            // 根据设置决定返回分组还是所有线路
            $show_all_rates = get_option('czl_show_all_rates', 'no');
            if ($show_all_rates === 'yes') {
                return array_values($all_rates);
            } else {
                return array_values($grouped_rates);
            }
            
        } catch (Exception $e) {
            error_log('CZL Express Error: ' . $e->getMessage());
            wc_add_notice($e->getMessage(), 'error');
            return array();
        }
    }
    
    private function translate_delivery_time($delivery_time) {
        // 获取当前语言环境
        $locale = determine_locale();
        
        // 如果是中文环境，保持原样
        if (strpos($locale, 'zh_') === 0) {
            return $delivery_time;
        }
        
        // 匹配中文时效格式
        if (preg_match('/(\d+)-(\d+)个工作日/', $delivery_time, $matches)) {
            return sprintf('%d-%d working days', $matches[1], $matches[2]);
        }
        
        if (preg_match('/(\d+)个工作日/', $delivery_time, $matches)) {
            return sprintf('%d working days', $matches[1]);
        }
        
        // 匹配"预计XX天左右"的格式
        if (preg_match('/预计(\d+)天左右/', $delivery_time, $matches)) {
            return sprintf('About %d days', $matches[1]);
        }
        
        // 匹配"预计XX-XX天"的格式
        if (preg_match('/预计(\d+)-(\d+)天/', $delivery_time, $matches)) {
            return sprintf('About %d-%d days', $matches[1], $matches[2]);
        }
        
        // 其他常见格式的翻译
        $translations = array(
            '当天送达' => 'Same day delivery',
            '次日送达' => 'Next day delivery',
            '隔日送达' => 'Second day delivery',
            '工作日' => 'working days',
            '左右' => 'about'
        );
        
        foreach ($translations as $cn => $en) {
            if (strpos($delivery_time, $cn) !== false) {
                return $en;
            }
        }
        
        // 如果没有匹配到任何格式，返回原始值
        return $delivery_time;
    }
} 