<?php
/**
 * 运费方式分组显示模板
 */
defined('ABSPATH') || exit;
?>

<tr class="shipping-method-group">
    <td>
        <input type="radio" 
               name="shipping_method[<?php echo esc_attr($index); ?>]" 
               data-index="<?php echo esc_attr($index); ?>" 
               value="<?php echo esc_attr($rate->id); ?>" 
               class="shipping_method" 
               <?php checked($rate->id, $chosen_method); ?> />
    </td>
    <td>
        <label for="shipping_method_<?php echo esc_attr($index); ?>">
            <?php echo wp_kses_post($rate->label); ?>
            <?php if (!empty($rate->has_sub_methods)): ?>
                <span class="toggle-sub-methods dashicons dashicons-arrow-down-alt2"></span>
            <?php endif; ?>
        </label>
        
        <?php if ($rate->remote_fee > 0): ?>
            <div class="remote-fee-notice">
                <?php
                /* translators: %s: Remote area fee amount in the site's currency format */
                printf(
                    esc_html__('Remote Area Fee: %s', 'czlexpress-for-woocommerce'), 
                    wp_kses_post(wc_price($rate->remote_fee))
                );
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($rate->has_sub_methods)): ?>
            <div class="sub-methods" style="display: none;">
                <table class="sub-methods-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Method', 'czlexpress-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Delivery Time', 'czlexpress-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Cost', 'czlexpress-for-woocommerce'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rate->sub_methods as $sub_method): ?>
                            <tr>
                                <td><?php echo esc_html($sub_method['method_title']); ?></td>
                                <td><?php echo esc_html($sub_method['delivery_time']); ?></td>
                                <td><?php echo wp_kses_post(wc_price($sub_method['cost'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </td>
    <td><?php echo wp_kses_post(wc_price($rate->cost)); ?></td>
</tr> 