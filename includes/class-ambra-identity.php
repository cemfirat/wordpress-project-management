<?php
namespace AMBRA_PM;

defined( 'ABSPATH' ) || exit;

final class Identity {
    private const DEFAULT_NAME = 'AMBRA Projektmanagement';
    private const DEFAULT_SHORT_NAME = 'AMBRA';

    /**
     * User-facing product identity.
     *
     * Persisted ambra_* identifiers are intentionally not derived from these
     * values. Branding can therefore change without migrating business data.
     *
     * @return array{name:string,short_name:string}
     */
    public static function info(): array {
        $info = array(
            'name'       => self::DEFAULT_NAME,
            'short_name' => self::DEFAULT_SHORT_NAME,
        );

        if ( function_exists( 'apply_filters' ) ) {
            $filtered = apply_filters( 'ambra_pm_product_identity', $info );
            if ( is_array( $filtered ) ) {
                $info = array_merge( $info, $filtered );
            }
        }

        $name = trim( (string) ( $info['name'] ?? '' ) );
        $short_name = trim( (string) ( $info['short_name'] ?? '' ) );

        return array(
            'name'       => '' !== $name ? $name : self::DEFAULT_NAME,
            'short_name' => '' !== $short_name ? $short_name : self::DEFAULT_SHORT_NAME,
        );
    }

    public static function name(): string {
        return self::info()['name'];
    }

    public static function short_name(): string {
        return self::info()['short_name'];
    }
}
