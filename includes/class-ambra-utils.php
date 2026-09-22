<?php
namespace AMBRA_PM;

use WP_Post;

defined( 'ABSPATH' ) || exit;

final class Utils {
    private static int $internal_writes = 0;

    public static function field( string $name, int $post_id, $default = '' ) {
        if ( function_exists( 'get_field' ) ) {
            $value = get_field( $name, $post_id );
            if ( null !== $value && false !== $value && '' !== $value ) {
                return $value;
            }
        }

        $value = get_post_meta( $post_id, $name, true );
        return '' === $value ? $default : $value;
    }

    public static function update_field( string $name, $value, int $post_id ): void {
        ++self::$internal_writes;
        try {
            if ( function_exists( 'update_field' ) ) {
                update_field( $name, $value, $post_id );
                return;
            }
            update_post_meta( $post_id, $name, $value );
        } finally {
            --self::$internal_writes;
        }
    }

    public static function is_internal_write(): bool {
        return self::$internal_writes > 0;
    }

    public static function relation_id( $value ): int {
        if ( $value instanceof WP_Post ) {
            return (int) $value->ID;
        }
        if ( is_array( $value ) && isset( $value['ID'] ) ) {
            return (int) $value['ID'];
        }
        return absint( $value );
    }

    public static function post_title( int $post_id ): string {
        $title = (string) get_post_field( 'post_title', $post_id, 'raw' );
        return '' !== trim( $title ) ? $title : '#' . $post_id;
    }

    public static function money( $value ): string {
        return number_format_i18n( (float) $value, 2 ) . ' €';
    }

    public static function datetime( $value ): string {
        if ( empty( $value ) ) {
            return '—';
        }
        $timestamp = is_numeric( $value ) ? (int) $value : strtotime( (string) $value );
        return $timestamp ? wp_date( 'd.m.Y, H:i', $timestamp ) : (string) $value;
    }

    public static function date( $value ): string {
        if ( empty( $value ) ) {
            return '—';
        }
        $timestamp = is_numeric( $value ) ? (int) $value : strtotime( (string) $value );
        return $timestamp ? wp_date( 'd.m.Y', $timestamp ) : (string) $value;
    }

    public static function current_url(): string {
        $scheme = is_ssl() ? 'https' : 'http';
        $host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
        $uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
        return esc_url_raw( $scheme . '://' . $host . $uri );
    }

    public static function is_ambra_post_type( string $post_type ): bool {
        return array_key_exists( $post_type, Post_Types::definitions() );
    }

    public static function is_demo( int $post_id ): bool {
        return '1' === (string) get_post_meta( $post_id, '_ambra_pm_demo', true );
    }

    /**
     * Detect YOOtheme Pro, including an individually named official child theme.
     * Official child themes keep `Template: yootheme`, so get_template() is the
     * most reliable signal even if YOOtheme runtime classes are not loaded yet.
     *
     * @return array{active:bool,label:string,signals:array<int,string>}
     */
    public static function yootheme_info(): array {
        $signals     = array();
        $theme_name  = '';
        $parent_name = '';

        if ( defined( 'YOO_THEME_PATH' ) ) {
            $signals[] = 'constant:YOO_THEME_PATH';
        }
        if ( class_exists( 'YOOtheme\\Theme' ) ) {
            $signals[] = 'class:YOOtheme\\Theme';
        }
        if ( class_exists( 'YOOtheme\\Application' ) ) {
            $signals[] = 'class:YOOtheme\\Application';
        }
        if ( function_exists( 'YOOtheme\\app' ) ) {
            $signals[] = 'function:YOOtheme\\app';
        }

        if ( function_exists( 'wp_get_theme' ) ) {
            $theme = wp_get_theme();
            if ( $theme && method_exists( $theme, 'exists' ) && $theme->exists() ) {
                $theme_name = trim( (string) $theme->get( 'Name' ) );
                self::collect_yootheme_marker( $signals, 'active-name', $theme_name );
                self::collect_yootheme_marker( $signals, 'active-template', (string) $theme->get_template() );
                self::collect_yootheme_marker( $signals, 'active-stylesheet', (string) $theme->get_stylesheet() );
                self::collect_yootheme_marker( $signals, 'active-template-header', (string) $theme->get( 'Template' ) );

                $parent = method_exists( $theme, 'parent' ) ? $theme->parent() : false;
                if ( $parent && method_exists( $parent, 'exists' ) && $parent->exists() ) {
                    $parent_name = trim( (string) $parent->get( 'Name' ) );
                    self::collect_yootheme_marker( $signals, 'parent-name', $parent_name );
                    self::collect_yootheme_marker( $signals, 'parent-template', (string) $parent->get_template() );
                    self::collect_yootheme_marker( $signals, 'parent-stylesheet', (string) $parent->get_stylesheet() );
                }
            }
        }

        if ( function_exists( 'get_template' ) ) {
            self::collect_yootheme_marker( $signals, 'template', (string) get_template() );
        }
        if ( function_exists( 'get_stylesheet' ) ) {
            self::collect_yootheme_marker( $signals, 'stylesheet', (string) get_stylesheet() );
        }
        if ( function_exists( 'get_template_directory' ) ) {
            self::collect_yootheme_marker( $signals, 'template-directory', basename( (string) get_template_directory() ) );
        }

        $signals = array_values( array_unique( array_filter( $signals ) ) );
        $label   = $theme_name ?: 'YOOtheme Pro';
        if ( $parent_name && 0 !== strcasecmp( $parent_name, $label ) ) {
            $label .= ' (Parent: ' . $parent_name . ')';
        }

        return array(
            'active'  => ! empty( $signals ),
            'label'   => $label,
            'signals' => $signals,
        );
    }

    private static function collect_yootheme_marker( array &$signals, string $source, string $value ): void {
        $value = trim( $value );
        if ( '' !== $value && false !== stripos( $value, 'yootheme' ) ) {
            $signals[] = $source . ':' . $value;
        }
    }

    public static function current_user_is_admin(): bool {
        return is_user_logged_in() && current_user_can( 'manage_options' );
    }

    public static function sanitize_ids( $value ): array {
        if ( ! is_array( $value ) ) {
            $value = array( $value );
        }
        return array_values( array_filter( array_map( 'absint', $value ) ) );
    }
}
