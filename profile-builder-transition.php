<?php


if( !class_exists('PB_Handle_Transition') ){
    class PB_Handle_Transition{

        function __construct(){

            add_action( 'admin_notices', array( $this, 'pb_main_plugin_notice' ) );

        }


        /**
         * Add notice related to the transition to Profile Builder
         */
        function pb_main_plugin_notice(){
            $pb_installation_status = $this->install_activate_pb();
            if ( $pb_installation_status !== 'no_action_requested' ){
                if ( $pb_installation_status === 'plugin_activated' ) {
                    echo '<div class="notice updated is-dismissible "><p>' . esc_html__('Plugin activated.', 'profile-builder') . '</p></div>';
                }
                if ( $pb_installation_status === 'error_activating' ) {
                    echo '<div class="notice notice-error is-dismissible "><p>' . wp_kses( sprintf( __('Could not install. Try again from <a href="%s" >Plugins Dashboard.</a>', 'profile-builder'), admin_url('plugins.php') ), array('a' => array( 'href' => array() ) ) ) . '</p></div>';
                }
            }

            if( !defined( 'PROFILE_BUILDER_VERSION' ) ){
                if ( $pb_installation_status === 'no_action_requested') {
                    echo '<div class="notice notice-info is-dismissible"><p>';
                    echo '<strong>User Profile Picture</strong></p><p>';
                    printf( esc_html__( 'The User Profile Picture functionality has been migrated into Profile Builder as an add-on. Please install and activate the Profile Builder plugin to use this new add-on.', 'profile-builder' ) );
                    echo '</p>';
                    echo '<p>';
                    printf( esc_html__( 'This plugin will continue to function as it is now, but it will not receive further updates. You can read more about this transition in', 'profile-builder' ) );
                    echo ' ';
                    echo '<a href="https://www.cozmoslabs.com/user-profile-picture/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'this', 'profile-builder' ) . '</a>';
                    echo ' ';
                    wp_kses( printf( esc_html__( "section of Profile Builder's Documentation.", 'profile-builder' ) ), array('a' => array( 'href' => array() ) ) );
                    echo '</p>';
                    echo '<p><a href="' . esc_url( add_query_arg( array( 'action' => 'pb_install_pb_plugin', 'nonce' => wp_create_nonce( 'pb_install_pb_plugin' ) ) ) ) . '" type="button" class="button-primary">' . esc_html__( 'Install & Activate', 'profile-builder' ) . '</a></p>';
                    echo '</div>';
                }
            }
            else{
                if( version_compare( PROFILE_BUILDER_VERSION, '3.11.7', '<' ) ){
                    echo '<div class="notice notice-info is-dismissible"><p>';
                    echo esc_html( __('The User Profile Picture functionality has been migrated into Profile Builder as an add-on. Please update the Profile Builder plugin to at least version 3.11.7 to make use of this new add-on.', 'profile-builder') );
                    echo '</p></div>';
                }
            }
        }

        /**
         * If action and nonce are set, attempt installing and activating PB Free
         *
         * @return string 'no_action_requested' || 'error_activating' || 'plugin_activated'
         */
        public function install_activate_pb(){
            if ( isset( $_REQUEST['pb_install_pb_plugin_success'] ) && $_REQUEST['pb_install_pb_plugin_success'] === 'true' ){
                return 'plugin_activated';
            }

            if (
                isset( $_REQUEST['action'] ) && !empty($_REQUEST['nonce']) && $_REQUEST['action'] === 'pb_install_pb_plugin' &&
                !isset( $_REQUEST['pb_install_pb_plugin_success']) &&
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
                    $activate = activate_plugin( $plugin_slug );

                    if ( is_null( $activate ) ) {
                        wp_safe_redirect( add_query_arg( 'pb_install_pb_plugin_success', 'true' ) );
                        return 'plugin_activated';
                    }
                }

                return 'error_activating';
            }

            return 'no_action_requested';
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
