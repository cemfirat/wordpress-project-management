<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

require_once dirname(__DIR__) . '/includes/class-ambra-workflow.php';

function fail_workflow_contract(string $message): void {
    fwrite(STDERR, "Workflow invariant contract failed: {$message}\n");
    exit(1);
}

function expect_true($actual, string $message): void {
    if (true !== $actual) {
        fail_workflow_contract($message . ' Actual: ' . var_export($actual, true));
    }
}

function expect_blocked($actual, string $message): void {
    if (true === $actual || !is_string($actual) || '' === trim($actual)) {
        fail_workflow_contract($message . ' Actual: ' . var_export($actual, true));
    }
}

if (-1 !== \AMBRA_PM\Workflow::stage_index('not-a-stage')) {
    fail_workflow_contract('Unknown stages must not receive a valid stage index.');
}

expect_true(
    \AMBRA_PM\Workflow::validate_project_action_context('active', 'inquiry', 'advance'),
    'Normal active workflow advance should remain allowed.'
);

expect_blocked(
    \AMBRA_PM\Workflow::validate_project_action_context('active', 'quote_sent', 'advance'),
    'quote_sent must not advance to quote_accepted without an explicit decision.'
);

expect_true(
    \AMBRA_PM\Workflow::validate_project_action_context('active', 'quote_sent', 'accept_quote'),
    'Explicit quote acceptance must remain allowed at quote_sent.'
);

expect_true(
    \AMBRA_PM\Workflow::validate_project_action_context('active', 'quote_sent', 'reject_quote'),
    'Explicit quote rejection must remain allowed at quote_sent.'
);

expect_blocked(
    \AMBRA_PM\Workflow::validate_project_action_context('active', 'positions_ready', 'accept_quote'),
    'Quote acceptance must be rejected outside quote_sent.'
);

expect_blocked(
    \AMBRA_PM\Workflow::validate_project_action_context('cancelled', 'quote_sent', 'accept_quote'),
    'Inactive projects must reject quote decisions.'
);

expect_blocked(
    \AMBRA_PM\Workflow::validate_project_action_context('active', 'not-a-stage', 'advance'),
    'Unknown project stages must reject actions.'
);

$pre_quote = \AMBRA_PM\Workflow::position_actions('active', 'positions_ready', 'calculated');
if (!isset($pre_quote['cancel']) || 'cancelled' !== $pre_quote['cancel']['to']) {
    fail_workflow_contract('Calculated positions must be cancellable before quote_sent.');
}

$post_quote = \AMBRA_PM\Workflow::position_actions('active', 'quote_sent', 'calculated');
if (isset($post_quote['cancel'])) {
    fail_workflow_contract('Commercial positions must not be cancellable after quote_sent.');
}

$delivery = \AMBRA_PM\Workflow::position_actions('active', 'ordered', 'ordered');
if (!isset($delivery['mark_delivered']) || 'delivered' !== $delivery['mark_delivered']['to']) {
    fail_workflow_contract('Ordered positions must still support delivery.');
}

$inactive = \AMBRA_PM\Workflow::position_actions('cancelled', 'ordered', 'ordered');
if ($inactive) {
    fail_workflow_contract('Inactive projects must expose no position actions.');
}

fwrite(STDOUT, "Workflow invariant contract passed.\n");
