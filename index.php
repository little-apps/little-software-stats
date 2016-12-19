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

$page_load_start = microtime(true);

// Prevents other pages from being loaded directly
define( 'LSS_LOADED', true );

require_once( dirname( __FILE__ ) . '/inc/main.php' );

// Make sure user is logged in
verify_user( );

// Must be kept in index.php otherwise prevents user from logging out
if ( $needs_refresh ) {
    redirect( get_page_url( $sanitized_input['page'], false, false ) );
}

// Check if data exists
$app_data_exists = ( MySQL::getInstance()->select_count( 'sessions', '*', array( 'ApplicationId' => $sanitized_input['id'] ) ) > 0 );
?>
<!DOCTYPE html>
<!--[if IE 6]><html id="ie6" dir="ltr" lang="en"><![endif]-->
<!--[if IE 7]><html id="ie7" dir="ltr" lang="en"><![endif]-->
<!--[if IE 8]><html id="ie8" dir="ltr" lang="en"><![endif]-->
<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!--><html dir="ltr" lang="en"><!--<![endif]-->
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="robots" content="none,noarchive,nofollow,noindex" />
        <title><?php page_title() ?></title>
        <link rel="stylesheet" href="<?php file_url( '/css/screen.css' ) ?>" type="text/css" media="screen" title="default" />
        <link rel="stylesheet" href="<?php file_url( '/css/jquery-ui.css' ) ?>" type="text/css" media="screen" />
        <!--[if IE]>
            <link rel="stylesheet" media="all" type="text/css" href="<?php file_url( '/css/pro_dropline_ie.css' ) ?>" />
        <![endif]-->

        <!-- favicon -->
        <link type="image/x-icon" href="<?php file_url( '/images/shared/favicon.ico' ) ?>" rel="shortcut icon" />

        <!--  jquery core -->
        <script src="<?php file_url( '/js/jquery/jquery.min.js' ) ?>" type="text/javascript"></script>
        <script src="<?php file_url( '/js/jquery/jquery-ui.min.js' ) ?>" type="text/javascript"></script>

        <!-- Highcharts -->
        <script src="//code.highcharts.com/highcharts.js" type="text/javascript"></script>

        <!-- Google Charts API -->
        <script type="text/javascript" src="//www.google.com/jsapi"></script>
    </head>
    <body>
        <!-- Start: page-top-outer -->
        <div id="page-top-outer">

            <!-- Start: page-top -->
            <div id="page-top">

                <!-- start logo -->
                <div id="logo">
                    <a href="<?php echo $site_url ?>"><img src="<?php file_url( '/images/shared/logo.png' ) ?>" width="261" height="40" alt="<?php _e( 'Little Software Stats' ); ?>" /></a>
                </div>
                <!-- end logo -->

                <!--  start top-search -->
                <div id="top-search">
                    <table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td id="versions"><?php app_versions(); ?></td>
                            <td style="color: #fff; font-size: 12px; padding: 6px 0 0 6px;"><?php _e( 'Graph By:' ); ?>&nbsp;&nbsp;</td>
                            <td>
                                <select id="graphBy" class="styledselect">
                                    <option value="day" <?php echo ( ( $sanitized_input['graphBy'] == 'day' ) ? ( 'selected' ) : ( '' ) ); ?>><?php _e( 'Day' ); ?></option>
                                    <option value="week" <?php echo ( ( $sanitized_input['graphBy'] == 'week' ) ? ( 'selected' ) : ( '' ) ); ?>><?php _e( 'Week' ); ?></option>
                                    <option value="month" <?php echo ( ( $sanitized_input['graphBy'] == 'month' ) ? ( 'selected' ) : ( '' ) ); ?>><?php _e( 'Month' ); ?></option>
                                </select>
                            </td>
                            <td><input type="text" id="from" name="from" /></td>
                            <td style="color: #fff; font-family: Arial; font-size: 12px; padding-left: 6px; padding-right: 6px">-</td>
                            <td><input type="text" id="to" name="to" /></td>
                        </tr>
                    </table>
                </div>
                <!--  end top-search -->
                <div class="clear"></div>

            </div>
            <!-- End: page-top -->

        </div>
        <!-- End: page-top-outer -->

        <div class="clear">&nbsp;</div>

        <!--  start nav-outer-repeat................................................................................................. START -->
        <div class="nav-outer-repeat">

            <!--  start nav-outer -->
            <div class="nav-outer">

				<!-- start nav-right -->
				<div id="nav-right">
		                    <div class="nav-divider">&nbsp;</div>
		                    <div class="applications">
		                        <ul>
		                            <li>
		                                 <img src="<?php file_url( '/images/shared/nav/nav_applications.png' ); ?>" width="96" height="14" alt="" />
		                                 <ul>
		                                    <?php foreach ( $apps as $app ) : ?>
		                                    <li>
		                                        <a href="<?php app_url( $app['AppId'] ); ?>"<?php echo ( ( $sanitized_input['id'] == $app['AppId'] ) ? ( ' class="selected"' ) : ( '' ) ); ?>><?php echo $app['AppName']; ?></a>
		                                    </li>
		                                    <?php endforeach; ?>
		                                    <li>
		                                        <a href="<?php app_url( 'add' ); ?>" <?php echo ( ( $sanitized_input['id'] == 'add' ) ? ( 'class="selected"' ) : ( '' ) ); ?>><?php _e( 'Add New Application' ); ?></a>
		                                    </li>
		                                </ul>
		                            </li>
		                        </ul>
		                    </div>
		                    <div class="nav-divider">&nbsp;</div>
		                    <a href="<?php echo get_page_url( 'myaccount' ); ?>" title="<?php _e( 'My Account' ); ?>" id="myaccount"><img src="<?php file_url( '/images/shared/nav/nav_myaccount.gif' ); ?>" width="93" height="14" alt="" /></a>
		                    <div class="nav-divider">&nbsp;</div>
		                    <a href="<?php echo get_page_url( 'settings' ); ?>" title="<?php _e( 'Settings' ); ?>" id="settings"><img src="<?php file_url( '/images/shared/nav/nav_settings.png' ); ?>" width="72" height="14" alt="" /></a>
		                    <div class="nav-divider">&nbsp;</div>
		                    <a href="<?php file_url( 'login.php', '?action=logout' ); ?>" title="<?php _e( 'Logout' ); ?>" id="logout"><img src="<?php file_url( '/images/shared/nav/nav_logout.gif' ); ?>" width="64" height="14" alt="" /></a>
		                    <div class="clear">&nbsp;</div>


				</div>
				<!-- end nav-right -->
                <!--  start nav -->
                <div class="nav">
                    <ul>
                        <li>
                            <a href="#"><strong><?php _e( 'Overview' ); ?></strong></a>
                            <ul>
                                <li class="<?php is_page_current( 'dashboard' ) ?>"><a href="<?php get_page_url( 'dashboard' ); ?>"><?php _e( 'Dashboard' ); ?></a></li>
                                <li class="<?php is_page_current( 'appsettings' ) ?>"><a href="<?php get_page_url( 'appsettings' ); ?>"><?php _e( 'Settings' ); ?></a></li>
                            </ul>
                        </li>
                        <li class="seperator"></li>
                        <li>
                            <a href="#"><strong><?php _e( 'Usage' ); ?></strong></a>
                            <ul>
                                <li class="<?php is_page_current( 'executions' ) ?>"><a href="<?php get_page_url( 'executions' ); ?>"><?php _e( 'Executions' ); ?></a></li>
                                <li class="<?php is_page_current( 'installations' ) ?>"><a href="<?php get_page_url( 'installations' ); ?>"><?php _e( 'Installations' ); ?></a></li>
                                <li class="<?php is_page_current( 'uninstallations' ) ?>"><a href="<?php get_page_url( 'uninstallations' ); ?>"><?php _e( 'Uninstallations' ); ?></a></li>
                                <li class="<?php is_page_current( 'versions' ) ?>"><a href="<?php get_page_url( 'versions' ); ?>"><?php _e( 'Versions' ); ?></a></li>
                                <li class="<?php is_page_current( 'licenses' ) ?>"><a href="<?php get_page_url( 'licenses' ); ?>"><?php _e( 'Licenses' ); ?></a></li>
                                <li class="<?php is_page_current( 'averagetime' ) ?>"><a href="<?php get_page_url( 'averagetime' ); ?>"><?php _e( 'Average Time' ); ?></a></li>
                                <li class="<?php is_page_current( 'loyaltytime' ) ?>"><a href="<?php get_page_url( 'loyaltytime' ); ?>"><?php _e( 'Loyalty Time' ); ?></a></li>
                                <li class="<?php is_page_current( 'newvsreturning' ) ?>"><a href="<?php get_page_url( 'newvsreturning' ); ?>"><?php _e( 'New vs. Returning' ); ?></a></li>
                                <li class="<?php is_page_current( 'bouncerate' ) ?>"><a href="<?php get_page_url( 'bouncerate' ); ?>"><?php _e( 'Bounce Rate' ); ?></a></li>
                            </ul>
                        </li>
                        <li class="seperator"></li>
                        <li>
                            <a href="#"><strong><?php _e( 'Data' ); ?></strong></a>
                            <ul>
                                <li class="<?php is_page_current( 'events' ) ?>"><a href="<?php get_page_url( 'events' ); ?>"><?php _e( 'Events' ); ?></a></li>
                                <li class="<?php is_page_current( 'eventstiming' ) ?>"><a href="<?php get_page_url( 'eventstiming' ); ?>"><?php _e( 'Events Timing' ); ?></a></li>
                                <li class="<?php is_page_current( 'eventsvalue' ) ?>"><a href="<?php get_page_url( 'eventsvalue' ); ?>"><?php _e( 'Events Value' ); ?></a></li>
                                <li class="<?php is_page_current( 'customdata' ) ?>"><a href="<?php get_page_url( 'customdata' ); ?>"><?php _e( 'Custom Data' ); ?></a></li>
                                <li class="<?php is_page_current( 'logs' ) ?>"><a href="<?php get_page_url( 'logs' ); ?>"><?php _e( 'Logs' ); ?></a></li>
                                <li class="<?php is_page_current( 'exceptions' ); ?>"><a href="<?php get_page_url( 'exceptions' ); ?>"><?php _e( 'Exceptions' ); ?></a></li>
                            </ul>
                        </li>
                        <li class="seperator"></li>
                        <li>
                            <a href="#"><strong><?php _e( 'Environments' ); ?></strong></a>
                            <ul>
                                <li class="<?php is_page_current( 'operatingsystems' ) ?>"><a href="<?php get_page_url( 'operatingsystems' ); ?>"><?php _e( 'Operating Systems' ); ?></a></li>
                                <li class="<?php is_page_current( 'languages' ) ?>"><a href="<?php get_page_url( 'languages' ); ?>"><?php _e( 'Languages' ); ?></a></li>
                                <li class="<?php is_page_current( 'cpus' ) ?>"><a href="<?php get_page_url( 'cpus' ); ?>"><?php _e( 'CPUs' ); ?></a></li>
                                <li class="<?php is_page_current( 'memory' ) ?>"><a href="<?php get_page_url( 'memory' ); ?>"><?php _e( 'Memory' ); ?></a></li>
                                <li class="<?php is_page_current( 'screenresolutions' ) ?>"><a href="<?php get_page_url( 'screenresolutions' ); ?>"><?php _e( 'Screen Resolutions' ); ?></a></li>
                                <li class="<?php is_page_current( 'pluginsandvms' ) ?>"><a href="<?php get_page_url( 'pluginsandvms' ); ?>"><?php _e( 'Plugins &amp; VMs' ); ?></a></li>
                            </ul>
                        </li>
                        <li class="seperator"></li>
                        <li>
                            <a href="<?php get_page_url( 'mapoverlay' ); ?>"><strong><?php _e( 'Maps' ); ?></strong></a>
                        </li>
                    </ul>

                </div>
            </div>

            <div class="clear"></div>

        <!--  start nav-outer -->
        </div>

        <!--  start nav-outer-repeat................................................... END -->

        <div class="clear"></div>

        <!-- start content-outer ........................................................................................................................START -->
        <div id="content-outer">
            <!-- start content -->
            <div id="content">
