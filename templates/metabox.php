<?php wp_nonce_field(HI_FCM_DIR, 'hi_fcm_nonce') ?>

<div class="components-base-control">
    <div class="components-base-control__field">
        <span class="components-checkbox-control__input-container">
            <input type="checkbox" name="hi_fcm_push_notification" value="1" <?php if (get_post_meta($post->ID, 'hi_fcm_push_notification', true) == '1'): ?> checked <?php endif; ?>>
        </span>
        <label class="components-checkbox-control__label" for="hi_fcm_push_notification">
            <?php esc_html_e('Push a notification?', 'hi-fcm') ?>
        </label>
    </div>
    <div class="components-base-control__field">
        <span class="components-checkbox-control__input-container">
            <input type="checkbox" name="hi_fcm_use_channel" value="1">
        </span>
        <label class="components-checkbox-control__label" for="hi_fcm_use_channel">
            <?php esc_html_e('Push to channel?', 'hi-fcm') ?>
        </label>
    </div>
    <div class="components-base-control__field">
        <label class="components-checkbox-control__label" for="hi_fcm_push_notification">
            <?php esc_html_e('Subscription', 'hi-fcm') ?>
        </label>
        <span class="components-checkbox-control__input-container">
            <select name="hi_fcm_subscription">
                <option value=""><?php esc_html_e('All', 'hi-fcm') ?></option>
                <?php foreach ($subscriptions as $subscription): ?>
                    <option value="<?php esc_attr_e($subscription) ?>" <?php if (get_post_meta($post->ID, 'hi_fcm_subscription', true) == $subscription): ?> selected <?php endif; ?>>
                        <?php esc_attr_e($subscription) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </span>
    </div>
</div>

<?php do_action('hi_fcm/metabox') ?>