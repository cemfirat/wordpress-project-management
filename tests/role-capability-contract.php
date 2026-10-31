<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

final class Test_Role {
    /** @var array<string,bool> */
    public array $caps;

    /** @param array<string,bool> $caps */
    public function __construct(array $caps = array()) {
        $this->caps = $caps;
    }

    public function add_cap(string $cap): void {
        $this->caps[$cap] = true;
    }
}

$GLOBALS['ambra_pm_test_roles'] = array(
    'administrator' => new Test_Role(array('manage_options' => true)),
);

function get_role($name) {
    return $GLOBALS['ambra_pm_test_roles'][$name] ?? null;
}

function add_role($slug, $display_name, $capabilities = array()) {
    unset($display_name);
    $role = new Test_Role($capabilities);
    $GLOBALS['ambra_pm_test_roles'][$slug] = $role;
    return $role;
}

require_once dirname(__DIR__) . '/includes/class-ambra-roles.php';

function fail_role_contract(string $message): void {
    fwrite(STDERR, "Role capability contract failed: {$message}\n");
    exit(1);
}

function assert_has_cap(Test_Role $role, string $cap, string $role_name): void {
    if (empty($role->caps[$cap])) {
        fail_role_contract("{$role_name} is missing required capability {$cap}.");
    }
}

\AMBRA_PM\Roles::register_roles_and_caps();
// Registration must remain safe to run again during upgrades.
\AMBRA_PM\Roles::register_roles_and_caps();

$member = get_role('project_team_member');
$admin = get_role('administrator');

if (!$member instanceof Test_Role) {
    fail_role_contract('project_team_member role was not created.');
}
if (!$admin instanceof Test_Role) {
    fail_role_contract('administrator role disappeared.');
}

// Keep the permitted policy independent of the production capability builders.
// Any additional granted capability must be explicitly reviewed in this contract.
$expected_member_caps = array(
    'read',
    'upload_files',
    'ambra_use_app',
    'edit_ambra_record',
    'read_ambra_record',
    'delete_ambra_record',
    'edit_ambra_records',
    'edit_others_ambra_records',
    'publish_ambra_records',
    'read_private_ambra_records',
    'delete_ambra_records',
    'delete_private_ambra_records',
    'delete_published_ambra_records',
    'delete_others_ambra_records',
    'edit_private_ambra_records',
    'edit_published_ambra_records',
    'create_ambra_records',
    'read_ambra_catalog',
    'read_private_ambra_catalogs',
    'read_ambra_team_member',
    'read_private_ambra_team_members',
);

foreach ($expected_member_caps as $cap) {
    assert_has_cap($member, $cap, 'project_team_member');
}

foreach ($member->caps as $cap => $granted) {
    if ($granted && !in_array($cap, $expected_member_caps, true)) {
        fail_role_contract("project_team_member unexpectedly has unapproved capability {$cap}.");
    }
}

foreach (array(
    'ambra_use_app',
    'ambra_manage_settings',
    'ambra_view_internal_prices',
    'ambra_manage_finance',
    'edit_ambra_record',
    'edit_ambra_records',
    'edit_ambra_catalog',
    'edit_ambra_catalogs',
    'edit_ambra_team_member',
    'edit_ambra_team_members',
) as $cap) {
    assert_has_cap($admin, $cap, 'administrator');
}

fwrite(STDOUT, "Role capability contract passed.\n");
