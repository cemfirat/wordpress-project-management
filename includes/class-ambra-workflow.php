<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

final class Workflow {
    public static function init(): void {
        add_action( 'admin_post_ambra_workflow_action', array( __CLASS__, 'handle_project_action' ) );
        add_action( 'admin_post_ambra_position_action', array( __CLASS__, 'handle_position_action' ) );
    }

    public static function stages(): array {
        return array(
            'inquiry'                => 'Kundenanfrage erhalten',
            'appointment_scheduled'  => 'Besichtigungstermin vereinbart',
            'visit_completed'        => 'Besichtigung / Aufmaß durchgeführt',
            'positions_ready'        => 'Positionen kalkuliert',
            'quote_sent'             => 'Angebot gesendet',
            'quote_accepted'         => 'Angebot angenommen',
            'deposit_invoice_sent'   => 'Anzahlungsrechnung gesendet',
            'deposit_paid'           => 'Anzahlung eingegangen',
            'ordered'                => 'Beim Hersteller bestellt',
            'delivered'              => 'Lieferung vollständig',
            'installation_scheduled' => 'Montagetermin vereinbart',
            'installed'              => 'Montage durchgeführt',
            'final_invoice_sent'     => 'Schlussrechnung gesendet',
            'final_paid'             => 'Restzahlung eingegangen',
            'completed'              => 'Abgeschlossen',
        );
    }

    public static function stage_label( string $stage ): string {
        return self::stages()[ $stage ] ?? $stage;
    }

    public static function stage_index( string $stage ): int {
        $keys  = array_keys( self::stages() );
        $index = array_search( $stage, $keys, true );
        return false === $index ? -1 : (int) $index;
    }

    public static function is_at_or_after( int $project_id, string $stage ): bool {
        $current = (string) Utils::field( 'ambra_project_stage', $project_id, 'inquiry' );
        return self::stage_index( $current ) >= self::stage_index( $stage );
    }

    public static function next_stage( string $stage ): ?string {
        $keys  = array_keys( self::stages() );
        $index = array_search( $stage, $keys, true );
        if ( false === $index || ! isset( $keys[ $index + 1 ] ) ) {
            return null;
        }
        return $keys[ $index + 1 ];
    }

    public static function validate_transition( int $project_id, string $target ): true|string {
        $current = (string) Utils::field( 'ambra_project_stage', $project_id, 'inquiry' );
        $state   = (string) Utils::field( 'ambra_project_state', $project_id, 'active' );

        if ( 'active' !== $state ) {
            return 'Der Auftrag ist nicht aktiv.';
        }
        if ( self::next_stage( $current ) !== $target ) {
            return 'Dieser Workflow-Schritt ist aktuell nicht zulässig.';
        }
        if ( in_array( $target, array( 'deposit_invoice_sent', 'deposit_paid', 'final_invoice_sent', 'final_paid' ), true ) && ! current_user_can( 'ambra_manage_finance' ) ) {
            return 'Für diesen Finanzschritt fehlt die Berechtigung.';
        }

        switch ( $target ) {
            case 'appointment_scheduled':
                if ( ! self::has_visit( $project_id, array( 'planned', 'completed' ) ) ) {
                    return 'Lege zuerst einen Besichtigungstermin an.';
                }
                break;

            case 'visit_completed':
                if ( ! self::has_visit( $project_id, array( 'completed' ) ) ) {
                    return 'Mindestens eine Besichtigung muss als durchgeführt markiert sein.';
                }
                break;

            case 'positions_ready':
                $positions = Pricing::project_positions( $project_id );
                if ( ! $positions ) {
                    return 'Lege zuerst mindestens eine Auftragsposition an.';
                }
                $active_positions = 0;
                foreach ( $positions as $position_id ) {
                    if ( 'cancelled' === Utils::field( 'ambra_position_status', $position_id ) ) {
                        continue;
                    }
                    ++$active_positions;
                    if ( (float) Utils::field( 'ambra_line_total', $position_id, 0 ) <= 0 ) {
                        return 'Alle aktiven Positionen müssen kalkuliert sein.';
                    }
                }
                if ( ! $active_positions ) {
                    return 'Mindestens eine aktive Auftragsposition ist erforderlich.';
                }
                break;

            case 'quote_sent':
                if ( Pricing::project_total( $project_id ) <= 0 ) {
                    return 'Der Auftragswert muss größer als 0 sein.';
                }
                break;

            case 'delivered':
                if ( ! self::all_positions_at_least( $project_id, 'delivered' ) ) {
                    return 'Noch nicht alle aktiven Positionen sind geliefert.';
                }
                break;

            case 'installation_scheduled':
                if ( ! Utils::field( 'ambra_installation_datetime', $project_id ) ) {
                    return 'Trage zuerst den Montagetermin im Auftrag ein.';
                }
                break;

            case 'installed':
                if ( ! self::all_positions_at_least( $project_id, 'delivered' ) ) {
                    return 'Vor Abschluss der Montage müssen alle aktiven Positionen geliefert sein.';
                }
                break;
        }

        return true;
    }

