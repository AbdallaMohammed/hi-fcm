<?php

if (! function_exists('hi_fcm_post_types')) {
    /**
     * Return the default post types.
     * 
     * @return array
     */
    function hi_fcm_post_types($args = []) {
        $types = [];
        $post_types = get_post_types([], 'objects');

        foreach ($post_types as $post_type) {
            if (! in_array($post_type->name, apply_filters('hi_fcm/excluded_post_types', ['attachment']))) {
                $types[] = $post_type;
            }
        }

        return $types;
    }
}

if (! function_exists('hi_fcm_can')) {
    /**
     * Determine whether the post can be notified or not.
     * 
     * @param WP_Post|integer|string $post
     * 
     * @return boolean
     */
    function hi_fcm_can($post) {
        if (is_numeric($post)) {
            $post = get_post($post);
        }

        if ($post instanceof WP_Post) {
            return get_option('hi_fcm_post_type_' . $post->post_type) == '1';
        }

        return get_option('hi_fcm_post_type_' . $post) == '1';
    }
}

if (! function_exists('hi_fcm_registered_post_types')) {
    /**
     * Reterive all registered post types.
     * 
     * @return array
     */
    function hi_fcm_registered_post_types() {
        $types = [];
        $post_types = hi_fcm_post_types();

        foreach ($post_types as $post_type) {
            if (get_option('hi_fcm_post_type_' . $post_type->name) == '1') {
                $types[] = $post_type;
            }
        }

        return apply_filters('hi_fcm/registered_post_types', $types);
    }
}

if (! function_exists('hi_fcm_get_column_data')) {
    /**
     * Get custom column data.
     * 
     * @param WP_Post|integer $post
     * @param string $key
     * @param string $search
     * 
     * @return mixed
     */
    function hi_fcm_get_column_data($post, $key, $search = 'post_ID') {
        global $wpdb, $hi_fcm;

        if (is_object($post)) {
            $post = $post->ID;
        }

        $table_name = $hi_fcm->tokens_table;
        $result = $wpdb->get_row(
            "SELECT $key FROM $table_name WHERE $search = $post"
        );

        return apply_filters('hi_fcm/columns_data', $result->$key, $post, $key);
    }
}

if (! function_exists('hi_fcm_get_subscribed_term_names')) {
    /**
     * Get subscription names of post.
     * 
     * @param WP_Post|integer $post
     * 
     * @return array
     */
    function hi_fcm_get_subscribed_term_names($post) {
        if (is_object($post)) {
            $post = $post->ID;
        }

        $terms = new WP_Term_Query([
            'object_ids' => $post,
            'hide_empty' => true,
        ]);
        $terms = $terms->get_terms();

        $results = [];
        foreach ($terms as $term) {
            $results[] = $term->name;
        }

        return apply_filters('hi_fcm/term_names', $results, $terms, $post);
    }
}

if (! function_exists('hi_fcm_token_exists')) {
    /**
     * Determine whether the token exists or not.
     * 
     * @param string $token
     * 
     * @return boolean
     */
    function hi_fcm_token_exists($token) {
        global $wpdb, $hi_fcm;

        $table = $hi_fcm->tokens_table;

        return $wpdb->get_row("SELECT * FROM $table WHERE device_token = '$token'");
    }
}

if (! function_exists('hi_fcm_insert_token')) {
    /**
     * Insert new token to tokens table.
     * 
     * @param array $args
     * 
     * @return void
     */
    function hi_fcm_insert_token($args) {
        global $wpdb, $hi_fcm;

        $wpdb->insert(
            $hi_fcm->tokens_table,
            $args,
            [
                '%d',
                '%s', 
                '%s',
                '%s'
            ]
        );

        return $wpdb->insert_id;
    }
}

if (! function_exists('hi_fcm_get_available_terms')) {
    /**
     * Reterive all available terms.
     * 
     * @return array
     */
    function hi_fcm_get_available_terms() {
        $terms = get_terms([
            'taxonomy' => 'hi_fcm_subscriptions',
            'hide_empty' => false,
        ]);

        $available_terms = [];
        foreach ($terms as $term) {
            $available_terms[] = $term->name;
        }

        return apply_filters('hi_fcm/terms', $available_terms);
    }
}

if (! function_exists('hi_fcm_get_device_by_token')) {
    /**
     * Reterive device data by token.
     * 
     * @param string $token
     * 
     * @return integer
     */
    function hi_fcm_get_device_by_token($token) {
        global $wpdb, $hi_fcm;

        $table = $hi_fcm->tokens_table;

        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE device_token = %s", $token)
        );

        if ($result) {
            return $result->post_ID;
        }

        return 0;
    }
}

if (! function_exists('hi_fcm_delete_device_data')) {
    /**
     * Delete device data.
     * 
     * @param WP_Post|int $post
     * 
     * @return boolean
     */
    function hi_fcm_delete_device_data($post) {
        global $wpdb, $hi_fcm;

        if (is_object($post)) {
            $post = $post->ID;
        }

        return $wpdb->delete(
            $hi_fcm->tokens_table,
            [
                'post_ID' => $post,
            ]
        );
    }
}

if (! function_exists('hi_fcm_find_devices_by_user_ID')) {
    /**
     * Reterive devices by user ID.
     * 
     * @param WP_User|integer $user
     * 
     * @return array
     */
    function hi_fcm_find_devices_by_user_ID($user) {
        global $wpdb;

        if (is_object($user)) {
            $user = $user->ID;
        }

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_author = %s AND post_type = %s AND post_status = 'publish'", $user, 'hi_fcm_tokens')
        );
    }
}

if (! function_exists('hi_fcm_get_subscribers_tokens')) {
    /**
     * Reterive list of subscribers tokens.
     * 
     * @param array|string $names
     * 
     * @return array
     */
    function hi_fcm_get_subscribers_tokens($names = []) {
        $args = [
            'post_type' => 'hi_fcm_tokens',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];

        if (! empty($names)) {
            $tax_query = [
                'taxonomy' => 'hi_fcm_subscriptions',
                'field' => 'name',
                'terms' => $names
            ];

            if (isset($args['tax_query']) && is_array($args['tax_query'])) {
                $args['tax_query'][] = $tax_query;
            } else {
                $args['tax_query'] = [
                    $tax_query
                ];
            }
        }

        $devices = get_posts(apply_filters('hi_fcm/get_tokens', $args));

        $tokens = [];
        foreach ($devices as $device) {
            $tokens[] = hi_fcm_get_column_data($device, 'device_token');
        }

        return $tokens;
    }
}