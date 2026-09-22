<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

final class Admin {
    public static function init(): void {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ), 5 );
        add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( AMBRA_PM_FILE ), array( __CLASS__, 'action_links' ) );
    }

    public static function menu(): void {
        add_menu_page(
            'AMBRA Projektmanagement',
            'AMBRA',
            'ambra_manage_settings',
            'ambra-project-management',
            array( __CLASS__, 'render' ),
            'dashicons-clipboard',
            26
        );
        add_submenu_page(
            'ambra-project-management',
            'Einrichtung',
            'Einrichtung',
            'ambra_manage_settings',
            'ambra-project-management',
            array( __CLASS__, 'render' )
        );
    }

    public static function dependency_notice(): void {
        if ( ! current_user_can( 'activate_plugins' ) || ACF_Integration::is_ready() ) {
            return;
        }
        echo '<div class="notice notice-error"><p><strong>AMBRA Projektmanagement:</strong> ACF Pro ist erforderlich. Bitte ACF Pro installieren und aktivieren.</p></div>';
    }

    public static function action_links( array $links ): array {
        if ( current_user_can( 'ambra_manage_settings' ) ) {
            array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=ambra-project-management' ) ) . '">Einrichtung</a>' );
            array_unshift( $links, '<a href="' . esc_url( home_url( '/' ) ) . '">Dashboard</a>' );
        }
        return $links;
    }

    public static function render(): void {
        if ( ! current_user_can( 'ambra_manage_settings' ) ) {
            wp_die( 'Keine Berechtigung.' );
        }

        $pages          = (array) get_option( Pages::OPTION, array() );
        $front_page_id  = absint( get_option( 'page_on_front' ) );
        $dashboard_id   = Pages::get_id( 'dashboard' );
        $yootheme_info  = Utils::yootheme_info();
        $yootheme       = (bool) $yootheme_info['active'];
        $yootheme_label = $yootheme ? 'Erkannt: ' . (string) $yootheme_info['label'] : 'Nicht erkannt – aktives Theme und Parent-Theme geprüft';

        echo '<div class="wrap"><h1>AMBRA Projektmanagement</h1>';
        echo '<p>Die operative Auftragsabwicklung findet im geschützten Frontend statt. Nur Administratoren können das WordPress-Backend öffnen.</p>';

        if ( isset( $_GET['pages'] ) && 'repaired' === $_GET['pages'] ) {
            echo '<div class="notice notice-success is-dismissible"><p>Frontend-Seiten inklusive Login wurden geprüft, auf die oberste Ebene verschoben und die Dashboardseite wurde als Startseite gesetzt.</p></div>';
        }
        if ( isset( $_GET['demo'] ) && 'seeded' === $_GET['demo'] ) {
            echo '<div class="notice notice-success is-dismissible"><p>Beispieldaten wurden neu angelegt.</p></div>';
        }
        if ( isset( $_GET['demo'] ) && 'deleted' === $_GET['demo'] ) {
            echo '<div class="notice notice-success is-dismissible"><p>Beispieldaten wurden gelöscht.</p></div>';
        }

        echo '<h2>Systemstatus</h2><table class="widefat striped" style="max-width:1000px"><tbody>';
        self::status_row( 'ACF Pro', ACF_Integration::is_ready(), ACF_Integration::is_ready() ? 'Aktiv' : 'Fehlt oder ist nicht aktiv' );
        self::status_row( 'YOOtheme Pro / UIkit', $yootheme, $yootheme_label );
        self::status_row( 'Private Website', '0' === (string) get_option( 'blog_public' ), 'Nicht angemeldete Besucher werden zu /login/ umgeleitet' );
        self::status_row( 'Frontend-Login', Pages::get_id( 'login' ) > 0, Pages::get_id( 'login' ) ? 'Loginseite vorhanden: ' . Pages::get_url( 'login' ) : 'Loginseite fehlt' );
        self::status_row( 'Dashboard als Startseite', $dashboard_id && $front_page_id === $dashboard_id && 'page' === get_option( 'show_on_front' ), $dashboard_id && $front_page_id === $dashboard_id ? 'Aktiv' : 'Nicht korrekt gesetzt' );
        self::status_row( 'Frontend-Seiten', count( array_filter( $pages ) ) === count( Pages::definitions() ), count( array_filter( $pages ) ) . ' / ' . count( Pages::definitions() ) . ' vorhanden' );
        self::status_row( 'Seitenhierarchie', Pages::top_level_count() === count( Pages::definitions() ), Pages::top_level_count() . ' / ' . count( Pages::definitions() ) . ' auf oberster Ebene' );
        self::status_row( 'Beispieldaten', Demo_Data::count() > 0, Demo_Data::count() . ' Beispieldatensätze vorhanden' );
        echo '<tr><td><strong>Plugin-Version</strong></td><td>' . esc_html( AMBRA_PM_VERSION ) . '</td></tr>';
        echo '</tbody></table>';

        echo '<h2>Frontend</h2><p><a class="button button-primary" href="' . esc_url( home_url( '/' ) ) . '">Dashboard öffnen</a></p>';
        echo '<p><strong>Login-URL:</strong> <code>' . esc_html( Pages::get_url( 'login' ) ) . '</code> – zum Testen in einem privaten Browserfenster öffnen.</p>';

        echo '<h2>Seiten prüfen und Startseite reparieren</h2>';
        echo '<p>Fehlende Systemseiten einschließlich der Loginseite werden erstellt. Alle AMBRA-Seiten werden auf die oberste Seitenebene gesetzt. Bestehende Seiteninhalte und YOOtheme-Layouts werden nicht überschrieben. Die Dashboardseite wird als statische Startseite gesetzt.</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ambra_repair_pages">';
        wp_nonce_field( 'ambra_repair_pages' );
        submit_button( 'Seiten prüfen / reparieren', 'secondary', 'submit', false );
        echo '</form>';

        echo '<h2>Beispieldaten</h2>';
        echo '<p>Die Datensätze sind mit „Beispiel“ gekennzeichnet. Beim Neuerstellen werden ausschließlich bisherige AMBRA-Beispieldaten entfernt; eigene Daten bleiben unangetastet.</p>';
        echo '<div style="display:flex;gap:10px;align-items:center">';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ambra_seed_demo">';
        wp_nonce_field( 'ambra_seed_demo' );
        submit_button( 'Beispieldaten neu anlegen', 'secondary', 'submit', false );
        echo '</form>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Alle AMBRA-Beispieldaten löschen? Eigene Daten bleiben erhalten.\')"><input type="hidden" name="action" value="ambra_delete_demo">';
        wp_nonce_field( 'ambra_delete_demo' );
        submit_button( 'Beispieldaten löschen', 'delete', 'submit', false );
        echo '</form></div>';

        echo '<h2>YOOtheme Pro und Navigation</h2>';
        echo '<p>Das Plugin gibt bewusst keine eigene obere Menüleiste mehr aus. Lege die Navigation selbst als WordPress-Menü an und veröffentliche sie über YOOtheme Pro. Die Shortcodes können in YOOtheme über ein Shortcode-Element platziert werden.</p>';
        echo '<table class="widefat striped" style="max-width:1000px"><thead><tr><th>Bereich</th><th>Seite</th><th>Shortcode</th></tr></thead><tbody>';
        foreach ( Pages::definitions() as $key => $definition ) {
            $page_id = Pages::get_id( $key );
            echo '<tr><td>' . esc_html( $definition['title'] ) . '</td><td>';
            if ( $page_id ) {
                echo '<a href="' . esc_url( get_edit_post_link( $page_id ) ) . '">Bearbeiten</a> · <a href="' . esc_url( get_permalink( $page_id ) ) . '">Ansehen</a>';
            } else {
                echo 'Fehlt';
            }
            echo '</td><td><code>' . esc_html( $definition['shortcode'] ) . '</code></td></tr>';
        }
        echo '</tbody></table>';

        echo '<h2>Team-Synchronisation</h2>';
        echo '<p>Beim Anlegen eines Teammitglieds wird der WordPress-Benutzer automatisch über die E-Mail-Adresse erzeugt oder verknüpft. Wird im Backend ein Benutzer mit der Rolle <strong>Projektmitarbeiter</strong> angelegt, entsteht umgekehrt automatisch der Team-Datensatz.</p>';

        echo '<h2>Hinweis zu Fotos und PDFs</h2>';
        echo '<p>Seiten, REST-Zugriffe und das AMBRA-Backend sind geschützt. Direkte URLs zu Dateien unter <code>wp-content/uploads</code> werden jedoch vom Webserver ausgeliefert. Für vollständig private Kundenfotos und PDFs ist zusätzlich eine hostingabhängige Apache-/Nginx-Regel oder eine Private-Media-Lösung erforderlich.</p>';
        echo '</div>';
    }

    private static function status_row( string $label, bool $ok, string $text ): void {
        echo '<tr><td><strong>' . esc_html( $label ) . '</strong></td><td><span style="color:' . ( $ok ? '#008a20' : '#b32d2e' ) . '">' . ( $ok ? '✓ ' : '✗ ' ) . esc_html( $text ) . '</span></td></tr>';
    }
}
