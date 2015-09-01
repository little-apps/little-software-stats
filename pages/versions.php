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

// Create date range
$date_range_day = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'] );
$start_point = $date_range_day[0];

$version_execs = array();
$version_installs = array();
$version_uninstalls = array();

$version_execs_exists = false;
$version_installs_exists = false;
$version_uninstalls_exists = false;

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    // Executions
    $query = "SELECT ApplicationVersion, COUNT(*) AS 'total' ";
    $query .= "FROM `".MySQL::getInstance()->prefix."sessions` ";
    $query .= "WHERE ApplicationId = '".$sanitized_input['id']."' ";
    $query .= "AND StartApp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") ";
    $query .= "GROUP BY ApplicationVersion";
    
    MySQL::getInstance()->execute_sql( $query );

    unset( $query );

    if ( MySQL::getInstance()->records > 0 ) {
        $rows = array();

        if ( MySQL::getInstance()->records == 1 )
            $rows[] = MySQL::getInstance()->array_result();
        else if ( MySQL::getInstance()->records > 1 )
            $rows = MySQL::getInstance()->array_results();

        foreach ( $rows as $row ) {
            $version = 'v' . rtrim( $row['ApplicationVersion'], ".0" );
            $total = intval( $row['total'] );

            if ( $version == "v" )
                $version = __( "Unknown" );

            if ( !array_key_exists( $version, $version_execs ) )
                $version_execs[$version] = array_fill( 0, count( $date_range_day ) - 1, 0 );

            $version_execs[$version][$i] = $total;

            unset( $version, $total );
        }

        unset( $rows, $row );

        ksort( $version_execs );

        if ( !$version_execs_exists )
            $version_execs_exists = true;
    }

    // Installations
    $query = "SELECT s.ApplicationVersion AS 'ApplicationVersion', COUNT(*) AS 'total' ";
    $query .= "FROM `".MySQL::getInstance()->prefix."events_install` AS e ";
    $query .= "INNER JOIN `".MySQL::getInstance()->prefix."sessions` AS s ON e.SessionId = s.SessionId ";
    $query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") ";
    $query .= "GROUP BY s.ApplicationVersion";
    
    MySQL::getInstance()->execute_sql( $query );

    unset( $query );

    if ( MySQL::getInstance()->records > 0 ) {
        $rows = array();

        if ( MySQL::getInstance()->records == 1 )
            $rows[] = MySQL::getInstance()->array_result();
        else if ( MySQL::getInstance()->records > 1 )
            $rows = MySQL::getInstance()->array_results();

        foreach ( $rows as $row ) {
            $version = 'v' . rtrim( $row['ApplicationVersion'], ".0" );
            $total = intval( $row['total'] );

            if ( $version == "v" )
                $version = __( "Unknown" );

            if ( !array_key_exists( $version, $version_installs ) )
                $version_installs[$version] = array_fill( 0, count( $date_range_day ) - 1, 0 );

            $version_installs[$version][$i] = $total;
        }

        unset( $rows, $row );

        ksort( $version_installs );

        if ( !$version_installs_exists )
            $version_installs_exists = true;
    }
    
    // Uninstallations
    $query = "SELECT s.ApplicationVersion AS 'ApplicationVersion', COUNT(*) AS 'total' ";
    $query .= "FROM `".MySQL::getInstance()->prefix."events_uninstall` AS e ";
    $query .= "INNER JOIN `".MySQL::getInstance()->prefix."sessions` AS s ON e.SessionId = s.SessionId ";
    $query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") ";
    $query .= "GROUP BY s.ApplicationVersion";
    
    MySQL::getInstance()->execute_sql( $query );

    unset( $query, $start, $end );

    if ( MySQL::getInstance()->records > 0 ) {
        $rows = array();

        if ( MySQL::getInstance()->records == 1 )
            $rows[] = MySQL::getInstance()->array_result();
        else if ( MySQL::getInstance()->records > 1 )
            $rows = MySQL::getInstance()->array_results();

        foreach ( $rows as $row ) {
            $version = 'v' . rtrim( $row['ApplicationVersion'], ".0" );
            $total = intval( $row['total'] );

            if ( $version == "v" )
                $version = __( "Unknown" );

            if ( !array_key_exists( $version, $version_uninstalls ) )
                $version_uninstalls[$version] = array_fill( 0, count( $date_range_day ) - 1, 0 );

            $version_uninstalls[$version][$i] = $total;

            unset( $version, $total );
        }

        ksort( $version_uninstalls );

        unset( $rows, $row );

        if ( !$version_uninstalls_exists )
            $version_uninstalls_exists = true;
    }
    

}

