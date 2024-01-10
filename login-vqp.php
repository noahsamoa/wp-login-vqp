<?php
/**
 * Plugin Name: Login via Query Params
 * Description: A plugin to allow login via query parameters for username and pass (only use if you know what that entails.)
 * Version: 1.0
 * Author: Noah M.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

function login_via_query_params() {
    // Check if the plugin is enabled based on the toggle switch.
    $is_plugin_enabled = get_option('login_via_query_params_enabled', false);

    if ($is_plugin_enabled && isset($_GET['username']) && isset($_GET['pass'])) {
        $user = get_user_by('login', $_GET['username']);

        if ($user && wp_check_password($_GET['pass'], $user->data->user_pass, $user->ID)) {
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID);
            do_action('wp_login', $user->user_login);

            wp_redirect(admin_url());
            exit;
        }

        wp_redirect(home_url());
        exit;
    }
}

// Hook into the WordPress init action.
add_action('init', 'login_via_query_params');

// Add a toggle switch in the admin header bar.
add_action('admin_bar_menu', 'login_via_query_params_admin_bar_menu', 999);

function login_via_query_params_admin_bar_menu($wp_admin_bar) {
    $is_plugin_enabled = get_option('login_via_query_params_enabled', false);

    $args = array(
        'id'    => 'login_via_query_params_toggle',
        'title' => $is_plugin_enabled ? 'Disable Query Login' : 'Enable Query Login',
        'meta'  => array(
            'class' => $is_plugin_enabled ? 'enabled' : 'disabled',
        ),
        'href'  => '#', // Set href to '#'
    );

    $wp_admin_bar->add_node($args);
}

// Handle the toggle switch state.
add_action('wp_ajax_login_via_query_params_toggle', 'login_via_query_params_toggle');

function login_via_query_params_toggle() {
    // Check nonce
    if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'login_via_query_params_toggle_nonce')) {
        $is_plugin_enabled = get_option('login_via_query_params_enabled', false);
        $is_plugin_enabled = !$is_plugin_enabled;
        update_option('login_via_query_params_enabled', $is_plugin_enabled);

        wp_send_json_success(array(
            'enabled' => $is_plugin_enabled,
            'title' => $is_plugin_enabled ? 'Disable Query Login' : 'Enable Query Login',
            'class' => $is_plugin_enabled ? 'enabled' : 'disabled',
        ));
    } else {
        wp_send_json_error(array('message' => 'Invalid nonce.'));
    }
}

// JavaScript to handle toggle without page reload
add_action('admin_footer', 'login_via_query_params_custom_js');

function login_via_query_params_custom_js() {
    ?>
    <script>
        jQuery(document).ready(function($) {
            $('#wp-admin-bar-login_via_query_params_toggle a').on('click', function(e) {
                e.preventDefault();

                var $this = $(this);

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'login_via_query_params_toggle',
                        _wpnonce: '<?php echo wp_create_nonce('login_via_query_params_toggle_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $this.parent().attr('class', response.data.class);
                            $this.text(response.data.title);
                        } else {
                            alert('Toggle failed. Please try again.');
                        }
                    },
                    error: function() {
                        alert('Toggle failed. Please try again.');
                    }
                });
            });
        });
    </script>
    <?php
}
?>
