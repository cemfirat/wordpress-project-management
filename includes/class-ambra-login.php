<?php
namespace AMBRA_PM;

use WP_User;

/**
 * Custom frontend login for the private AMBRA application.
 *
 * Authentication is still performed by WordPress. This class only replaces
 * the visible login interface and controls the redirects around it.
 */
final class Login {
    public static function init(): void {
        add_shortcode( 'ambra_login', array( __CLASS__, 'shortcode' ) );

        add_action( 'template_redirect', array( __CLASS__, 'handle_login_page' ), -20 );
        add_action( 'login_init', array( __CLASS__, 'redirect_classic_login' ), 1 );

        add_filter( 'login_url', array( __CLASS__, 'filter_login_url' ), 20, 3 );
        add_filter( 'lostpassword_url', array( __CLASS__, 'filter_lostpassword_url' ), 20, 2 );
        add_filter( 'logout_redirect', array( __CLASS__, 'logout_redirect' ), 20, 3 );
        add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
    }

    /**
     * Process frontend login and password-reset requests before output starts.
     */
    public static function handle_login_page(): void {
        if ( ! Pages::is_login_page() ) {
            return;
        }

        nocache_headers();

        if ( is_user_logged_in() ) {
            wp_safe_redirect( home_url( '/' ) );
            exit;
        }

        if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
            return;
        }

        $action = isset( $_POST['ambra_login_action'] )
            ? sanitize_key( wp_unslash( $_POST['ambra_login_action'] ) )
            : '';

        if ( 'login' === $action ) {
            self::process_login();
        }

