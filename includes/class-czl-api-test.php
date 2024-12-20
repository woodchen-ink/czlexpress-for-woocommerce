<?php
class CZL_API_Test {
    private $api;
    
    public function __construct() {
        $this->api = new CZL_API();
    }
    
    /**
     * 测试API连接
     */
    public function test_connection() {
        try {
            // 尝试获取token
            $this->api->get_token();
            return array(
                'success' => true,
                'message' => __('Successfully connected to CZL Express API', 'czlexpress-for-woocommerce')
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * 测试运费查询
     */
    public function test_shipping_rate() {
        $test_package = array(
            'destination' => array(
                'country' => 'US',
                'state' => 'CA',
                'city' => 'Los Angeles',
                'address' => '123 Test St',
                'postcode' => '90001'
            ),
            'contents' => array(
                array(
                    'data' => new WC_Product_Simple(array(
                        'weight' => 1,
                        'length' => 10,
                        'width' => 10,
                        'height' => 10,
                        'price' => 100
                    )),
                    'quantity' => 1
                )
            )
        );
        
        try {
            $rates = $this->api->get_shipping_rate($test_package);
            return array(
                'success' => true,
                'data' => $rates
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
} 