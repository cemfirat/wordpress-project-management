<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

final class Team_Sync {
    private static bool $syncing = false;

    public static function init(): void {
        add_action( 'acf/save_post', array( __CLASS__, 'sync_from_team' ), 35 );
        add_action( 'user_register', array( __CLASS__, 'sync_from_user' ), 30 );
        add_action( 'profile_update', array( __CLASS__, 'sync_from_user' ), 30 );
        add_action( 'set_user_role', array( __CLASS__, 'user_role_changed' ), 30, 3 );
        add_action( 'add_user_role', array( __CLASS__, 'user_role_added' ), 30, 2 );
    }

    public static function sync_from_team( $post_id ): void {
        $post_id = absint( $post_id );
        if ( ! $post_id || self::$syncing || 'ambra_team' !== get_post_type( $post_id ) ) {
            return;
        }

        $email = sanitize_email( (string) Utils::field( 'ambra_email', $post_id ) );
        if ( ! $email ) {
            return;
        }

        self::$syncing = true;

        $first          = sanitize_text_field( (string) Utils::field( 'ambra_first_name', $post_id ) );
        $last           = sanitize_text_field( (string) Utils::field( 'ambra_last_name', $post_id ) );
        $linked_user_id = Utils::relation_id( Utils::field( 'ambra_linked_user', $post_id ) );
        $user           = $linked_user_id ? get_user_by( 'id', $linked_user_id ) : false;

        if ( ! $user ) {
            $user = get_user_by( 'email', $email );
        }

        if ( ! $user ) {
            $username = self::unique_username( $email, $first, $last );
            $user_id  = wp_insert_user(
                array(
                    'user_login'   => $username,
                    'user_email'   => $email,
                    'user_pass'    => wp_generate_password( 32, true, true ),
                    'first_name'   => $first,
                    'last_name'    => $last,
                    'display_name' => trim( $first . ' ' . $last ) ?: $email,
                    'role'         => 'project_team_member',
                )
            );

            if ( ! is_wp_error( $user_id ) ) {
                $user = get_user_by( 'id', $user_id );
                if ( function_exists( 'wp_new_user_notification' ) ) {
                    wp_new_user_notification( $user_id, null, 'user' );
                }
            }
        } else {
            $update = array(
                'ID'           => $user->ID,
                'first_name'   => $first,
                'last_name'    => $last,
                'display_name' => trim( $first . ' ' . $last ) ?: $user->display_name,
            );
            if ( $email !== $user->user_email && ! email_exists( $email ) ) {
                $update['user_email'] = $email;
            }
            wp_update_user( $update );

            if ( ! in_array( 'administrator', (array) $user->roles, true ) && ! in_array( 'project_team_member', (array) $user->roles, true ) ) {
                $user->add_role( 'project_team_member' );
            }
        }

        if ( $user instanceof \WP_User ) {
            Utils::update_field( 'ambra_linked_user', (int) $user->ID, $post_id );
            update_user_meta( (int) $user->ID, 'ambra_team_post_id', $post_id );
        }

        self::$syncing = false;
    }

    public static function sync_from_user( int $user_id ): void {
        if ( self::$syncing ) {
            return;
        }

        $user = get_user_by( 'id', $user_id );
        if ( ! $user || ! in_array( 'project_team_member', (array) $user->roles, true ) ) {
            return;
        }

        self::$syncing = true;
        $team_id = self::find_team_for_user( $user );

        if ( ! $team_id ) {
            $team_id = wp_insert_post(
                array(
                    'post_type'   => 'ambra_team',
                    'post_status' => 'private',
                    'post_title'  => $user->display_name ?: $user->user_login,
                    'post_author' => $user_id,
                ),
                true
            );
            if ( is_wp_error( $team_id ) ) {
                self::$syncing = false;
                return;
            }
        }

        Utils::update_field( 'ambra_first_name', (string) $user->first_name, (int) $team_id );
        Utils::update_field( 'ambra_last_name', (string) $user->last_name, (int) $team_id );
        Utils::update_field( 'ambra_email', (string) $user->user_email, (int) $team_id );
        Utils::update_field( 'ambra_linked_user', $user_id, (int) $team_id );
        update_user_meta( $user_id, 'ambra_team_post_id', (int) $team_id );

        $title = trim( $user->first_name . ' ' . $user->last_name );
        wp_update_post(
            array(
                'ID'          => (int) $team_id,
                'post_title'  => $title ?: $user->display_name,
                'post_status' => 'private',
            )
        );

        self::$syncing = false;
    }

    public static function user_role_changed( int $user_id, string $role, array $old_roles ): void {
        if ( 'project_team_member' === $role ) {
            self::sync_from_user( $user_id );
        }
    }

    public static function user_role_added( int $user_id, string $role ): void {
        if ( 'project_team_member' === $role ) {
            self::sync_from_user( $user_id );
        }
    }


    public static function sync_existing_team_records(): void {
        $team_ids = get_posts(
            array(
                'post_type'      => 'ambra_team',
                'post_status'    => 'private',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            )
        );
        foreach ( $team_ids as $team_id ) {
            self::sync_from_team( (int) $team_id );
        }
    }

    public static function sync_existing_project_members(): void {
        $users = get_users(
            array(
                'role'   => 'project_team_member',
                'fields' => array( 'ID' ),
            )
        );
        foreach ( $users as $user ) {
            self::sync_from_user( (int) $user->ID );
        }
    }

    private static function find_team_for_user( \WP_User $user ): int {
        $stored = absint( get_user_meta( $user->ID, 'ambra_team_post_id', true ) );
        if ( $stored && 'ambra_team' === get_post_type( $stored ) && 'trash' !== get_post_status( $stored ) ) {
            return $stored;
        }

        $by_user = get_posts(
            array(
                'post_type'      => 'ambra_team',
                'post_status'    => 'private',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_key'       => 'ambra_linked_user',
                'meta_value'     => $user->ID,
            )
        );
        if ( $by_user ) {
            return (int) $by_user[0];
        }

        $by_email = get_posts(
            array(
                'post_type'      => 'ambra_team',
                'post_status'    => 'private',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_key'       => 'ambra_email',
                'meta_value'     => $user->user_email,
            )
        );
        return $by_email ? (int) $by_email[0] : 0;
    }

    private static function unique_username( string $email, string $first, string $last ): string {
        $base = sanitize_user( strtolower( trim( $first . '.' . $last, '.' ) ), true );
        if ( ! $base ) {
            $base = sanitize_user( strtok( $email, '@' ), true );
        }
        if ( ! $base ) {
            $base = 'ambra-team';
        }

        $username = $base;
        $suffix   = 1;
        while ( username_exists( $username ) ) {
            $username = $base . '-' . $suffix;
            ++$suffix;
        }
        return $username;
    }
}
