<?php
/**
 * Little Software Stats
 *
 * An open source program that allows developers to keep track of how their software is being used
 *
 * @package		Little Software Stats
 * @author		Little Apps
 * @copyright   Copyright (c) 2011, Little Apps
 * @license		http://www.gnu.org/licenses/gpl.html GNU General Public License v3
 * @link		http://little-software-stats.com
 * @since		Version 0.1
 */

if ( !defined( 'LSS_LOADED' ) )
    die( 'This page cannot be loaded directly' );

// Make sure user is logged in
verify_user( );

$admin_email = get_option( 'site_adminemail' );
$rewrite = get_option( 'site_rewrite' );

$recaptcha_enabled = get_option( 'recaptcha_enabled' );
$recaptcha_public_key = get_option( 'recaptcha_public_key' );
$recaptcha_private_key = get_option( 'recaptcha_private_key' );

$mail_protocol = get_option( 'mail_protocol' );
$mail_smtp_server = get_option( 'mail_smtp_server' );
$mail_smtp_port = get_option( 'mail_smtp_port' );
$mail_smtp_user = get_option( 'mail_smtp_username' );
$mail_smtp_pass = get_option( 'mail_smtp_password' );
$mail_sendmail_path = get_option( 'mail_sendmail_path' );

$geoip_service = get_option( 'geoips_service' );
$geoip_api_key = get_option( 'geoips_api_key' );
$geoip_database_file = $config->site->geoip_path;

