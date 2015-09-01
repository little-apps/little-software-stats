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

if ( !defined( 'LSS_LOADED' ))
    die( 'This page cannot be loaded directly' );

// Make sure user is logged in
verify_user( );

// Arrays for MySQL queries
$countries = array( );
$operating_systems = array( );
$languages = array( );

$version_query = ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '" . $sanitized_input['ver'] . "' " ) : ( "" ));

$inner_query = "SELECT COUNT(*) FROM `" . MySQL::getInstance()->prefix . "sessions` AS s2 WHERE s2.ApplicationId = '" . $sanitized_input['id'] . "' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s2.ApplicationVersion = '" . $sanitized_input['ver'] . "' " ) : ( "" )) . " AND s2.StartApp BETWEEN FROM_UNIXTIME(" . $sanitized_input['start'] . ") AND FROM_UNIXTIME(" . $sanitized_input['end'] . ")";

$query = "SELECT u.LangID, l.DisplayName, ";
$query .= "((COUNT(*) / (".$inner_query.")) * 100) AS 'percent' ";
$query .= "FROM `" . MySQL::getInstance()->prefix . "sessions` AS s ";
$query .= "INNER JOIN `" . MySQL::getInstance()->prefix . "uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
$query .= "INNER JOIN `" . MySQL::getInstance()->prefix . "locales` AS l ON u.LangID = l.LCID ";
$query .= "WHERE s.ApplicationId = '" . $sanitized_input['id'] . "' " . $version_query;
$query .= "AND s.StartApp BETWEEN FROM_UNIXTIME(" . $sanitized_input['start'] . ") AND FROM_UNIXTIME(" . $sanitized_input['end'] . ") ";
$query .= "GROUP BY u.LangID ";
$query .= "ORDER BY percent DESC ";
$query .= "LIMIT 0,5";

MySQL::getInstance()->execute_sql( $query );

$languages = array( );

if ( MySQL::getInstance()->records == 1 )
    $languages[] = MySQL::getInstance()->array_result( );
else if ( MySQL::getInstance()->records > 1 )
    $languages = MySQL::getInstance()->array_results( );

$query = "SELECT u.OSVersion, ";
$query .= "((COUNT(*) / (".$inner_query.")) * 100) AS 'percent' ";
$query .= "FROM `" . MySQL::getInstance()->prefix . "sessions` AS s ";
$query .= "INNER JOIN `" . MySQL::getInstance()->prefix . "uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
$query .= "WHERE s.ApplicationId = '" . $sanitized_input['id'] . "' " . $version_query;
$query .= "AND s.StartApp BETWEEN FROM_UNIXTIME(" . $sanitized_input['start'] . ") AND FROM_UNIXTIME(" . $sanitized_input['end'] . ") ";
$query .= "GROUP BY u.OSVersion ";
$query .= "ORDER BY percent DESC ";
$query .= "LIMIT 0,5";

MySQL::getInstance()->execute_sql( $query );

$operating_systems = array( );

if ( MySQL::getInstance()->records == 1 )
    $operating_systems[] = MySQL::getInstance()->array_result( );
else if ( MySQL::getInstance()->records > 1 )
    $operating_systems = MySQL::getInstance()->array_results( );

$query = "SELECT u.Country, ";
$query .= "((COUNT(*) / (".$inner_query.")) * 100) AS 'percent' ";
$query .= "FROM `" . MySQL::getInstance()->prefix . "sessions` AS s ";
$query .= "INNER JOIN `" . MySQL::getInstance()->prefix . "uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
$query .= "WHERE s.ApplicationId = '" . $sanitized_input['id'] . "' " . $version_query;
$query .= "AND s.StartApp BETWEEN FROM_UNIXTIME(" . $sanitized_input['start'] . ") AND FROM_UNIXTIME(" . $sanitized_input['end'] . ") ";
$query .= "GROUP BY u.Country ";
$query .= "ORDER BY percent DESC ";
$query .= "LIMIT 0,5";

MySQL::getInstance()->execute_sql( $query );

$countries = array( );

if ( MySQL::getInstance()->records == 1 )
    $countries[] = MySQL::getInstance()->array_result( );
else if ( MySQL::getInstance()->records > 1 )
    $countries = MySQL::getInstance()->array_results( );

// Get events
$version_query = ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '" . $sanitized_input['ver'] . "' " ) : ( "" ));

$inner_query = "SELECT COUNT(*) FROM `" . MySQL::getInstance()->prefix . "events_event` AS e2 INNER JOIN `" . MySQL::getInstance()->prefix . "sessions` AS s2 ON s2.SessionId = e2.SessionId WHERE s2.ApplicationId = '" . $sanitized_input['id'] . "' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s2.ApplicationVersion = '" . $sanitized_input['ver'] . "' " ) : ( "" )) . " AND e2.UtcTimestamp BETWEEN FROM_UNIXTIME(" . $sanitized_input['start'] . ") AND FROM_UNIXTIME(" . $sanitized_input['end'] . ")";

$query = "SELECT EventName, ";
$query .= "((COUNT(*) / (".$inner_query.")) * 100) AS 'percent' ";
$query .= "FROM `" . MySQL::getInstance()->prefix . "events_event` AS e ";
$query .= "INNER JOIN `" . MySQL::getInstance()->prefix . "sessions` AS s ON s.SessionId = e.SessionId ";
$query .= "WHERE s.ApplicationId = '" . $sanitized_input['id'] . "' " . $version_query;
$query .= "AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(" . $sanitized_input['start'] . ") AND FROM_UNIXTIME(" . $sanitized_input['end'] . ") ";
$query .= "GROUP BY EventName ";
$query .= "ORDER BY percent DESC ";
$query .= "LIMIT 0,5";

