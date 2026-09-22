<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

final class ACF_Integration {
    public static function init(): void {
        add_filter( 'acf/settings/load_json', array( __CLASS__, 'load_json_paths' ) );
        add_action( 'template_redirect', array( __CLASS__, 'prepare_frontend_forms' ), 1 );

        foreach ( self::system_field_names() as $field_name ) {
            add_filter( 'acf/prepare_field/name=' . $field_name, array( __CLASS__, 'prepare_system_field' ) );
            add_filter( 'acf/update_value/name=' . $field_name, array( __CLASS__, 'protect_system_update' ), 5, 4 );
        }
        foreach ( self::internal_price_field_names() as $field_name ) {
            add_filter( 'acf/prepare_field/name=' . $field_name, array( __CLASS__, 'prepare_internal_price_field' ) );
            add_filter( 'acf/update_value/name=' . $field_name, array( __CLASS__, 'protect_internal_price_update' ), 5, 4 );
        }
        foreach ( self::finance_field_names() as $field_name ) {
            add_filter( 'acf/prepare_field/name=' . $field_name, array( __CLASS__, 'prepare_finance_field' ) );
            add_filter( 'acf/update_value/name=' . $field_name, array( __CLASS__, 'protect_finance_update' ), 5, 4 );
        }

        add_filter( 'acf/prepare_field/name=ambra_linked_user', array( __CLASS__, 'prepare_linked_user_field' ) );
        add_filter( 'acf/update_value/name=ambra_linked_user', array( __CLASS__, 'protect_system_update' ), 5, 4 );
        add_filter( 'acf/prepare_field/name=ambra_visit_project', array( __CLASS__, 'prefill_project_relation' ) );
        add_filter( 'acf/prepare_field/name=ambra_position_project', array( __CLASS__, 'prefill_project_relation' ) );
        add_filter( 'acf/fields/post_object/query/name=ambra_position_visit', array( __CLASS__, 'filter_position_visits' ), 10, 3 );
        add_filter( 'acf/validate_value/key=field_ambra_position_project', array( __CLASS__, 'validate_position_project' ), 10, 4 );
        add_filter( 'acf/validate_value/key=field_ambra_team_email', array( __CLASS__, 'validate_team_email' ), 10, 4 );
    }

    public static function load_json_paths( array $paths ): array {
        $paths[] = AMBRA_PM_DIR . 'acf-json';
        return array_values( array_unique( $paths ) );
    }

    public static function prepare_frontend_forms(): void {
        if ( ! is_user_logged_in() || ! Pages::is_app_page() || ! current_user_can( 'ambra_use_app' ) ) {
            return;
        }
        if ( function_exists( 'acf_form_head' ) ) {
            acf_form_head();
        }
    }

    private static function system_field_names(): array {
        return array(
            'ambra_project_number',
            'ambra_project_stage',
            'ambra_project_state',
            'ambra_quote_sent_at',
            'ambra_quote_decision',
            'ambra_quote_decision_at',
            'ambra_ordered_at',
            'ambra_completed_at',
            'ambra_position_number',
            'ambra_position_status',
            'ambra_internal_cost_snapshot',
            'ambra_markup_snapshot',
            'ambra_price_model_snapshot',
            'ambra_manufacturer_snapshot',
            'ambra_line_total',
        );
    }

    private static function internal_price_field_names(): array {
        return array(
            'ambra_blueprint_internal_price',
            'ambra_blueprint_markup_percent',
            'ambra_internal_cost_snapshot',
            'ambra_markup_snapshot',
        );
    }

    private static function finance_field_names(): array {
        return array(
            'ambra_deposit_amount',
            'ambra_deposit_invoice_number',
            'ambra_deposit_invoice_file',
            'ambra_deposit_invoice_sent_at',
            'ambra_deposit_paid_at',
            'ambra_final_amount',
            'ambra_final_invoice_number',
            'ambra_final_invoice_file',
            'ambra_final_invoice_sent_at',
            'ambra_final_paid_at',
        );
    }



    public static function protect_internal_price_update( $value, $post_id, array $field, $original ) {
        return self::protect_capability_update( $value, $post_id, $field, 'ambra_view_internal_prices' );
    }

    public static function protect_finance_update( $value, $post_id, array $field, $original ) {
        return self::protect_capability_update( $value, $post_id, $field, 'ambra_manage_finance' );
    }

    private static function protect_capability_update( $value, $post_id, array $field, string $capability ) {
        if ( Utils::is_internal_write() || current_user_can( $capability ) ) {
            return $value;
        }
        $post_id = absint( $post_id );
        return $post_id ? get_post_meta( $post_id, (string) $field['name'], true ) : '';
    }

