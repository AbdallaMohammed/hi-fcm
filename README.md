# HI FCM


This is a plugin give you the ability to push notifications directly from your WordPress site to mobiles (Android or IOS) via [Firebase Cloud Messaging](https://firebase.google.com/) service.

- [Installation](#installation)
- [Features](#features)
- [Endpoints](#endpoints)
- [Filters](#filters)
- [Actions](#actions)

## Installation

1. Copy the `hi-fcm` folder into your `wp-content/plugins` folder
2. Activate the `HI FCM - Firebase Cloud Messaging` plugin via the plugin admin page

## Features

* Push notifications for each post.
* Devices are subscribed in category wise, so that the notifications can also be sent based on the category.
* Specify a sound for the notification.
* Specify a channel id to send notifications.
* Development friendly.

## Endpoints

| Endpoint | Method | Params |
|----------|:--------:|:--------:|
| /wp-json/hifcm/v1/fcm/subscribe | POST | **user_id**<br>required, integer<br>**device_token**<br>required, string<br>**taxonomy**<br>required, string<br>**device_name**<br>nullable, string<br>**os_version**<br>nullable, string
| /wp-json/hifcm/v1/fcm/unsubscribe | DELETE, POST | **user_id**<br>required, integer<br>**device_token**<br>nullable, string

## Filters

| Filter    | Argument(s) |
|-----------|-----------|
| hi_fcm/excluded_post_types | **$post_types**<br>array |
| hi_fcm/registered_post_types | **$post_types**<br>array |
| hi_fcm/columns_data | **$result**<br>mixed<br>**$post**<br>WP_Post<br>**$key**<br>string |
| hi_fcm/term_names | **$results**<br>array<br>**$terms**<br>WP_Term_Query<br>**$post**<br>WP_Post |
| hi_fcm/get_tokens | **$args**<br>array |
| hi_fcm/notifications/post | **$args**<br>array |
| hi_fcm/endpoints/subscribe | **$args**<br>array |
| hi_fcm/endpoints/unsubscribe | **$args**<br>array |

## Actions

| Actions | Argument(s) |
|---------|-------------|
| hi_fcm/loaded | *NONE* |
| hi_fcm/metabox | *NONE* |
| hi_fcm/dashboard/tabs | *NONE* |
| hi_fcm/dashboard/tabs/contents | *NONE* |
| hi_fcm/notification/response | **$response**<br>mixed<br>**$post**<br>WP_Post |

## Notes

### Using 3rd party service
Please note that this plugin is relying on a 3rd party service, which is the Google Firebase Cloud Messaging service (FCM) and your data is being sent through their servers via HTTP API (https://fcm.googleapis.com/fcm/send). This is very legal to use the Google Firebase Cloud Messaging service (FCM), based on their terms and conditions. (https://firebase.google.com/terms/)