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
                'ups_expedited' => array(
                    'enabled' => true,
                    'groupName' => 'UPS Expedited',
                    'prefixes' => array('UPS 蓝单')
                ),
                'ups_saver' => array(
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
                    'groupName' => __('Customs duty line', 'czlexpress-for-woocommerce'),
                    'prefixes' => array(
                        __('欧美经济专线(普货)', 'czlexpress-for-woocommerce'),
                        __('欧洲经济专线(普货)', 'czlexpress-for-woocommerce')
                    )
                ),
                'europe_fast' => array(
                    'enabled' => true,
                    'groupName' => __('Fast customs duty line', 'czlexpress-for-woocommerce'),
                    'prefixes' => array(
                        __('欧美标准专线(普货)', 'czlexpress-for-woocommerce'),
                        __('欧洲标准专线(普货)', 'czlexpress-for-woocommerce')
                    )
                ),
                'ems' => array(
                    'enabled' => true,
                    'groupName' => __('EMS', 'czlexpress-for-woocommerce'),
                    'prefixes' => array(__('EMS', 'czlexpress-for-woocommerce'))
                ),
                'czl_uae' => array(
                    'enabled' => true,
                    'groupName' => 'CZL UAE Line',
                    'prefixes' => array('CZL阿联酋经济专线')
                ),
                'czl_uae_fast' => array(
                    'enabled' => true,
                    'groupName' => 'CZL UAE Fast Line',
                    'prefixes' => array('CZL阿联酋特快专线')
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
    
    /**
     * 计算单个产品的计费重
     * @param WC_Product $product 产品对象
     * @param int $quantity 数量
     * @return float 计费重
     */
    private function calculate_chargeable_weight($product, $quantity) {
        // 获取产品尺寸和重量
        $length = (float)$product->get_length();
        $width = (float)$product->get_width();
        $height = (float)$product->get_height();
        $actual_weight = (float)$product->get_weight();

        // 确保所有值都大于0
        $length = max($length, 1);
        $width = max($width, 1);
        $height = max($height, 1);
        $actual_weight = max($actual_weight, 0.1);

        // 计算体积重 (长*宽*高/5000)
        $volumetric_weight = ($length * $width * $height) / 5000;

        // 取实重和体积重中的较大值
        $chargeable_weight = max($actual_weight, $volumetric_weight);

        // 乘以数量
        return $chargeable_weight * $quantity;
    }

    public function calculate_shipping_rate($package) {
        try {
            error_log('CZL Express: Calculating shipping rate for package: ' . print_r($package, true));
            
            // 基本验证
            if (empty($package['destination']['country'])) {
                error_log('CZL Express: Empty destination country');
                return array();
            }
            
            // 计算总计费重
            $total_chargeable_weight = 0;
            
            foreach ($package['contents'] as $item) {
                $product = $item['data'];
                $quantity = $item['quantity'];
                
                // 累加计费重
                $total_chargeable_weight += $this->calculate_chargeable_weight($product, $quantity);
            }
            
            // 调用API获取运费
            $api_params = array(
                'weight' => $total_chargeable_weight,  // 使用计算出的总计费重
                'country' => $package['destination']['country'],
                'postcode' => $package['destination']['postcode'],
                'cargoType' => 'P',
                'length' => 10,  // 添加固定尺寸
                'width' => 10,   // 添加固定尺寸
                'height' => 10   // 添加固定尺寸
            );
            
            error_log('CZL Express: API params with chargeable weight: ' . print_r($api_params, true));
            
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
                $product_name = isset($rate['product_name']) ? $rate['product_name'] : '';
                $product_id = isset($rate['product_id']) ? $rate['product_id'] : '';
                $delivery_time = isset($rate['product_aging']) ? $rate['product_aging'] : '';
                $amount = isset($rate['total_amount']) ? floatval($rate['total_amount']) : 0;
                
                // 调整运费
                $adjusted_amount = $this->adjust_shipping_rate($amount);
                
                // 查找匹配的分组
                foreach ($this->product_groups as $group_key => $group) {
                    foreach ($group['prefixes'] as $prefix) {
                        if (strpos($product_name, $prefix) === 0) {
                            $group_id = sanitize_title($group['groupName']);
                            if (!isset($grouped_rates[$group_id]) || 
                                !isset($grouped_rates[$group_id]['meta_data']) ||
                                !isset($grouped_rates[$group_id]['meta_data']['original_amount']) ||
                                $amount < $grouped_rates[$group_id]['meta_data']['original_amount']) {
                                
                                // 构建分组运费数据
                                $grouped_rates[$group_id] = array(
                                    'product_id' => $product_id,
                                    'method_title' => sprintf(
                                        '%s (%s)',
                                        $group['groupName'],
                                        $this->translate_delivery_time($delivery_time)
                                    ),
                                    'cost' => $this->convert_currency($adjusted_amount),
                                    'delivery_time' => $delivery_time,
                                    'original_name' => $product_name,
                                    'is_group' => true,
                                    'group_name' => $group['groupName'],
                                    'original_amount' => $amount
                                );
                            }
                            break;
                        }
                    }
                }
                
                // 构建单独线路运费数据
                $all_rates[] = array(
                    'product_id' => $product_id,
                    'method_title' => sprintf(
                        '%s (%s)',
                        $product_name,
                        $this->translate_delivery_time($delivery_time)
                    ),
                    'cost' => $this->convert_currency($adjusted_amount),
                    'delivery_time' => $delivery_time,
                    'original_name' => $product_name,
                    'is_group' => false,
                    'group_name' => '',
                    'original_amount' => $amount
                );
            }
            
            // 根据设置决定返回分组还是所有线路
            $show_all_rates = get_option('czl_show_all_rates', 'no');
            return $show_all_rates === 'yes' ? array_values($all_rates) : array_values($grouped_rates);
            
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
        
        // 匹配"XX个工作日"格式
        if (preg_match('/(\d+)个工作日/', $delivery_time, $matches)) {
            return sprintf('%d working days', $matches[1]);
        }
        
        // 匹配"预计XX天左右"格式
        if (preg_match('/预计(\d+)天左右/', $delivery_time, $matches)) {
            return sprintf('About %d days', $matches[1]);
        }
        
        // 匹配"预计XX-XX天"格式
        if (preg_match('/预计(\d+)-(\d+)天/', $delivery_time, $matches)) {
            return sprintf('About %d-%d days', $matches[1], $matches[2]);
        }
        
        // 匹配"XX-XX天"格式
        if (preg_match('/(\d+)-(\d+)天/', $delivery_time, $matches)) {
            return sprintf('%d-%d days', $matches[1], $matches[2]);
        }
        
        // 匹配"XX天"格式
        if (preg_match('/(\d+)天/', $delivery_time, $matches)) {
            return sprintf('%d days', $matches[1]);
        }
        
        // 其他常见格式的翻译
        $translations = array(
            '当天送达' => 'Same day delivery',
            '次日送达' => 'Next day delivery',
            '隔日送达' => 'Second day delivery',
            '工作日' => 'working days',
            '左右' => 'about',
            '预计' => 'About',
            '快速' => 'Express',
            '标准' => 'Standard'
        );
        
        $translated = $delivery_time;
        foreach ($translations as $cn => $en) {
            $translated = str_replace($cn, $en, $translated);
        }
        
        // 如果经过翻译后与原文相同，说明没有匹配到任何规则
        if ($translated === $delivery_time) {
            // 添加调试日志
            error_log('CZL Express: Unable to translate delivery time - ' . $delivery_time);
            return $delivery_time;
        }
        
        return $translated;
    }
} 