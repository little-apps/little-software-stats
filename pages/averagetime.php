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

// Create date range
$date_range_day = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'] );
$start_point = $date_range_day[0];

$data_exists = false;

$line_chart_data = array( );

for ( $i = 0; $i < count( $date_range_day ) - 1; $i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];

    $query = "SELECT TIMESTAMPDIFF(SECOND, StartApp, StopApp ) AS 'duration' FROM `" . MySQL::getInstance()->prefix . "sessions` ";
    $query .= "WHERE `StartApp` >= FROM_UNIXTIME(" . $start . ") AND `StopApp` <= FROM_UNIXTIME(" . $end . ") AND `StopApp` > 0 ";
    $query .= "AND `ApplicationId` = '" . $sanitized_input['id'] . "' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND `ApplicationVersion` = '" . $sanitized_input['ver'] . "' " ) : ( "" ));

    MySQL::getInstance()->execute_sql( $query );

    unset( $start, $end, $query );

    if ( MySQL::getInstance()->records > 0 ) {
        $sessions = array( );

        if ( MySQL::getInstance()->records == 1 )
            $sessions[] = MySQL::getInstance()->array_result( );
        else if ( MySQL::getInstance()->records > 1 )
            $sessions = MySQL::getInstance()->array_results( );

        $time_span_total = 0;
        $sessions_count = count( $sessions );

        foreach ( $sessions as $session_row ) {
            if ( $session_row['duration'] > 0 )
                $time_span_total += $session_row['duration'];
        }

        unset( $sessions, $session_row );

        $line_chart_data[] = round( $time_span_total / $sessions_count);

        unset( $time_span_total, $sessions_count );

        if ( !$data_exists )
            $data_exists = true;
    } else {
        $line_chart_data[] = 0;
    }
}

if ( $data_exists ) :
    // Line chart
    $line_chart = new Highchart( );

    $line_chart->chart = array( 'renderTo' => 'chart_div', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart->yAxis->title = '';
    $line_chart->yAxis->labels->formatter = new HighchartJsExpr( "function() { return TimeSpan(this.value); }" );
    $line_chart->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.series.name +'</b><br/>'+new Date(this.x).toDateString() +': '+ TimeSpan(this.y); }" );
    $line_chart->legend = array( 'layout' => 'horizontal', 'align' => 'right', 'verticalAlign' => 'top', 'floating' => true, 'x' => - 10, 'y' => - 10, 'borderWidth' => 0 );
    $line_chart->series[] = array( 'name' => __( 'Average Time' ), 'data' => $line_chart_data );

    unset( $date_range_day, $line_chart_data, $start_point );

    // Average time chart data
    $time_chart_data = array( );
    $total_average_time = 0;

    $date_range = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'], $sanitized_input['graphBy'] );

    for ( $i = 0; $i < count( $date_range ) - 1; $i++ ) {
        $start = $date_range[$i];
        $end = $date_range[$i + 1];

        $time_span_total = 0;
        $average_time = 0;

        $query = "SELECT TIMESTAMPDIFF(SECOND, StartApp, StopApp ) AS 'duration' FROM `" . MySQL::getInstance()->prefix . "sessions` ";
        $query .= "WHERE `StartApp` >= FROM_UNIXTIME(" . $start . ") AND `StopApp` <= FROM_UNIXTIME(" . $end . ") AND `StopApp` > FROM_UNIXTIME(0) ";
        $query .= "AND `ApplicationId` = '" . $sanitized_input['id'] . "' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND `ApplicationVersion` = '" . $sanitized_input['ver'] . "' " ) : ( "" ));

        MySQL::getInstance()->execute_sql( $query );

        unset( $query );

        if ( MySQL::getInstance()->records > 0 ) {
            $sessions = array( );

            if ( MySQL::getInstance()->records == 1 )
                $sessions[] = MySQL::getInstance()->array_result( );
            else if ( MySQL::getInstance()->records > 1 )
                $sessions = MySQL::getInstance()->array_results( );

            $sessions_count = count( $sessions );

            foreach ( $sessions as $session_row ) {
                if ( $session_row['duration'] > 0 )
                    $time_span_total += $session_row['duration'];
            }

            unset( $sessions, $session_row );

            $average_time = round( $time_span_total / $sessions_count );

            // Used to calculate percent
            $total_average_time += $average_time;

            unset( $time_span_total, $sessions_count );
        }

        $time_chart_data[] = array( 'start' => $start, 'end' => $end, 'averagetime' => $average_time );

        unset( $start, $end, $average_time );
    }

    unset( $date_range );
?>
<script type="text/javascript">
var chart;
$(document).ready(function() {
    TimeSpan = function(time) {
        var hours = 0;
        var minutes = 0;
        var seconds = 0;

        while(time >= 3600) {
            hours++;
            time -= 3600;
        }

        while(time >= 60) {
            minutes++;
            time -= 60;
        }

        seconds = Math.round(time);

        return ( ( hours > 0 ) ? ( hours + 'h ' ) : ( '' ) ) + ( ( minutes > 0 ) ? ( minutes + 'm ' ) : ( '' ) ) + seconds + 's';
    }

<?php
    $line_chart->render( "chart" );
    unset( $line_chart );
?>
});
</script>
<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Average Time' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url( '/images/shared/side_shadowleft.jpg' ); ?>" /></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url( '/images/shared/side_shadowright.jpg' ); ?>" /></th>
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

<div style="clear: both"></div>

<div class="contentcontainers">
    <!-- Executions Chart Data Start -->
    <div class="contentcontainer">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Average Time Chart Data' ); ?></h2>
        </div>
        <div class="contentbox">
            <table>
                <?php foreach ( $time_chart_data as $chart_data ) : $percent = calculate_percent( $chart_data['averagetime'], $total_average_time ); ?>
                    <tr>
                        <td>
<?php
                        if ( $sanitized_input['graphBy'] == 'day' )
                            echo date( 'l, F j, o', $chart_data['start'] );
                        else
                            echo date( 'l, F j, o', $chart_data['start'] ) . ' to ' . date( 'l, F j, o', $chart_data['end'] );
?>
                        </td>
                        <td width="725">
                            <div class="usagebox">
                                <div class="lowbar" style="width: <?php echo $percent; ?>%;"></div>
                            </div>
                        </td>
                        <td><strong><?php echo $percent; ?>% (<?php echo get_time_duration( $chart_data['averagetime'] ); ?>)</strong></td>
                        <?php unset( $percent ); ?>
                    </tr>
               <?php endforeach; ?>
               <?php unset( $time_chart_data, $chart_data, $total_average_time ); ?>
            </table>
        </div>
    </div>
    <!-- Executions Chart Data End -->
</div>
<?php else : ?>
<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Average Time' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url( '/images/shared/side_shadowleft.jpg' ); ?>" /></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url( '/images/shared/side_shadowright.jpg' ); ?>" /></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner">
                    <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
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

<div style="clear: both"></div>

<div class="contentcontainers">
    <!-- Executions Chart Data Start -->
    <div class="contentcontainer">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Average Time Chart Data' ); ?></h2>
        </div>
        <div class="contentbox">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
    </div>
    <!-- Executions Chart Data End -->
</div>
<?php endif; ?>