MySQL::getInstance()->execute_sql( $query );

$events = array( );

if ( MySQL::getInstance()->records == 1 )
    $events[] = MySQL::getInstance()->array_result( );
else if ( MySQL::getInstance()->records > 1 )
    $events = MySQL::getInstance()->array_results( );

unset( $query, $version_query );

// Create date range
$date_range = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'] );
$start_point = $date_range[0];

// Line chart
$line_chart = new Highchart( );

$line_chart->chart = array( 'renderTo' => 'chart_div', 'defaultSeriesType' => 'line', 'height' => 200 );
$line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
$line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
$line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
$line_chart->yAxis->title = '';
$line_chart->legend = array( 'layout' => 'horizontal', 'align' => 'right', 'verticalAlign' => 'top', 'floating' => true, 'x' => - 10, 'y' => - 10, 'borderWidth' => 0 );

$line_chart_data = array( __( 'Executions' ) => array( ), __( 'Installs' ) => array( ), __( 'Uninstalls' ) => array( ));

for ( $i = 0; $i < count( $date_range ) - 1; $i++ ) {
    $start = $date_range[$i];
    $end = $date_range[$i + 1];

    $execs = MySQL::getInstance()->select_sessions( $sanitized_input['id'], $sanitized_input['ver'], $start, $end, '*', false, true );
    $installs = MySQL::getInstance()->select_events( 'install', $sanitized_input['id'], $sanitized_input['ver'], $start, $end, true );
    $uninstalls = MySQL::getInstance()->select_events( 'uninstall', $sanitized_input['id'], $sanitized_input['ver'], $start, $end, true );

    unset( $start, $end );

    $line_chart_data[__( 'Executions' )][] = $execs;
    $line_chart_data[__( 'Installs' )][] = $installs;
    $line_chart_data[__( 'Uninstalls' )][] = $uninstalls;

    unset( $execs, $installs, $uninstalls );
}

$line_chart->series = convert_line_chart_data_to_array( $line_chart_data );

unset( $date_range, $start_point, $line_chart_data );
?>

<script type="text/javascript">
var chart;
$(document).ready(function() { <?php $line_chart->render( 'chart' ); unset( $line_chart ); ?> });
</script>

<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Overview' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url( '/images/shared/side_shadowleft.jpg' ); ?>"></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url( '/images/shared/side_shadowright.jpg' ); ?>"></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner" style="height: 200px">
                    <div id="chart_div"></div>
                </div>
            </td>
            <td id="tbl-border-right"></td>
        </tr>
        <tr>
            <th class="sized bottomleft"></th>
            <td id="tbl-border-bottom">&nbsp;</td>
            <th class="sized bottomright"></th>
        </tr>
    </tbody>
</table>
<!-- end stats graph -->

<div class="clear">&nbsp;</div>
<div class="clear">&nbsp;</div>

<div class="contentcontainers">
    <!-- Events Start -->
    <div class="contentcontainer left" style="width: 49%">
        <div class="headings alt">
            <h2><?php _e( 'Events' ); ?></h2>
        </div>
        <div class="contentbox">
            <?php if ( count( $events ) > 0 ) : ?>
            <table>
                <?php foreach ( $events as $row ) : ?>
                <tr>
                    <td width="150"><strong><?php echo htmlspecialchars( $row['EventName'] ); ?></strong></td>
                    <td width="500">
                        <div class="usagebox">
                            <div class="lowbar" style="width: <?php echo $row['percent'] . '%;' ?>"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php unset( $events, $row ); ?>
            </table>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Events End -->

    <!-- OSes Start -->
    <div class="contentcontainer right" style="width: 49%">
        <div class="headings alt">
            <h2><?php _e( 'Operating Systems' ); ?></h2>
        </div>
        <div class="contentbox">
            <?php if ( count( $operating_systems ) > 0 ) : ?>
            <table>
                <?php foreach ( $operating_systems as $row ) : ?>
                <tr>
                    <td width="150"><strong><?php echo htmlspecialchars( $row['OSVersion'] ); ?></strong></td>
                    <td width="500">
                        <div class="usagebox">
                            <div class="lowbar" style="width: <?php echo $row['percent'] . '%;' ?>"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php unset( $operating_systems, $row ); ?>
            </table>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <!-- OSes End -->

    <!-- Countries Start -->
    <div class="contentcontainer left" style="width: 49%; clear: both">
        <div class="headings alt">
            <h2><?php _e( 'Countries' ); ?></h2>
        </div>
        <div class="contentbox">
            <?php if ( count( $countries ) > 0 ) : ?>
            <table>
                <?php foreach ( $countries as $row ) : ?>
                <tr>
                    <td width="150"><strong><?php echo $row['Country']; ?></strong></td>
                    <td width="500">
                        <div class="usagebox">
                            <div class="lowbar" style="width: <?php echo $row['percent'] . '%;' ?>"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php unset( $countries, $row ); ?>
            </table>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Countries End -->

    <!-- Languages Start -->
    <div class="contentcontainer right" style="width: 49%">
        <div class="headings alt">
            <h2><?php _e( 'Languages' ); ?></h2>
        </div>
        <div class="contentbox">
            <?php if ( count( $languages ) > 0 ) : ?>
            <table>
                <?php foreach ( $languages as $row ) : ?>
                <tr>
                    <td width="150"><strong><?php echo $row['DisplayName']; ?></strong></td>
                    <td width="500">
                        <div class="usagebox">
                            <div class="lowbar" style="width: <?php echo $row['percent'] . '%;' ?>"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php unset( $languages, $row ); ?>
            </table>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Languages End -->
</div>