<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$GLOBALS['ambra_pm_test_demo_override'] = false;

function test_custom_demo_seed(int $author): array {
    return array('custom_author' => $author);
}

function apply_filters($hook, $value) {
    if ('ambra_pm_demo_seed_callback' !== $hook || empty($GLOBALS['ambra_pm_test_demo_override'])) {
        return $value;
    }

    return 'test_custom_demo_seed';
}

require_once dirname(__DIR__) . '/includes/class-ambra-demo-profile.php';
require_once dirname(__DIR__) . '/includes/class-ambra-demo-data.php';

function fail_demo_profile_contract(string $message): void {
    fwrite(STDERR, "Demo profile contract failed: {$message}\n");
    exit(1);
}

$default = \AMBRA_PM\Demo_Data::seed_callback();
if (!is_array($default) || \AMBRA_PM\Demo_Profile::class !== $default[0] || 'seed' !== $default[1]) {
    fail_demo_profile_contract('Default AMBRA demo profile changed.');
}

$GLOBALS['ambra_pm_test_demo_override'] = true;
$custom = \AMBRA_PM\Demo_Data::seed_callback();
if ('test_custom_demo_seed' !== $custom) {
    fail_demo_profile_contract('Custom demo seed callback was not applied.');
}

$core = file_get_contents(dirname(__DIR__) . '/includes/class-ambra-demo-data.php');
$profile = file_get_contents(dirname(__DIR__) . '/includes/class-ambra-demo-profile.php');

if (false === $core || false === $profile) {
    fail_demo_profile_contract('Could not read demo source files.');
}
foreach (array('HELLA (Beispiel)', 'Insektenschutz Spannrahmen Standard', 'Fliegengitter Büro Muster GmbH') as $industry_example) {
    if (false !== strpos($core, $industry_example)) {
        fail_demo_profile_contract('Industry-specific example content leaked back into Demo_Data core.');
    }
    if (false === strpos($profile, $industry_example)) {
        fail_demo_profile_contract('Default AMBRA demo profile lost expected example content.');
    }
}

if (false === strpos($core, "'_ambra_pm_demo'")) {
    fail_demo_profile_contract('Stable demo marker is no longer owned by the core lifecycle.');
}

fwrite(STDOUT, "Demo profile contract passed.\n");
