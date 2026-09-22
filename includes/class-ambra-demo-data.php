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
        $author = get_current_user_id();
        if ( ! $author ) {
            $admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ids' ) );
            $author = $admins ? (int) $admins[0] : 1;
        }

        $manufacturer = self::create(
            'ambra_manufacturer',
            'HELLA (Beispiel)',
            array(
                'ambra_company'      => 'HELLA (Beispiel)',
                'ambra_website'      => 'https://www.hella.info/',
                'ambra_first_name'   => 'Anna',
                'ambra_last_name'    => 'Beispiel',
                'ambra_phone'        => '+43 1 555 0100',
                'ambra_email'        => 'hersteller@example.com',
                'ambra_notes'        => 'Beispieldatensatz – bitte für echte Bestellungen durch den realen Herstellerdatensatz ersetzen.',
            ),
            $author
        );

        $supplier = self::create(
            'ambra_supplier',
            'Transportpartner Wien (Beispiel)',
            array(
                'ambra_company'    => 'Transportpartner Wien (Beispiel)',
                'ambra_website'    => 'https://example.com/',
                'ambra_first_name' => 'Lukas',
                'ambra_last_name'  => 'Transport',
                'ambra_phone'      => '+43 1 555 0200',
                'ambra_email'      => 'transport@example.com',
                'ambra_notes'      => 'Beispieldatensatz.',
            ),
            $author
        );

        $blueprint_frame = self::create(
            'ambra_blueprint',
            'Insektenschutz Spannrahmen Standard (Beispiel)',
            array(
                'ambra_blueprint_name'             => 'Insektenschutz Spannrahmen Standard (Beispiel)',
                'ambra_blueprint_manufacturer'     => $manufacturer,
                'ambra_blueprint_sku'              => 'IS-SP-STD',
                'ambra_blueprint_category'         => 'Fliegengitter',
                'ambra_blueprint_description'      => 'Spannrahmen für Fenster als Beispieldatensatz.',
                'ambra_blueprint_price_model'      => 'sqm',
                'ambra_blueprint_internal_price'   => 115,
                'ambra_blueprint_markup_percent'   => 45,
                'ambra_blueprint_requires_width'   => 1,
                'ambra_blueprint_requires_height'  => 1,
                'ambra_blueprint_config_schema'    => array(
                    array( 'ambra_config_label' => 'Rahmenfarbe', 'ambra_config_key' => 'rahmenfarbe', 'ambra_config_default' => 'Anthrazit' ),
                    array( 'ambra_config_label' => 'Gewebe', 'ambra_config_key' => 'gewebe', 'ambra_config_default' => 'Standard' ),
                ),
                'ambra_blueprint_notes'            => 'Preise sind reine Beispieldaten.',
            ),
            $author
        );

        $blueprint_door = self::create(
            'ambra_blueprint',
            'Insektenschutz Drehtür Premium (Beispiel)',
            array(
                'ambra_blueprint_name'             => 'Insektenschutz Drehtür Premium (Beispiel)',
                'ambra_blueprint_manufacturer'     => $manufacturer,
                'ambra_blueprint_sku'              => 'IS-DT-PRM',
                'ambra_blueprint_category'         => 'Insektenschutztür',
                'ambra_blueprint_description'      => 'Drehtür für Balkon- und Terrassentüren als Beispieldatensatz.',
                'ambra_blueprint_price_model'      => 'fixed',
                'ambra_blueprint_internal_price'   => 420,
                'ambra_blueprint_markup_percent'   => 38,
                'ambra_blueprint_requires_width'   => 1,
                'ambra_blueprint_requires_height'  => 1,
                'ambra_blueprint_config_schema'    => array(
                    array( 'ambra_config_label' => 'Öffnungsrichtung', 'ambra_config_key' => 'oeffnungsrichtung', 'ambra_config_default' => 'Nach außen' ),
                    array( 'ambra_config_label' => 'Rahmenfarbe', 'ambra_config_key' => 'rahmenfarbe', 'ambra_config_default' => 'Anthrazit' ),
                ),
                'ambra_blueprint_notes'            => 'Preise sind reine Beispieldaten.',
            ),
            $author
        );

        $customer_berger = self::create(
            'ambra_customer',
            'Anna Berger (Beispiel)',
            array(
                'ambra_first_name'  => 'Anna',
                'ambra_last_name'   => 'Berger',
                'ambra_street'      => 'Musterstraße 10',
                'ambra_postal_code' => '1120',
                'ambra_city'        => 'Wien',
                'ambra_country'     => 'Österreich',
                'ambra_phone'       => '+43 660 111 2233',
                'ambra_email'       => 'anna.berger@example.com',
                'ambra_notes'       => 'Beispielkunde – Anfrage wegen Fliegengitter.',
            ),
            $author
        );

        $customer_company = self::create(
            'ambra_customer',
            'Muster GmbH (Beispiel)',
            array(
                'ambra_company'     => 'Muster GmbH (Beispiel)',
                'ambra_first_name'  => 'Markus',
                'ambra_last_name'   => 'Muster',
                'ambra_street'      => 'Beispielgasse 5',
                'ambra_postal_code' => '1010',
                'ambra_city'        => 'Wien',
                'ambra_country'     => 'Österreich',
                'ambra_phone'       => '+43 1 555 0300',
                'ambra_email'       => 'office@example.com',
                'ambra_notes'       => 'Beispielkunde – Auftrag bereits bestellt.',
            ),
            $author
        );

        $customer_steiner = self::create(
            'ambra_customer',
            'Familie Steiner (Beispiel)',
            array(
                'ambra_first_name'  => 'Julia',
                'ambra_last_name'   => 'Steiner',
                'ambra_street'      => 'Testweg 22',
                'ambra_postal_code' => '1230',
                'ambra_city'        => 'Wien',
                'ambra_country'     => 'Österreich',
                'ambra_phone'       => '+43 660 333 4455',
                'ambra_email'       => 'julia.steiner@example.com',
                'ambra_notes'       => 'Beispielkunde – Nachmessung geplant.',
            ),
            $author
        );

        $project_quote = self::create_project(
            'Insektenschutz Wohnhaus Berger (Beispiel)',
            $customer_berger,
            'positions_ready',
            array(
                'ambra_project_manufacturers' => array( $manufacturer ),
                'ambra_project_suppliers'     => array( $supplier ),
                'ambra_project_notes'         => 'Beispielauftrag mit gesendetem Angebot.',
                'ambra_quote_number'          => 'ANG-BEISPIEL-001',
            ),
            $author
        );

        $visit_quote = self::create(
            'ambra_visit',
            'Besichtigung – Berger (Beispiel)',
            array(
                'ambra_visit_project'  => $project_quote,
                'ambra_visit_datetime' => wp_date( 'Y-m-d H:i:s', strtotime( '-10 days 10:00' ) ),
                'ambra_visit_type'     => 'initial',
                'ambra_visit_status'   => 'completed',
                'ambra_visit_notes'    => 'Küchenfenster und Terrassentür aufgenommen.',
            ),
            $author
        );

        $position_quote_1 = self::create_position(
            $project_quote,
            $visit_quote,
            1,
            'Küchenfenster',
            'Küche',
            1200,
            950,
            $blueprint_frame,
            $author
        );
        $position_quote_2 = self::create_position(
            $project_quote,
            $visit_quote,
            2,
            'Terrassentür',
            'Wohnzimmer',
            980,
            2150,
            $blueprint_door,
            $author
        );
        Pricing::calculate_position( $position_quote_1 );
        Pricing::calculate_position( $position_quote_2 );
        Utils::update_field( 'ambra_project_stage', 'quote_sent', $project_quote );
        Utils::update_field( 'ambra_quote_sent_at', wp_date( 'Y-m-d H:i:s', strtotime( '-4 days' ) ), $project_quote );
        Utils::update_field( 'ambra_quote_decision', 'pending', $project_quote );
        Pricing::lock_project( $project_quote );

        $project_ordered = self::create_project(
            'Fliegengitter Büro Muster GmbH (Beispiel)',
            $customer_company,
            'positions_ready',
            array(
                'ambra_project_manufacturers' => array( $manufacturer ),
                'ambra_project_suppliers'     => array( $supplier ),
                'ambra_project_notes'         => 'Beispielauftrag: Angebot angenommen, Anzahlung eingegangen und bestellt.',
                'ambra_quote_number'          => 'ANG-BEISPIEL-002',
                'ambra_deposit_invoice_number'=> 'AR-BEISPIEL-002',
                'ambra_deposit_amount'        => 500,
            ),
            $author
        );
        $visit_ordered = self::create(
            'ambra_visit',
            'Besichtigung – Muster GmbH (Beispiel)',
            array(
                'ambra_visit_project'  => $project_ordered,
                'ambra_visit_datetime' => wp_date( 'Y-m-d H:i:s', strtotime( '-21 days 14:00' ) ),
                'ambra_visit_type'     => 'initial',
                'ambra_visit_status'   => 'completed',
                'ambra_visit_notes'    => 'Drei Bürofenster ausgemessen.',
            ),
            $author
        );
        $position_ordered = self::create_position(
            $project_ordered,
            $visit_ordered,
            1,
            'Bürofenster',
            'Büro',
            1100,
            900,
            $blueprint_frame,
            $author,
            3
        );
        Pricing::calculate_position( $position_ordered );
        Utils::update_field( 'ambra_project_stage', 'ordered', $project_ordered );
        Utils::update_field( 'ambra_quote_sent_at', wp_date( 'Y-m-d H:i:s', strtotime( '-18 days' ) ), $project_ordered );
        Utils::update_field( 'ambra_quote_decision', 'accepted', $project_ordered );
        Utils::update_field( 'ambra_quote_decision_at', wp_date( 'Y-m-d H:i:s', strtotime( '-15 days' ) ), $project_ordered );
        Utils::update_field( 'ambra_deposit_invoice_sent_at', wp_date( 'Y-m-d H:i:s', strtotime( '-14 days' ) ), $project_ordered );
        Utils::update_field( 'ambra_deposit_paid_at', wp_date( 'Y-m-d H:i:s', strtotime( '-12 days' ) ), $project_ordered );
        Utils::update_field( 'ambra_ordered_at', wp_date( 'Y-m-d H:i:s', strtotime( '-10 days' ) ), $project_ordered );
        Utils::update_field( 'ambra_position_status', 'ordered', $position_ordered );
        Pricing::lock_project( $project_ordered );

        $project_visit = self::create_project(
            'Nachmessung Familie Steiner (Beispiel)',
            $customer_steiner,
            'appointment_scheduled',
            array(
                'ambra_project_notes' => 'Zweiter Termin zur Nachmessung vorgesehen.',
            ),
            $author
        );
        self::create(
            'ambra_visit',
            'Erstbesichtigung – Steiner (Beispiel)',
            array(
                'ambra_visit_project'  => $project_visit,
                'ambra_visit_datetime' => wp_date( 'Y-m-d H:i:s', strtotime( '-3 days 09:00' ) ),
                'ambra_visit_type'     => 'initial',
                'ambra_visit_status'   => 'completed',
                'ambra_visit_notes'    => 'Terrassentür muss nochmals geprüft werden.',
            ),
            $author
        );
        self::create(
            'ambra_visit',
            'Nachmessung – Steiner (Beispiel)',
            array(
                'ambra_visit_project'  => $project_visit,
                'ambra_visit_datetime' => wp_date( 'Y-m-d H:i:s', strtotime( '+3 days 11:30' ) ),
                'ambra_visit_type'     => 'remeasure',
                'ambra_visit_status'   => 'planned',
                'ambra_visit_notes'    => 'Schwelle und Öffnungsrichtung kontrollieren.',
            ),
            $author
        );

        update_option( self::OPTION, '1', false );

        return array(
            'manufacturer' => $manufacturer,
            'supplier'     => $supplier,
            'blueprints'   => array( $blueprint_frame, $blueprint_door ),
            'customers'    => array( $customer_berger, $customer_company, $customer_steiner ),
            'projects'     => array( $project_quote, $project_ordered, $project_visit ),
        );
    }

    private static function create_project( string $title, int $customer_id, string $stage, array $extra, int $author ): int {
        return self::create(
            'ambra_project',
            $title,
            array_merge(
                array(
                    'ambra_project_title'    => $title,
                    'ambra_project_customer' => $customer_id,
                    'ambra_project_stage'    => $stage,
                    'ambra_project_state'    => 'active',
                ),
                $extra
            ),
            $author
        );
    }

    private static function create_position( int $project_id, int $visit_id, int $number, string $label, string $room, float $width, float $height, int $blueprint_id, int $author, float $quantity = 1 ): int {
        return self::create(
            'ambra_position',
            sprintf( '#%02d – %s (Beispiel)', $number, $label ),
            array(
                'ambra_position_project'   => $project_id,
                'ambra_position_visit'     => $visit_id,
                'ambra_position_number'    => $number,
                'ambra_position_label'     => $label,
                'ambra_position_room'      => $room,
                'ambra_quantity'           => $quantity,
                'ambra_width_mm'           => $width,
                'ambra_height_mm'          => $height,
                'ambra_position_blueprint' => $blueprint_id,
                'ambra_position_notes'     => 'Beispielposition.',
                'ambra_position_status'    => 'recorded',
            ),
            $author
        );
    }

    private static function create( string $post_type, string $title, array $fields, int $author ): int {
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
