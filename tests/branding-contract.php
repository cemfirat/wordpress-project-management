<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$GLOBALS['ambra_pm_test_branding_override'] = false;

function apply_filters($hook, $value) {
    if ('ambra_pm_branding' !== $hook || empty($GLOBALS['ambra_pm_test_branding_override'])) {
        return $value;
    }

    return array(
        'name' => 'Universal Project Management',
        'short_name' => 'UPM',
    );
}

require_once dirname(__DIR__) . '/includes/class-ambra-branding.php';

function fail_branding_contract(string $message): void {
    fwrite(STDERR, "Branding contract failed: {$message}\n");
    exit(1);
}

$default = \AMBRA_PM\Branding::info();
if ('AMBRA Projektmanagement' !== $default['name'] || 'AMBRA' !== $default['short_name']) {
    fail_branding_contract('Default AMBRA branding changed.');
}

$GLOBALS['ambra_pm_test_branding_override'] = true;
$custom = \AMBRA_PM\Branding::info();

if ('Universal Project Management' !== $custom['name']) {
    fail_branding_contract('Custom product name was not applied.');
}
if ('UPM' !== $custom['short_name']) {
    fail_branding_contract('Custom short name was not applied.');
}

fwrite(STDOUT, "Branding contract passed.\n");
