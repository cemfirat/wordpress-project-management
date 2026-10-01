<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

$GLOBALS['ambra_pm_test_number_override'] = false;
$GLOBALS['ambra_pm_test_empty_number_override'] = false;

function wp_date($format) {
    return 'Y' === $format ? '2026' : '';
}

function apply_filters($hook, $value, ...$args) {
    if ('ambra_pm_project_number' !== $hook) {
        return $value;
    }

    if (!empty($GLOBALS['ambra_pm_test_empty_number_override'])) {
        return '   ';
    }

    if (!empty($GLOBALS['ambra_pm_test_number_override'])) {
        $post_id = (int) ($args[0] ?? 0);
        $year = (string) ($args[1] ?? '');
        return sprintf('PRJ-%s-%04d', $year, $post_id);
    }

    return $value;
}

function sanitize_text_field($value) {
    return trim((string) $value);
}

require_once dirname(__DIR__) . '/includes/class-ambra-records.php';

function fail_numbering_contract(string $message): void {
    fwrite(STDERR, "Project numbering contract failed: {$message}\n");
    exit(1);
}

$default = \AMBRA_PM\Records::project_number(42);
if ('AMB-2026-000042' !== $default) {
    fail_numbering_contract('Existing AMBRA default project-number format changed: ' . $default);
}

$GLOBALS['ambra_pm_test_number_override'] = true;
$custom = \AMBRA_PM\Records::project_number(42);
if ('PRJ-2026-0042' !== $custom) {
    fail_numbering_contract('Custom project-number filter was not applied: ' . $custom);
}

$GLOBALS['ambra_pm_test_number_override'] = false;
$GLOBALS['ambra_pm_test_empty_number_override'] = true;
$fallback = \AMBRA_PM\Records::project_number(42);
if ('AMB-2026-000042' !== $fallback) {
    fail_numbering_contract('Empty custom project number must fall back to the AMBRA default.');
}

fwrite(STDOUT, "Project numbering contract passed.\n");
