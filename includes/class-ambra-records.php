<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

final class Records {
    private static bool $updating = false;

    public static function init(): void {
        add_action( 'acf/save_post', array( __CLASS__, 'sync_record' ), 20 );
        add_filter( 'wp_insert_post_data', array( __CLASS__, 'force_private_status' ), 20, 2 );
    }

    public static function force_private_status( array $data, array $postarr ): array {
        if ( isset( $data['post_type'] ) && Utils::is_ambra_post_type( (string) $data['post_type'] ) ) {
            if ( ! in_array( $data['post_status'], array( 'trash', 'auto-draft', 'inherit' ), true ) ) {
                $data['post_status'] = 'private';
            }
        }
        return $data;
    }

    public static function sync_record( $post_id ): void {
        $post_id = absint( $post_id );
        if ( ! $post_id || self::$updating ) {
            return;
        }

        $post_type = (string) get_post_type( $post_id );
        if ( ! Utils::is_ambra_post_type( $post_type ) ) {
            return;
        }

        self::$updating = true;
        $title = '';

        switch ( $post_type ) {
            case 'ambra_customer':
                $company = trim( (string) Utils::field( 'ambra_company', $post_id ) );
                $person  = trim( (string) Utils::field( 'ambra_first_name', $post_id ) . ' ' . (string) Utils::field( 'ambra_last_name', $post_id ) );
                $title   = $company && $person ? $company . ' – ' . $person : ( $company ?: $person );
                break;

            case 'ambra_manufacturer':
            case 'ambra_supplier':
                $title = trim( (string) Utils::field( 'ambra_company', $post_id ) );
                break;

            case 'ambra_team':
                $title = trim( (string) Utils::field( 'ambra_first_name', $post_id ) . ' ' . (string) Utils::field( 'ambra_last_name', $post_id ) );
                break;

            case 'ambra_project':
                $title = trim( (string) Utils::field( 'ambra_project_title', $post_id ) );
                if ( ! Utils::field( 'ambra_project_number', $post_id ) ) {
                    Utils::update_field( 'ambra_project_number', self::project_number( $post_id ), $post_id );
                }
                if ( ! Utils::field( 'ambra_project_stage', $post_id ) ) {
                    Utils::update_field( 'ambra_project_stage', 'inquiry', $post_id );
                }
                if ( ! Utils::field( 'ambra_project_state', $post_id ) ) {
                    Utils::update_field( 'ambra_project_state', 'active', $post_id );
                }
                self::restore_locked_customer( $post_id );
                break;

            case 'ambra_blueprint':
                $title = trim( (string) Utils::field( 'ambra_blueprint_name', $post_id ) );
                break;

            case 'ambra_visit':
                $project_id = Utils::relation_id( Utils::field( 'ambra_visit_project', $post_id ) );
                $date       = (string) Utils::field( 'ambra_visit_datetime', $post_id );
                $title      = 'Besichtigung';
                if ( $project_id ) {
                    $title .= ' – ' . Utils::post_title( $project_id );
                }
                if ( $date ) {
                    $title .= ' – ' . Utils::date( $date );
                }
                break;

            case 'ambra_position':
                $project_id = Utils::relation_id( Utils::field( 'ambra_position_project', $post_id ) );
                $number     = absint( Utils::field( 'ambra_position_number', $post_id ) );
                if ( ! $number && $project_id ) {
                    $number = self::next_position_number( $project_id, $post_id );
                    Utils::update_field( 'ambra_position_number', $number, $post_id );
                }
                $label = trim( (string) Utils::field( 'ambra_position_label', $post_id ) );
                $title = sprintf( '#%02d – %s', max( 1, $number ), $label ?: 'Position' );
                if ( ! Utils::field( 'ambra_position_status', $post_id ) ) {
                    Utils::update_field( 'ambra_position_status', 'recorded', $post_id );
                }
                break;
        }

        if ( '' !== trim( $title ) ) {
            wp_update_post(
                array(
                    'ID'          => $post_id,
                    'post_title'  => wp_strip_all_tags( $title ),
                    'post_status' => 'private',
                )
            );
        }

        self::$updating = false;
    }

    private static function restore_locked_customer( int $project_id ): void {
        $locked = absint( get_post_meta( $project_id, '_ambra_locked_customer', true ) );
        if ( ! $locked || ! Workflow::is_at_or_after( $project_id, 'quote_sent' ) ) {
            return;
        }
        $current = Utils::relation_id( Utils::field( 'ambra_project_customer', $project_id ) );
        if ( $current !== $locked ) {
            Utils::update_field( 'ambra_project_customer', $locked, $project_id );
        }
    }

    private static function project_number( int $post_id ): string {
        return sprintf( 'AMB-%s-%06d', wp_date( 'Y' ), $post_id );
    }

    private static function next_position_number( int $project_id, int $exclude_id ): int {
        $ids = get_posts(
            array(
                'post_type'      => 'ambra_position',
                'post_status'    => 'private',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'post__not_in'   => array( $exclude_id ),
                'meta_query'     => array(
                    array(
                        'key'     => 'ambra_position_project',
                        'value'   => $project_id,
                        'compare' => '=',
                    ),
                ),
            )
        );

        $max = 0;
        foreach ( $ids as $id ) {
            $max = max( $max, absint( Utils::field( 'ambra_position_number', (int) $id ) ) );
        }
        return $max + 1;
    }
}
