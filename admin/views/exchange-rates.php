<?php
defined('ABSPATH') || exit;

$current_currency = get_woocommerce_currency();
$supported_currencies = get_woocommerce_currencies();
$exchange_rates = array();

// 获取所有已保存的汇率
foreach ($supported_currencies as $code => $name) {
    if ($code !== 'CNY') {
        $rate = get_option('czl_exchange_rate_' . $code, '');
        if ($rate !== '') {
            $exchange_rates[$code] = $rate;
        }
    }
}
?>

<div class="wrap czl-exchange-rates-page">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="czl-page-description">
        <p>
            <?php _e('在这里设置人民币(CNY)与其他货币的汇率。运费将根据这些汇率自动转换。', 'woo-czl-express'); ?>
            <?php printf(__('当前商店使用的货币是: %s', 'woo-czl-express'), '<strong>' . $current_currency . '</strong>'); ?>
        </p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('czl_save_exchange_rates', 'czl_exchange_rates_nonce'); ?>
        
        <div class="czl-table-container">
            <table class="widefat czl-exchange-rates" id="czl-exchange-rates">
                <thead>
                    <tr>
                        <th class="column-currency"><?php _e('货币', 'woo-czl-express'); ?></th>
                        <th class="column-rate"><?php _e('汇率 (1 CNY =)', 'woo-czl-express'); ?></th>
                        <th class="column-actions"><?php _e('操作', 'woo-czl-express'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($exchange_rates)) : ?>
                        <?php foreach ($exchange_rates as $code => $rate): ?>
                        <tr>
                            <td class="column-currency">
                                <select name="rates[<?php echo esc_attr($code); ?>][currency]" class="currency-select">
                                    <?php
                                    foreach ($supported_currencies as $currency_code => $currency_name) {
                                        if ($currency_code !== 'CNY') {
                                            printf(
                                                '<option value="%s" %s>%s (%s)</option>',
                                                esc_attr($currency_code),
                                                selected($currency_code, $code, false),
                                                esc_html($currency_name),
                                                esc_html($currency_code)
                                            );
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                            <td class="column-rate">
                                <input type="number" name="rates[<?php echo esc_attr($code); ?>][rate]" 
                                       value="<?php echo esc_attr($rate); ?>" step="0.000001" min="0" class="regular-text">
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button remove-rate" title="<?php esc_attr_e('删除此汇率', 'woo-czl-express'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">
                            <button type="button" class="button add-rate">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php _e('添加汇率', 'woo-czl-express'); ?>
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php submit_button(__('保存汇率设置', 'woo-czl-express')); ?>
    </form>
</div>

<script type="text/template" id="rate-row-template">
    <tr>
        <td class="column-currency">
            <select name="rates[{{index}}][currency]" class="currency-select">
                <?php
                foreach ($supported_currencies as $code => $name) {
                    if ($code !== 'CNY') {
                        printf(
                            '<option value="%s">%s (%s)</option>',
                            esc_attr($code),
                            esc_html($name),
                            esc_html($code)
                        );
                    }
                }
                ?>
            </select>
        </td>
        <td class="column-rate">
            <input type="number" name="rates[{{index}}][rate]" value="" step="0.000001" min="0" class="regular-text">
        </td>
        <td class="column-actions">
            <button type="button" class="button remove-rate" title="<?php esc_attr_e('删除此汇率', 'woo-czl-express'); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </td>
    </tr>
</script>

<style>
.czl-exchange-rates-page {
    max-width: 800px;
    margin: 20px auto;
}

.czl-page-description {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border-left: 4px solid #2271b1;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.czl-table-container {
    margin: 20px 0;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.czl-exchange-rates {
    border: none;
}

.czl-exchange-rates th {
    padding: 15px;
    font-weight: 600;
}

.czl-exchange-rates td {
    padding: 15px;
}

.column-currency {
    width: 40%;
}

.column-rate {
    width: 40%;
}

.column-actions {
    width: 20%;
    text-align: center;
}

.currency-select {
    width: 100%;
}

.remove-rate .dashicons {
    margin-top: 3px;
    color: #b32d2e;
}

.add-rate .dashicons {
    margin-top: 3px;
    margin-right: 5px;
}
</style>

<script>
jQuery(function($) {
    var $table = $('#czl-exchange-rates');
    var template = $('#rate-row-template').html();
    var rateIndex = $table.find('tbody tr').length;
    
    $('.add-rate').on('click', function() {
        var newRow = template.replace(/{{index}}/g, rateIndex++);
        $table.find('tbody').append(newRow);
    });
    
    $table.on('click', '.remove-rate', function() {
        var $row = $(this).closest('tr');
        $row.fadeOut(300, function() {
            $row.remove();
        });
    });
});
</script> 