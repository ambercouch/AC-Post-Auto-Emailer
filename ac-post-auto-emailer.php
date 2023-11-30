<?php
/*
  Plugin Name: AC Post Auto Emailer
  Plugin URI: https://github.com/ambercouch/ac-post-auto-emailer
  Description: Automatically email scheduled post when they are published.
  Version: 0.0.1
  Author: AmberCouch
  Author URI: http://ambercouch.co.uk
  Author Email: richard@ambercouch.co.uk
  Text Domain: ac-post-auto-emailer
  Domain Path: /lang/
  License:
  Copyright 2018 AmberCouch
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
defined('ABSPATH') or die('You do not have the required permissions');

if (!defined('ACPAE_VERSION')) define( 'ACPAE_VERSION', '0.0.3' );
if (!defined('ACPAE_PLUGIN')) define( 'ACPAE_PLUGIN', __FILE__ );
if (!defined('ACPAE_PREFIX')) define( 'ACPAE_PREFIX', 'acpae_' );

define( 'ACPAE_TEXT_DOMAIN', 'ac-plugin-name' );

define( 'ACPAE_PLUGIN_BASENAME', plugin_basename( ACPAE_PLUGIN ) );

define( 'ACPAE_PLUGIN_NAME', trim( dirname( ACPAE_PLUGIN_BASENAME ), '/' ) );

define( 'ACPAE_PLUGIN_DIR', untrailingslashit( dirname( ACPAE_PLUGIN ) ) );

define( 'ACPAE_PLUGIN_LIB_DIR', ACPAE_PLUGIN_DIR . '/lib' );

//require ACPAE_PLUGIN_DIR .  '/vendor/autoload.php';

function acpae_plugin_url( $path = '' ) {
      $url = plugins_url( $path, ACPAE_PLUGIN );
    if ( is_ssl() && 'http:' == substr( $url, 0, 5 ) ) {
        $url = 'https:' . substr( $url, 5 );
    }
    return $url;
}

// Act on plugin activation
register_activation_hook( __FILE__, "acpae_activate" );

// Act on plugin de-activation
register_deactivation_hook( __FILE__, "acpae_deactivate" );

// Activate Plugin
function acpae_activate() {
    // Execute tasks on Plugin activation
}

// De-activate Plugin
function acpae_deactivate() {
    // Execute tasks on Plugin de-activation
}

function acpae_template_with_vars($template_path, $vars = []) {
    extract($vars);  // Make variables available to the template
    ob_start();
    include($template_path);
    return ob_get_clean();
}


class ACPAE_PostAutoEmailer
{

    function __construct()
    {

        add_action('wp_enqueue_scripts', array($this, 'acpae_enqueue_frontend_styles'), 11);
        add_action('wp_enqueue_scripts', array($this, 'acpae_enqueue_frontend_scripts'), 11);
        add_action('admin_enqueue_scripts', array($this, 'acpae_enqueue_admin_styles'), 11);
        add_action('admin_enqueue_scripts', array($this, 'acpae_enqueue_admin_scripts'), 11);
        add_action('admin_notices', array($this, 'acpae_admin_notice'));

        add_action('transition_post_status', array($this, 'acpae_set_email_marker'), 10, 3);
        add_action('save_post', array($this, 'acpae_send_email_after_save'), 10, 3);



        add_action('acf/init', array($this, 'acpae_acf'));

    }

    /**
     * Display an admin notice if ACF is not active
     */
    public function acpae_admin_notice()
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (!is_plugin_active('advanced-custom-fields/acf.php'))
        {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>AC Post Auto Emailer</strong> requires the Advanced Custom Fields plugin to be activated.</p>';
            echo '</div>';
        }
    }


    /**
     * Initialize ACF fields
     */
    public function acpae_acf()
    {
        if (function_exists('acf_add_local_field_group'))
        {

            acf_add_local_field_group(array(
                'key' => 'group_acpae_email',
                'title' => 'Auto Emailer Settings',
                'fields' => array(
                    array(
                        'key' => 'field_acpae_email_address',
                        'label' => 'Recipient Email Address',
                        'name' => 'acpae_email_address',
                        'type' => 'email',
                        'instructions' => 'Enter the email address where the post will be sent upon publication.',
                        'required' => 0,
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'post',
                        ),
                    ),
                ),
                'position' => 'side', // Add this line to position the field group in the sidebar
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
            ));
        }
    }

    /**
     *
     * Load the admin styles
     *
     */
    public static function acpae_enqueue_admin_styles($hook_suffix)
    {

        // Define the path to the CSS file.
        $css_path = plugins_url('assets/css/acpae-admin-styles.css', __FILE__);
        wp_register_style('acpae-admin-styles', $css_path, '', ACPAE_VERSION);
        // Enqueue the CSS file.
        wp_enqueue_style('acpae-admin-styles');
    }

    /**
     *
     * Load the frontend styles
     *
     */
    public static function acpae_enqueue_frontend_styles($hook_suffix)
    {

        // Define the path to the CSS file.
        $css_path = plugins_url('assets/css/acpae-styles.css', __FILE__);
        wp_register_style('acpae-styles', $css_path, '', ACPAE_VERSION);

        // Enqueue the CSS file.
        wp_enqueue_style('acpae-styles', $css_path);
    }

    /**
     *
     * Load the admin scripts if this is a acpae-admin page
     *
     */
    public static function acpae_enqueue_admin_scripts($hook_suffix)
    {

        global $post;
        if ($hook_suffix == 'toplevel_page_acpae-admin')
        {
            wp_register_script('acpae-scripts-admin', acpae_plugin_url('assets/js/scripts_admin.js'), array('jquery'), ACPAE_VERSION, true);
        }
        $data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acpae-admin-nonce')
        );
        wp_localize_script('acpae-scripts-admin', 'acpae_admin', $data);
        wp_enqueue_script('acpae-scripts-admin');

    }

    /**
     *
     * Load the front end scripts
     *
     */
    public static function acpae_enqueue_frontend_scripts($hook_suffix)
    {
        global $post;

        if (!$post)
        {
            return;
        }

        wp_register_script('acpae-scripts', acpae_plugin_url('assets/js/scripts.js'), array('jquery'), ACPAE_VERSION, true);

        wp_localize_script('acpae-scripts', 'acpae', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'posturl' => get_permalink($post->ID),
            'postid' => $post->ID,
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'updateusernonce' => wp_create_nonce('update-user'),
        ));

        wp_enqueue_script('acpae-scripts');
    }

    /**
     * Set a marker when post status transitions to publish.
     */
    public function acpae_set_email_marker($new_status, $old_status, $post) {
        if ('publish' === $new_status && 'publish' !== $old_status && 'post' === get_post_type($post)) {
            update_post_meta($post->ID, '_acpae_ready_to_email', 'yes');
        }
    }

    /**
     * Send email after post is saved if marker is set.
     */
    public function acpae_send_email_after_save($post_ID, $post, $update) {
        if (get_post_meta($post_ID, '_acpae_ready_to_email', true) === 'yes' && 'post' === get_post_type($post_ID)) {
            $recipient_email = get_field('acpae_email_address', $post_ID);

            if (!empty($recipient_email) && is_email($recipient_email)) {
                $post_url = get_permalink($post_ID);
                $subject = 'New Post Published';
                $message = 'A new post has been published. Check it out: ' . $post_url;

                wp_mail($recipient_email, $subject, $message);

                // Clear the marker
                delete_post_meta($post_ID, '_acpae_ready_to_email');
            }
        }
    }



}

/**
 *
 * Load the plugin
 *
 */
add_action( 'plugins_loaded', 'acpae_load', 10, 0 );
function acpae_load(){
    new ACPAE_PostAutoEmailer();
}