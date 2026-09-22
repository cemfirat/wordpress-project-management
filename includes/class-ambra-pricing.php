<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

final class Pricing {
    private static bool $calculating = false;

    public static function init(): void {
        add_action( 'acf/save_post', array( __CLASS__, 'calculate_position' ), 45 );
    }

    public static function calculate_position( $post_id ): void {
        $post_id = absint( $post_id );
        if ( ! $post_id || self::$calculating || 'ambra_position' !== get_post_type( $post_id ) ) {
            return;
        }

        $project_id = Utils::relation_id( Utils::field( 'ambra_position_project', $post_id ) );
        if ( $project_id && Workflow::is_at_or_after( $project_id, 'quote_sent' ) ) {
            self::restore_locked_position( $post_id );
            Records::sync_record( $post_id );
            return;
        }

        self::$calculating = true;

        $blueprint_id    = Utils::relation_id( Utils::field( 'ambra_position_blueprint', $post_id ) );
        $quantity        = max( 0.01, (float) Utils::field( 'ambra_quantity', $post_id, 1 ) );
        $width_mm        = max( 0, (float) Utils::field( 'ambra_width_mm', $post_id, 0 ) );
        $height_mm       = max( 0, (float) Utils::field( 'ambra_height_mm', $post_id, 0 ) );
        $manual_override = (bool) Utils::field( 'ambra_manual_price_override', $post_id, false );
        $sale_unit       = max( 0, (float) Utils::field( 'ambra_sale_unit_price', $post_id, 0 ) );
        $internal_unit   = 0.0;
        $markup          = 0.0;
        $model           = 'manual';
        $manufacturer_id = 0;

        if ( $blueprint_id ) {
            $base_internal  = max( 0, (float) Utils::field( 'ambra_blueprint_internal_price', $blueprint_id, 0 ) );
            $markup         = (float) Utils::field( 'ambra_blueprint_markup_percent', $blueprint_id, 0 );
            $model          = (string) Utils::field( 'ambra_blueprint_price_model', $blueprint_id, 'fixed' );
            $manufacturer_id = Utils::relation_id( Utils::field( 'ambra_blueprint_manufacturer', $blueprint_id ) );

            if ( 'sqm' === $model ) {
                $area_m2       = ( $width_mm * $height_mm ) / 1000000;
                $internal_unit = $base_internal * $area_m2;
            } else {
                $internal_unit = $base_internal;
            }

            if ( ! $manual_override ) {
                $sale_unit = $internal_unit * ( 1 + ( $markup / 100 ) );
            }

            self::copy_configuration_defaults( $blueprint_id, $post_id );
        }

        $line_total = $sale_unit * $quantity;

        Utils::update_field( 'ambra_internal_cost_snapshot', round( $internal_unit, 2 ), $post_id );
        Utils::update_field( 'ambra_markup_snapshot', round( $markup, 2 ), $post_id );
        Utils::update_field( 'ambra_price_model_snapshot', $model, $post_id );
        Utils::update_field( 'ambra_manufacturer_snapshot', $manufacturer_id ?: '', $post_id );
        Utils::update_field( 'ambra_sale_unit_price', round( $sale_unit, 2 ), $post_id );
        Utils::update_field( 'ambra_line_total', round( $line_total, 2 ), $post_id );

        $status = (string) Utils::field( 'ambra_position_status', $post_id, 'recorded' );
        if ( $line_total > 0 && 'recorded' === $status ) {
            Utils::update_field( 'ambra_position_status', 'calculated', $post_id );
        }

        self::$calculating = false;
    }

