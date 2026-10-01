<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$GLOBALS['ambra_pm_test_disable_auto_seed'] = false;
$GLOBALS['ambra_pm_test_custom_seed'] = false;
$GLOBALS['ambra_pm_test_custom_seed_calls'] = 0;

function apply_filters($hook, $value) {
    if ('ambra_pm_demo_auto_seed' === $hook && !empty($GLOBALS['ambra_pm_test_disable_auto_seed'])) {
        return false;
    }

    if ('ambra_pm_demo_seed_callback' === $hook && !empty($GLOBALS['ambra_pm_test_custom_seed'])) {
        return static function (): array {
            ++$GLOBALS['ambra_pm_test_custom_seed_calls'];
            return array('profile' => 'custom');
        };
    }

    return $value;
}

require_once dirname(__DIR__) . '/includes/class-ambra-demo-data.php';

function fail_demo_seed_contract(string $message): void {
    fwrite(STDERR, "Demo seed contract failed: {$message}\n");
    exit(1);
}

if (true !== \AMBRA_PM\Demo_Data::automatic_seed_enabled()) {
    fail_demo_seed_contract('Automatic AMBRA demo seeding must remain enabled by default.');
}

$GLOBALS['ambra_pm_test_disable_auto_seed'] = true;
if (false !== \AMBRA_PM\Demo_Data::automatic_seed_enabled()) {
    fail_demo_seed_contract('Automatic demo seeding could not be disabled.');
}
$GLOBALS['ambra_pm_test_disable_auto_seed'] = false;

$GLOBALS['ambra_pm_test_custom_seed'] = true;
$result = \AMBRA_PM\Demo_Data::seed();

if (array('profile' => 'custom') !== $result) {
    fail_demo_seed_contract('Custom demo seed callback result was not returned.');
}
if (1 !== $GLOBALS['ambra_pm_test_custom_seed_calls']) {
    fail_demo_seed_contract('Custom demo seed callback was not called exactly once.');
}

fwrite(STDOUT, "Demo seed contract passed.\n");
