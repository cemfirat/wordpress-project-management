<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$GLOBALS['ambra_pm_test_identity_override'] = false;

function apply_filters($hook, $value) {
    if ('ambra_pm_product_identity' !== $hook || empty($GLOBALS['ambra_pm_test_identity_override'])) {
        return $value;
    }

    return array(
        'name' => 'Universal Project Management',
        'short_name' => 'UPM',
    );
}

require_once dirname(__DIR__) . '/includes/class-ambra-identity.php';

function fail_identity_contract(string $message): void {
    fwrite(STDERR, "Product identity contract failed: {$message}\n");
    exit(1);
}

$default = \AMBRA_PM\Identity::info();

if ('AMBRA Projektmanagement' !== $default['name']) {
    fail_identity_contract('Existing default product name changed.');
}
if ('AMBRA' !== $default['short_name']) {
    fail_identity_contract('Existing default short name changed.');
}

$GLOBALS['ambra_pm_test_identity_override'] = true;
$custom = \AMBRA_PM\Identity::info();

if ('Universal Project Management' !== $custom['name']) {
    fail_identity_contract('Custom product name filter was not applied.');
}
if ('UPM' !== $custom['short_name']) {
    fail_identity_contract('Custom short name filter was not applied.');
}

$admin_source = file_get_contents(dirname(__DIR__) . '/includes/class-ambra-admin.php');
if (false === $admin_source) {
    fail_identity_contract('Could not read admin source.');
}
if (false !== strpos($admin_source, "'AMBRA Projektmanagement'")) {
    fail_identity_contract('Admin UI still hard-codes the full AMBRA product name.');
}
if (false !== strpos($admin_source, "'AMBRA',")) {
    fail_identity_contract('Admin menu still hard-codes the AMBRA short name.');
}

fwrite(STDOUT, "Product identity contract passed.\n");