function update_settings() {
    global $db, $session;
    global $admin_email, $rewrite;
    global $recaptcha_enabled, $recaptcha_public_key, $recaptcha_private_key;
    global $mail_protocol, $mail_smtp_server, $mail_smtp_port, $mail_smtp_user, $mail_smtp_pass, $mail_sendmail_path;
    global $geoip_service, $geoip_api_key, $geoip_database_file;
    
    // Verify CSRF token
    verify_csrf_token( );

    require_once( ROOTDIR . '/inc/class.passwordhash.php' );
    $password_hash = new PasswordHash(8, false);

    if ( !$db->select( "users", array( "UserName" => $session->user_info['username'] ), "", "0,1" ) ) {
        show_msg_box( __( "Unable to query database: " ) . $db->last_error, "red" );
        return;
    }

    $current_pass = $db->arrayed_result['UserPass'];

    if ( !$password_hash->check_password( trim( $_POST['password'] ), $current_pass ) ) {
        show_msg_box( __( "The password does not match your current password" ), "red" );
        return;
    }

    // Sanitze POST variables
    $sanitized_post = array();

    foreach ($_POST as $k => $v) {
        // We can skip stripping these as they will be filtered
        if ( $k == 'email' || $k == 'protocol' || $k == 'rewrite' || $k == 'recaptcha' || $k == 'smtp-port' || $k == 'geoips-service' )
            $sanitized_post[$k] = trim( $v );
        else
            $sanitized_post[$k] = trim( strip_tags( $v ) );
    }

    if ( ( isset( $sanitized_post['email'] ) ) && $sanitized_post['email'] != $admin_email ) {
        if ( !filter_var( $sanitized_post['email'], FILTER_VALIDATE_EMAIL ) ) {
            show_msg_box( __( "The e-mail address is invalid" ), "red" );
            return;
        }

        set_option( 'site_adminemail' , $sanitized_post['email'] );
        $admin_email = $sanitized_post['email'];
    }

    if ( ( isset( $sanitized_post['rewrite'] ) ) && $sanitized_post['rewrite'] != $rewrite ) {
        if ( $sanitized_post['rewrite'] != 'true' && $sanitized_post['rewrite'] != 'false' ) {
            show_msg_box( __( "Invalid value for 'rewrite' specified" ), "red" );
            return;
        }

        set_option( 'site_rewrite', $sanitized_post['rewrite'] );
        $rewrite = $sanitized_post['rewrite'];
    }

    if ( ( isset( $sanitized_post['recaptcha'] ) ) && $sanitized_post['recaptcha'] != $recaptcha_enabled ) {
        if ( $sanitized_post['recaptcha'] != 'true' && $sanitized_post['recaptcha'] != 'false' ) {
            show_msg_box( __( "Invalid value for 'recaptcha' specified" ), "red" );
            return;
        }

        set_option( 'recaptcha_enabled', $sanitized_post['recaptcha'] );
        $recaptcha_enabled = $sanitized_post['recaptcha'];
    }

    if ( ( ( isset( $sanitized_post['protocol'] ) ) && $sanitized_post['recaptcha-public'] != $recaptcha_public_key ) || ( ( isset( $sanitized_post['protocol'] ) ) && $sanitized_post['recaptcha-private'] != $recaptcha_private_key ) ) {
        set_option( 'recaptcha_public_key', $sanitized_post['recaptcha-public'] );
        set_option( 'recaptcha_private_key', $sanitized_post['recaptcha-private'] );
        
        $recaptcha_public_key = $sanitized_post['recaptcha-public'];
        $recaptcha_private_key = $sanitized_post['recaptcha-private'];
    }

    if ( ( isset( $sanitized_post['protocol'] ) ) && $sanitized_post['protocol'] != $mail_protocol ) {
        if ( $sanitized_post['protocol'] != 'mail' && $sanitized_post['protocol'] != 'sendmail' && $sanitized_post['protocol'] != 'smtp' ) {
            show_msg_box( __( "Invalid value for 'protocol' specified" ), "red" );
            return;
        }

        set_option( 'mail_protocol', $sanitized_post['protocol'] );
        $mail_protocol = $sanitized_post['protocol'];
    }

    if ( ( isset( $sanitized_post['smtp-server'] ) ) && $sanitized_post['smtp-server'] != $mail_smtp_server ) {
        if ( empty( $sanitized_post['smtp-server'] ) ) {
            show_msg_box( __( "SMTP server must be specified" ), "red" );
            return;
        }

        set_option( 'smtp-server', $sanitized_post['mail_smtp_server'] );
        $mail_smtp_server = $sanitized_post['mail_smtp_server'];
    }

    if ( ( isset( $sanitized_post['smtp-port'] ) ) && $sanitized_post['smtp-port'] != $mail_smtp_port ) {
        if ( ( !is_numeric( $sanitized_post['smtp-port'] ) ) && ( $sanitized_post['smtp-port'] < 1 || $sanitized_post['smtp-port'] > 65535 ) ) {
            show_msg_box( __( "SMTP port is invalid (must be between 1 and 65535)" ), "red" );
            return;
        }

        set_option( 'mail_smtp_port', $sanitized_post['smtp-port'] );
        $mail_smtp_port = $sanitized_post['smtp-port'];
    }

    if ( ( isset( $sanitized_post['smtp-user'] ) ) && $sanitized_post['smtp-user'] != $mail_smtp_user ) {
        if ( empty( $sanitized_post['smtp-user'] ) ) {
            show_msg_box( __( "SMTP username must be specified" ), "red" );
            return;
        }

        set_option( 'mail_smtp_username', $sanitized_post['smtp-user'] );
        $mail_smtp_user = $sanitized_post['smtp-user'];
    }

    if ( ( isset( $sanitized_post['smtp-pass'] ) ) && $sanitized_post['smtp-pass'] != $mail_smtp_pass ) {
        if ( empty( $sanitized_post['smtp-pass'] ) ) {
            show_msg_box( __( "SMTP password must be specified" ), "red" );
            return;
        }

        set_option( 'mail_smtp_password', $sanitized_post['smtp-pass'] );
        $mail_smtp_pass = $sanitized_post['smtp-pass'];
    }

    if ( ( isset( $sanitized_post['sendmail-path'] ) ) && $sanitized_post['sendmail-path'] != $mail_sendmail_path ) {
        if ( empty( $sanitized_post['sendmail-path'] ) ) {
            show_msg_box( __( "Sendmail path must be specified" ), "red" );
            return;
        }

        set_option( 'mail_sendmail_path', $sanitized_post['sendmail-path'] );
        $mail_sendmail_path = $sanitized_post['sendmail-path'];
    }

    if ( ( isset( $sanitized_post['geoips-service'] ) ) && $sanitized_post['geoips-service'] != $geoip_service ) {
        if ( $sanitized_post['geoips-service'] != 'api' && $sanitized_post['geoips-service'] != 'database' ) {
            show_msg_box( __( "GeoIP service must be specified" ), "red" );
            return;
        }

        set_option( 'geoips_service', $sanitized_post['geoips-service'] );
        $geoip_service = $sanitized_post['geoips-service'];
    }

    if ( ( isset( $sanitized_post['geoips-apikey'] ) ) && $sanitized_post['geoips-apikey'] != $geoip_api_key ) {
        if ( $sanitized_post['geoips-service'] == 'api' && empty( $sanitized_post['geoips-apikey'] ) ) {
            show_msg_box( __( "GeoIP API key must be specified" ), "red" );
            return;
        }

        set_option( 'geoips_api_key', $sanitized_post['geoips-apikey'] );
        $geoip_api_key = $sanitized_post['geoips-apikey'];
    }

    if ( ( isset( $sanitized_post['geoips-database'] ) ) && $sanitized_post['geoips-database'] != $geoip_database_file ) {
        show_msg_box( __( "GeoIP database location can only be changed in config.php" ), "red" );
		return;
    }

    show_msg_box( __( "Settings have been successfully updated" ), "green" );
}