        if ( 'lostpassword' === $action ) {
            self::process_lost_password();
        }
    }

    private static function process_login(): void {
        if ( ! self::valid_nonce( 'ambra_frontend_login', 'ambra_login_nonce' ) ) {
            self::redirect_with( array( 'login_error' => 'security' ) );
        }

        $login    = isset( $_POST['log'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['log'] ) ) ) : '';
        $password = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '';
        $remember = isset( $_POST['rememberme'] ) && 'forever' === (string) wp_unslash( $_POST['rememberme'] );

        if ( '' === $login || '' === $password ) {
            self::redirect_with( array( 'login_error' => 'empty' ) );
        }

        $user = wp_signon(
            array(
                'user_login'    => $login,
                'user_password' => $password,
                'remember'      => $remember,
            ),
            is_ssl()
        );

        if ( is_wp_error( $user ) ) {
            /**
             * Keep the public error intentionally generic. Detailed WordPress
             * authentication errors would reveal whether an account exists.
             */
            do_action( 'ambra_pm_login_failed', $user, $login );
            self::redirect_with( array( 'login_error' => 'invalid' ) );
        }

        if ( ! user_can( $user, 'ambra_use_app' ) && ! user_can( $user, 'manage_options' ) ) {
            wp_logout();
            self::redirect_with( array( 'login_error' => 'no_access' ) );
        }

        wp_safe_redirect( home_url( '/' ) );
        exit;
    }

    private static function process_lost_password(): void {
        if ( ! self::valid_nonce( 'ambra_frontend_lostpassword', 'ambra_lostpassword_nonce' ) ) {
            self::redirect_with(
                array(
                    'action'      => 'lostpassword',
                    'login_error' => 'security',
                )
            );
        }

        $login = isset( $_POST['user_login'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) ) : '';

        if ( '' === $login ) {
            self::redirect_with(
                array(
                    'action'      => 'lostpassword',
                    'login_error' => 'empty_reset',
                )
            );
        }

        // WordPress sends the normal password-reset email if the account exists.
        retrieve_password( $login );

        // Always show the same response to avoid account enumeration.
        self::redirect_with( array( 'login_message' => 'reset_sent' ) );
    }

    private static function valid_nonce( string $action, string $field ): bool {
        $nonce = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
        return '' !== $nonce && (bool) wp_verify_nonce( $nonce, $action );
    }

    private static function redirect_with( array $args ): void {
        wp_safe_redirect( add_query_arg( $args, Pages::get_url( 'login' ) ) );
        exit;
    }

    /**
     * Redirect only the visible classic login screens. Core logout and reset-key
     * processing remain available because WordPress needs those endpoints.
     */
    public static function redirect_classic_login(): void {
        if ( is_user_logged_in() ) {
            return;
        }

        $action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login';
        $method = strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) );

        if ( 'GET' !== $method ) {
            return;
        }

        if ( in_array( $action, array( '', 'login' ), true ) ) {
            wp_safe_redirect( Pages::get_url( 'login' ) );
            exit;
        }

        if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
            wp_safe_redirect( Pages::get_url( 'login', array( 'action' => 'lostpassword' ) ) );
            exit;
        }
    }

    public static function filter_login_url( string $login_url, string $redirect, bool $force_reauth ): string {
        unset( $login_url, $redirect, $force_reauth );
        return Pages::get_url( 'login' );
    }

    public static function filter_lostpassword_url( string $lostpassword_url, string $redirect ): string {
        unset( $lostpassword_url, $redirect );
        return Pages::get_url( 'login', array( 'action' => 'lostpassword' ) );
    }

    public static function logout_redirect( string $redirect_to, string $requested_redirect_to, WP_User $user ): string {
        unset( $redirect_to, $requested_redirect_to, $user );
        return Pages::get_url( 'login', array( 'logged_out' => '1' ) );
    }

    public static function body_class( array $classes ): array {
        if ( Pages::is_login_page() ) {
            $classes[] = 'ambra-login-page';
        }
        return array_values( array_unique( $classes ) );
    }

    public static function shortcode(): string {
        if ( is_user_logged_in() ) {
            return '';
        }

        $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'login';
        if ( 'lostpassword' === $action ) {
            return self::lost_password_markup();
        }

        return self::login_markup();
    }

    private static function login_markup(): string {
        $error   = isset( $_GET['login_error'] ) ? sanitize_key( wp_unslash( $_GET['login_error'] ) ) : '';
        $message = isset( $_GET['login_message'] ) ? sanitize_key( wp_unslash( $_GET['login_message'] ) ) : '';
        $logged_out = isset( $_GET['logged_out'] ) && '1' === (string) wp_unslash( $_GET['logged_out'] );

        ob_start();
        echo self::layout_start();
        self::brand_header();

        if ( 'reset_sent' === $message ) {
            self::notice( 'Wenn ein passendes Benutzerkonto vorhanden ist, wurde eine E-Mail zum Zurücksetzen des Passworts gesendet.', 'success' );
        }
        if ( $logged_out ) {
            self::notice( 'Du wurdest erfolgreich abgemeldet.', 'primary' );
        }
        if ( $error ) {
            self::notice( self::error_message( $error ), 'danger' );
        }

        echo '<form class="uk-form-stacked" name="loginform" id="loginform" action="' . esc_url( Pages::get_url( 'login' ) ) . '" method="post">';
        echo '<input type="hidden" name="ambra_login_action" value="login">';
        wp_nonce_field( 'ambra_frontend_login', 'ambra_login_nonce' );

        echo '<div class="uk-margin">';
        echo '<label class="uk-form-label" for="user_login">Benutzername oder E-Mail</label>';
        echo '<div class="uk-form-controls uk-inline uk-width-1-1">';
        echo '<span class="uk-form-icon" uk-icon="icon: user"></span>';
        echo '<input class="uk-input uk-form-large" type="text" name="log" id="user_login" required autocomplete="username" autofocus>';
        echo '</div></div>';

        echo '<div class="uk-margin">';
        echo '<label class="uk-form-label" for="user_pass">Passwort</label>';
        echo '<div class="uk-form-controls uk-inline uk-width-1-1">';
        echo '<span class="uk-form-icon" uk-icon="icon: lock"></span>';
        echo '<input class="uk-input uk-form-large" type="password" name="pwd" id="user_pass" required autocomplete="current-password">';
        echo '</div></div>';

        echo '<div class="uk-margin uk-flex uk-flex-between uk-flex-middle uk-flex-wrap uk-grid-small" uk-grid>';
        echo '<div><label class="uk-text-small"><input class="uk-checkbox" type="checkbox" name="rememberme" id="rememberme" value="forever"> Angemeldet bleiben</label></div>';
        echo '<div><a class="uk-link-text uk-text-small" href="' . esc_url( Pages::get_url( 'login', array( 'action' => 'lostpassword' ) ) ) . '">Passwort vergessen?</a></div>';
        echo '</div>';

        echo '<div class="uk-margin-medium-top">';
        echo '<button type="submit" name="wp-submit" id="wp-submit" class="uk-button uk-button-primary uk-button-large uk-width-1-1">Anmelden</button>';
        echo '</div>';
        echo '</form>';

        echo self::layout_end();
        return (string) ob_get_clean();
    }

    private static function lost_password_markup(): string {
        $error = isset( $_GET['login_error'] ) ? sanitize_key( wp_unslash( $_GET['login_error'] ) ) : '';

        ob_start();
        echo self::layout_start();
        self::brand_header( 'Passwort zurücksetzen', 'Gib deinen Benutzernamen oder deine E-Mail-Adresse ein.' );

        if ( $error ) {
            self::notice( self::error_message( $error ), 'danger' );
        }

        echo '<form class="uk-form-stacked" action="' . esc_url( Pages::get_url( 'login' ) ) . '" method="post">';
        echo '<input type="hidden" name="ambra_login_action" value="lostpassword">';
        wp_nonce_field( 'ambra_frontend_lostpassword', 'ambra_lostpassword_nonce' );
        echo '<div class="uk-margin">';
        echo '<label class="uk-form-label" for="lost_user_login">Benutzername oder E-Mail</label>';
        echo '<div class="uk-form-controls uk-inline uk-width-1-1">';
        echo '<span class="uk-form-icon" uk-icon="icon: mail"></span>';
        echo '<input class="uk-input uk-form-large" type="text" name="user_login" id="lost_user_login" required autocomplete="username" autofocus>';
        echo '</div></div>';
        echo '<div class="uk-margin-medium-top">';
        echo '<button type="submit" class="uk-button uk-button-primary uk-button-large uk-width-1-1">Link anfordern</button>';
        echo '</div>';
        echo '<div class="uk-text-center uk-margin"><a class="uk-link-text uk-text-small" href="' . esc_url( Pages::get_url( 'login' ) ) . '"><span uk-icon="arrow-left"></span> Zurück zur Anmeldung</a></div>';
        echo '</form>';

        echo self::layout_end();
        return (string) ob_get_clean();
    }

    private static function layout_start(): string {
        return '<section data-id="ambra-login-page" id="login" class="ambra-login uk-section uk-flex uk-flex-middle" uk-height-viewport="offset-top: true;">'
            . '<div class="uk-width-1-1"><div class="uk-container uk-container-expand">'
            . '<div class="uk-flex uk-flex-center uk-flex-middle">'
            . '<div class="ambra-login-card uk-width-large uk-padding uk-light uk-background-secondary uk-box-shadow-large uk-border-rounded">';
    }

    private static function layout_end(): string {
        return '</div></div></div></div></section>';
    }

    private static function brand_header( string $title = 'AMBRA', string $subtitle = 'Bitte melde dich an' ): void {
        echo '<div class="uk-text-center uk-margin-medium-bottom">';
        echo '<span uk-icon="icon: lock; ratio: 2.5" class="uk-text-primary"></span>';
        echo '<h2 class="uk-card-title uk-margin-small-top uk-margin-remove-bottom">' . esc_html( $title ) . '</h2>';
        echo '<p class="uk-text-meta uk-margin-remove-top">' . esc_html( $subtitle ) . '</p>';
        echo '</div>';
    }

    private static function notice( string $message, string $type ): void {
        $type = in_array( $type, array( 'primary', 'success', 'warning', 'danger' ), true ) ? $type : 'primary';
        echo '<div class="uk-alert-' . esc_attr( $type ) . ' uk-margin" uk-alert><p>' . esc_html( $message ) . '</p></div>';
    }

    private static function error_message( string $error ): string {
        return match ( $error ) {
            'empty'       => 'Bitte Benutzername bzw. E-Mail-Adresse und Passwort eingeben.',
            'empty_reset' => 'Bitte Benutzername oder E-Mail-Adresse eingeben.',
            'security'    => 'Die Anmeldung konnte nicht überprüft werden. Bitte lade die Seite neu und versuche es erneut.',
            'no_access'   => 'Dieses Benutzerkonto hat keinen Zugriff auf AMBRA Projektmanagement.',
            default       => 'Benutzername bzw. E-Mail-Adresse oder Passwort ist falsch.',
        };
    }
}
