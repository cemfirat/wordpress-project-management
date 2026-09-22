<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

final class Plugin {
    private static ?Plugin $instance = null;

    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function boot(): void {
        Roles::init();
        Post_Types::init();
        Pages::init();
        Login::init();
        Privacy::init();
        ACF_Integration::init();
        Records::init();
        Team_Sync::init();
        Pricing::init();
        Workflow::init();
        Demo_Data::init();
        Frontend::init();
        Admin::init();

        add_action( 'init', array( $this, 'maybe_upgrade' ), 30 );
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
    }

    public function load_textdomain(): void {
        load_plugin_textdomain( 'ambra-project-management', false, dirname( plugin_basename( AMBRA_PM_FILE ) ) . '/languages' );
    }

    public static function activate(): void {
        Roles::register_roles_and_caps();
        Post_Types::register_post_types();
        Pages::create_pages();
        Privacy::configure_site();
        Team_Sync::sync_existing_project_members();
        Team_Sync::sync_existing_team_records();
        update_option( 'ambra_pm_pending_setup', '1', false );
        flush_rewrite_rules();
    }

    public function maybe_upgrade(): void {
        $installed     = (string) get_option( 'ambra_pm_version', '0.0.0' );
        $needs_upgrade = version_compare( $installed, AMBRA_PM_VERSION, '<' );
        $pending       = (bool) get_option( 'ambra_pm_pending_setup' );

        if ( $needs_upgrade ) {
            Roles::register_roles_and_caps();
            Pages::create_pages();
            Privacy::configure_site();
            Team_Sync::sync_existing_project_members();
            Team_Sync::sync_existing_team_records();
            update_option( 'ambra_pm_version', AMBRA_PM_VERSION, false );
            update_option( 'ambra_pm_pending_setup', '1', false );
            $pending = true;
            flush_rewrite_rules( false );
        }

        if ( $pending && ACF_Integration::is_ready() ) {
            Demo_Data::seed_once();
            delete_option( 'ambra_pm_pending_setup' );
        }
    }

    public static function deactivate(): void {
        // Geschäftsdaten, Benutzer, Rollen, Seiten und private Website-Einstellungen bleiben absichtlich erhalten.
        flush_rewrite_rules();
    }
}