<?php
                    require_once( ROOTDIR . '/inc/highcharts/Highchart.php' );

                    $current_dir = getcwd();

                    chdir( 'pages' );

                    if ( $sanitized_input['id'] == 'add' )
                        include_once( ROOTDIR . '/pages/add.php' );
                    else
                        include_once( ROOTDIR . '/pages/' .  $sanitized_input['page'] . '.php' );

                    chdir( $current_dir );

                    unset( $current_dir );
?>
            </div>
            <!--  end content -->
            <div class="clear">&nbsp;</div>
        </div>
        <!--  end content-outer........................................................END -->

        <div class="clear">&nbsp;</div>

        <!-- start footer -->
        <div id="footer">
            <!--  start footer-left -->
            <div id="footer-left">
                <div id="loadtime"></div>
                <?php _e( 'Little Software Stats' ); ?> &copy; <a href="http://www.little-apps.org/" target="_blank">Little Apps</a>. <?php _e( 'It is licensed under the' ); ?> <a href="http://www.gnu.org/licenses/gpl.html" target="_blank"><?php _e( 'GNU General Public License v3' ); ?></a>.<br /><br />
                <?php _e( 'Admin Skin' ); ?> &copy; Internet Dreams Ltd. <span id="spanYear"></span> <a href="http://www.netdreams.co.uk/" target="_blank">www.netdreams.co.uk</a>. <?php _e( 'All rights reserved.' ); ?><br /><br />
                <?php _e( 'IP Geolocation database maintained by' ); ?> <a href="http://www.maxmind.com/" target="_blank">MaxMind</a>
            </div>
            <!--  end footer-left -->
            <div class="clear">&nbsp;</div>
        </div>
        <!-- end footer -->