    public static function protect_system_update( $value, $post_id, array $field, $original ) {
        if ( Utils::is_internal_write() || is_admin() || doing_action( 'acf/save_post' ) ) {
            return $value;
        }
        $post_id = absint( $post_id );
        if ( ! $post_id ) {
            return '';
        }
        return get_post_meta( $post_id, (string) $field['name'], true );
    }

    public static function prepare_system_field( $field ) {
        if ( ! is_array( $field ) ) {
            return $field;
        }
        if ( ! is_admin() ) {
            return false;
        }
        $field['readonly'] = 1;
        $field['disabled'] = 1;
        return $field;
    }

    public static function prepare_internal_price_field( $field ) {
        if ( ! is_array( $field ) ) {
            return $field;
        }
        if ( ! current_user_can( 'ambra_view_internal_prices' ) ) {
            return false;
        }
        return $field;
    }

    public static function prepare_finance_field( $field ) {
        if ( ! is_array( $field ) ) {
            return $field;
        }
        if ( ! current_user_can( 'ambra_manage_finance' ) ) {
            return false;
        }
        return $field;
    }

    public static function prepare_linked_user_field( $field ) {
        if ( ! is_array( $field ) ) {
            return $field;
        }
        if ( ! is_admin() ) {
            return false;
        }
        $field['readonly'] = 1;
        $field['disabled'] = 1;
        $field['instructions'] = 'Wird automatisch über die E-Mail-Adresse synchronisiert.';
        return $field;
    }

    public static function prefill_project_relation( array $field ): array {
        if ( ! empty( $field['value'] ) || empty( $_GET['project'] ) ) {
            return $field;
        }
        $project_id = absint( wp_unslash( $_GET['project'] ) );
        if ( $project_id && 'ambra_project' === get_post_type( $project_id ) && current_user_can( 'read_ambra_record', $project_id ) ) {
            $field['default_value'] = $project_id;
        }
        return $field;
    }

    public static function filter_position_visits( array $args, array $field, $post_id ): array {
        $project_id = 0;
        if ( ! empty( $_GET['project'] ) ) {
            $project_id = absint( wp_unslash( $_GET['project'] ) );
        }
        if ( ! $project_id && is_numeric( $post_id ) && 'ambra_position' === get_post_type( (int) $post_id ) ) {
            $project_id = Utils::relation_id( Utils::field( 'ambra_position_project', (int) $post_id ) );
        }
        if ( $project_id ) {
            $args['meta_query'] = array(
                array(
                    'key'     => 'ambra_visit_project',
                    'value'   => $project_id,
                    'compare' => '=',
                ),
            );
        }
        return $args;
    }

