<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

require_once dirname(__DIR__) . '/includes/class-ambra-post-types.php';
require_once dirname(__DIR__) . '/includes/class-ambra-pages.php';
require_once dirname(__DIR__) . '/includes/class-ambra-roles.php';

function fail_contract(string $message): void {
    fwrite(STDERR, "Compatibility contract failed: {$message}\n");
    exit(1);
}

function assert_same_contract($expected, $actual, string $message): void {
    if ($expected !== $actual) {
        fail_contract($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}

function assert_contains_contract(string $needle, array $haystack, string $message): void {
    if (!in_array($needle, $haystack, true)) {
        fail_contract($message . " Missing: {$needle}");
    }
}

$expected_post_types = array(
    'ambra_customer',
    'ambra_project',
    'ambra_visit',
    'ambra_position',
    'ambra_blueprint',
    'ambra_manufacturer',
    'ambra_supplier',
    'ambra_team',
);

assert_same_contract(
    $expected_post_types,
    array_keys(\AMBRA_PM\Post_Types::definitions()),
    'Persisted custom post type identifiers changed.'
);

assert_same_contract(
    'ambra_pm_pages',
    \AMBRA_PM\Pages::OPTION,
    'Stored system-page option key changed.'
);

$pages = \AMBRA_PM\Pages::definitions();
$expected_page_keys = array(
    'login',
    'dashboard',
    'projects',
    'customers',
    'visits',
    'positions',
    'blueprints',
    'manufacturers',
    'suppliers',
    'team',
);

assert_same_contract(
    $expected_page_keys,
    array_keys($pages),
    'Managed system-page identity changed.'
);

$expected_shortcodes = array(
    'login' => '[ambra_login]',
    'dashboard' => '[ambra_dashboard]',
    'projects' => '[ambra_projects]',
    'customers' => '[ambra_customers]',
    'visits' => '[ambra_visits]',
    'positions' => '[ambra_positions]',
    'blueprints' => '[ambra_blueprints]',
    'manufacturers' => '[ambra_manufacturers]',
    'suppliers' => '[ambra_suppliers]',
    'team' => '[ambra_team]',
);

foreach ($expected_shortcodes as $key => $shortcode) {
    assert_same_contract(
        $shortcode,
        $pages[$key]['shortcode'] ?? null,
        "Legacy shortcode for {$key} changed."
    );
}

foreach (array('edit_ambra_record', 'read_ambra_record', 'edit_ambra_records', 'read_private_ambra_records') as $cap) {
    assert_contains_contract($cap, \AMBRA_PM\Roles::operational_caps(), 'Operational capability family changed.');
}

foreach (array('edit_ambra_catalog', 'read_ambra_catalog', 'edit_ambra_catalogs', 'read_private_ambra_catalogs') as $cap) {
    assert_contains_contract($cap, \AMBRA_PM\Roles::catalog_caps(), 'Catalog capability family changed.');
}

foreach (array('edit_ambra_team_member', 'read_ambra_team_member', 'edit_ambra_team_members', 'read_private_ambra_team_members') as $cap) {
    assert_contains_contract($cap, \AMBRA_PM\Roles::team_caps(), 'Team capability family changed.');
}

$plugin_source = file_get_contents(dirname(__DIR__) . '/includes/class-ambra-plugin.php');
$roles_source = file_get_contents(dirname(__DIR__) . '/includes/class-ambra-roles.php');

foreach (array('ambra_pm_version', 'ambra_pm_pending_setup') as $option_key) {
    if ($plugin_source === false || strpos($plugin_source, $option_key) === false) {
        fail_contract("Persistent plugin option missing: {$option_key}");
    }
}

if ($roles_source === false || strpos($roles_source, "'project_team_member'") === false) {
    fail_contract('Existing project_team_member role slug changed.');
}

$required_groups = array(
    'group_ambra_customer',
    'group_ambra_project',
    'group_ambra_visit',
    'group_ambra_position',
    'group_ambra_blueprint',
    'group_ambra_manufacturer',
    'group_ambra_supplier',
    'group_ambra_team',
);

$found_groups = array();
foreach (glob(dirname(__DIR__) . '/acf-json/*.json') ?: array() as $path) {
    $group = json_decode((string) file_get_contents($path), true);
    if (!is_array($group) || !isset($group['key'])) {
        fail_contract('Invalid ACF JSON in ' . basename($path));
    }
    $found_groups[] = (string) $group['key'];
}

foreach ($required_groups as $group_key) {
    assert_contains_contract($group_key, $found_groups, 'Required persisted ACF field group disappeared.');
}

fwrite(STDOUT, "Legacy compatibility contract passed.\n");