    public static function handle_project_action(): void {
        $project_id = isset( $_POST['project_id'] ) ? absint( $_POST['project_id'] ) : 0;
        $action     = isset( $_POST['workflow_action'] ) ? sanitize_key( wp_unslash( $_POST['workflow_action'] ) ) : '';

        if ( ! $project_id || 'ambra_project' !== get_post_type( $project_id ) || ! current_user_can( 'edit_ambra_record', $project_id ) ) {
            wp_die( 'Keine Berechtigung.' );
        }
        check_admin_referer( 'ambra_workflow_' . $project_id );

        $redirect = Pages::get_url( 'projects', array( 'id' => $project_id ) );

        if ( in_array( $action, array( 'cancel', 'reject_quote' ), true ) ) {
            $reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
            if ( ! $reason ) {
                self::redirect_message( $redirect, 'danger', 'Bitte einen Grund angeben.' );
            }

            Utils::update_field( 'ambra_project_state', 'cancelled', $project_id );
            Utils::update_field( 'ambra_cancellation_reason', $reason, $project_id );

            if ( 'reject_quote' === $action ) {
                Utils::update_field( 'ambra_quote_decision', 'rejected', $project_id );
                Utils::update_field( 'ambra_quote_decision_at', current_time( 'mysql' ), $project_id );
            }

            self::redirect_message( $redirect, 'success', 'Auftrag wurde beendet.' );
        }

        if ( 'accept_quote' === $action ) {
            $current = (string) Utils::field( 'ambra_project_stage', $project_id, 'inquiry' );
            if ( 'quote_sent' !== $current ) {
                self::redirect_message( $redirect, 'danger', 'Das Angebot kann aktuell nicht angenommen werden.' );
            }

            Utils::update_field( 'ambra_quote_decision', 'accepted', $project_id );
            Utils::update_field( 'ambra_quote_decision_at', current_time( 'mysql' ), $project_id );
            Utils::update_field( 'ambra_project_stage', 'quote_accepted', $project_id );
            self::redirect_message( $redirect, 'success', 'Angebot als angenommen markiert.' );
        }

        if ( 'advance' === $action ) {
            $current = (string) Utils::field( 'ambra_project_stage', $project_id, 'inquiry' );
            $target  = self::next_stage( $current );
            if ( ! $target ) {
                self::redirect_message( $redirect, 'danger', 'Kein weiterer Schritt verfügbar.' );
            }

            $valid = self::validate_transition( $project_id, $target );
            if ( true !== $valid ) {
                self::redirect_message( $redirect, 'danger', $valid );
            }

            self::apply_stage_side_effects( $project_id, $target );
            Utils::update_field( 'ambra_project_stage', $target, $project_id );

            if ( 'completed' === $target ) {
                Utils::update_field( 'ambra_project_state', 'completed', $project_id );
                Utils::update_field( 'ambra_completed_at', current_time( 'mysql' ), $project_id );
            }

            self::redirect_message( $redirect, 'success', 'Workflow aktualisiert: ' . self::stage_label( $target ) );
        }

        self::redirect_message( $redirect, 'danger', 'Unbekannte Aktion.' );
    }

