<?php
class CZL_API {
    private $api_url;
    private $username;
    private $password;
    private $token;
    private $token_expires;
    private $country_mapping;
    private $customer_id;
    private $customer_userid;
    
    public function __construct() {
        $this->api_url = get_option('czl_api_url', '');
        $this->username = get_option('czl_username', '');
        $this->password = get_option('czl_password', '');
        $this->token = get_transient('czl_api_token');
        $this->token_expires = get_transient('czl_api_token_expires');
        $this->init_country_mapping();
    }
    
    /**
     * 初始化国家代码映射
     */
    private function init_country_mapping() {
        // 从API获取国家列表并缓存
        $cached_mapping = get_transient('czl_country_mapping');
        if ($cached_mapping !== false) {
            $this->country_mapping = $cached_mapping;
            return;
        }
        
        try {
            $response = wp_remote_get('https://tms-api-go.czl.net/api/countries', array(
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($data['data'])) {
                $mapping = array();
                foreach ($data['data'] as $country) {
                    $mapping[$country['ename']] = $country['code'];
                }
                $this->country_mapping = $mapping;
                set_transient('czl_country_mapping', $mapping, DAY_IN_SECONDS);
                error_log('CZL Express: Country mapping updated');
            }
        } catch (Exception $e) {
            error_log('CZL Express Error: Failed to get country mapping - ' . $e->getMessage());
            $this->country_mapping = array();
        }
    }
    
    /**
     * 转换国家代码
     */
    private function convert_country_code($wc_country) {
        error_log('CZL Express: Converting country code ' . $wc_country);
        
        // 获取WooCommerce国家名称
        $countries = WC()->countries->get_countries();
        $country_name = isset($countries[$wc_country]) ? $countries[$wc_country] : '';
        
        // 在映射中查找
        foreach ($this->country_mapping as $name => $code) {
            if (stripos($name, $country_name) !== false || stripos($country_name, $name) !== false) {
                error_log('CZL Express: Found country mapping ' . $wc_country . ' => ' . $code);
                return $code;
            }
        }
        
        // 如果没找到映射，返回原始代码
        error_log('CZL Express: No mapping found for ' . $wc_country . ', using original code');
        return $wc_country;
    }
    
    /**
     * 获取API认证Token
     */
    private function get_token() {
        if ($this->token && time() < $this->token_expires) {
            return $this->token;
        }
        
        $response = wp_remote_post($this->api_url . '/auth/login', array(
            'body' => array(
                'username' => $this->username,
                'password' => md5($this->password)  // CZL API需要MD5加密的密码
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['success'])) {
            throw new Exception(!empty($body['message']) ? $body['message'] : '认证失败');
        }
        
        $this->token = $body['data']['token'];
        $this->token_expires = time() + 7200; // CZL token有效期通常是2小时
        
        set_transient('czl_api_token', $this->token, 7200);
        set_transient('czl_api_token_expires', $this->token_expires, 7200);
        
        return $this->token;
    }
    
    /**
     * 发送API请求
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => $this->get_token(),
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );
        
        if ($data !== null) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($this->api_url . $endpoint, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['success'])) {
            throw new Exception(!empty($body['message']) ? $body['message'] : '请求失败');
        }
        
        return $body['data'];
    }
    
    /**
     * 获取运费报价
     */
    public function get_shipping_rate($params) {
        try {
            $api_url = 'https://tms.czl.net/defaultPriceSearchJson.htm';
            
            // 转换国家代码
            $country_code = $this->convert_country_code($params['country']);
            
            // 构建请求参数
            $query = array(
                'weight' => $params['weight'],
                'country' => $country_code,
                'cargoType' => $params['cargoType'],
                'length' => $params['length'],
                'width' => $params['width'],
                'height' => $params['height'],
                'postcode' => $params['postcode']
            );
            
            error_log('CZL Express: Shipping rate request - ' . print_r($query, true));
            
            // 发送请求
            $response = wp_remote_post($api_url . '?' . http_build_query($query), array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password)
                ),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $body = wp_remote_retrieve_body($response);
            error_log('CZL Express: API raw response - ' . $body);
            
            $data = json_decode($body, true);
            if (empty($data)) {
                throw new Exception('Empty API response');
            }
            
            // 检查响应格式
            if (!is_array($data)) {
                throw new Exception('Invalid API response format');
            }
            
            return $data;
            
        } catch (Exception $e) {
            error_log('CZL Express API Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 格式化运费报价结果
     */
    private function format_shipping_rates($rates) {
        $formatted_rates = array();
        foreach ($rates as $rate) {
            $formatted_rates[] = array(
                'method_id' => 'czl_express_' . sanitize_title($rate['product_id']),
                'method_title' => $rate['product_name'],
                'method_name' => $rate['product_name'],
                'cost' => floatval($rate['total_amount']),
                'delivery_time' => $rate['product_aging'],
                'product_id' => $rate['product_id'],
                'product_note' => $rate['product_note']
            );
        }
        return $formatted_rates;
    }
    
    /**
     * 创建运单
     */
    public function create_order($order_data) {
        try {
            // 添加请求前的日志
            error_log('CZL Express: Creating order with data - ' . print_r($order_data, true));
            
            $response = wp_remote_post('https://tms.czl.net/createOrderApi.htm', array(
                'body' => array(
                    'Param' => json_encode($order_data)
                ),
                'timeout' => 30
            ));
            
            // 添加响应日志
            error_log('CZL Express: Raw API response - ' . print_r($response, true));
            
            if (is_wp_error($response)) {
                throw new Exception('API请求失败: ' . $response->get_error_message());
            }
            
            $result = json_decode(wp_remote_retrieve_body($response), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON解析失败: ' . json_last_error_msg());
            }
            
            if (empty($result['ack']) || $result['ack'] !== 'true') {
                throw new Exception(!empty($result['message']) ? urldecode($result['message']) : '未知错误');
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('CZL Express Error: Create order failed - ' . $e->getMessage());
            error_log('CZL Express Error Stack Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * 获取运单跟踪信息
     */
    public function get_tracking($tracking_number) {
        try {
            $response = wp_remote_post('https://tms.czl.net/selectTrack.htm', array(
                'body' => array(
                    'documentCode' => $tracking_number
                ),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $result = json_decode(wp_remote_retrieve_body($response), true);
            
            if (empty($result[0]['ack']) || $result[0]['ack'] !== 'true') {
                throw new Exception('获取跟踪信息失败');
            }
            
            return $result[0]['data'][0];
            
        } catch (Exception $e) {
            error_log('CZL Express API Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 获取运单标签
     */
    public function get_label($order_id) {
        $url = sprintf(
            'https://tms-label.czl.net/order/FastRpt/PDF_NEW.aspx?Format=lbl_sub一票多件161810499441.frx&PrintType=1&order_id=%s',
            $order_id
        );
        return $url;
    }
    
    /**
     * 取消运单
     */
    public function cancel_order($order_number) {
        return $this->request('/shipping/cancel', 'POST', array(
            'order_number' => $order_number
        ));
    }
    
    /**
     * 测试认证
     */
    public function test_auth() {
        try {
            $response = wp_remote_post('https://tms.czl.net/selectAuth.htm', array(
                'body' => array(
                    'username' => $this->username,
                    'password' => $this->password
                ),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $result = json_decode(wp_remote_retrieve_body($response), true);
            
            if (empty($result['success'])) {
                throw new Exception('认证失败');
            }
            
            return array(
                'customer_id' => $result['customer_id'],
                'customer_userid' => $result['customer_userid']
            );
            
        } catch (Exception $e) {
            error_log('CZL Express API Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 获取支持的国家列表
     */
    public function get_countries() {
        try {
            $response = wp_remote_get('https://tms-api-go.czl.net/api/countries', array(
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $result = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($result['code'] !== 200) {
                throw new Exception('获取国家列表失败');
            }
            
            return $result['data'];
            
        } catch (Exception $e) {
            error_log('CZL Express API Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function ensure_logged_in() {
        try {
            error_log('CZL Express: Starting authentication');
            
            $auth_url = 'https://tms.czl.net/selectAuth.htm';
            $auth_data = array(
                'username' => $this->username,
                'password' => $this->password
            );
            
            error_log('CZL Express: Auth request data - ' . print_r($auth_data, true));
            
            $ch = curl_init($auth_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($auth_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept-Language: zh-cn',
                'Connection: Keep-Alive',
                'Cache-Control: no-cache'
            ));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            error_log('CZL Express: Auth raw response - ' . $response);
            
            if (curl_errno($ch)) {
                throw new Exception('认证请求失败');
            }
            
            curl_close($ch);
            
            // 解析响应
            $result = json_decode(str_replace("'", '"', $response), true);
            error_log('CZL Express: Auth decoded response - ' . print_r($result, true));
            
            if (empty($result) || !isset($result['customer_id'])) {
                throw new Exception('认证失败');
            }
            
            // 保存认证信息
            $this->customer_id = $result['customer_id'];
            $this->customer_userid = $result['customer_userid'];
            
        } catch (Exception $e) {
            error_log('CZL Express Error: Authentication failed - ' . $e->getMessage());
            throw new Exception('认证失败，请联系CZL Express');
        }
    }
    
    private function make_request($url, $data = array(), $method = 'POST') {
        try {
            $ch = curl_init();
            
            $curl_options = array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_TIMEOUT => 60,        // 增加超时时间到60秒
                CURLOPT_CONNECTTIMEOUT => 30, // 连接超时时间30秒
                CURLOPT_VERBOSE => true,      // 启用详细日志
                CURLOPT_HTTPHEADER => array(
                    'Accept: */*',
                    'Accept-Language: zh-cn',
                    'Cache-Control: no-cache',
                    'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
                )
            );
            
            if ($method === 'POST') {
                $curl_options[CURLOPT_POST] = true;
                if (!empty($data)) {
                    // 确保数据正确编码
                    $post_data = is_array($data) ? http_build_query($data) : $data;
                    $curl_options[CURLOPT_POSTFIELDS] = $post_data;
                }
            }
            
            curl_setopt_array($ch, $curl_options);
            
            // 捕获详细日志
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
            
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            
            // 记录详细日志
            rewind($verbose);
            $verbose_log = stream_get_contents($verbose);
            error_log('CZL Express: Curl verbose log - ' . $verbose_log);
            fclose($verbose);
            
            if ($errno) {
                error_log('CZL Express: Curl error - ' . $error);
                throw new Exception('请求失败: ' . $error);
            }
            
            if ($response === false) {
                throw new Exception('请求失败');
            }
            
            error_log('CZL Express: Raw response - ' . $response);
            
            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('CZL Express: JSON decode error - ' . json_last_error_msg());
                throw new Exception('响应数据格式错误');
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('CZL Express Error: Request failed - ' . $e->getMessage());
            throw $e;
        } finally {
            if (isset($ch)) {
                curl_close($ch);
            }
        }
    }
    
    /**
     * 认证方法
     */
    public function authenticate() {
        try {
            error_log('CZL Express: Starting authentication');
            
            $auth_url = 'https://tms.czl.net/selectAuth.htm';
            $auth_data = array(
                'username' => $this->username,
                'password' => $this->password
            );
            
            error_log('CZL Express: Auth request data - ' . print_r($auth_data, true));
            
            $ch = curl_init($auth_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($auth_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept-Language: zh-cn',
                'Connection: Keep-Alive',
                'Cache-Control: no-cache'
            ));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            error_log('CZL Express: Auth raw response - ' . $response);
            
            if (curl_errno($ch)) {
                throw new Exception('认证请求失败: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
            // 解析响应
            $result = json_decode(str_replace("'", '"', $response), true);
            error_log('CZL Express: Auth decoded response - ' . print_r($result, true));
            
            if (empty($result) || !isset($result['customer_id'])) {
                throw new Exception('认证失败: 无效的响应数据');
            }
            
            // 返回认证结果
            return array(
                'ack' => true,
                'customer_id' => $result['customer_id'],
                'customer_userid' => $result['customer_userid'],
                'message' => ''
            );
            
        } catch (Exception $e) {
            error_log('CZL Express Error: Authentication failed - ' . $e->getMessage());
            return array(
                'ack' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    public function create_shipment($params) {
        try {
            error_log('CZL Express: Starting create shipment');
            
            // 先进行认证
            $auth_result = $this->authenticate();
            if (!$auth_result['ack']) {
                throw new Exception('认证失败: ' . $auth_result['message']);
            }
            
            // 添加认证信息到参数
            $params['customer_id'] = $auth_result['customer_id'];
            $params['customer_userid'] = $auth_result['customer_userid'];
            
            // 准备请求数据
            $request_data = array(
                'param' => json_encode($params)
            );
            
            error_log('CZL Express: Create shipment request data - ' . print_r($request_data, true));
            
            // 发送请求
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://tms.czl.net/createOrderApi.htm',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query($request_data),
                CURLOPT_HTTPHEADER => array(
                    'Accept: */*',
                    'Accept-Language: zh-cn',
                    'Cache-Control: no-cache',
                    'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
                ),
                CURLOPT_VERBOSE => true,
                CURLOPT_STDERR => fopen('php://temp', 'w+')
            ));
            
            // 获取详细的CURL日志
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($curl, CURLOPT_STDERR, $verbose);
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            
            // 记录CURL详细日志
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            error_log('CZL Express: Curl verbose log - ' . $verboseLog);
            
            if ($err) {
                throw new Exception('CURL错误: ' . $err);
            }
            
            error_log('CZL Express: Raw response - ' . $response);
            
            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON解析错误: ' . json_last_error_msg());
            }
            
            error_log('CZL Express: Create shipment response - ' . print_r($result, true));
            
            // 检查API错误信息
            if (isset($result['message']) && !empty($result['message'])) {
                error_log('CZL Express: API error message - ' . $result['message']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('CZL Express API Error: ' . $e->getMessage());
            throw $e;
        } finally {
            if (isset($curl)) {
                curl_close($curl);
            }
        }
    }
    
    /**
     * 获取跟踪单号
     * 
     * @param string $czl_order_id CZL订单号
     * @return array 包含跟踪单号的响应数据
     * @throws Exception 如果请求失败
     */
    public function get_tracking_number($czl_order_id) {
        $endpoint = '/api/tracking/number';
        
        $params = array(
            'order_id' => $czl_order_id
        );
        
        try {
            $response = $this->send_request('GET', $endpoint, $params);
            
            if (empty($response['tracking_number'])) {
                throw new Exception('未找到跟踪单号');
            }
            
            return array(
                'tracking_number' => $response['tracking_number'],
                'status' => isset($response['status']) ? $response['status'] : null
            );
            
        } catch (Exception $e) {
            throw new Exception('获取跟踪单号失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取运单跟踪信息
     */
    public function get_tracking_info($tracking_number) {
        try {
            $response = wp_remote_post('https://tms.czl.net/selectTrack.htm', array(
                'body' => array(
                    'documentCode' => $tracking_number
                ),
                'timeout' => 30,
                'headers' => array(
                    'Accept-Encoding' => '',
                    'Accept-Language' => 'zh-CN,zh;q=0.9'
                )
            ));
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $body = wp_remote_retrieve_body($response);
            error_log('CZL Express: Track raw response - ' . $body);
            
            // 处理中文编码问题
            $body = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $body);
            $result = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('CZL Express: JSON decode error - ' . json_last_error_msg());
                throw new Exception('JSON解析失败: ' . json_last_error_msg());
            }
            
            error_log('CZL Express: Track decoded response - ' . print_r($result, true));
            
            if (empty($result) || !isset($result[0])) {
                throw new Exception('无效的API响应');
            }
            
            if (empty($result[0]['ack']) || $result[0]['ack'] !== 'true') {
                throw new Exception('获取跟踪信息失败: ' . ($result[0]['message'] ?? '未知错误'));
            }
            
            if (empty($result[0]['data']) || !is_array($result[0]['data'])) {
                return array(
                    'success' => true,
                    'data' => array(
                        'status' => 'pending',
                        'track_content' => '暂无轨迹信息',
                        'track_time' => current_time('mysql'),
                        'track_location' => ''
                    ),
                    'message' => ''
                );
            }
            
            // 获取最新的轨迹状态
            $latest_track = $result[0]['data'][0];
            $track_details = $latest_track['trackDetails'] ?? array();
            $latest_detail = !empty($track_details) ? $track_details[0] : array();
            
            // 根据轨迹内容判断状态
            $status = 'in_transit'; // 默认状态
            $track_content = strtolower($latest_detail['track_content'] ?? '');
            
            if (strpos($track_content, 'delivered') !== false || strpos($track_content, 'signed') !== false) {
                $status = 'delivered';
            } elseif (strpos($track_content, 'pickup') !== false || strpos($track_content, 'picked up') !== false) {
                $status = 'picked_up';
            }
            
            return array(
                'success' => true,
                'data' => array(
                    'status' => $status,
                    'track_content' => $latest_detail['track_content'] ?? '',
                    'track_time' => $latest_detail['track_date'] ?? current_time('mysql'),
                    'track_location' => $latest_detail['track_location'] ?? ''
                ),
                'message' => ''
            );
            
        } catch (Exception $e) {
            error_log('CZL Express API Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'data' => null,
                'message' => $e->getMessage()
            );
        }
    }
} 