<?php
/**
 * CZL日志处理类
 */
class CZL_Logger {
    /**
     * 记录日志
     *
     * @param string $message 日志消息
     * @param mixed $data 额外数据
     * @param string $level 日志级别 (error, warning, info, debug)
     */
    public static function log($message, $data = null, $level = 'info') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $logger = wc_get_logger();
        if (!$logger) {
            return;
        }

        $context = array('source' => 'czlexpress-for-woocommerce');
        
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $message .= ' Data: ' . wp_json_encode($data);
            } else {
                $message .= ' Data: ' . strval($data);
            }
        }

        switch ($level) {
            case 'error':
                $logger->error($message, $context);
                break;
            case 'warning':
                $logger->warning($message, $context);
                break;
            case 'info':
                $logger->info($message, $context);
                break;
            case 'debug':
                $logger->debug($message, $context);
                break;
        }
    }

    /**
     * 记录错误日志
     */
    public static function error($message, $data = null) {
        self::log($message, $data, 'error');
    }

    /**
     * 记录警告日志
     */
    public static function warning($message, $data = null) {
        self::log($message, $data, 'warning');
    }

    /**
     * 记录信息日志
     */
    public static function info($message, $data = null) {
        self::log($message, $data, 'info');
    }

    /**
     * 记录调试日志
     */
    public static function debug($message, $data = null) {
        self::log($message, $data, 'debug');
    }
} 