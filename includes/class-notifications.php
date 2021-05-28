<?php

class HIF_Notifications {
    /**
     * FCM API Key.
     * 
     * @var string
     */
    private $key = '';

    /**
     * HIF_Notifications Constructor.
     */
    public function __construct(string $key) {
        $this->key = $key;
    }

    /**
     * Send notification on post.
     * 
     * @return array
     */
    public function post($post, $args = []) {
        if (is_numeric($post)) {
            $post = get_post($post);
        }

        $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
        $content = _mb_strlen($post->post_excerpt) == 0 ? _mb_substr(wp_strip_all_tags(esc_html(wp_strip_all_tags(preg_replace( "/\r|\n/", " ", $post->post_content)))), 0, 55) . '...' : $post->post_excerpt;

        $defaults = [
            'data' => [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'message' => $content,
                'post_ID' => $post->ID,
                'post_type' => $post->post_type,
                'title' => $post->post_title,
                'image' => $image,
                'url' => get_the_permalink($post->ID),
                'show_in_notification' => true,
                'command' => '',
                'dialog_title' => $post->post_title,
                'dialog_text' => $content,
                'dialog_image' => $image,
                'sound' => 'default',
            ],
            'collapse_key' => 'type_a',
            'priority' => 'heigh',
            'timeToLive' => 10,
        ];

        if (
            ! empty(get_post_meta($post->ID, 'hi_fcm_subscription', true))
            && 
            (
                empty(get_post_meta($post->ID, 'hi_fcm_use_channel', true))
                ||
                get_post_meta($post->ID, 'hi_fcm_use_channel', true) == '0'
            )
        ) {
            $defaults['registration_ids'] = hi_fcm_get_subscribers_tokens(
                get_post_meta($post->ID, 'hi_fcm_subscription', true)
            );
        }

        if (
            empty(get_post_meta($post->ID, 'hi_fcm_subscription', true))
            &&
            (
                ! empty(get_post_meta($post->ID, 'hi_fcm_use_channel', true))
                ||
                get_post_meta($post->ID, 'hi_fcm_use_channel', true) == '1'
            )
            &&
            ! empty(get_option('hi_fcm_channel'))
        ) {
            $defaults['android_channel_id'] = get_option('hi_fcm_channel');
        }

        $args = wp_parse_args($args, $defaults);

        $response = wp_remote_post('https://fcm.googleapis.com/fcm/send', apply_filters('hi_fcm/notifications/post', [
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.1',
            'body' => json_encode($args),
            'sslverify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'key=' . $this->key,
            ],
            'cookies' => [],
        ], $post, $args));

        do_action('hi_fcm/notification/response', $response, $post);

        return $response;
    }
}