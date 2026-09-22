<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

final class Post_Types {
    public static function init(): void {
        add_action( 'init', array( __CLASS__, 'register_post_types' ), 10 );
        add_filter( 'private_title_format', array( __CLASS__, 'remove_private_prefix' ), 10, 2 );
    }

    public static function definitions(): array {
        return array(
            'ambra_customer'     => array( 'singular' => 'Kunde', 'plural' => 'Kunden', 'rest' => 'ambra_customers', 'cap' => array( 'ambra_record', 'ambra_records' ) ),
            'ambra_project'      => array( 'singular' => 'Auftrag', 'plural' => 'Aufträge', 'rest' => 'ambra_projects', 'cap' => array( 'ambra_record', 'ambra_records' ) ),
            'ambra_visit'        => array( 'singular' => 'Besichtigung', 'plural' => 'Besichtigungen', 'rest' => 'ambra_visits', 'cap' => array( 'ambra_record', 'ambra_records' ) ),
            'ambra_position'     => array( 'singular' => 'Auftragsposition', 'plural' => 'Auftragspositionen', 'rest' => 'ambra_positions', 'cap' => array( 'ambra_record', 'ambra_records' ) ),
            'ambra_blueprint'    => array( 'singular' => 'Produkt-Blueprint', 'plural' => 'Produkt-Blueprints', 'rest' => 'ambra_blueprints', 'cap' => array( 'ambra_catalog', 'ambra_catalogs' ) ),
            'ambra_manufacturer' => array( 'singular' => 'Hersteller', 'plural' => 'Hersteller', 'rest' => 'ambra_manufacturers', 'cap' => array( 'ambra_catalog', 'ambra_catalogs' ) ),
            'ambra_supplier'     => array( 'singular' => 'Lieferant', 'plural' => 'Lieferanten / Transport', 'rest' => 'ambra_suppliers', 'cap' => array( 'ambra_catalog', 'ambra_catalogs' ) ),
            'ambra_team'         => array( 'singular' => 'Teammitglied', 'plural' => 'Team', 'rest' => 'ambra_team_members', 'cap' => array( 'ambra_team_member', 'ambra_team_members' ) ),
        );
    }

    public static function register_post_types(): void {
        foreach ( self::definitions() as $post_type => $definition ) {
            register_post_type(
                $post_type,
                array(
                    'labels' => array(
                        'name'               => $definition['plural'],
                        'singular_name'      => $definition['singular'],
                        'add_new'            => 'Neu anlegen',
                        'add_new_item'       => $definition['singular'] . ' anlegen',
                        'edit_item'          => $definition['singular'] . ' bearbeiten',
                        'new_item'           => $definition['singular'] . ' neu',
                        'view_item'          => $definition['singular'] . ' ansehen',
                        'search_items'       => $definition['plural'] . ' durchsuchen',
                        'not_found'          => 'Keine Einträge gefunden',
                        'not_found_in_trash' => 'Keine Einträge im Papierkorb gefunden',
                        'all_items'          => $definition['plural'],
                        'menu_name'          => $definition['plural'],
                    ),
                    'public'              => false,
                    'publicly_queryable'  => false,
                    'exclude_from_search' => true,
                    'show_ui'             => true,
                    'show_in_menu'        => 'ambra-project-management',
                    'show_in_rest'        => true,
                    'rest_base'           => $definition['rest'],
                    'has_archive'         => false,
                    'rewrite'             => false,
                    'query_var'           => false,
                    'supports'            => array( 'title', 'author', 'revisions' ),
                    'capability_type'      => $definition['cap'],
                    'map_meta_cap'         => true,
                    'delete_with_user'     => false,
                    'menu_position'        => 30,
                )
            );
        }
    }

    public static function remove_private_prefix( string $format, $post ): string {
        if ( $post instanceof \WP_Post && self::is_ambra_type( $post->post_type ) ) {
            return '%s';
        }
        return $format;
    }

    private static function is_ambra_type( string $post_type ): bool {
        return array_key_exists( $post_type, self::definitions() );
    }
}
