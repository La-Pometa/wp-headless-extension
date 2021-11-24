<div class="wrap">
    <form method="post" action="options.php">
        <?php settings_fields('wp_headless-group'); ?>

        <?php do_settings_sections('wp_headless'); ?>

        <?php submit_button(); ?>
    </form>
</div>