    private static function apply_stage_side_effects( int $project_id, string $target ): void {
        switch ( $target ) {
            case 'quote_sent':
                Utils::update_field( 'ambra_quote_sent_at', current_time( 'mysql' ), $project_id );
                Utils::update_field( 'ambra_quote_decision', 'pending', $project_id );
                Pricing::lock_project( $project_id );
                break;

            case 'deposit_invoice_sent':
                Utils::update_field( 'ambra_deposit_invoice_sent_at', current_time( 'mysql' ), $project_id );
                break;

            case 'deposit_paid':
                Utils::update_field( 'ambra_deposit_paid_at', current_time( 'mysql' ), $project_id );
                break;

            case 'ordered':
                self::set_all_positions( $project_id, 'ordered', array( 'recorded', 'calculated' ) );
                Utils::update_field( 'ambra_ordered_at', current_time( 'mysql' ), $project_id );
                break;

            case 'installed':
                self::set_all_positions( $project_id, 'installed', array( 'delivered' ) );
                break;

            case 'final_invoice_sent':
                Utils::update_field( 'ambra_final_invoice_sent_at', current_time( 'mysql' ), $project_id );
                break;

            case 'final_paid':
                Utils::update_field( 'ambra_final_paid_at', current_time( 'mysql' ), $project_id );
                break;
        }
    }

    public static function handle_position_action(): void {
        $position_id = isset( $_POST['position_id'] ) ? absint( $_POST['position_id'] ) : 0;
        $action      = isset( $_POST['position_action'] ) ? sanitize_key( wp_unslash( $_POST['position_action'] ) ) : '';

        if ( ! $position_id || 'ambra_position' !== get_post_type( $position_id ) || ! current_user_can( 'edit_ambra_record', $position_id ) ) {
            wp_die( 'Keine Berechtigung.' );
        }
        check_admin_referer( 'ambra_position_' . $position_id );

        $project_id = Utils::relation_id( Utils::field( 'ambra_position_project', $position_id ) );
        $redirect   = Pages::get_url( 'positions', array( 'edit' => $position_id, 'project' => $project_id ) );
        $status     = (string) Utils::field( 'ambra_position_status', $position_id, 'recorded' );

        $allowed = array(
            'mark_delivered' => array( 'from' => array( 'ordered' ), 'to' => 'delivered' ),
            'mark_installed' => array( 'from' => array( 'delivered' ), 'to' => 'installed' ),
            'cancel'         => array( 'from' => array( 'recorded', 'calculated' ), 'to' => 'cancelled' ),
        );

        if ( ! isset( $allowed[ $action ] ) || ! in_array( $status, $allowed[ $action ]['from'], true ) ) {
            self::redirect_message( $redirect, 'danger', 'Diese Positionsaktion ist aktuell nicht zulässig.' );
        }

        Utils::update_field( 'ambra_position_status', $allowed[ $action ]['to'], $position_id );
        self::redirect_message( $redirect, 'success', 'Positionsstatus aktualisiert.' );
    }

    private static function has_visit( int $project_id, array $statuses ): bool {
        $ids = get_posts(
            array(
                'post_type'      => 'ambra_visit',
                'post_status'    => 'private',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'   => 'ambra_visit_project',
                        'value' => $project_id,
                    ),
                    array(
                        'key'     => 'ambra_visit_status',
                        'value'   => $statuses,
                        'compare' => 'IN',
                    ),
                ),
            )
        );
        return ! empty( $ids );
    }

    private static function all_positions_at_least( int $project_id, string $minimum ): bool {
        $rank      = array( 'recorded' => 1, 'calculated' => 2, 'ordered' => 3, 'delivered' => 4, 'installed' => 5 );
        $positions = Pricing::project_positions( $project_id );
        $active    = 0;

        foreach ( $positions as $position_id ) {
            $status = (string) Utils::field( 'ambra_position_status', $position_id, 'recorded' );
            if ( 'cancelled' === $status ) {
                continue;
            }
            ++$active;
            if ( ! isset( $rank[ $status ] ) || $rank[ $status ] < $rank[ $minimum ] ) {
                return false;
            }
        }

        return $active > 0;
    }

    private static function set_all_positions( int $project_id, string $to, array $from ): void {
        foreach ( Pricing::project_positions( $project_id ) as $position_id ) {
            $status = (string) Utils::field( 'ambra_position_status', $position_id, 'recorded' );
            if ( in_array( $status, $from, true ) ) {
                Utils::update_field( 'ambra_position_status', $to, $position_id );
            }
        }
    }

    private static function redirect_message( string $url, string $type, string $message ): void {
        wp_safe_redirect(
            add_query_arg(
                array(
                    'ambra_message' => rawurlencode( $message ),
                    'ambra_type'    => $type,
                ),
                $url
            )
        );
        exit;
    }
}