<?php
        $app_exists = false;
        foreach ( $apps as $app ) {
            if ( $app['AppId'] == $sanitized_input['id'] || $sanitized_input['id'] == 'add' ) {
                $app_exists = true;
                break;
            }
        }

        // no longer need apps list, free it
        unset( $apps );
?>
        <div id="invalididbox">
            <h1 style="color: #fff"><center><?php _e( 'The application ID specified is invalid or does not exist' ); ?></center></h1>
        </div>
<?php
        if ( isset( $_POST['update-geoip'] ) ) {
            $updated_geo_ip = download_geoip_update();
            $geo_ip_update_available = false;
        } else {
            if ( !isset( Session::getInstance()->geoip_update ) && !isset( Session::getInstance()->geoip_update_v6 ) ) {
				if ( is_geoip_update_available() || is_geoipv6_update_available() )
					$geo_ip_update_available = true;
				else
					$geo_ip_update_available = false;
			}
            else {
				$geo_ip_update_available = true;
			}
        }

        if ( isset( Session::getInstance()->time_changed ) ) {
            unset( Session::getInstance()->time_changed );
            $notify_bar_html = __( "The interval selected will not work with that date range so it has been changed automatically" );
        } else if ( ( !$app_data_exists ) && ( $sanitized_input['id'] != 'add' ) ) {
            $notify_bar_html = __( "No data has been recieved yet. <a href='http://www.little-software-stats.com/docs/' target='_blank'>Have you configured your application?</a>" );
        } else if ( isset( $_POST['update-geoip'] ) ) {
            if ( $updated_geo_ip )
                $notify_bar_html = __( "Your GeoIP database is now up to date" );
            else
                $notify_bar_html = __( "There was an error trying to update GeoIP" );
        } else if ( $geo_ip_update_available == true ) {
            $notify_bar_html = __( "An update for your GeoIP database is available. <a href='#' id='update-geoip'>Update Now?</a>" );
        }
