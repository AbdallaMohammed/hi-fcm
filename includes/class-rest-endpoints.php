<?php

class HIF_REST_Endpoints {
    /**
     * Register API endpoints.
     * 
     * @return void
     */
    public static function register_endpoints() {
        register_rest_route(
            'hifcm/v1',
            '/fcm/subscribe/',
            apply_filters('hi_fcm/endpoints/subscribe', [
                'methods' => 'POST',
                'callback' => [__CLASS__, 'subscribe_endpoint'],
                'permission_callback' => '__return_true',
                'args' => [
                    'user_id' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                    'device_token' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'taxonomy' => [
                        'required' => true,
                        'type' => 'string',
                        'enum' => hi_fcm_get_available_terms(),
                    ],
                ],
            ])
        );

        register_rest_route(
            'hifcm/v1',
            '/fcm/unsubscribe',
            apply_filters('hi_fcm/endpoints/unsubscribe', [
                'methods' => ['POST', 'DELETE'],
                'callback' => [__CLASS__, 'unsubscribe_endpoint'],
                'permission_callback' => '__return_true',
                'args' => [
                    'user_id' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                    'device_token' => [
                        'required' => false,
                        'type' => 'string',
                    ],
                ],
            ])
        );

        register_rest_route(
            'hifcm/v1',
            '/fcm/send/user',
            apply_filters('hi_fcm/endpoints/send/user', [
                'methods' => ['POST'],
                'callback' => [__CLASS__, 'send_user_endpoint'],
                'permission_callback' => '__return_true',
                'args' => [
                    'user_id' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                    'message' => [
                        'required' => true,
                    ],
                    'title' => [
                        'required' => true,
                    ],
                ],
            ])
        );

        register_rest_route(
            'hifcm/v1',
            '/fcm/notifications',
            apply_filters('hi_fcm/endpoints/notifications', [
                'methods' => ['GET'],
                'callback' => [__CLASS__, 'get_notifications'],
                'permission_callback' => fn () => is_user_logged_in(),
            ])
        );
    }

    public static function subscribe_endpoint($request) {
        if (hi_fcm_token_exists(sanitize_text_field($request->get_param('device_token')))) {
            return new WP_Error(
                'rest_device_token_exists',
                esc_html__('This device token already exists', 'hi-fcm'),
                [
                    'status' => 400,
                ]
            );
        }

        $user = get_user_by('ID', sanitize_text_field($request->get_param('user_id')));
        $post = wp_insert_post([
            'post_title' => sanitize_email($user->email),
            'post_author' => absint($user->ID),
            'post_status' => 'publish',
            'post_type' => 'hi_fcm_tokens',
        ], true);

        if (is_wp_error($post)) {
            return $post;
        }

        wp_set_object_terms(
            absint($post),
            sanitize_text_field($request->get_param('taxonomy')),
            'hi_fcm_subscriptions'
        );

        hi_fcm_insert_token([
            'post_ID' => absint($post),
            'user_ID' => absint($user->ID),
            'device_token' => sanitize_text_field($request->get_param('device_token')),
            'device' => sanitize_text_field($request->get_param('device_name')),
            'os_version' => sanitize_text_field($request->get_param('os_version')),
        ]);

        return rest_ensure_response([
            'code' => 'rest_hi_fcm_insert_token',
            'message' => esc_html__('Token has been stored successfully.'),
            'data' => [
                'status' => 200,
            ],
        ]);
    }

    public static function unsubscribe_endpoint($request) {
        if (! empty($request->get_param('device_token')) && ! hi_fcm_token_exists(sanitize_text_field($request->get_param('device_token')))) {
            return new WP_Error(
                'rest_device_token_not_exists',
                esc_html__('This device token does not exists', 'hi-fcm'),
                [
                    'status' => 400,
                ]
            );
        }

        if (absint($request->get_param('user_id')) == 0) {
            $post_id = hi_fcm_get_device_by_token(sanitize_text_field($request->get_param('device_token')));

            if ($post_id) {
                wp_delete_post($post_id, false);
                hi_fcm_delete_device_data($post_id);
            }
        } else {
            $posts = hi_fcm_find_devices_by_user_ID(absint($request->get_param('user_id')));

            foreach ($posts as $post) {
                wp_delete_post($post->ID, false);
                hi_fcm_delete_device_data($post->ID);
            }
        }

        return rest_ensure_response([
            'code' => 'rest_hi_fcm_delete_device',
            'message' => esc_html__('Device has been deleted successfully', 'hi-fcm'),
            'data' => [
                'status' => 200,
            ],
        ]);
    }

    public static function send_user_endpoint($request) {
        global $hi_fcm;

        $devices = hi_fcm_find_devices_by_user_ID($request->get_param('user_id'));

        if (! count($devices)) {
            return new WP_Error(
                'rest_user_token_not_exists',
                esc_html__('This user doesn\'t have token', 'hi-fcm'),
                [
                    'status' => 400,
                ]
            );
        }

        $hi_fcm->notifications->devices($devices, [
            'message' => $request->get_param('message'),
            'title' => $request->get_param('title'),
            'image' => $request->get_param('image'),
            'dialog_title' => $request->get_param('dialog_title'),
            'dialog_text' => $request->get_param('dialog_text'),
            'dialog_image' => $request->get_param('dialog_image'),
        ]);

        return rest_ensure_response([
            'code' => 'rest_hi_fcm_send_custom_message',
            'message' => esc_html__('The notification has been sent successfully', 'hi-fcm'),
            'data' => [
                'status' => 200,
            ],
        ]);
    }

    public static function get_notifications($request) {
        return rest_ensure_response(
            hi_fcm_get_current_user_notifications(),
        );
    }
}