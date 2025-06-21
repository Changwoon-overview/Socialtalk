# SMS Connect Developer Guide

This guide provides technical details for developers looking to contribute to or extend the SMS Connect plugin.

## Project Structure

The plugin is organized with a clear, object-oriented structure:

-   `/`: Contains the main plugin file (`sms-connect.php`) and documentation (`readme.txt`, `DEVELOPERS.md`).
-   `/includes`: The core logic of the plugin.
    -   `/autoloader.php`: A PSR-4 autoloader for automatically loading class files.
    -   `/core`: Core business logic classes.
        -   `Sms_Connect.php`: The main plugin singleton class that initializes all handlers and acts as a service container.
        -   `Sms_Api_Client.php`: Handles all API communication for sending SMS.
        -   `Alimtalk_Api_Client.php`: Handles API communication for sending Alimtalk.
        -   `Message_Manager.php`: Determines whether a message should be SMS or LMS.
        -   `Template_Variables.php`: Replaces placeholders in messages with order data.
        -   `Db_Manager.php`: Manages custom database table creation.
    -   `/admin`: All admin-facing functionality.
        -   `Admin_Menu.php`: Creates the main admin menu and submenus.
        -   `Dashboard.php`: Renders the main dashboard page.
        -   `Settings_Page.php`: Renders the General Settings page.
        -   `Sms_Settings_Page.php`: Renders the SMS Settings page.
        -   `Alimtalk_Settings_Page.php`: Renders the Alimtalk Settings page.
        -   `History_Page.php`: Renders the sending history page, which uses:
        -   `History_List_Table.php`: A `WP_List_Table` implementation for displaying logs.
        -   `Admin_Notices.php`: Manages and displays admin notices.
    -   `/woocommerce`: Integration logic specific to WooCommerce.
        -   `WC_Hooks.php`: Hooks into `woocommerce_order_status_changed` to trigger notifications.
-   `/uninstall.php`: Contains the logic for cleaning up on plugin uninstallation (currently empty but ready).

## Main Classes

### `Sms_Connect` (The Singleton)

This is the central orchestrator of the plugin, located in `/includes/core/Sms_Connect.php`. It uses the Singleton pattern to ensure only one instance exists. Its primary responsibility is to initialize and store all other functional classes (we call them "handlers") in its `$handlers` property.

You can access any handler from anywhere like this:
`\SmsConnect\Sms_Connect::instance()->get_handler( 'wc_hooks' );`

### `WC_Hooks`

This class (`/includes/woocommerce/WC_Hooks.php`) is the entry point for all order-related notifications. It listens for the `woocommerce_order_status_changed` action and decides whether to send an Alimtalk or an SMS based on the configured settings.

## Hooks (Actions and Filters)

The plugin is built to be extensible. The following hooks are available:

*(Currently, no custom hooks have been explicitly added for extension, but this is where they would be documented. We have prioritized a stable core first.)*

For example, a filter could be added in `Template_Variables.php` to allow adding custom placeholders:
`$replacements = apply_filters( 'sms_connect_template_variables', $replacements, $this->order );`

## How to Add a New Notification Type (e.g., Email)

1.  **Create an API/Client Class:** Create a new class in `/includes/core/`, for example, `Email_Client.php`, responsible for sending the email.
2.  **Create a Settings Page:** Create a new class in `/includes/admin/`, like `Email_Settings_Page.php`, to manage email templates and settings.
3.  **Integrate with the Main Class:**
    *   In `Sms_Connect::init_handlers()`, instantiate your new classes and add them to the `$handlers` array.
    *   In `Admin_Menu::setup_menu_items()`, add a new submenu for your email settings page.
4.  **Update the Trigger Logic:**
    *   In `WC_Hooks::process_hooks()`, add logic to check if an email should be sent for the given order status, similar to how it checks for Alimtalk and SMS.
    *   Create a new private method like `send_email()` within `WC_Hooks.php`.

This structured approach ensures that new features can be added cleanly without modifying the core logic of other components. 