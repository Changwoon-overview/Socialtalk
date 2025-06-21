=== SMS Connect ===
Contributors: (your-name)
Tags: woocommerce, sms, alimtalk, messaging, notifications
Requires at least: 5.0
Tested up to: 6.0
Stable tag: 0.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful and easy-to-use plugin to send SMS and Alimtalk notifications for WooCommerce order status changes. Keep your customers informed automatically.

== Description ==

SMS Connect provides a seamless integration between your WooCommerce store and popular messaging services. Automate customer notifications by sending SMS, LMS, or Kakao Alimtalk messages whenever an order status is updated.

This plugin allows you to:
*   **Automate Notifications:** Automatically send messages for any WooCommerce order status (e.g., Pending, Processing, Completed).
*   **Customize Messages:** Use template variables like `{customer_name}`, `{order_id}`, and `{order_total}` to create personalized messages.
*   **SMS & LMS Support:** Automatically detects message length and sends as SMS or long-form LMS.
*   **Kakao Alimtalk Integration:** Send official Kakao Alimtalk messages using pre-approved templates for reliable delivery.
*   **Detailed Sending History:** Keep a log of every message sent, including status, recipient, and content.
*   **Dashboard Overview:** Check your message credit balance and view sending statistics at a glance.

== Installation ==

1.  Upload the `sms-connect` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to `SMS Connect > General Settings` to enter your API credentials for SMS sending.
4.  Go to `SMS Connect > Alimtalk Settings` to enter your credentials for Alimtalk sending.
5.  Configure your message templates for each order status in the `SMS Settings` and `Alimtalk Settings` pages.

== Frequently Asked Questions ==

= Which messaging APIs are supported? =

This plugin is designed to be flexible. The initial version is built with compatibility for CoolSMS, but the structure allows for adding other API providers in the future.

= What's the difference between SMS and Alimtalk? =

SMS are standard text messages. Kakao Alimtalk are template-based informational messages sent via KakaoTalk, often used for official notifications like order confirmations. They require pre-approval of your templates.

= How are template variables used? =

In the SMS or Alimtalk message settings, you can use placeholders like `{customer_name}`. When a message is sent, the plugin automatically replaces these with the actual order data.

== Screenshots ==

1. Dashboard overview with account balance and sending stats.
2. Sending history log with detailed information.
3. SMS settings page for each order status.
4. Alimtalk settings page.

== Changelog ==

= 0.0.1 =
*   Initial release.

== Upgrade Notice ==

= 0.0.1 =
*   Initial release of the plugin. 