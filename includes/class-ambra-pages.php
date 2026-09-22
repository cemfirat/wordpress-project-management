<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

final class Pages {
    public const OPTION = 'ambra_pm_pages';

    public static function init(): void {
        add_action( 'admin_post_ambra_repair_pages', array( __CLASS__, 'repair_pages_action' ) );
        add_filter( 'the_title', array( __CLASS__, 'hide_system_page_title' ), 20, 2 );
    }

    public static function definitions(): array {
        return array(
            'login'         => array(
                'title'     => 'Login',
                'slug'      => 'login',
                'shortcode' => '[ambra_login]',
            ),
            'dashboard'     => array(
                'title'        => 'Dashboard',
                'slug'         => 'dashboard',
                'legacy_slugs' => array( 'ambra-dashboard', 'ambra' ),
                'shortcode'    => '[ambra_dashboard]',
            ),
            'projects'      => array( 'title' => 'Aufträge', 'slug' => 'auftraege', 'shortcode' => '[ambra_projects]' ),
            'customers'     => array( 'title' => 'Kunden', 'slug' => 'kunden', 'shortcode' => '[ambra_customers]' ),
            'visits'        => array( 'title' => 'Besichtigungen', 'slug' => 'besichtigungen', 'shortcode' => '[ambra_visits]' ),
            'positions'     => array( 'title' => 'Auftragspositionen', 'slug' => 'positionen', 'shortcode' => '[ambra_positions]' ),
            'blueprints'    => array( 'title' => 'Produkt-Blueprints', 'slug' => 'produkt-blueprints', 'shortcode' => '[ambra_blueprints]' ),
            'manufacturers' => array( 'title' => 'Hersteller', 'slug' => 'hersteller', 'shortcode' => '[ambra_manufacturers]' ),
            'suppliers'     => array( 'title' => 'Lieferanten', 'slug' => 'lieferanten', 'shortcode' => '[ambra_suppliers]' ),
            'team'          => array( 'title' => 'Team', 'slug' => 'team', 'shortcode' => '[ambra_team]' ),
        );
    }

    public static function create_pages(): array {
        $stored = (array) get_option( self::OPTION, array() );
        $result = array();

        foreach ( self::definitions() as $key => $definition ) {
            $page_id = self::locate_page( $key, $definition, $stored );

            if ( ! $page_id ) {
                $page_id = wp_insert_post(
                    array(
                        'post_type'    => 'page',
                        'post_status'  => 'publish',
                        'post_title'   => $definition['title'],
                        'post_name'    => $definition['slug'],
                        'post_content' => $definition['shortcode'],
                        'post_parent'  => 0,
                    ),
                    true
                );

                if ( is_wp_error( $page_id ) ) {
                    continue;
                }
            }

            $page_id = (int) $page_id;
            self::normalize_system_page( $page_id, $key, $definition );
            $result[ $key ] = $page_id;
        }

        update_option( self::OPTION, $result, false );
        self::set_dashboard_as_front_page( $result );

        return $result;
    }

    private static function locate_page( string $key, array $definition, array $stored ): int {
        $stored_id = isset( $stored[ $key ] ) ? absint( $stored[ $key ] ) : 0;
        if ( $stored_id && 'page' === get_post_type( $stored_id ) && 'trash' !== get_post_status( $stored_id ) ) {
            return $stored_id;
        }

        $meta_matches = get_posts(
            array(
                'post_type'      => 'page',
                'post_status'    => array( 'publish', 'private', 'draft' ),
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_key'       => '_ambra_pm_page',
                'meta_value'     => $key,
            )
        );
        if ( $meta_matches ) {
            return (int) $meta_matches[0];
        }

        $slugs = array_merge(
            array( (string) $definition['slug'] ),
            isset( $definition['legacy_slugs'] ) ? (array) $definition['legacy_slugs'] : array()
        );

        foreach ( array_unique( array_filter( $slugs ) ) as $slug ) {
            $existing = get_page_by_path( $slug, OBJECT, 'page' );
            if ( $existing && 'trash' !== get_post_status( $existing->ID ) ) {
                return (int) $existing->ID;
            }
        }

        return 0;
    }

    private static function normalize_system_page( int $page_id, string $key, array $definition ): void {
        $page = get_post( $page_id );
        if ( ! $page || 'page' !== $page->post_type ) {
            return;
        }

        $changes = array( 'ID' => $page_id );

        if ( (string) $page->post_title !== (string) $definition['title'] ) {
            $changes['post_title'] = (string) $definition['title'];
        }
        if ( (string) $page->post_name !== (string) $definition['slug'] ) {
            $changes['post_name'] = (string) $definition['slug'];
        }
        if ( 0 !== (int) $page->post_parent ) {
            $changes['post_parent'] = 0;
        }
        if ( 'publish' !== (string) $page->post_status ) {
            $changes['post_status'] = 'publish';
        }

        // Do not touch post_content or YOOtheme metadata. Existing layouts stay intact.
        if ( count( $changes ) > 1 ) {
            wp_update_post( wp_slash( $changes ) );
        }

        update_post_meta( $page_id, '_ambra_pm_page', $key );
    }

    public static function top_level_count(): int {
        $count = 0;
        foreach ( (array) get_option( self::OPTION, array() ) as $page_id ) {
            $page = get_post( absint( $page_id ) );
            if ( $page && 'page' === $page->post_type && 0 === (int) $page->post_parent && 'trash' !== $page->post_status ) {
                ++$count;
            }
        }
        return $count;
    }

    public static function all_top_level(): bool {
        return self::top_level_count() === count( self::definitions() );
    }

    private static function set_dashboard_as_front_page( array $pages ): void {
        $dashboard_id = isset( $pages['dashboard'] ) ? absint( $pages['dashboard'] ) : 0;
        if ( ! $dashboard_id ) {
            return;
        }

        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $dashboard_id );
    }

    public static function get_id( string $key ): int {
        $pages = (array) get_option( self::OPTION, array() );
        return isset( $pages[ $key ] ) ? absint( $pages[ $key ] ) : 0;
    }

    public static function get_url( string $key, array $args = array() ): string {
        $id = self::get_id( $key );
        if ( $id ) {
            $url = get_permalink( $id );
        } elseif ( 'login' === $key ) {
            $url = home_url( '/login/' );
        } else {
            $url = home_url( '/' );
        }
        return $args ? add_query_arg( $args, $url ) : $url;
    }

    public static function is_login_page(): bool {
        $login_id = self::get_id( 'login' );
        return $login_id > 0 && is_page( $login_id );
    }

    public static function is_app_page(): bool {
        if ( ! is_page() && ! is_front_page() ) {
            return false;
        }

        $current = get_queried_object_id();
        return in_array( $current, array_map( 'absint', (array) get_option( self::OPTION, array() ) ), true );
    }

    public static function hide_system_page_title( string $title, int $post_id ): string {
        if ( is_admin() || ! ( is_page() || is_front_page() ) ) {
            return $title;
        }

        if ( $post_id === get_queried_object_id() && in_array( $post_id, array_map( 'absint', (array) get_option( self::OPTION, array() ) ), true ) ) {
            return '';
        }

        return $title;
    }

    public static function repair_pages_action(): void {
        if ( ! current_user_can( 'ambra_manage_settings' ) ) {
            wp_die( 'Keine Berechtigung.' );
        }

        check_admin_referer( 'ambra_repair_pages' );
        self::create_pages();
        flush_rewrite_rules( false );
        wp_safe_redirect( admin_url( 'admin.php?page=ambra-project-management&pages=repaired' ) );
        exit;
    }
}