if ( isset( $_POST['password'] ) ) {
    echo '<div id="output">';
    update_settings();
    echo '</div>';
}
?>
<form id="form" action="#" method="post">
    <?php generate_csrf_token(); ?>
    <div class="contentcontainers">
        <div class="contentcontainer left" style="width: 49%">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'Site Options' ); ?></h2>
            </div>

            <!-- Site Options Start -->
            <div class="contentbox">
                <table id="id-form" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <th><?php _e( 'Admin Email:' ); ?></th>
                            <td><input type="text" class="inp-form" name="email" value="<?php echo $admin_email ?>" /></td>
                        </tr>
                        <tr>
                            <th><?php _e( 'URL Rewriting:' ); ?></th>
                            <td>
                                <input type="radio" class="inp-form" name="rewrite" value="true" <?php echo ( ( $rewrite == 'true' ) ? ( 'checked' ) : ( '' ) ) ?> /> Enabled
                                <input type="radio" class="inp-form" name="rewrite" value="false" <?php echo ( ( $rewrite == 'false' ) ? ( 'checked' ) : ( '' ) ) ?> /> Disabled
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php unset( $admin_email, $rewrite ); ?>
            </div>
            <!-- Site Options End -->
        </div>
        
        <div class="contentcontainer right" style="width: 49%">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'reCAPTCHA' ); ?></h2>
            </div>

            <!-- reCAPTCHA Start -->
            <div class="contentbox">
                <table id="id-form" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <th><?php _e( 'Use reCAPTCHA:' ); ?></th>
                            <td>
                                <input type="radio" class="inp-form" name="recaptcha" value="true" <?php echo ( ( $recaptcha_enabled == 'true' ) ? ( 'checked' ) : ( '' ) ) ?> /> Enabled
                                <input type="radio" class="inp-form" name="recaptcha" value="false" <?php echo ( ( $recaptcha_enabled == 'false' ) ? ( 'checked' ) : ( '' ) ) ?> /> Disabled
                            </td>
                        </tr>
                        <tr id="recaptcha-settings">
                            <th><?php _e( 'Public Key:' ); ?></th>
                            <td><input type="text" class="inp-form" name="recaptcha-public" value="<?php echo $recaptcha_public_key; ?>" /></td>
                        </tr>
                        <tr id="recaptcha-settings">
                            <th><?php _e( 'Private Key:' ); ?></th>
                            <td><input type="text" class="inp-form" name="recaptcha-private" value="<?php echo $recaptcha_private_key; ?>" /></td>
                        </tr>
                    </tbody>
                </table>
                <?php unset( $recaptcha_enabled, $recaptcha_public_key, $recaptcha_private_key ); ?>
            </div>
            <!-- reCAPTCHA End -->
        </div>
        
        <div class="clear"></div>
        
        <div class="contentcontainer left" style="width: 49%; min-height: 160px;">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'Mail Options' ); ?></h2>
            </div>

            <!-- Mail Options Start -->
            <div class="contentbox">
                <table id="id-form" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <th><?php _e( 'Protocol:' ); ?></th>
                            <td>
                                <input type="radio" class="inp-form" name="protocol" value="mail" <?php echo ( ( $mail_protocol == 'mail' ) ? ( 'checked' ) : ( '' ) ) ?> /> Mail<br />
                                <input type="radio" class="inp-form" name="protocol" value="sendmail" <?php echo ( ( $mail_protocol == 'sendmail' ) ? ( 'checked' ) : ( '' ) ) ?> /> Sendmail<br />
                                <input type="radio" class="inp-form" name="protocol" value="smtp" <?php echo ( ( $mail_protocol == 'smtp' ) ? ( 'checked' ) : ( '' ) ) ?> /> SMTP
                            </td>
                        </tr>
                        <tr id="mail-smtp">
                            <th><?php _e( 'SMTP Server:' ); ?></th>
                            <td><input type="text" class="inp-form" name="smtp-server" value="<?php echo $mail_smtp_server; ?>" /></td>
                        </tr>
                        <tr id="mail-smtp">
                            <th><?php _e( 'SMTP Port:' ); ?></th>
                            <td><input type="text" class="inp-form" name="smtp-port" value="<?php echo $mail_smtp_port; ?>" /></td>
                        </tr>
                        <tr id="mail-smtp">
                            <th><?php _e( 'SMTP Username:' ); ?></th>
                            <td><input type="text" class="inp-form" name="smtp-user" value="<?php echo $mail_smtp_user; ?>" /></td>
                        </tr>
                        <tr id="mail-smtp">
                            <th><?php _e( 'SMTP Password:' ); ?></th>
                            <td><input type="text" class="inp-form" name="smtp-pass" value="<?php echo $mail_smtp_pass; ?>" /></td>
                        </tr>
                        <tr id="mail-sendmail">
                            <th><?php _e( 'Sendmail Path:' ); ?></th>
                            <td><input type="text" class="inp-form" name="sendmail-path" value="<?php echo $mail_sendmail_path; ?>" /></td>
                        </tr>
                    </tbody>
                </table>
                <?php unset( $mail_protocol, $mail_smtp_server, $mail_smtp_port, $mail_smtp_user, $mail_smtp_pass, $mail_sendmail_path ); ?>
            </div>
            <!-- Mail Options End -->
        </div>
        
        <div class="contentcontainer right" style="width: 49%">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'IP Geolocation' ); ?></h2>
            </div>

            <!-- IP Geolocation Start -->
            <div class="contentbox">
                <table id="id-form" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <th><?php _e( 'Service:' ); ?></th>
                            <td>
                                <input type="radio" class="inp-form" name="geoips-service" value="api" <?php echo ( ( $geoip_service == 'api' ) ? ( 'checked' ) : ( '' ) ) ?> /> API
                                <input type="radio" class="inp-form" name="geoips-service" value="database" <?php echo ( ( $geoip_service == 'database' ) ? ( 'checked' ) : ( '' ) ) ?> /> Database
                            </td>
                        </tr>
                        <tr id="geoips-api">
                            <th><?php _e( 'API Key:' ); ?></th>
                            <td><input type="text" class="inp-form" name="geoips-apikey" value="<?php echo $geoip_api_key; ?>" /></td>
                        </tr>
                        <tr id="geoips-database">
                            <th><?php _e( 'Database Location:' ); ?></th>
                            <td><input type="text" class="inp-form" name="geoips-database" value="<?php echo $geoip_database_file; ?>" readonly /></td>
                        </tr>
                    </tbody>
                </table>
                <?php unset( $geoip_service, $geoip_api_key, $geoip_database_file ); ?>
            </div>
            <!-- IP Geolocation End -->
        </div>

        <div class="contentcontainer right" style="width: 49%">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'Update Settings' ); ?></h2>
            </div>

            <div class="contentbox">
                <p><?php _e( 'You must verify your password in order to update the settings' ); ?></p>
                <table id="id-form" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <th valign="top"><?php _e( 'Current Password:' ); ?></th>
                            <td><input name="password" id="validate-text" type="password" class="inp-form" style="width: 155px;" /></td>
                            <td id="error"></td>
                        </tr>
                        <tr>
                            <th>&nbsp;</th>
                            <td><input name="apply" type="submit" value="<?php _e( 'Apply' ); ?>" class="form-submit" /></td>
                            <td>&nbsp;</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>