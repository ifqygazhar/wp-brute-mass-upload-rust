<?php
$required_plugins = Cf7_Required_Plugin::get_required_plugins();
$plugin_url = untrailingslashit( plugins_url( '/', CF7CSTMZR_PLUGIN_FILE ) );

if (!empty($required_plugins)) {
    ?>
    <div style="width100%; max-width: 650px; margin: auto;">
        <p>
            <?php _e('Before using WOW Style Contact Form 7, you need to install and activate Contact Form 7 plugin.', 'cf7-styler'); ?>
        </p>

        <div class="processing-holder">
            <div class="required-plugin-container">
                <?php
                foreach ($required_plugins as $slug => $data) {
                    $plugin_installed = Cf7_Required_Plugin::is_plugin_installed($data['slug']);
                    ?>
                    <div class="required-plugin-holder">
                        <div class="required-plugin-holder-inner">
                            <div class="img-holder">
                                <img src="<?php echo $plugin_url . '/admin/img/icon-'.$slug.'.png'; ?>" alt="">
                            </div>

                            <div class="info-holder">
                                <h3><a href="<?php echo $data['link'] ?>" target="_blank"><?php echo $data['label'] ?></a></h3>
                                <p><?php echo $data['author'] ?></p>
                                <?php
                                if (!empty($data['notice'])) {
                                    echo $data['notice'];
                                }
                                ?>
                            </div>

                            <div class="button-holder">
                                <button id="install-<?php echo $slug ?>" class="cf7cstmzr-install-plugin button button-primary" data-plugin="<?php echo $slug ?>">
                                    <?php _e('Install and Activate', 'more-better-reviews-for-woocommerce'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>

            <div class="processing-spinner-holder">
                <div class="processing-spinner"></div>
            </div>
        </div>
    </div>
    <?php
}