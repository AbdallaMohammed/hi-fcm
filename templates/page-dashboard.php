<?php
if (! defined('ABSPATH')) {
    exit;
}
?>

<h1><?php esc_html_e('Firbase Cloud Messaging', 'hi-fcm') ?></h1>

<h2 class="nav-tab-wrapper">
    <a href="<?php echo esc_url(admin_url('admin.php?page=hi_fcm')) ?>" class="nav-tab nav-tab-active">
        <?php esc_html_e('Settings', 'hi-fcm') ?>
    </a>
    <?php do_action('hi_fcm/dashboard/tabs') ?>
</h2>

<div class="nav-tab-content">
    <div class="nav-tab-inside">
        <h3><?php esc_html_e('Settings', 'hi-fcm') ?></h3>
        <form action="options.php" method="POST">
            <?php settings_fields('hi_fcm_settings_group') ?>
	        <?php do_settings_sections('hi_fcm_settings_group') ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="hi_fcm_api_key"><?php esc_html_e('API Key', 'hi-fcm') ?></label>
                        </th>
                        <td>
                            <input id="hi_fcm_api_key" name="hi_fcm_api_key" type="text" value="<?php esc_attr_e(get_option('hi_fcm_api_key')) ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hi_fcm_channel"><?php esc_html_e('Channel ID', 'hi-fcm') ?></label>
                        </th>
                        <td>
                            <input id="hi_fcm_channel" name="hi_fcm_channel" type="text" value="<?php esc_attr_e(get_option('hi_fcm_channel')) ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hi_fcm_sound"><?php esc_html_e('Sound', 'hi-fcm') ?></label>
                        </th>
                        <td>
                            <input id="hi_fcm_sound" name="hi_fcm_sound" type="text" value="<?php esc_attr_e(get_option('hi_fcm_sound', 'default')) ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Post Types', 'hi-fcm') ?>
                        </th>
                        <td>
                            <fieldset>
                                <p>
                                    <?php foreach ($post_types as $post_type): ?>
                                        <label>
                                            <input type="checkbox" name="hi_fcm_post_type_<?php esc_attr_e($post_type->name) ?>" <?php if (hi_fcm_can($post_type->name)): ?> checked <?php endif; ?> value="1">
                                            <?php esc_attr_e($post_type->labels->singular_name) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e('Save Changes', 'wp') ?>">
            </p>
        </form>
    </div>
    <?php do_action('hi_fcm/dashboard/tabs/contents') ?>
</div>