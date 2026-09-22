<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

final class Roles {
    public static function init(): void {
        // Rollen werden nur bei Aktivierung/Update eingerichtet, damit nicht bei jedem Request Benutzeroptionen geschrieben werden.
    }

    public static function register_roles_and_caps(): void {
        if ( ! get_role( 'project_team_member' ) ) {
            add_role(
                'project_team_member',
                'Projektmitarbeiter',
                array(
                    'read'          => true,
                    'upload_files'  => true,
                    'ambra_use_app' => true,
                )
            );
        }

        $member = get_role( 'project_team_member' );
        if ( $member ) {
            foreach ( self::member_caps() as $cap ) {
                $member->add_cap( $cap );
            }
        }

        $admin = get_role( 'administrator' );
        if ( $admin ) {
            foreach ( self::admin_caps() as $cap ) {
                $admin->add_cap( $cap );
            }
        }
    }

    private static function caps_for( string $singular, string $plural ): array {
        return array(
            "edit_{$singular}",
            "read_{$singular}",
            "delete_{$singular}",
            "edit_{$plural}",
            "edit_others_{$plural}",
            "publish_{$plural}",
            "read_private_{$plural}",
            "delete_{$plural}",
            "delete_private_{$plural}",
            "delete_published_{$plural}",
            "delete_others_{$plural}",
            "edit_private_{$plural}",
            "edit_published_{$plural}",
            "create_{$plural}",
        );
    }

    public static function operational_caps(): array {
        return self::caps_for( 'ambra_record', 'ambra_records' );
    }

    public static function catalog_caps(): array {
        return self::caps_for( 'ambra_catalog', 'ambra_catalogs' );
    }

    public static function team_caps(): array {
        return self::caps_for( 'ambra_team_member', 'ambra_team_members' );
    }

    private static function member_caps(): array {
        return array_values(
            array_unique(
                array_merge(
                    self::operational_caps(),
                    array(
                        'read_ambra_catalog',
                        'read_private_ambra_catalogs',
                        'read_ambra_team_member',
                        'read_private_ambra_team_members',
                        'ambra_use_app',
                        'upload_files',
                    )
                )
            )
        );
    }

    private static function admin_caps(): array {
        return array_values(
            array_unique(
                array_merge(
                    self::operational_caps(),
                    self::catalog_caps(),
                    self::team_caps(),
                    array(
                        'ambra_use_app',
                        'ambra_manage_settings',
                        'ambra_view_internal_prices',
                        'ambra_manage_finance',
                        'upload_files',
                    )
                )
            )
        );
    }
}
