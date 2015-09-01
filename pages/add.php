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

function add_app() {
    global $site_url;

    // Verify CSRF token
    verify_csrf_token( );
    
    if ( ( !isset( $_POST['appname'] ) ) || trim( $_POST['appname'] ) == '' ) {
        show_msg_box( __( "The application name cannot be empty." ), "red"  );
        return;
    }

    MySQL::getInstance()->select( "applications", array( "ApplicationName" => $_POST['appname'] ), "", "0,1" );
    if ( MySQL::getInstance()->records == 1 ) {
        show_msg_box( __( "That application already exists." ), "red" );
        return;
    }

    // Generate App ID
    $app_id = generate_app_id();

    MySQL::getInstance()->insert( array( "ApplicationName" => $_POST['appname'], "ApplicationId" => $app_id ), "applications" );

    show_msg_box( __( "You will be redirected in a moment to the settings page. Click" ) . " <a href='javascript: redirect()'> " . __( "here" ) . "</a> " . __( "if your not redirected" ), "green" );
    echo "<script type='text/javascript'>";
    echo "function redirect() { window.location.href = '" . $site_url . "/?id=" . $app_id . "&page=appsettings'; }";
    echo "window.setTimeout('redirect()', 3000);";
    echo "</script>";
}

if ( isset( $_POST['appname'] ) ) {
    echo '<div id="output">';
    add_app();
    echo '</div>';
}
?>
<div class="contentcontainers">
    <form id="form" action="#" method="post">
        <?php generate_csrf_token(); ?>
        <div class="contentcontainer med left">
            <div class="headings alt">
                <h2 class="left"><?php _e( 'Application' ); ?></h2>
            </div>

            <div class="contentbox">
                <table id="id-form" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr id="appname">
                            <th valign="top"><?php _e( 'Application Name:' ); ?></th>
                            <td><input name="appname" id="appname" type="text" class="inp-form" /></td>
                            <td id="error"></td>
                        </tr>
                        <tr>
                            <th valign="top"><?php _e( 'Programming Language:' ); ?></th>
                            <td>
                                <select name="language" id="language" class="styledselect_pages_1">
                                    <option value="default" selected>Select</option>
                                    <option value="dotnet">C#/VB.NET/Mono</option>
                                    <option value="actionscript">ActionScript 3 (Adobe Air)</option>
                                    <option value="cplusplus-windows">C/C++ (Windows)</option>
                                    <option value="cplusplus-linux">C/C++ (Linux)</option>
                                    <option value="delphi">Delphi</option>
                                    <option value="java">Java</option>
                                    <option value="objectivec">Objective-C</option>
                                </select>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <th valign="top"><?php _e( 'Installer Used:' ); ?></th>
                            <td>
                                <select name="installer" id="installer" class="styledselect_pages_1">
                                    <option selected>Select</option>
                                    <option value="inno">Inno Setup</option>
                                    <option value="installanywhere">Install Anywhere</option>
                                    <option value="visie">Visie</option>
                                    <option value="installershield">Installer Shield</option>
                                    <option value="lzpack">lzPack</option>
                                    <option value="msinstaller">Microsoft Installer</option>
                                    <option value="nsis">NSIS</option>
                                    <option value="none">None</option>
                                    <option value="other">Other</option>
                                </select>
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
			
        <div class="contentcontainer sml right">
            <div class="headings alt">
                <h2><?php _e( 'Integration' ); ?></h2>
            </div>
            <div class="contentbox" id="integration">
                <!-- Default Text Start -->
                <div id="default">
                    <p><?php _e( 'Please select the programming language from the left that your application is using.' ); ?></p>
                </div>
                <!-- Default Text End -->
                <!-- Programming Languages Start -->
                <div id="dotnet" style="display: none">
                    <h3>1) <?php _e( 'Download and integrate our library' ); ?></h3>
                    <p><?php _e( "You need to add some lines of code into your application. Using only few lines, you will track the basic information of your application like executions, user' environments, average time on it and so on. " ); ?></p>
                    <a href="http://little-software-stats.com/docs/libraries/csharp-vb-net-mono/" title="C#/VB.NET/Mono" target="_blank"><?php _e( 'Check the documentation' ); ?></a>
                    <br /><br />
                </div>
                <div id="actionscript" style="display: none">
                    <h3>1) <?php _e( 'Download and integrate our library' ); ?></h3>
                    <p><?php _e( "You need to add some lines of code into your application. Using only few lines, you will track the basic information of your application like executions, user' environments, average time on it and so on. " ); ?></p>
                    <a href="http://little-software-stats.com/docs/libraries/actionscript-3/" title="ActionScript 3" target="_blank"><?php _e( 'Check the documentation' ); ?></a>
                    <br /><br />
                </div>
                <div id="cplusplus-windows" style="display: none">
                    <h3>1) <?php _e( 'Download and integrate our library' ); ?></h3>
                    <p><?php _e( "You need to add some lines of code into your application. Using only few lines, you will track the basic information of your application like executions, user' environments, average time on it and so on. " ); ?></p>
                    <a href="http://little-software-stats.com/docs/libraries/cplusplus-windows/" title="C++ (Windows)" target="_blank"><?php _e( 'Check the documentation' ); ?></a>
                    <br /><br />
                </div>
                <div id="cplusplus-linux" style="display: none">
                    <h3>1) <?php _e( 'Download and integrate our library' ); ?></h3>
                    <p><?php _e( "You need to add some lines of code into your application. Using only few lines, you will track the basic information of your application like executions, user' environments, average time on it and so on. " ); ?></p>
                    <a href="http://little-software-stats.com/docs/libraries/cplusplus-linux/" title="C++ (Linux)" target="_blank"><?php _e( 'Check the documentation' ); ?></a>
                    <br /><br />
                </div>
                <div id="delphi" style="display: none">
                    <h3>1) <?php _e( 'Download and integrate our library' ); ?></h3>
                    <p><?php _e( "You need to add some lines of code into your application. Using only few lines, you will track the basic information of your application like executions, user' environments, average time on it and so on. " ); ?></p>
                    <a href="http://little-software-stats.com/docs/libraries/delphi/" title="Delphi" target="_blank"><?php _e( 'Check the documentation' ); ?></a>
                    <br /><br />
                </div>
                <div id="java" style="display: none">
                    <h3>1) <?php _e( 'Download and integrate our library' ); ?></h3>
                    <p><?php _e( "You need to add some lines of code into your application. Using only few lines, you will track the basic information of your application like executions, user' environments, average time on it and so on. " ); ?></p>
                    <a href="http://little-software-stats.com/docs/libraries/java/" title="Java" target="_blank"><?php _e( 'Check the documentation' ); ?></a>
                    <br /><br />
                </div>
                <div id="objectivec" style="display: none">
                    <h3>1) <?php _e( 'Download and integrate our library' ); ?></h3>
                    <p><?php _e( "You need to add some lines of code into your application. Using only few lines, you will track the basic information of your application like executions, user' environments, average time on it and so on. " ); ?></p>
                    <a href="http://little-software-stats.com/docs/libraries/objective-c/" title="Objective-C" target="_blank"><?php _e( 'Check the documentation' ); ?></a>
                    <br /><br />
                </div>
                <!-- Programming Languages End -->
					
                <!-- Setup Libraries Start -->
                <div id="inno" style="display: none">
                    <h3>2) <?php _e( 'Setup integration' ); ?></h3>
                    <p><?php _e( 'You can get more information from our' ); ?> <a href="http://little-software-stats.com/docs/libraries/inno-setup/" title="Inno Setup" target="_blank"><?php _e( 'documentation' ); ?></a></p>
                </div>
                <div id="installanywhere" style="display: none">
                    <h3>2) <?php _e( 'Setup integration' ); ?></h3>
                    <p><?php _e( 'You can get more information from our' ); ?> <a href="http://little-software-stats.com/docs/libraries/install-anywhere/" title="Install Anywhere" target="_blank"><?php _e( 'documentation' ); ?></a></p>
                </div>
                <div id="visie" style="display: none">
                    <h3>2) <?php _e( 'Setup integration' ); ?></h3>
                    <p><?php _e( 'You can get more information from our' ); ?> <a href="http://little-software-stats.com/docs/libraries/visie/" title="Visie" target="_blank"><?php _e( 'documentation' ); ?></a></p>
                </div>
                <div id="installershield" style="display: none">
                    <h3>2) <?php _e( 'Setup integration' ); ?></h3>
                    <p><?php _e( 'You can get more information from our' ); ?> <a href="http://little-software-stats.com/docs/libraries/installer-shield/" title="Installer Shield" target="_blank"><?php _e( 'documentation' ); ?></a></p>
                </div>
                <div id="lzpack" style="display: none">
                    <h3>2) <?php _e( 'Setup integration' ); ?></h3>
                    <p><?php _e( 'You can get more information from our' ); ?> <a href="http://little-software-stats.com/docs/libraries/lzpack/" title="lzPack" target="_blank"><?php _e( 'documentation' ); ?></a></p>
                </div>
                <div id="msinstaller" style="display: none">
                    <h3>2) <?php _e( 'Setup integration' ); ?></h3>
                    <p><?php _e( 'You can get more information from our' ); ?> <a href="http://little-software-stats.com/docs/libraries/microsoft-installer/" title="Microsoft Installer" target="_blank"><?php _e( 'documentation' ); ?></a></p>
                </div>
                <div id="nsis" style="display: none">
                    <h3>2) <?php _e( 'Setup integration' ); ?></h3>
                    <p><?php _e( 'You can get more information from our' ); ?> <a href="http://little-software-stats.com/docs/libraries/nsis/" title="NSIS" target="_blank"><?php _e( 'documentation' ); ?></a></p>
                </div>
                <div id="other" style="display: none">
                    <h3>2) <?php _e( 'Setup integration' ); ?></h3>
                    <p><?php _e( 'You can get more information from our' ); ?> <a href="http://little-software-stats.com/docs/libraries/not-listed/" title="Not Listed/Other" target="_blank"><?php _e( 'documentation' ); ?></a></p>
                </div>
                <!-- Setup Libraries End -->
					
                <input type="submit" value="<?php _e( 'Submit' ); ?>" class="form-submit right" />
            </div>
        </div>
    </form>
</div>