<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

/**
 * Central user-facing product identity.
 *
 * Legacy storage/API identifiers intentionally remain AMBRA-prefixed for
 * backward compatibility. This class only controls visible product wording.
 */
final class Branding {
    /**
     * @return array{name:string,short_name:string}
     */
    public static function info(): array {
        $defaults = array(
            'name'       => 'AMBRA Projektmanagement',
            'short_name' => 'AMBRA',
        );

        $branding = $defaults;
        if ( function_exists( 'apply_filters' ) ) {
            $filtered = apply_filters( 'ambra_pm_branding', $branding );
            if ( is_array( $filtered ) ) {
                $branding = array_merge( $branding, $filtered );
            }
        }

        foreach ( $defaults as $key => $fallback ) {
            $value = trim( (string) ( $branding[ $key ] ?? '' ) );
            $branding[ $key ] = '' !== $value ? $value : $fallback;
        }

        return array(
            'name'       => (string) $branding['name'],
            'short_name' => (string) $branding['short_name'],
        );
    }

    public static function name(): string {
        return self::info()['name'];
    }

    public static function short_name(): string {
        return self::info()['short_name'];
    }
}
