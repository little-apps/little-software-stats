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

// Get user information
if ( !$db->select( "users", array( "UserName" => $_SESSION['UserName'] ), "", "0,1" ) )
    die( "Unable to query database: " . $db->last_error );

if ( $db->records == 0 )
    die( "Unable to find user information" );

$user_email = $db->arrayed_result['UserEmail'];

function update_account() {
    global $db, $user_email;
    
    // Verify CSRF token
    verify_csrf_token( );
    
    require_once( ROOTDIR . '/inc/class.passwordhash.php' );
    $password_hash = new PasswordHash(8, false);

    $current_username = $_SESSION['UserName'];

    if ( !$db->select( "users", array( "UserName" => $current_username ), "", "0,1" ) ) {
        show_msg_box( __( "Unable to query database: " ) . $db->last_error, "red" );
        return;
    }

    $current_id = $db->arrayed_result['UserId'];
    $current_email = $db->arrayed_result['UserEmail'];
    $current_pass = $db->arrayed_result['UserPass'];

    $verify_pass = trim( $_POST['password'] );

    $new_username = trim( $_POST['username'] );
    $new_email = trim( $_POST['email'] );
    $new_pass = trim( $_POST['newpassword'] );
    $new_pass2 = trim( $_POST['newpassword2'] );

    $new_config = array();

    $change_user = false;
    $change_pass = false;
    $change_email = false;

    if ( !$password_hash->check_password( $verify_pass, $current_pass ) ) {
        show_msg_box( __( "The password does not match your current password" ), "red" );
        return;
    }

    if ( $new_username == '' ) {
        show_msg_box( __( "The username cannot be empty" ), "red" );
        return;
    }

    if ( $new_email == '' ) {
        show_msg_box( __( "The e-mail address cannot be empty" ), "red" );
        return;
    }

    // Check valid username
    if ( $new_username != $current_username ) {
        if ( !preg_match( "#^([-a-z0-9_-])+$#i", $new_username ) ) {
            show_msg_box( __( "Username can only contain alpha-numeric characters (a-z, A-Z, 0-9), dashes and underscores" ), "red" );
            return;
        } else if ( strlen( $new_username ) < 5 ) {
            show_msg_box( __( "Username must be at least 5 characters" ), "red" );
            return;
        } else if ( strlen( $new_username ) > 20 ) {
            show_msg_box( __( "Username cannot be more then 20 characters" ), "red" );
            return;
        } else {
            $new_config['UserName'] = $new_username;

            $change_user = true;
        }
    }

    // Check valid email address
    if ( $new_email != $current_email ) {
        if ( !filter_var( $new_email, FILTER_VALIDATE_EMAIL ) ) {
            show_msg_box( __( "The e-mail address is invalid" ), "red" );
            return;
        } else {
            $new_config['UserEmail'] = $new_email;

            $change_email = true;
        }
    }

    // Check valid new password
    if ( $new_pass != '' && $new_pass2 != '' ) {
        if ( $new_pass != $new_pass2 ) {
            show_msg_box( __( "The passwords do not match" ), "red" );
            return;
        } else if ( !preg_match( '#^([a-z0-9])+$#i', $new_pass ) ) {
            show_msg_box( __( "Password can only contain alpha-numeric characters (a-z, A-Z, 0-9)" ), "red" );
            return;
        } else if ( strlen($new_pass) < 5 ) {
            show_msg_box( __( "Password must be more than 5 characters" ), "red" );
            return;
        } else if ( strlen($new_pass) > 20 ) {
            show_msg_box( __( "Password must be less than 20 characters" ), "red" );
            return;
        } else {
            $new_config['UserPass'] = $password_hash->hash_password( $new_pass );

            $change_pass = true;
        }
    }

    if ( empty( $new_config ) ) {
        show_msg_box( __( "Nothing needs to be updated" ) ,"yellow" );
        return;
    }

    if ( !$db->update( "users", $new_config, array( "UserId" => $current_id ) ) ) {
        show_msg_box( __( "Unable to query database: " ) . $db->last_error, "red" );
        return;
    }

    $subject = __( "Your account at " ) . SITE_NAME;
    $message = __( "Below is your updated account information:" ) . "\n\n";
    $message .= __( "Username: " ) . ( ( $change_user ) ? ( $new_username ) : ( $current_username ) ) . "\n";
    $message .= __( "E-mail address: " ) . ( ( $change_email ) ? ( $new_email ) : ( $current_email ) ) . "\n";
    $message .= __( "Password: " ) . ( ( $change_pass ) ? ( $new_pass ) : ( __( "(Password has not been changed)" ) ) ) . "\n\n";
    $message .= __( "This is an automated response, please do not reply!" );

    if ( !send_mail( $current_email, $subject, $message ) ) {
        show_msg_box( __( "Unable to send account update notification" ), "red" );
        return;
    }

    if ( $change_user )
        $_SESSION['UserName'] = $new_username;
    
    if ( $change_email )
        $current_email = $new_email;

    show_msg_box( __( "Your account information was successfully updated" ), "green" );
}

if ( isset( $_POST['username'] ) ) {
    echo '<div id="output">';
    update_account();
    echo '</div>';
}
?>
<div id="contentcontainers">	
    <form id="form" action="#" method="post">
        <?php generate_csrf_token(); ?>
        <div class="contentcontainer med left">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'My Account' ); ?></h2>
            </div>

            <div class="contentbox">
                <table id="id-form" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <th valign="top"><?php _e( 'Username:' ); ?></th>
                            <td><input name="username" id="validate-text" type="text" class="inp-form" value="<?php echo $_SESSION['UserName'] ?>" /></td>
                            <td id="error"></td>
                        </tr>
                        <tr>
                            <th valign="top"><?php _e( 'E-Mail:' ); ?></th>
                            <td><input name="email" id="validate-text" type="text" class="inp-form" value="<?php echo $user_email; ?>" /></td>
                            <td id="error"></td>
                        </tr>
                        <tr>
                            <th valign="top"><?php _e( 'New Password: (optional)' ); ?></th>
                            <td><input name="newpassword" type="password" class="inp-form" /></td>
                            <td id="error"></td>
                        </tr>
                        <tr>
                            <th valign="top"><?php _e( 'New Password Again: (optional)' ); ?></th>
                            <td><input name="newpassword2" type="password" class="inp-form" /></td>
                            <td id="error"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="contentcontainer sml right">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'Update Information' ); ?></h2>
            </div>

            <div class="contentbox">
                <p><?php _e( 'You must verify your password in order to update your information' ); ?></p>
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
    </form>
</div>