    private static function copy_configuration_defaults( int $blueprint_id, int $position_id ): void {
        $existing = Utils::field( 'ambra_position_configuration', $position_id, array() );
        if ( is_array( $existing ) && ! empty( $existing ) ) {
            return;
        }

        $schema = Utils::field( 'ambra_blueprint_config_schema', $blueprint_id, array() );
        if ( ! is_array( $schema ) || empty( $schema ) ) {
            return;
        }

        $rows = array();
        foreach ( $schema as $item ) {
            if ( empty( $item['ambra_config_label'] ) ) {
                continue;
            }
            $rows[] = array(
                'ambra_position_config_label' => sanitize_text_field( (string) $item['ambra_config_label'] ),
                'ambra_position_config_key'   => sanitize_key( (string) ( $item['ambra_config_key'] ?? '' ) ),
                'ambra_position_config_value' => sanitize_text_field( (string) ( $item['ambra_config_default'] ?? '' ) ),
            );
        }
        if ( $rows ) {
            Utils::update_field( 'ambra_position_configuration', $rows, $position_id );
        }
    }

    public static function project_total( int $project_id ): float {
        $total = 0.0;
        foreach ( self::project_positions( $project_id ) as $position_id ) {
            if ( 'cancelled' === Utils::field( 'ambra_position_status', $position_id ) ) {
                continue;
            }
            $total += (float) Utils::field( 'ambra_line_total', $position_id, 0 );
        }
        return round( $total, 2 );
    }

    public static function project_positions( int $project_id ): array {
        return array_map(
            'absint',
            get_posts(
                array(
                    'post_type'      => 'ambra_position',
                    'post_status'    => 'private',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'orderby'        => 'meta_value_num',
                    'meta_key'       => 'ambra_position_number',
                    'order'          => 'ASC',
                    'meta_query'     => array(
                        array(
                            'key'   => 'ambra_position_project',
                            'value' => $project_id,
                        ),
                    ),
                )
            )
        );
    }

    public static function lock_project( int $project_id ): void {
        $customer_id = Utils::relation_id( Utils::field( 'ambra_project_customer', $project_id ) );
        if ( $customer_id ) {
            update_post_meta( $project_id, '_ambra_locked_customer', $customer_id );
        }

        foreach ( self::project_positions( $project_id ) as $position_id ) {
            $snapshot = array();
            foreach ( get_post_meta( $position_id ) as $meta_key => $values ) {
                if ( self::is_commercial_meta_key( (string) $meta_key ) ) {
                    $snapshot[ (string) $meta_key ] = isset( $values[0] ) ? maybe_unserialize( $values[0] ) : '';
                }
            }
            update_post_meta( $position_id, '_ambra_locked_commercial', $snapshot );
        }
    }

    public static function restore_locked_position( int $position_id ): void {
        $snapshot = get_post_meta( $position_id, '_ambra_locked_commercial', true );
        if ( ! is_array( $snapshot ) || ! $snapshot ) {
            return;
        }

        foreach ( array_keys( get_post_meta( $position_id ) ) as $meta_key ) {
            if ( self::is_commercial_meta_key( (string) $meta_key ) && ! array_key_exists( $meta_key, $snapshot ) ) {
                delete_post_meta( $position_id, (string) $meta_key );
            }
        }
        foreach ( $snapshot as $meta_key => $value ) {
            update_post_meta( $position_id, (string) $meta_key, $value );
        }
    }

    private static function is_commercial_meta_key( string $meta_key ): bool {
        $normalized = ltrim( $meta_key, '_' );
        foreach ( self::commercial_meta_keys() as $base_key ) {
            if ( $normalized === $base_key || str_starts_with( $normalized, $base_key . '_' ) ) {
                return true;
            }
        }
        return false;
    }

    private static function commercial_meta_keys(): array {
        return array(
            'ambra_position_project',
            'ambra_position_visit',
            'ambra_position_number',
            'ambra_position_label',
            'ambra_position_room',
            'ambra_quantity',
            'ambra_width_mm',
            'ambra_height_mm',
            'ambra_position_blueprint',
            'ambra_position_configuration',
            'ambra_manual_price_override',
            'ambra_internal_cost_snapshot',
            'ambra_markup_snapshot',
            'ambra_price_model_snapshot',
            'ambra_manufacturer_snapshot',
            'ambra_sale_unit_price',
            'ambra_line_total',
            'ambra_position_status',
        );
    }
}
