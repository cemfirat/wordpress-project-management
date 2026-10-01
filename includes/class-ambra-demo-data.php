<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

final class Demo_Data {
    private const OPTION = 'ambra_pm_demo_seeded_v2';

    public static function init(): void {
        add_action( 'init', array( __CLASS__, 'seed_once' ), 40 );
        add_action( 'admin_post_ambra_seed_demo', array( __CLASS__, 'seed_action' ) );
        add_action( 'admin_post_ambra_delete_demo', array( __CLASS__, 'delete_action' ) );
    }

    public static function seed_once(): void {
        if ( ! ACF_Integration::is_ready() || get_option( self::OPTION ) ) {
            return;
        }

        $existing = get_posts(
            array(
                'post_type'      => array_keys( Post_Types::definitions() ),
                'post_status'    => array( 'private', 'publish', 'draft', 'trash' ),
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_key'       => '_ambra_pm_demo',
                'meta_value'     => '1',
            )
        );

        if ( ! $existing ) {
            self::seed();
        }
        update_option( self::OPTION, '1', false );
    }

    public static function seed(): array {
        $author   = self::author();
        $callback = self::seed_callback();
        $result   = call_user_func( $callback, $author );

        update_option( self::OPTION, '1', false );

        return is_array( $result ) ? $result : array();
    }

    /**
     * Resolve the active demo-data provider.
     *
     * The existing AMBRA/sun-protection dataset remains the default profile.
     * Integrations can replace it without changing the lifecycle, cleanup or
     * persisted _ambra_pm_demo marker used by the core.
     */
    public static function seed_callback(): callable {
        $callback = array( Demo_Profile::class, 'seed' );

        if ( function_exists( 'apply_filters' ) ) {
            $filtered = apply_filters( 'ambra_pm_demo_seed_callback', $callback );
            if ( is_callable( $filtered ) ) {
                return $filtered;
            }
        }

        return $callback;
    }

    private static function author(): int {
        $author = get_current_user_id();
        if ( $author ) {
            return (int) $author;
        }

        $admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ids' ) );
        return $admins ? (int) $admins[0] : 1;
    }

    /**
     * Create one demo record using the stable AMBRA storage schema.
     *
     * Demo profiles may call this helper so marker/record synchronization stays
     * in the core lifecycle instead of being duplicated by each profile.
     */
    public static function create_record( string $post_type, string $title, array $fields, int $author ): int {
        if ( ! Utils::is_ambra_post_type( $post_type ) ) {
            return 0;
        }

        $post_id = wp_insert_post(
            array(
                'post_type'   => $post_type,
                'post_status' => 'private',
                'post_title'  => $title,
                'post_author' => $author,
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            return 0;
        }

        update_post_meta( $post_id, '_ambra_pm_demo', '1' );
        foreach ( $fields as $name => $value ) {
            Utils::update_field( (string) $name, $value, (int) $post_id );
        }
        Records::sync_record( (int) $post_id );

        return (int) $post_id;
    }

    public static function delete_demo_data(): int {
        $ids = get_posts(
            array(
                'post_type'      => array_keys( Post_Types::definitions() ),
                'post_status'    => array( 'private', 'publish', 'draft', 'trash' ),
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_key'       => '_ambra_pm_demo',
                'meta_value'     => '1',
            )
        );

        foreach ( $ids as $id ) {
            wp_delete_post( (int) $id, true );
        }
        delete_option( self::OPTION );
        return count( $ids );
    }

    public static function count(): int {
        $ids = get_posts(
            array(
                'post_type'      => array_keys( Post_Types::definitions() ),
                'post_status'    => array( 'private', 'publish', 'draft' ),
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_key'       => '_ambra_pm_demo',
                'meta_value'     => '1',
            )
        );
        return count( $ids );
    }

    public static function seed_action(): void {
        if ( ! current_user_can( 'ambra_manage_settings' ) ) {
            wp_die( 'Keine Berechtigung.' );
        }
        check_admin_referer( 'ambra_seed_demo' );
        self::delete_demo_data();
        self::seed();
        wp_safe_redirect( admin_url( 'admin.php?page=ambra-project-management&demo=seeded' ) );
        exit;
    }

    public static function delete_action(): void {
        if ( ! current_user_can( 'ambra_manage_settings' ) ) {
            wp_die( 'Keine Berechtigung.' );
        }
        check_admin_referer( 'ambra_delete_demo' );
        self::delete_demo_data();
        update_option( self::OPTION, '1', false );
        wp_safe_redirect( admin_url( 'admin.php?page=ambra-project-management&demo=deleted' ) );
        exit;
    }
}
