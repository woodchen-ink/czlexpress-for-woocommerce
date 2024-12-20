<?php
defined('ABSPATH') || exit;

$groups = get_option('czl_product_groups', array());
?>

<div class="wrap czl-product-groups-page">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="czl-page-description">
        <p><?php _e('在这里管理运输方式的分组显示。相同分组的运输方式将合并显示，并显示最低价格。', 'czlexpress-for-woocommerce'); ?></p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('czl_save_product_groups', 'czl_product_groups_nonce'); ?>
        
        <div class="czl-table-container">
            <table class="widefat czl-product-groups" id="czl-product-groups">
                <thead>
                    <tr>
                        <th class="column-enabled"><?php _e('启用', 'czlexpress-for-woocommerce'); ?></th>
                        <th class="column-name"><?php _e('分组名称', 'czlexpress-for-woocommerce'); ?></th>
                        <th class="column-prefixes"><?php _e('匹配前缀', 'czlexpress-for-woocommerce'); ?></th>
                        <th class="column-actions"><?php _e('操作', 'czlexpress-for-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($groups)) : ?>
                        <?php foreach ($groups as $key => $group): ?>
                        <tr>
                            <td class="column-enabled">
                                <input type="checkbox" name="groups[<?php echo esc_attr($key); ?>][enabled]" 
                                       value="1" <?php checked(!empty($group['enabled'])); ?>>
                            </td>
                            <td class="column-name">
                                <input type="text" name="groups[<?php echo esc_attr($key); ?>][groupName]" 
                                       value="<?php echo esc_attr($group['groupName']); ?>" class="regular-text">
                            </td>
                            <td class="column-prefixes">
                                <textarea name="groups[<?php echo esc_attr($key); ?>][prefixes]" rows="3" class="large-text"
                                ><?php echo esc_textarea(implode("\n", $group['prefixes'])); ?></textarea>
                                <p class="description"><?php _e('每行输入一个前缀，运输方式名称以此前缀开头将被归入此分组', 'czlexpress-for-woocommerce'); ?></p>
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button remove-group" title="<?php esc_attr_e('删除此分组', 'czlexpress-for-woocommerce'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="no-items">
                            <td colspan="4"><?php _e('没有找到分组配置', 'czlexpress-for-woocommerce'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">
                            <button type="button" class="button add-group">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php _e('添加分组', 'czlexpress-for-woocommerce'); ?>
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php submit_button(__('保存分组设置', 'czlexpress-for-woocommerce'), 'primary', 'submit', true); ?>
    </form>
</div>

<script type="text/template" id="group-row-template">
    <tr>
        <td class="column-enabled">
            <input type="checkbox" name="groups[{{index}}][enabled]" value="1" checked>
        </td>
        <td class="column-name">
            <input type="text" name="groups[{{index}}][groupName]" value="" class="regular-text" placeholder="<?php esc_attr_e('输入分组名称', 'czlexpress-for-woocommerce'); ?>">
        </td>
        <td class="column-prefixes">
            <textarea name="groups[{{index}}][prefixes]" rows="3" class="large-text" placeholder="<?php esc_attr_e('每行输入一个前缀', 'czlexpress-for-woocommerce'); ?>"></textarea>
            <p class="description"><?php _e('每行输入一个前缀，运输方式名称以此前缀开头将被归入此分组', 'czlexpress-for-woocommerce'); ?></p>
        </td>
        <td class="column-actions">
            <button type="button" class="button remove-group" title="<?php esc_attr_e('删除此分组', 'czlexpress-for-woocommerce'); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </td>
    </tr>
</script>

<style>
.czl-product-groups-page {
    max-width: 1200px;
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

.czl-product-groups {
    border: none;
}

.czl-product-groups th {
    padding: 15px;
    font-weight: 600;
}

.czl-product-groups td {
    vertical-align: top;
    padding: 15px;
}

.column-enabled {
    width: 60px;
    text-align: center;
}

.column-name {
    width: 200px;
}

.column-actions {
    width: 80px;
    text-align: center;
}

.remove-group .dashicons {
    margin-top: 3px;
    color: #b32d2e;
}

.add-group .dashicons {
    margin-top: 3px;
    margin-right: 5px;
}

.no-items td {
    text-align: center;
    padding: 20px !important;
    background: #f8f8f8;
}

textarea {
    min-height: 80px;
}

.description {
    margin-top: 5px;
    color: #666;
}
</style>

<script>
jQuery(function($) {
    var $table = $('#czl-product-groups');
    var template = $('#group-row-template').html();
    var groupIndex = $table.find('tbody tr').length;
    
    $('.add-group').on('click', function() {
        var newRow = template.replace(/{{index}}/g, groupIndex++);
        if ($table.find('.no-items').length) {
            $table.find('.no-items').remove();
        }
        $table.find('tbody').append(newRow);
    });
    
    $table.on('click', '.remove-group', function() {
        var $row = $(this).closest('tr');
        $row.fadeOut(300, function() {
            $row.remove();
            if ($table.find('tbody tr').length === 0) {
                $table.find('tbody').append('<tr class="no-items"><td colspan="4"><?php _e('没有找到分组配置', 'czlexpress-for-woocommerce'); ?></td></tr>');
            }
        });
    });
});
</script> 