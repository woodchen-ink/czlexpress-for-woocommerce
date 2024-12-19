<?php
defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('czl_options_group');
        do_settings_sections('czl_options');
        submit_button();
        ?>
    </form>
</div>
  </rewritten_file> 