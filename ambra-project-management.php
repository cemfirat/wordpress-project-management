<?php
/**
 * Plugin Name: AMBRA Projektmanagement
 * Description: Geschlossene Frontend-Anwendung für Projektmanagement und Auftragsabwicklung mit ACF Pro, YOOtheme Pro und UIkit.
 * Version: 2.0.2
 * Author: AMBRA / Cem Firat
 * Text Domain: ambra-project-management
 * Requires at least: 6.5
 * Requires PHP: 8.0
 */

defined( 'ABSPATH' ) || exit;

define( 'AMBRA_PM_VERSION', '2.0.2' );
define( 'AMBRA_PM_FILE', __FILE__ );
define( 'AMBRA_PM_DIR', plugin_dir_path( __FILE__ ) );
define( 'AMBRA_PM_URL', plugin_dir_url( __FILE__ ) );

require_once AMBRA_PM_DIR . 'includes/class-ambra-utils.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-roles.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-post-types.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-pages.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-login.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-privacy.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-acf.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-records.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-team-sync.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-pricing.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-workflow.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-demo-data.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-frontend.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-admin.php';
require_once AMBRA_PM_DIR . 'includes/class-ambra-plugin.php';

register_activation_hook( __FILE__, array( '\AMBRA_PM\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\AMBRA_PM\Plugin', 'deactivate' ) );

\AMBRA_PM\Plugin::instance()->boot();
