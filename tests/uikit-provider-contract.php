<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('YOO_THEME_PATH', '/test/yootheme');

$GLOBALS['ambra_pm_test_uikit_override'] = false;

function apply_filters($hook, $value) {
    if ('ambra_pm_uikit_provider_info' !== $hook || empty($GLOBALS['ambra_pm_test_uikit_override'])) {
        return $value;
    }

    $value['active'] = true;
    $value['provider'] = 'custom';
    $value['label'] = 'Custom UIkit Provider';
    $value['signals'][] = 'test:custom-provider';

    return $value;
}

require_once dirname(__DIR__) . '/includes/class-ambra-utils.php';

function fail_uikit_contract(string $message): void {
    fwrite(STDERR, "UIkit provider contract failed: {$message}\n");
    exit(1);
}

$yootheme = \AMBRA_PM\Utils::uikit_provider_info();

if (true !== $yootheme['active']) {
    fail_uikit_contract('YOOtheme should remain an automatically detected UIkit provider.');
}
if ('yootheme' !== $yootheme['provider']) {
    fail_uikit_contract('YOOtheme provider identity changed.');
}
if (!in_array('constant:YOO_THEME_PATH', $yootheme['signals'], true)) {
    fail_uikit_contract('YOOtheme detection signal is missing.');
}

$GLOBALS['ambra_pm_test_uikit_override'] = true;
$custom = \AMBRA_PM\Utils::uikit_provider_info();

if (true !== $custom['active']) {
    fail_uikit_contract('Custom UIkit provider should be able to declare itself active.');
}
if ('custom' !== $custom['provider']) {
    fail_uikit_contract('Custom UIkit provider filter was not applied.');
}
if ('Custom UIkit Provider' !== $custom['label']) {
    fail_uikit_contract('Custom UIkit provider label was not preserved.');
}
if (!in_array('test:custom-provider', $custom['signals'], true)) {
    fail_uikit_contract('Custom UIkit provider signal was not preserved.');
}

fwrite(STDOUT, "UIkit provider contract passed.\n");
