Requires at least: 5.0
Tested up to: 6.0
Stable tag: 0.5.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to:
*   **Automate Notifications:** Automatically send messages for any WooCommerce order status (e.g., Pending, Processing, Completed).
*   **Advanced Rule-Based Sending:** Create complex rules to send specific messages for certain products, categories, or order total amounts.
*   **Customize Messages:** Use template variables like `{customer_name}`, `{order_id}`, and `{order_total}` to create personalized messages.
*   **SMS & LMS Support:** Automatically detects message length and sends as SMS or long-form LMS.

= 0.5.1 =
*   Feature: Added an "Advanced Rules" system to allow conditional message sending based on order contents or total price.
*   Fix: Completely refactored the autoloader for stability and performance, resolving numerous "critical error" issues.
*   Fix: Overhauled the API clients for both SMS and Alimtalk, ensuring correct data flow and adding missing functionality like balance checking.
*   Fix: Repaired the Admin Dashboard, which was previously non-functional due to fatal errors.
*   Fix: Corrected multiple issues in settings pages and ensured consistent inheritance from a base class.
*   Tweak: Standardized plugin folder name and structure according to WordPress best practices.

== Upgrade Notice ==

= 0.5.1 =
*   This version contains major stability fixes and new features. It is a recommended update. The plugin structure has been refactored, please ensure you have a backup before upgrading.
= 0.0.1 =
*   Initial release of the plugin.
