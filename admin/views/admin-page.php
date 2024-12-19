<?php
defined('ABSPATH') || exit;

$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=czl-express&tab=settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php _e('基本设置', 'woo-czl-express'); ?>
        </a>
        <a href="?page=czl-express&tab=test" class="nav-tab <?php echo $tab === 'test' ? 'nav-tab-active' : ''; ?>">
            <?php _e('接口测试', 'woo-czl-express'); ?>
        </a>
    </nav>
    
    <div class="tab-content">
        <?php
        switch ($tab) {
            case 'test':
                require_once WOO_CZL_EXPRESS_PATH . 'admin/views/api-test.php';
                break;
            default:
                require_once WOO_CZL_EXPRESS_PATH . 'admin/views/settings.php';
                break;
        }
        ?>
    </div>
</div> 