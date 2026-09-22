<?php
namespace AMBRA_PM;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class Privacy {
    public static function init(): void {
        add_action( 'template_redirect', array( __CLASS__, 'require_login' ), 0 );
        add_action( 'admin_init', array( __CLASS__, 'restrict_admin' ), 0 );
        add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 20, 3 );
        add_filter( 'show_admin_bar', array( __CLASS__, 'show_admin_bar' ) );
        add_filter( 'rest_authentication_errors', array( __CLASS__, 'restrict_rest' ), 99 );
        add_filter( 'xmlrpc_enabled', '__return_false' );
        add_filter( 'wp_sitemaps_enabled', '__return_false' );
        add_filter( 'wp_robots', array( __CLASS__, 'noindex' ) );
        remove_action( 'wp_head', 'rest_output_link_wp_head' );
        remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    }

    public static function configure_site(): void {
        update_option( 'blog_public', '0' );
    }

    public static function require_login(): void {
        if ( wp_doing_cron() || Pages::is_login_page() ) {
            return;
        }

        if ( is_user_logged_in() ) {
            return;
        }

        nocache_headers();
        wp_safe_redirect( Pages::get_url( 'login' ) );
        exit;
    }

    public static function restrict_admin(): void {
        if ( ! is_user_logged_in() || current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
            return;
        }

        global $pagenow;
        if ( in_array( (string) $pagenow, array( 'admin-post.php', 'async-upload.php' ), true ) ) {
            return;
        }

        wp_safe_redirect( home_url( '/' ) );
        exit;
    }

    public static function login_redirect( string $redirect_to, string $requested_redirect_to, $user ): string {
        unset( $requested_redirect_to );
        if ( ! $user instanceof \WP_User ) {
            return $redirect_to;
        }
        return home_url( '/' );
    }

    public static function show_admin_bar( bool $show ): bool {
        return current_user_can( 'manage_options' ) ? $show : false;
    }

    public static function restrict_rest( $result ) {
        if ( null !== $result && false !== $result ) {
            return $result;
        }
        if ( is_user_logged_in() ) {
            return $result;
        }
        return new WP_Error(
            'ambra_rest_login_required',
            'Anmeldung erforderlich.',
            array( 'status' => 401 )
        );
    }

    public static function noindex( array $robots ): array {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        return $robots;
    }
}