    public static function frontend_fields( string $post_type, int $post_id = 0 ): array {
        $fields = array(
            'ambra_customer' => array(
                'field_ambra_customer_company',
                'field_ambra_customer_first',
                'field_ambra_customer_last',
                'field_ambra_customer_street',
                'field_ambra_customer_zip',
                'field_ambra_customer_city',
                'field_ambra_customer_country',
                'field_ambra_customer_phone',
                'field_ambra_customer_email',
                'field_ambra_customer_notes',
            ),
            'ambra_manufacturer' => array(
                'field_ambra_manufacturer_company',
                'field_ambra_manufacturer_street',
                'field_ambra_manufacturer_zip',
                'field_ambra_manufacturer_city',
                'field_ambra_manufacturer_country',
                'field_ambra_manufacturer_website',
                'field_ambra_manufacturer_contact_first',
                'field_ambra_manufacturer_contact_last',
                'field_ambra_manufacturer_phone',
                'field_ambra_manufacturer_email',
                'field_ambra_manufacturer_notes',
            ),
            'ambra_supplier' => array(
                'field_ambra_supplier_company',
                'field_ambra_supplier_street',
                'field_ambra_supplier_zip',
                'field_ambra_supplier_city',
                'field_ambra_supplier_country',
                'field_ambra_supplier_website',
                'field_ambra_supplier_contact_first',
                'field_ambra_supplier_contact_last',
                'field_ambra_supplier_phone',
                'field_ambra_supplier_email',
                'field_ambra_supplier_notes',
            ),
            'ambra_team' => array(
                'field_ambra_team_first',
                'field_ambra_team_last',
                'field_ambra_team_phone',
                'field_ambra_team_email',
                'field_ambra_team_notes',
            ),
            'ambra_visit' => array(
                'field_ambra_visit_project',
                'field_ambra_visit_datetime',
                'field_ambra_visit_team',
                'field_ambra_visit_type',
                'field_ambra_visit_status',
                'field_ambra_visit_photos',
                'field_ambra_visit_notes',
            ),
            'ambra_blueprint' => array(
                'field_ambra_blueprint_name',
                'field_ambra_blueprint_manufacturer',
                'field_ambra_blueprint_sku',
                'field_ambra_blueprint_category',
                'field_ambra_blueprint_description',
                'field_ambra_blueprint_price_model',
                'field_ambra_blueprint_internal_price',
                'field_ambra_blueprint_markup',
                'field_ambra_blueprint_width',
                'field_ambra_blueprint_height',
                'field_ambra_blueprint_config',
                'field_ambra_blueprint_notes',
            ),
            'ambra_position' => array(
                'field_ambra_position_project',
                'field_ambra_position_visit',
                'field_ambra_position_label',
                'field_ambra_position_room',
                'field_ambra_quantity',
                'field_ambra_position_width',
                'field_ambra_position_height',
                'field_ambra_position_photos',
                'field_ambra_position_notes',
                'field_ambra_position_blueprint',
                'field_ambra_position_config',
                'field_ambra_manual_override',
                'field_ambra_sale_unit',
            ),
            'ambra_project' => array(
                'field_ambra_project_title',
                'field_ambra_project_customer',
                'field_ambra_project_team',
                'field_ambra_project_manufacturers',
                'field_ambra_project_suppliers',
                'field_ambra_project_notes',
                'field_ambra_quote_number',
                'field_ambra_quote_file',
                'field_ambra_installation_datetime',
            ),
        );

        $result = $fields[ $post_type ] ?? array();

        if ( 'ambra_project' === $post_type && $post_id && Workflow::is_at_or_after( $post_id, 'quote_sent' ) ) {
            $result = array_values( array_diff( $result, array( 'field_ambra_project_customer' ) ) );
        }

        if ( 'ambra_project' === $post_type && current_user_can( 'ambra_manage_finance' ) ) {
            $result = array_merge(
                $result,
                array(
                    'field_ambra_deposit_invoice_number',
                    'field_ambra_deposit_invoice_file',
                    'field_ambra_deposit_amount',
                    'field_ambra_final_invoice_number',
                    'field_ambra_final_invoice_file',
                    'field_ambra_final_amount',
                )
            );
        }

        if ( 'ambra_position' === $post_type && $post_id ) {
            $project_id = Utils::relation_id( Utils::field( 'ambra_position_project', $post_id ) );
            if ( $project_id && Workflow::is_at_or_after( $project_id, 'quote_sent' ) ) {
                $allowed_after_quote = array(
                    'field_ambra_position_photos',
                    'field_ambra_position_notes',
                );
                $result = array_values( array_intersect( $result, $allowed_after_quote ) );
            }
        }

        return $result;
    }


    public static function validate_position_project( $valid, $value, array $field, string $input ) {
        if ( true !== $valid ) {
            return $valid;
        }
        $project_id = absint( $value );
        if ( $project_id && 'ambra_project' === get_post_type( $project_id ) && Workflow::is_at_or_after( $project_id, 'quote_sent' ) ) {
            return 'Für diesen Auftrag wurde bereits ein Angebot gesendet. Neue Positionen sind deshalb gesperrt.';
        }
        return $valid;
    }

    public static function validate_team_email( $valid, $value, array $field, string $input ) {
        if ( true !== $valid ) {
            return $valid;
        }
        $email = sanitize_email( (string) $value );
        if ( ! $email ) {
            return 'Bitte eine gültige E-Mail-Adresse eingeben.';
        }

        $current_post_id = 0;
        if ( isset( $_POST['_acf_post_id'] ) && is_numeric( $_POST['_acf_post_id'] ) ) {
            $current_post_id = absint( wp_unslash( $_POST['_acf_post_id'] ) );
        } elseif ( isset( $_POST['post_ID'] ) ) {
            $current_post_id = absint( wp_unslash( $_POST['post_ID'] ) );
        }

        $matches = get_posts(
            array(
                'post_type'      => 'ambra_team',
                'post_status'    => 'private',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'post__not_in'   => $current_post_id ? array( $current_post_id ) : array(),
                'meta_key'       => 'ambra_email',
                'meta_value'     => $email,
            )
        );
        if ( $matches ) {
            return 'Für diese E-Mail-Adresse existiert bereits ein Teammitglied.';
        }
        return $valid;
    }

    public static function is_ready(): bool {
        return class_exists( 'ACF' ) && function_exists( 'acf_form' );
    }
}