unset( $date_range_day );

if ( $version_execs_exists ) {
    // Line chart (execs)
    $line_chart_execs = new Highchart( );

    $line_chart_execs->chart = array( 'renderTo' => 'execs_div', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart_execs->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart_execs->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart_execs->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart_execs->yAxis->title = '';
    $line_chart_execs->legend = array( 'layout' => 'horizontal', 'align' => 'right', 'verticalAlign' => 'top', 'floating' => true, 'x' => - 10, 'y' => - 10, 'borderWidth' => 0 );
    $line_chart_execs->series = convert_line_chart_data_to_array( $version_execs );
}

unset( $version_execs );

if ( $version_installs_exists ) {
    // Line chart (installs)
    $line_chart_installs = new Highchart( );

    $line_chart_installs->chart = array( 'renderTo' => 'installs_div', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart_installs->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart_installs->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart_installs->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart_installs->yAxis->title = '';
    $line_chart_installs->legend = array( 'layout' => 'horizontal', 'align' => 'right', 'verticalAlign' => 'top', 'floating' => true, 'x' => - 10, 'y' => - 10, 'borderWidth' => 0 );

    $line_chart_installs->series = convert_line_chart_data_to_array( $version_installs );
}

unset( $version_installs );

if ( $version_uninstalls_exists ) {
    // Line chart (uninstalls)
    $line_chart_uninstalls = new Highchart( );

    $line_chart_uninstalls->chart = array( 'renderTo' => 'uninstalls_div', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart_uninstalls->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart_uninstalls->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart_uninstalls->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart_uninstalls->yAxis->title = '';
    $line_chart_uninstalls->legend = array( 'layout' => 'horizontal', 'align' => 'right', 'verticalAlign' => 'top', 'floating' => true, 'x' => - 10, 'y' => - 10, 'borderWidth' => 0 );
    $line_chart_uninstalls->series = convert_line_chart_data_to_array( $version_uninstalls );
}

unset( $version_uninstalls, $start_point );
?>
<script type="text/javascript">
var chart_execs, chart_installs, chart_uninstalls;
$(document).ready(function() {
<?php
    if ( $version_execs_exists ) $line_chart_execs->render( 'chart_execs' ); unset( $line_chart_execs );
    if ( $version_installs_exists ) $line_chart_installs->render( 'chart_installs' ); unset( $line_chart_installs );
    if ( $version_uninstalls_exists ) $line_chart_uninstalls->render( 'chart_uninstalls' ); unset( $line_chart_uninstalls );
?>
});
</script>
<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Executions' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowleft.jpg'); ?>"></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowright.jpg'); ?>"></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner">
                    <?php if ( $version_execs_exists ) : ?>
                    <div id="execs_div"></div>
                    <?php else : ?>
                    <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
                    <?php endif; ?>
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

<br />

<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Installations' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowleft.jpg'); ?>"></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowright.jpg'); ?>"></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner">
                    <?php if ( $version_installs_exists ) : ?>
                    <div id="installs_div"></div>
                    <?php else : ?>
                    <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
                    <?php endif; ?>
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

<br />

<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Uninstallations' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowleft.jpg'); ?>"></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowright.jpg'); ?>"></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner">
                    <?php if ( $version_uninstalls_exists ) : ?>
                    <div id="uninstalls_div"></div>
                    <?php else : ?>
                    <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
                    <?php endif; ?>
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