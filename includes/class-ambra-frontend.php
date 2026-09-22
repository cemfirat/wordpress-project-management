<?php
namespace AMBRA_PM;

use WP_Query;

defined( 'ABSPATH' ) || exit;

final class Frontend {
    public static function init(): void {
        add_shortcode( 'ambra_dashboard', array( __CLASS__, 'dashboard_shortcode' ) );
        add_shortcode( 'ambra_projects', array( __CLASS__, 'projects_shortcode' ) );
        add_shortcode( 'ambra_customers', static fn() => self::entity_shortcode( 'customers' ) );
        add_shortcode( 'ambra_visits', static fn() => self::entity_shortcode( 'visits' ) );
        add_shortcode( 'ambra_positions', static fn() => self::entity_shortcode( 'positions' ) );
        add_shortcode( 'ambra_blueprints', static fn() => self::entity_shortcode( 'blueprints' ) );
        add_shortcode( 'ambra_manufacturers', static fn() => self::entity_shortcode( 'manufacturers' ) );
        add_shortcode( 'ambra_suppliers', static fn() => self::entity_shortcode( 'suppliers' ) );
        add_shortcode( 'ambra_team', static fn() => self::entity_shortcode( 'team' ) );

        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'admin_post_ambra_trash_record', array( __CLASS__, 'trash_record' ) );
    }

    public static function enqueue_assets(): void {
        if ( ! Pages::is_app_page() ) {
            return;
        }
        wp_enqueue_style( 'ambra-pm-frontend', AMBRA_PM_URL . 'assets/css/frontend.css', array(), AMBRA_PM_VERSION );
        wp_enqueue_script( 'ambra-pm-frontend', AMBRA_PM_URL . 'assets/js/frontend.js', array(), AMBRA_PM_VERSION, true );
    }

    private static function entity_configs(): array {
        return array(
            'customers' => array(
                'post_type'  => 'ambra_customer',
                'page'       => 'customers',
                'plural'     => 'Kunden',
                'singular'   => 'Kunde',
                'create_cap' => 'edit_ambra_records',
                'columns'    => array( 'name' => 'Kunde', 'phone' => 'Telefon', 'email' => 'E-Mail' ),
            ),
            'visits' => array(
                'post_type'  => 'ambra_visit',
                'page'       => 'visits',
                'plural'     => 'Besichtigungen',
                'singular'   => 'Besichtigung',
                'create_cap' => 'edit_ambra_records',
                'columns'    => array( 'date' => 'Termin', 'project' => 'Auftrag', 'visit_type' => 'Art', 'visit_status' => 'Status' ),
            ),
            'positions' => array(
                'post_type'  => 'ambra_position',
                'page'       => 'positions',
                'plural'     => 'Auftragspositionen',
                'singular'   => 'Auftragsposition',
                'create_cap' => 'edit_ambra_records',
                'columns'    => array( 'position' => 'Position', 'project' => 'Auftrag', 'blueprint' => 'Produkt', 'position_status' => 'Status', 'total' => 'Summe' ),
            ),
            'blueprints' => array(
                'post_type'  => 'ambra_blueprint',
                'page'       => 'blueprints',
                'plural'     => 'Produkt-Blueprints',
                'singular'   => 'Produkt-Blueprint',
                'create_cap' => 'edit_ambra_catalogs',
                'columns'    => array( 'name' => 'Produkt', 'manufacturer' => 'Hersteller', 'price_model' => 'Preismodell', 'markup' => 'Aufschlag' ),
            ),
            'manufacturers' => array(
                'post_type'  => 'ambra_manufacturer',
                'page'       => 'manufacturers',
                'plural'     => 'Hersteller',
                'singular'   => 'Hersteller',
                'create_cap' => 'edit_ambra_catalogs',
                'columns'    => array( 'name' => 'Firma', 'phone' => 'Telefon', 'email' => 'E-Mail', 'website' => 'Website' ),
            ),
            'suppliers' => array(
                'post_type'  => 'ambra_supplier',
                'page'       => 'suppliers',
                'plural'     => 'Lieferanten / Transport',
                'singular'   => 'Lieferant',
                'create_cap' => 'edit_ambra_catalogs',
                'columns'    => array( 'name' => 'Firma', 'phone' => 'Telefon', 'email' => 'E-Mail', 'website' => 'Website' ),
            ),
            'team' => array(
                'post_type'  => 'ambra_team',
                'page'       => 'team',
                'plural'     => 'Team',
                'singular'   => 'Teammitglied',
                'create_cap' => 'edit_ambra_team_members',
                'columns'    => array( 'name' => 'Name', 'phone' => 'Telefon', 'email' => 'E-Mail', 'wp_user' => 'WordPress-Benutzer' ),
            ),
        );
    }

    private static function guard(): ?string {
        if ( ! is_user_logged_in() ) {
            return self::alert( 'Anmeldung erforderlich.', 'danger' );
        }
        if ( ! current_user_can( 'ambra_use_app' ) && ! current_user_can( 'manage_options' ) ) {
            return self::alert( 'Keine Berechtigung für AMBRA Projektmanagement.', 'danger' );
        }
        if ( ! ACF_Integration::is_ready() ) {
            return self::alert( 'ACF Pro ist erforderlich und muss aktiviert sein.', 'danger' );
        }
        return null;
    }

    private static function shell_start( string $title, string $subtitle = '' ): string {
        ob_start();
        echo '<section class="ambra-pm uk-section uk-section-small">';
        echo '<div class="uk-container uk-container-expand">';
        self::render_flash_message();
        echo '<div class="uk-flex uk-flex-between uk-flex-middle uk-flex-wrap uk-margin-medium-bottom">';
        echo '<div><h1 class="uk-heading-medium uk-margin-remove">' . esc_html( $title ) . '</h1>';
        if ( $subtitle ) {
            echo '<p class="uk-text-meta uk-margin-small-top uk-margin-remove-bottom">' . esc_html( $subtitle ) . '</p>';
        }
        echo '</div></div>';
        return (string) ob_get_clean();
    }

    private static function shell_end(): string {
        return '</div></section>';
    }

    private static function alert( string $message, string $type = 'primary' ): string {
        $allowed = array( 'primary', 'success', 'warning', 'danger' );
        $type    = in_array( $type, $allowed, true ) ? $type : 'primary';
        return '<div class="ambra-pm uk-section uk-section-small"><div class="uk-container"><div class="uk-alert-' . esc_attr( $type ) . '" uk-alert><p>' . esc_html( $message ) . '</p></div></div></div>';
    }

    private static function render_flash_message(): void {
        if ( empty( $_GET['ambra_message'] ) ) {
            return;
        }
        $message = sanitize_text_field( rawurldecode( wp_unslash( $_GET['ambra_message'] ) ) );
        $type    = ! empty( $_GET['ambra_type'] ) ? sanitize_key( wp_unslash( $_GET['ambra_type'] ) ) : 'primary';
        if ( ! in_array( $type, array( 'primary', 'success', 'warning', 'danger' ), true ) ) {
            $type = 'primary';
        }
        echo '<div class="uk-alert-' . esc_attr( $type ) . ' uk-margin-medium-bottom" uk-alert>';
        echo '<a class="uk-alert-close" uk-close></a><p>' . esc_html( $message ) . '</p></div>';
    }

    public static function dashboard_shortcode(): string {
        if ( $guard = self::guard() ) {
            return $guard;
        }

        ob_start();
        echo self::shell_start( 'Dashboard', 'AMBRA Projektmanagement und Auftragsabwicklung' );
        echo '<div class="uk-child-width-1-2@s uk-child-width-1-4@l uk-grid-small uk-grid-match" uk-grid>';
        self::stat_card( 'Aktive Aufträge', self::count_projects_by_state( 'active' ), Pages::get_url( 'projects' ), 'file-text' );
        self::stat_card( 'Angebote offen', self::count_projects_by_stage( 'quote_sent' ), Pages::get_url( 'projects' ), 'mail' );
        self::stat_card( 'Bestellt', self::count_projects_by_stage( 'ordered' ), Pages::get_url( 'projects' ), 'cart' );
        self::stat_card( 'Montagetermine', self::count_projects_by_stage( 'installation_scheduled' ), Pages::get_url( 'projects' ), 'calendar' );
        echo '</div>';

        echo '<div class="uk-card uk-card-default uk-card-body uk-margin-large-top">';
        echo '<div class="uk-flex uk-flex-between uk-flex-middle uk-flex-wrap uk-margin-bottom">';
        echo '<h2 class="uk-card-title uk-margin-remove">Zuletzt bearbeitete Aufträge</h2>';
        if ( current_user_can( 'edit_ambra_records' ) ) {
            echo '<a class="uk-button uk-button-primary" href="' . esc_url( Pages::get_url( 'projects', array( 'action' => 'new' ) ) ) . '"><span uk-icon="plus"></span> Neuer Auftrag</a>';
        }
        echo '</div>';
        self::render_projects_table( 8 );
        echo '</div>';
        echo self::shell_end();
        return (string) ob_get_clean();
    }

    private static function stat_card( string $label, int $value, string $url, string $icon ): void {
        echo '<div><a class="uk-card uk-card-default uk-card-body uk-display-block uk-link-reset" href="' . esc_url( $url ) . '">';
        echo '<div class="uk-flex uk-flex-between uk-flex-middle"><div><div class="uk-text-large uk-text-bold">' . esc_html( (string) $value ) . '</div><div class="uk-text-meta">' . esc_html( $label ) . '</div></div>';
        echo '<span uk-icon="icon: ' . esc_attr( $icon ) . '; ratio: 1.5"></span></div></a></div>';
    }

    public static function projects_shortcode(): string {
        if ( $guard = self::guard() ) {
            return $guard;
        }

        $id     = ! empty( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $action = ! empty( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
        $edit   = ! empty( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;

        if ( $id ) {
            return self::render_project_detail( $id );
        }
        if ( 'new' === $action || $edit ) {
            return self::render_acf_entity_form( 'projects', $edit );
        }

        ob_start();
        echo self::shell_start( 'Aufträge' );
        echo '<div class="uk-flex uk-flex-between uk-flex-middle uk-flex-wrap uk-grid-small uk-margin-medium-bottom" uk-grid>';
        echo '<div class="uk-width-expand@s">' . self::search_form( 'Aufträge suchen' ) . '</div>';
        if ( current_user_can( 'edit_ambra_records' ) ) {
            echo '<div class="uk-width-auto@s"><a class="uk-button uk-button-primary" href="' . esc_url( Pages::get_url( 'projects', array( 'action' => 'new' ) ) ) . '"><span uk-icon="plus"></span> Neuer Auftrag</a></div>';
        }
        echo '</div>';
        echo '<div class="uk-card uk-card-default uk-card-body">';
        self::render_projects_table( 50, self::search_term() );
        echo '</div>';
        echo self::shell_end();
        return (string) ob_get_clean();
    }

    private static function render_projects_table( int $limit = 30, string $search = '' ): void {
        $query = new WP_Query(
            array(
                'post_type'      => 'ambra_project',
                'post_status'    => 'private',
                'posts_per_page' => $limit,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                's'              => $search,
            )
        );

        echo '<div class="uk-overflow-auto"><table class="uk-table uk-table-divider uk-table-hover uk-table-middle uk-table-responsive">';
        echo '<thead><tr><th class="uk-table-shrink">Nr.</th><th>Auftrag</th><th>Kunde</th><th>Etappe</th><th>Summe</th><th class="uk-table-shrink"></th></tr></thead><tbody>';
        if ( ! $query->have_posts() ) {
            echo '<tr><td colspan="6" class="uk-text-muted">Keine Aufträge gefunden.</td></tr>';
        }
        while ( $query->have_posts() ) {
            $query->the_post();
            $id       = get_the_ID();
            $customer = Utils::relation_id( Utils::field( 'ambra_project_customer', $id ) );
            $stage    = (string) Utils::field( 'ambra_project_stage', $id, 'inquiry' );
            echo '<tr>';
            echo '<td data-label="Nr." class="uk-text-nowrap">' . esc_html( (string) Utils::field( 'ambra_project_number', $id, '—' ) ) . '</td>';
            echo '<td data-label="Auftrag"><strong>' . esc_html( Utils::post_title( $id ) ) . '</strong>' . self::demo_label( $id ) . '</td>';
            echo '<td data-label="Kunde">' . esc_html( $customer ? Utils::post_title( $customer ) : '—' ) . '</td>';
            echo '<td data-label="Etappe"><span class="uk-label">' . esc_html( Workflow::stage_label( $stage ) ) . '</span></td>';
            echo '<td data-label="Summe" class="uk-text-nowrap">' . esc_html( Utils::money( Pricing::project_total( $id ) ) ) . '</td>';
            echo '<td data-label="Aktionen"><a class="uk-button uk-button-text" href="' . esc_url( Pages::get_url( 'projects', array( 'id' => $id ) ) ) . '">Öffnen <span uk-icon="chevron-right"></span></a></td>';
            echo '</tr>';
        }
        wp_reset_postdata();
        echo '</tbody></table></div>';
    }

    private static function render_project_detail( int $project_id ): string {
        if ( 'ambra_project' !== get_post_type( $project_id ) || ! current_user_can( 'read_ambra_record', $project_id ) ) {
            return self::alert( 'Auftrag nicht gefunden oder keine Berechtigung.', 'danger' );
        }

        $stage       = (string) Utils::field( 'ambra_project_stage', $project_id, 'inquiry' );
        $state       = (string) Utils::field( 'ambra_project_state', $project_id, 'active' );
        $customer_id = Utils::relation_id( Utils::field( 'ambra_project_customer', $project_id ) );

        ob_start();
        echo self::shell_start( Utils::post_title( $project_id ), (string) Utils::field( 'ambra_project_number', $project_id, '' ) );
        echo '<div class="uk-child-width-1-2@s uk-child-width-1-5@l uk-grid-small uk-grid-match" uk-grid>';
        self::meta_card( 'Kunde', $customer_id ? Utils::post_title( $customer_id ) : '—' );
        self::meta_card( 'Etappe', Workflow::stage_label( $stage ) );
        self::meta_card( 'Status', self::project_state_label( $state ) );
        self::meta_card( 'Auftragswert', Utils::money( Pricing::project_total( $project_id ) ) );
        self::meta_card( 'Angebot', (string) Utils::field( 'ambra_quote_number', $project_id, '—' ) );
        echo '</div>';

        self::render_workflow( $project_id );

        echo '<div class="uk-card uk-card-default uk-card-body uk-margin-large-top">';
        echo '<div class="uk-flex uk-flex-between uk-flex-middle uk-flex-wrap uk-margin-bottom"><h2 class="uk-card-title uk-margin-remove">Besichtigungen</h2>';
        if ( current_user_can( 'edit_ambra_records' ) ) {
            echo '<a class="uk-button uk-button-default" href="' . esc_url( Pages::get_url( 'visits', array( 'action' => 'new', 'project' => $project_id ) ) ) . '"><span uk-icon="plus"></span> Besichtigung</a>';
        }
        echo '</div>';
        self::render_child_rows( 'visits', $project_id );
        echo '</div>';

        echo '<div class="uk-card uk-card-default uk-card-body uk-margin-large-top">';
        echo '<div class="uk-flex uk-flex-between uk-flex-middle uk-flex-wrap uk-margin-bottom"><h2 class="uk-card-title uk-margin-remove">Auftragspositionen</h2>';
        if ( current_user_can( 'edit_ambra_records' ) && ! Workflow::is_at_or_after( $project_id, 'quote_sent' ) ) {
            echo '<a class="uk-button uk-button-default" href="' . esc_url( Pages::get_url( 'positions', array( 'action' => 'new', 'project' => $project_id ) ) ) . '"><span uk-icon="plus"></span> Position</a>';
        }
        echo '</div>';
        self::render_child_rows( 'positions', $project_id );
        echo '</div>';

        if ( current_user_can( 'edit_ambra_record', $project_id ) ) {
            echo '<ul class="uk-margin-large-top" uk-accordion><li><a class="uk-accordion-title" href>Auftrag bearbeiten</a><div class="uk-accordion-content">';
            echo '<div class="uk-card uk-card-default uk-card-body">';
            self::acf_form( 'ambra_project', $project_id, Pages::get_url( 'projects', array( 'id' => $project_id, 'saved' => 1 ) ), 'Auftrag speichern' );
            echo '</div></div></li></ul>';
        }

        echo self::shell_end();
        return (string) ob_get_clean();
    }

    private static function meta_card( string $label, string $value ): void {
        echo '<div><div class="uk-card uk-card-body uk-padding-small uk-background-muted"><div class="uk-text-meta">' . esc_html( $label ) . '</div><div class="uk-text-bold uk-margin-small-top">' . esc_html( $value ) . '</div></div></div>';
    }

    private static function render_workflow( int $project_id ): void {
        $stage = (string) Utils::field( 'ambra_project_stage', $project_id, 'inquiry' );
        $state = (string) Utils::field( 'ambra_project_state', $project_id, 'active' );
        $current_index = Workflow::stage_index( $stage );

        echo '<div class="uk-card uk-card-default uk-card-body uk-margin-large-top"><h2 class="uk-card-title">Workflow</h2>';
        echo '<div class="uk-child-width-1-2@s uk-child-width-1-3@m uk-child-width-1-5@xl uk-grid-small" uk-grid>';
        foreach ( Workflow::stages() as $key => $label ) {
            $index = Workflow::stage_index( $key );
            $class = 'uk-card uk-card-small uk-card-body ambra-step';
            if ( $index < $current_index ) {
                $class .= ' ambra-step-done';
            } elseif ( $index === $current_index ) {
                $class .= ' ambra-step-current';
            }
            echo '<div><div class="' . esc_attr( $class ) . '">';
            echo '<div class="uk-text-meta">Schritt ' . esc_html( (string) ( $index + 1 ) ) . '</div><div class="uk-text-small uk-text-bold">' . esc_html( $label ) . '</div>';
            if ( $index === $current_index ) {
                echo '<span class="uk-label uk-margin-small-top">Aktuell</span>';
            } elseif ( $index < $current_index ) {
                echo '<span class="uk-label uk-label-success uk-margin-small-top">Erledigt</span>';
            }
            echo '</div></div>';
        }
        echo '</div>';

        if ( 'active' === $state && current_user_can( 'edit_ambra_record', $project_id ) ) {
            echo '<div class="uk-flex uk-flex-wrap uk-flex-middle uk-grid-small uk-margin-top" uk-grid>';
            if ( 'quote_sent' === $stage ) {
                self::workflow_form( $project_id, 'accept_quote', 'Angebot angenommen', false, 'uk-button-primary' );
                self::workflow_form( $project_id, 'reject_quote', 'Angebot abgelehnt', true, 'uk-button-danger' );
            } else {
                $next = Workflow::next_stage( $stage );
                if ( $next ) {
                    $valid = Workflow::validate_transition( $project_id, $next );
                    if ( true === $valid ) {
                        self::workflow_form( $project_id, 'advance', 'Weiter: ' . Workflow::stage_label( $next ), false, 'uk-button-primary' );
                    } else {
                        echo '<div><div class="uk-alert-warning uk-margin-remove" uk-alert><p><strong>Nächster Schritt: ' . esc_html( Workflow::stage_label( $next ) ) . '</strong><br>' . esc_html( $valid ) . '</p></div></div>';
                    }
                }
            }
            if ( 'completed' !== $stage ) {
                self::workflow_form( $project_id, 'cancel', 'Auftrag abbrechen', true, 'uk-button-default' );
            }
            echo '</div>';
        } elseif ( 'cancelled' === $state ) {
            echo '<div class="uk-alert-warning uk-margin-top" uk-alert><p><strong>Auftrag beendet:</strong> ' . esc_html( (string) Utils::field( 'ambra_cancellation_reason', $project_id, 'Kein Grund angegeben.' ) ) . '</p></div>';
        }
        echo '</div>';
    }

    private static function workflow_form( int $project_id, string $action, string $label, bool $reason, string $button_class ): void {
        echo '<div><form class="uk-flex uk-flex-wrap uk-grid-small" uk-grid method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="ambra_workflow_action"><input type="hidden" name="project_id" value="' . esc_attr( (string) $project_id ) . '"><input type="hidden" name="workflow_action" value="' . esc_attr( $action ) . '">';
        wp_nonce_field( 'ambra_workflow_' . $project_id );
        if ( $reason ) {
            echo '<div><input class="uk-input" type="text" name="reason" placeholder="Grund" required></div>';
        }
        echo '<div><button class="uk-button ' . esc_attr( $button_class ) . '" type="submit">' . esc_html( $label ) . '</button></div></form></div>';
    }

    private static function render_child_rows( string $entity_key, int $project_id ): void {
        $config       = self::entity_configs()[ $entity_key ];
        $relation_key = 'visits' === $entity_key ? 'ambra_visit_project' : 'ambra_position_project';
        $ids          = get_posts(
            array(
                'post_type'      => $config['post_type'],
                'post_status'    => 'private',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array( array( 'key' => $relation_key, 'value' => $project_id ) ),
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );

        $second_label = 'visits' === $entity_key ? 'Termin' : 'Status';
        $third_label  = 'visits' === $entity_key ? 'Status' : 'Summe';
        echo '<div class="uk-overflow-auto"><table class="uk-table uk-table-divider uk-table-hover uk-table-middle uk-table-responsive">';
        echo '<thead><tr><th>Eintrag</th><th>' . esc_html( $second_label ) . '</th><th>' . esc_html( $third_label ) . '</th><th class="uk-table-shrink"></th></tr></thead><tbody>';
        if ( ! $ids ) {
            echo '<tr><td colspan="4" class="uk-text-muted">Noch keine Einträge.</td></tr>';
        }
        foreach ( $ids as $id ) {
            $id = (int) $id;
            echo '<tr><td data-label="Eintrag"><strong>' . esc_html( Utils::post_title( $id ) ) . '</strong>' . self::demo_label( $id ) . '</td>';
            if ( 'visits' === $entity_key ) {
                echo '<td data-label="Termin">' . esc_html( Utils::datetime( Utils::field( 'ambra_visit_datetime', $id ) ) ) . '</td>';
                echo '<td data-label="Status"><span class="uk-label">' . esc_html( self::visit_status_label( (string) Utils::field( 'ambra_visit_status', $id ) ) ) . '</span></td>';
            } else {
                echo '<td data-label="Status"><span class="uk-label">' . esc_html( self::position_status_label( (string) Utils::field( 'ambra_position_status', $id ) ) ) . '</span></td>';
                echo '<td data-label="Summe">' . esc_html( Utils::money( Utils::field( 'ambra_line_total', $id, 0 ) ) ) . '</td>';
            }
            echo '<td data-label="Aktionen" class="uk-text-nowrap"><a class="uk-button uk-button-text" href="' . esc_url( Pages::get_url( $config['page'], array( 'edit' => $id, 'project' => $project_id ) ) ) . '">Bearbeiten</a></td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public static function entity_shortcode( string $key ): string {
        if ( $guard = self::guard() ) {
            return $guard;
        }

        $configs = self::entity_configs();
        if ( ! isset( $configs[ $key ] ) ) {
            return '';
        }
        $config = $configs[ $key ];

        if ( in_array( $key, array( 'blueprints', 'manufacturers', 'suppliers' ), true ) && ! current_user_can( 'read_private_ambra_catalogs' ) ) {
            return self::alert( 'Keine Berechtigung.', 'danger' );
        }
        if ( 'team' === $key && ! current_user_can( 'read_private_ambra_team_members' ) && ! current_user_can( 'edit_ambra_team_members' ) ) {
            return self::alert( 'Keine Berechtigung.', 'danger' );
        }

        $action = ! empty( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
        $edit   = ! empty( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;
        if ( 'new' === $action || $edit ) {
            return self::render_acf_entity_form( $key, $edit );
        }

        ob_start();
        echo self::shell_start( $config['plural'] );
        echo '<div class="uk-flex uk-flex-between uk-flex-middle uk-flex-wrap uk-grid-small uk-margin-medium-bottom" uk-grid>';
        echo '<div class="uk-width-expand@s">' . self::search_form( 'Suchen' ) . '</div>';
        if ( current_user_can( $config['create_cap'] ) ) {
            $args = array( 'action' => 'new' );
            if ( ! empty( $_GET['project'] ) ) {
                $args['project'] = absint( wp_unslash( $_GET['project'] ) );
            }
            echo '<div class="uk-width-auto@s"><a class="uk-button uk-button-primary" href="' . esc_url( Pages::get_url( $config['page'], $args ) ) . '"><span uk-icon="plus"></span> ' . esc_html( $config['singular'] ) . ' anlegen</a></div>';
        }
        echo '</div>';
        echo '<div class="uk-card uk-card-default uk-card-body">';
        self::render_entity_table( $key, $config );
        echo '</div>';
        echo self::shell_end();
        return (string) ob_get_clean();
    }

    private static function render_acf_entity_form( string $key, int $edit = 0 ): string {
        $configs = self::entity_configs();
        $config  = 'projects' === $key
            ? array( 'post_type' => 'ambra_project', 'page' => 'projects', 'plural' => 'Aufträge', 'singular' => 'Auftrag', 'create_cap' => 'edit_ambra_records' )
            : ( $configs[ $key ] ?? null );

        if ( ! $config ) {
            return '';
        }

        if ( ! $edit && 'positions' === $key && ! empty( $_GET['project'] ) ) {
            $project_id = absint( wp_unslash( $_GET['project'] ) );
            if ( $project_id && Workflow::is_at_or_after( $project_id, 'quote_sent' ) ) {
                return self::alert( 'Nach dem Angebotsversand können keine neuen Positionen mehr angelegt werden.', 'warning' );
            }
        }

        if ( $edit ) {
            if ( $config['post_type'] !== get_post_type( $edit ) ) {
                return self::alert( 'Eintrag nicht gefunden.', 'danger' );
            }
            $obj = get_post_type_object( $config['post_type'] );
            if ( ! $obj || ! current_user_can( $obj->cap->edit_post, $edit ) ) {
                return self::alert( 'Keine Berechtigung.', 'danger' );
            }
        } elseif ( ! current_user_can( $config['create_cap'] ) ) {
            return self::alert( 'Keine Berechtigung.', 'danger' );
        }

        $return_args = array( 'saved' => 1 );
        if ( 'projects' === $key ) {
            $return_args['id'] = '%post_id%';
        } else {
            $return_args['edit'] = '%post_id%';
            if ( ! empty( $_GET['project'] ) ) {
                $return_args['project'] = absint( wp_unslash( $_GET['project'] ) );
            }
        }

        ob_start();
        echo self::shell_start( ( $edit ? 'Bearbeiten: ' : 'Neu: ' ) . $config['singular'] );
        echo '<div class="uk-card uk-card-default uk-card-body">';
        if ( 'team' === $key ) {
            echo '<div class="uk-alert-primary uk-margin-bottom" uk-alert><p>Der WordPress-Benutzer wird anhand der E-Mail-Adresse automatisch angelegt oder verknüpft. Eine manuelle Benutzerverknüpfung ist nicht erforderlich.</p></div>';
        }
        self::acf_form( $config['post_type'], $edit, Pages::get_url( $config['page'], $return_args ), $edit ? 'Speichern' : 'Anlegen' );
        echo '</div>';
        if ( $edit && 'ambra_position' === $config['post_type'] ) {
            self::render_position_actions( $edit );
        }
        echo self::shell_end();
        return (string) ob_get_clean();
    }

    private static function acf_form( string $post_type, int $edit, string $return, string $submit ): void {
        $settings = array(
            'id'              => 'ambra-form-' . $post_type . '-' . ( $edit ?: 'new' ),
            'post_id'         => $edit ?: 'new_post',
            'post_title'      => false,
            'post_content'    => false,
            'fields'          => ACF_Integration::frontend_fields( $post_type, $edit ),
            'return'          => $return,
            'submit_value'    => $submit,
            'updated_message' => 'Gespeichert.',
            'uploader'        => 'wp',
            'honeypot'       => true,
            'kses'            => true,
            'html_submit_button' => '<input type="submit" class="acf-button button button-primary button-large" value="%s">',
        );
        if ( ! $edit ) {
            $settings['new_post'] = array(
                'post_type'   => $post_type,
                'post_status' => 'private',
                'post_title'  => 'Neuer Eintrag',
            );
        }
        acf_form( $settings );
    }

    private static function render_position_actions( int $position_id ): void {
        $status  = (string) Utils::field( 'ambra_position_status', $position_id, 'recorded' );
        $actions = array();
        if ( 'ordered' === $status ) {
            $actions['mark_delivered'] = 'Als geliefert markieren';
        }
        if ( 'delivered' === $status ) {
            $actions['mark_installed'] = 'Als montiert markieren';
        }
        if ( in_array( $status, array( 'recorded', 'calculated' ), true ) ) {
            $actions['cancel'] = 'Position stornieren';
        }
        if ( ! $actions ) {
            return;
        }

        echo '<div class="uk-card uk-card-default uk-card-body uk-margin-large-top"><h2 class="uk-card-title">Positionsstatus</h2><div class="uk-flex uk-flex-wrap uk-grid-small" uk-grid>';
        foreach ( $actions as $action => $label ) {
            echo '<div><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            echo '<input type="hidden" name="action" value="ambra_position_action"><input type="hidden" name="position_id" value="' . esc_attr( (string) $position_id ) . '"><input type="hidden" name="position_action" value="' . esc_attr( $action ) . '">';
            wp_nonce_field( 'ambra_position_' . $position_id );
            echo '<button class="uk-button uk-button-default" type="submit">' . esc_html( $label ) . '</button></form></div>';
        }
        echo '</div></div>';
    }

    private static function render_entity_table( string $key, array $config ): void {
        $args = array(
            'post_type'      => $config['post_type'],
            'post_status'    => 'private',
            'posts_per_page' => 50,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            's'              => self::search_term(),
        );

        if ( in_array( $key, array( 'visits', 'positions' ), true ) && ! empty( $_GET['project'] ) ) {
            $args['meta_query'] = array(
                array(
                    'key'   => 'visits' === $key ? 'ambra_visit_project' : 'ambra_position_project',
                    'value' => absint( wp_unslash( $_GET['project'] ) ),
                ),
            );
        }

        $query = new WP_Query( $args );
        echo '<div class="uk-overflow-auto"><table class="uk-table uk-table-divider uk-table-hover uk-table-middle uk-table-responsive"><thead><tr>';
        foreach ( $config['columns'] as $label ) {
            echo '<th>' . esc_html( $label ) . '</th>';
        }
        echo '<th class="uk-table-shrink"></th></tr></thead><tbody>';

        if ( ! $query->have_posts() ) {
            echo '<tr><td colspan="' . esc_attr( (string) ( count( $config['columns'] ) + 1 ) ) . '" class="uk-text-muted">Keine Einträge gefunden.</td></tr>';
        }

        while ( $query->have_posts() ) {
            $query->the_post();
            $id = get_the_ID();
            echo '<tr>';
            foreach ( $config['columns'] as $column => $label ) {
                echo '<td data-label="' . esc_attr( $label ) . '">' . self::cell( (string) $column, $id ) . '</td>';
            }
            echo '<td data-label="Aktionen" class="uk-text-nowrap">';
            $obj = get_post_type_object( $config['post_type'] );
            if ( $obj && current_user_can( $obj->cap->edit_post, $id ) ) {
                echo '<a class="uk-button uk-button-text" href="' . esc_url( Pages::get_url( $config['page'], array( 'edit' => $id ) ) ) . '"><span uk-icon="pencil"></span> Bearbeiten</a>';
            }

            $can_delete = self::can_trash_record( $id, $config['post_type'] );
            if ( $can_delete ) {
                $url = wp_nonce_url( admin_url( 'admin-post.php?action=ambra_trash_record&record_id=' . $id ), 'ambra_trash_' . $id );
                echo ' <a class="uk-button uk-button-text uk-text-danger" data-ambra-confirm="Eintrag wirklich in den Papierkorb verschieben?" href="' . esc_url( $url ) . '"><span uk-icon="trash"></span> Papierkorb</a>';
            }
            echo '</td></tr>';
        }
        wp_reset_postdata();
        echo '</tbody></table></div>';
    }

    private static function can_trash_record( int $id, string $post_type ): bool {
        $obj = get_post_type_object( $post_type );
        if ( ! $obj || ! current_user_can( $obj->cap->delete_post, $id ) ) {
            return false;
        }
        if ( 'ambra_position' === $post_type ) {
            $project_id = Utils::relation_id( Utils::field( 'ambra_position_project', $id ) );
            if ( $project_id && Workflow::is_at_or_after( $project_id, 'quote_sent' ) ) {
                return false;
            }
        }
        return true;
    }

    private static function cell( string $column, int $id ): string {
        switch ( $column ) {
            case 'name':
                return esc_html( Utils::post_title( $id ) ) . self::demo_label( $id );
            case 'phone':
                return esc_html( (string) Utils::field( 'ambra_phone', $id, '—' ) );
            case 'email':
                return esc_html( (string) Utils::field( 'ambra_email', $id, '—' ) );
            case 'website':
                $url = (string) Utils::field( 'ambra_website', $id );
                return $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">Website <span uk-icon="link"></span></a>' : '—';
            case 'date':
                return esc_html( Utils::datetime( Utils::field( 'ambra_visit_datetime', $id ) ) );
            case 'project':
                $field   = 'ambra_visit' === get_post_type( $id ) ? 'ambra_visit_project' : 'ambra_position_project';
                $project = Utils::relation_id( Utils::field( $field, $id ) );
                return esc_html( $project ? Utils::post_title( $project ) : '—' );
            case 'visit_type':
                return '<span class="uk-label">' . esc_html( self::visit_type_label( (string) Utils::field( 'ambra_visit_type', $id ) ) ) . '</span>';
            case 'visit_status':
                return '<span class="uk-label">' . esc_html( self::visit_status_label( (string) Utils::field( 'ambra_visit_status', $id ) ) ) . '</span>';
            case 'position':
                return esc_html( Utils::post_title( $id ) ) . self::demo_label( $id );
            case 'blueprint':
                $blueprint = Utils::relation_id( Utils::field( 'ambra_position_blueprint', $id ) );
                return esc_html( $blueprint ? Utils::post_title( $blueprint ) : 'Noch nicht gewählt' );
            case 'position_status':
                return '<span class="uk-label">' . esc_html( self::position_status_label( (string) Utils::field( 'ambra_position_status', $id ) ) ) . '</span>';
            case 'total':
                return esc_html( Utils::money( Utils::field( 'ambra_line_total', $id, 0 ) ) );
            case 'manufacturer':
                $manufacturer = Utils::relation_id( Utils::field( 'ambra_blueprint_manufacturer', $id ) );
                return esc_html( $manufacturer ? Utils::post_title( $manufacturer ) : '—' );
            case 'price_model':
                return esc_html( self::price_model_label( (string) Utils::field( 'ambra_blueprint_price_model', $id, 'fixed' ) ) );
            case 'markup':
                return current_user_can( 'ambra_view_internal_prices' )
                    ? esc_html( number_format_i18n( (float) Utils::field( 'ambra_blueprint_markup_percent', $id, 0 ), 2 ) . ' %' )
                    : '—';
            case 'wp_user':
                $user_id = Utils::relation_id( Utils::field( 'ambra_linked_user', $id ) );
                $user    = $user_id ? get_user_by( 'id', $user_id ) : false;
                return esc_html( $user ? $user->user_login : 'Wird automatisch erstellt' );
        }
        return '—';
    }

    private static function search_form( string $placeholder ): string {
        ob_start();
        echo '<form class="uk-grid-small" uk-grid method="get">';
        if ( ! empty( $_GET['project'] ) ) {
            echo '<input type="hidden" name="project" value="' . esc_attr( (string) absint( wp_unslash( $_GET['project'] ) ) ) . '">';
        }
        echo '<div class="uk-width-expand"><div class="uk-search uk-search-default uk-width-1-1"><span uk-search-icon></span><input class="uk-search-input" type="search" name="q" value="' . esc_attr( self::search_term() ) . '" placeholder="' . esc_attr( $placeholder ) . '" aria-label="' . esc_attr( $placeholder ) . '"></div></div><div class="uk-width-auto"><button class="uk-button uk-button-default" type="submit">Suchen</button></div></form>';
        return (string) ob_get_clean();
    }

    private static function demo_label( int $id ): string {
        return Utils::is_demo( $id ) ? ' <span class="uk-label uk-label-warning uk-margin-small-left">Beispiel</span>' : '';
    }

    public static function trash_record(): void {
        $id        = ! empty( $_GET['record_id'] ) ? absint( wp_unslash( $_GET['record_id'] ) ) : 0;
        $post_type = $id ? (string) get_post_type( $id ) : '';
        if ( ! $id || ! $post_type || ! Utils::is_ambra_post_type( $post_type ) ) {
            wp_die( 'Ungültiger Eintrag.' );
        }
        if ( ! self::can_trash_record( $id, $post_type ) ) {
            wp_die( 'Keine Berechtigung oder Datensatz ist durch den Workflow gesperrt.' );
        }
        check_admin_referer( 'ambra_trash_' . $id );
        wp_trash_post( $id );
        wp_safe_redirect( wp_get_referer() ?: home_url( '/' ) );
        exit;
    }

    private static function search_term(): string {
        return ! empty( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
    }

    private static function count_projects_by_state( string $state ): int {
        $query = new WP_Query(
            array(
                'post_type'      => 'ambra_project',
                'post_status'    => 'private',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_key'       => 'ambra_project_state',
                'meta_value'     => $state,
            )
        );
        return (int) $query->found_posts;
    }

    private static function count_projects_by_stage( string $stage ): int {
        $query = new WP_Query(
            array(
                'post_type'      => 'ambra_project',
                'post_status'    => 'private',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array( 'key' => 'ambra_project_state', 'value' => 'active' ),
                    array( 'key' => 'ambra_project_stage', 'value' => $stage ),
                ),
            )
        );
        return (int) $query->found_posts;
    }

    private static function visit_type_label( string $value ): string {
        return array( 'initial' => 'Erstbesichtigung', 'follow_up' => 'Nachbesichtigung', 'remeasure' => 'Nachmessung', 'other' => 'Sonstige' )[ $value ] ?? $value;
    }

    private static function visit_status_label( string $value ): string {
        return array( 'planned' => 'Geplant', 'completed' => 'Durchgeführt', 'cancelled' => 'Abgesagt' )[ $value ] ?? $value;
    }

    private static function position_status_label( string $value ): string {
        return array( 'recorded' => 'Erfasst', 'calculated' => 'Kalkuliert', 'ordered' => 'Bestellt', 'delivered' => 'Geliefert', 'installed' => 'Montiert', 'cancelled' => 'Storniert' )[ $value ] ?? $value;
    }

    private static function price_model_label( string $value ): string {
        return array( 'fixed' => 'Fixpreis / Stück', 'unit' => 'Stückpreis', 'sqm' => 'Preis pro m²' )[ $value ] ?? $value;
    }

    private static function project_state_label( string $value ): string {
        return array( 'active' => 'Aktiv', 'cancelled' => 'Beendet', 'completed' => 'Abgeschlossen' )[ $value ] ?? $value;
    }
}
