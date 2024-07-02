<?php


if( !class_exists('PB_Handle_Transition') ){
    class PB_Handle_Transition{

        public static $pb_installation_status = '';
        function __construct(){

            add_action( 'admin_init', array( $this, 'install_activate_pb' ) );
            add_action( 'admin_notices', array( $this, 'pb_main_plugin_notice' ) );

        }


        /**
         * Add notice related to the transition to Profile Builder
         */
        function pb_main_plugin_notice(){

            global $pagenow;
            if ( $pagenow !== 'plugins.php' ) {
                return;
            }

            if ( isset( $_REQUEST['upp_install_pb_plugin_success'] ) ){
                if ( $_REQUEST['upp_install_pb_plugin_success'] === 'true' ){
                    echo '<div class="notice updated is-dismissible "><p>' . apply_filters( 'upp_plugin_activation_success_message', esc_html__('Plugin activated.', 'profile-builder') ) . '</p></div>';
                } else if ( $_REQUEST['upp_install_pb_plugin_success'] === 'false' ) {
                    echo '<div class="notice notice-error is-dismissible "><p>' . wp_kses( sprintf( apply_filters( 'upp_plugin_activation_fail_message', __('Could not install. Try again from the <a href="%s" >Plugins Dashboard.</a>', 'profile-builder') ), apply_filters( 'upp_plugin_activation_fail_link', admin_url('plugins.php') ) ), array('a' => array( 'href' => array() ) ) ) . '</p></div>';
                }
            } elseif( !defined( 'PROFILE_BUILDER_VERSION' ) ){
                echo '<div class="notice notice-info is-dismissible"><p>';
                echo '<strong>User Profile Picture</strong></p><p>';
                printf( apply_filters( 'upp_transition_notice_part_1', esc_html__( 'The User Profile Picture functionality has been migrated into Profile Builder as an add-on. Please install and activate the Profile Builder plugin to use this new add-on.', 'profile-builder' ) ) );
                echo '</p>';
                echo '<p>';
                printf( apply_filters( 'upp_transition_notice_part_2', esc_html__( 'This plugin will continue to function as it is now, but it will not receive further updates. You can read more about this transition in', 'profile-builder' ) ) );
                echo ' ';
                echo '<a href="' . apply_filters( 'upp_transition_notice_link_target', "https://www.cozmoslabs.com/user-profile-picture/" ) . '" target="_blank" rel="noopener noreferrer">' . apply_filters( 'upp_transition_notice_link_text', esc_html__( 'this', 'profile-builder' ) ) . '</a>';
                echo ' ';
                wp_kses( printf( apply_filters( 'upp_transition_notice_part_3', esc_html__( "section of Profile Builder's Documentation.", 'profile-builder' ) ) ), array('a' => array( 'href' => array() ) ) );
                echo '</p>';
                echo '<p><a href="' . esc_url( add_query_arg( array( 'action' => 'pb_install_pb_plugin', 'nonce' => wp_create_nonce( 'pb_install_pb_plugin' ) ) ) ) . '" type="button" class="button-primary">' . apply_filters( 'upp_transition_notice_button_text', esc_html__( 'Install & Activate', 'profile-builder' ) ) . '</a></p>';
                echo '</div>';
            } else {
                if( version_compare( PROFILE_BUILDER_VERSION, '3.11.7', '<' ) ){
                    echo '<div class="notice notice-info is-dismissible"><p>';
                    echo apply_filters( 'upp_transition_notice_update_pb', esc_html__('The User Profile Picture functionality has been migrated into Profile Builder as an add-on. Please update the Profile Builder plugin to at least version 3.11.7 to make use of this new add-on.', 'profile-builder') );
                    echo '</p></div>';
                } else {
                    echo '<div class="notice notice-info is-dismissible"><p>';
                    echo '<strong>User Profile Picture</strong></p><p>';
                    printf( apply_filters( 'upp_transition_notice_enable_add_on_part_1', esc_html__( 'The User Profile Picture functionality has been migrated into Profile Builder as an add-on. Do you wish to enable this new add-on and deactivate the User Profile Picture plugin?', 'profile-builder' ) ) );
                    echo '</p>';
                    echo '<p>';
                    printf( apply_filters( 'upp_transition_notice_enable_add_on_part_2', esc_html__( 'This plugin will continue to function as it is now, but it will not receive further updates. You can read more about this transition in', 'profile-builder' ) ) );
                    echo ' ';
                    echo '<a href="' . apply_filters( 'upp_transition_notice_enable_add_on_link_target', "https://www.cozmoslabs.com/user-profile-picture/" ) . '" target="_blank" rel="noopener noreferrer">' . apply_filters( 'upp_transition_notice_enable_add_on_link_text', esc_html__( 'this', 'profile-builder' ) ) . '</a>';
                    echo ' ';
                    wp_kses( printf( apply_filters( 'upp_transition_notice_enable_add_on_part_3', esc_html__( "section of Profile Builder's Documentation.", 'profile-builder' ) ) ), array('a' => array( 'href' => array() ) ) );
                    echo '</p>';
                    echo '<p><a href="' . esc_url( add_query_arg( array( 'action' => 'pb_install_pb_plugin', 'nonce' => wp_create_nonce( 'pb_install_pb_plugin' ) ) ) ) . '" type="button" class="button-primary">' . apply_filters( 'upp_transition_notice_enable_add_on_button_text', esc_html__( 'Activate the add-on', 'profile-builder' ) ) . '</a></p>';
                    echo '</div>';
                }
            }
        }

        /**
         * If action and nonce are set, attempt installing and activating PB Free
         *
         * @return string 'no_action_requested' || 'error_activating' || 'plugin_activated'
         */
        public function install_activate_pb(){
            if (
                isset( $_REQUEST['action'] ) && !empty($_REQUEST['nonce']) && $_REQUEST['action'] === 'pb_install_pb_plugin' &&
                !isset( $_REQUEST['upp_install_pb_plugin_success']) &&
                current_user_can( 'manage_options' ) &&
                wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'pb_install_pb_plugin' )
            ) {

                $plugin_slug = 'profile-builder-dev/index.php';

                $installed = true;
                if ( !$this->is_plugin_installed( $plugin_slug ) ){
                    $plugin_zip = 'https://downloads.wordpress.org/plugin/profile-builder.zip';
                    $installed = $this->install_plugin($plugin_zip);
                }

                if ( !is_wp_error( $installed ) && $installed ) {
                    $activate = activate_plugin( $plugin_slug, '', false, true );

                    if ( is_null( $activate ) ) {

                        // Enable the User Profile Picture add-on
                        $wppb_free_add_ons_settings = get_option( 'wppb_free_add_ons_settings', array() );
                        $wppb_free_add_ons_settings['user-profile-picture'] = true;
                        update_option( 'wppb_free_add_ons_settings', $wppb_free_add_ons_settings );

                        wp_safe_redirect( add_query_arg( 'upp_install_pb_plugin_success', 'true', admin_url( 'plugins.php' ) ) );
                        return;
                    }
                }
                wp_safe_redirect( add_query_arg( 'upp_install_pb_plugin_success', 'false', admin_url( 'plugins.php' ) ) );
                return;
            }
            return;
        }

        /**
         * Check if plugin is installed
         *
         * @param $plugin_slug
         * @return bool
         */
        public function is_plugin_installed( $plugin_slug ) {
            if ( !function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $all_plugins = get_plugins();

            if ( !empty( $all_plugins[ $plugin_slug ] ) ) {
                return true;
            }

            return false;
        }

        /**
         * Install plugin by providing downloadable zip address
         *
         * @param $plugin_zip
         * @return array|bool|WP_Error
         */
        public function install_plugin( $plugin_zip ) {
            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            wp_cache_flush();
            $upgrader  = new Plugin_Upgrader();

            // do not output any messages
            $upgrader->skin = new Automatic_Upgrader_Skin();

            $installed = $upgrader->install( $plugin_zip );
            return $installed;
        }

    }

    //initialize the handle of the included addons
    $pb_add_ons_handler = new PB_Handle_Transition();
}