?>
        <script type="text/javascript">
            // variables for custom jquery
            var baseUrl = '<?php echo $site_url; ?>';
            var rewriteEnabled = <?php echo get_option( 'site_rewrite' ); ?>;
            var appExists = <?php echo ( ( $app_exists ) ? ( 'true' ) : ( 'false' ) ); ?>;
            var page = '<?php echo $sanitized_input['page']; ?>';
            var id = '<?php echo $sanitized_input['id']; ?>';
            var ver = '<?php echo $sanitized_input['ver']; ?>';
            var graphBy = '<?php echo $sanitized_input['graphBy']; ?>';
            var start = '<?php echo date( 'Y-m-d', $sanitized_input['start'] ); ?>';
            var end = '<?php echo date( 'Y-m-d', $sanitized_input['end'] ); ?>';
        </script>

        <script src="<?php echo get_min_uri( 'index' ); ?>" type="text/javascript"></script>

        <script type="text/javascript" charset="utf-8">
        $(document).ready( function() {
            <?php if ( isset( $notify_bar_html ) ) : ?>
            $.notifyBar( {
                html: <?php echo '"' . addslashes( $notify_bar_html ) . '"'; ?>,
                delay: 10000
            } );

                <?php if ( isset( Session::getInstance()->geoip_update_url ) ) : ?>
                    $('a#update-geoip').click(function() {
                        $('body').append($('<form/>', {
                            id: 'updateGeoipForm',
                            method: 'POST',
                            action: '#'
                        }));

                        $('#updateGeoipForm').append($('<input/>', {
                            type: 'hidden',
                            name: 'update-geoip',
                            value: 'true'
                        }));

                        $('#updateGeoipForm').submit();

                        return false;
                    });
<?php
                endif;
            endif;
?>
            // date picker
            var dates = $( "#from, #to" ).datepicker({
                changeMonth: true,
                numberOfMonths: 1,
                onSelect: function( selectedDate ) {
                    var option = this.id == "from" ? "minDate" : "maxDate",
                                instance = $( this ).data( "datepicker" ),
                                date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings );

                    dates.not( this ).datepicker( "option", option, date );

                    if (option == "minDate")
                        start = $.datepicker.formatDate($.datepicker.ISO_8601, $(this).datepicker('getDate'));
                    else
                        end = $.datepicker.formatDate($.datepicker.ISO_8601, $(this).datepicker('getDate'));

                    refreshUrl();
                }
            });

            $("#from, #to").datepicker("option", "maxDate", '+1d');

            var fromYear = <?php echo date( "Y", $sanitized_input['start'] ); ?>,
                fromMonth = <?php echo date( "m", $sanitized_input['start'] ) - 1; ?>,
                fromDay = <?php echo date( "d", $sanitized_input['start'] ); ?>;

            var toYear = <?php echo date( "Y", $sanitized_input['end'] ); ?>,
                toMonth = <?php echo date( "m", $sanitized_input['end'] ) - 1; ?>,
                toDay = <?php echo date( "d", $sanitized_input['end'] ); ?>;

            $( "#from" ).datepicker( "setDate", new Date( fromYear, fromMonth, fromDay ) );
            $( "#to" ).datepicker( "setDate", new Date( toYear, toMonth, toDay ) );

            <?php if ( Config::getInstance()->site->debug ) : $page_load_dur = number_format( microtime(true) - $page_load_start, 3 ) . ' seconds'; ?>
                $("#loadtime").html('<?php echo MySQL::getInstance()->total_queries . __( ' queries executed in ' ) . $page_load_dur . "<br /><br />" ?>');
            <?php endif; ?>
        });
        </script>

        <!-- MUST BE THE LAST SCRIPT IN <BODY> -->
        <script type="text/javascript">$(document).ready(function(){ $(document).pngFix( ); });</script>
    </body>
</html>