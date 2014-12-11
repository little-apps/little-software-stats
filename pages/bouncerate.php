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

$chart_data = array( __( 'Installs and executes' ) => array_fill( 0, count( $date_range_day ) - 1, 0 ), __( 'Installs and doesnt execute' ) => array_fill( 0, count( $date_range_day ) - 1, 0 ) );

$data_exists = false;

$total = $execute_total = $no_execute_total = 0;

$version_query = ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '" . $sanitized_input['ver'] . "' " ) : ( "" ));

for ( $i = 0; $i < count( $date_range_day ) - 1; $i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];

    $execute = $no_execute = 0;

    $query = "SELECT (";
    $query .= "SELECT COUNT(*) FROM `" . $db->prefix . "sessions` ";
    $query .= "WHERE UniqueUserId = u.UniqueUserId AND ApplicationId = '" . $sanitized_input['id'] . "' " . $version_query . "AND StartApp BETWEEN e.UtcTimestamp AND FROM_UNIXTIME(" . $end . ")";
    $query .= ") AS bounces ";
    $query .= "FROM `" . $db->prefix . "events_install` AS e ";
    $query .= "INNER JOIN `" . $db->prefix . "sessions` AS s ON e.SessionId = s.SessionId ";
    $query .= "INNER JOIN `" . $db->prefix . "uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
    $query .= "WHERE s.ApplicationId = '" . $sanitized_input['id'] . "' " . $version_query;
    $query .= "AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(" . $start . ") AND FROM_UNIXTIME(" . $end . ")";

    $db->execute_sql( $query );

    unset( $query, $start, $end );

    if ( $db->records > 0 ) {
        $rows = array( );

        if ( $db->records == 1 )
            $rows[] = $db->array_result( );
        else if ( $db->records > 1 )
            $rows = $db->array_results( );

        foreach ( $rows as $row ) {
            $bounces = intval( $row['bounces'] );

            if ( $bounces == 1 )
                $no_execute++;
            else if ( $bounces >= 2 )
                $execute++;

            unset( $bounces );
        }

        unset( $rows, $row );

        $total += $execute + $no_execute;
        $execute_total += $execute;
        $no_execute_total += $no_execute;

        $chart_data[__( 'Installs and executes' )][$i] = $execute;
        $chart_data[__( 'Installs and doesnt execute' )][$i] = $no_execute;

        unset( $execute, $no_execute );

        if ( !$data_exists )
            $data_exists = true;
    }
}

unset( $date_range_day );

$no_execute_last_month_total = 0;

$query = "SELECT COUNT(*) AS total ";
$query .= "FROM `" . $db->prefix . "events_install` AS e ";
$query .= "INNER JOIN `" . $db->prefix . "sessions` AS s ON e.SessionId = s.SessionId ";
$query .= "INNER JOIN `" . $db->prefix . "uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
$query .= "WHERE s.ApplicationId = '" . $sanitized_input['id'] . "' " . $version_query;
$query .= "AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(" . ( $sanitized_input['start'] - ( 30 * 24 * 3600 )) . ") AND FROM_UNIXTIME(" . $sanitized_input['start'] . ") AND (";
$query .= "SELECT COUNT(*) FROM `" . $db->prefix . "sessions` ";
$query .= "WHERE UniqueUserId = u.UniqueUserId AND ApplicationId = '" . $sanitized_input['id'] . "' " . $version_query . "AND StartApp BETWEEN e.UtcTimestamp AND FROM_UNIXTIME(" . $sanitized_input['start'] . ")) = 1";

$db->execute_sql( $query );

$row = $db->array_result( );

$no_execute_last_month_total = intval( $row['total'] );

unset( $query, $row, $version_query );

if ( $no_execute_last_month_total > 0 && !$data_exists )
    $data_exists = true;

if ( $data_exists ) :
    $percentage_increase = calculate_percentage_increase( $no_execute_last_month_total, $no_execute_total );

    $percentage_increase_up = false;
    $percentage_increase_str = $percentage_increase . '%';
    if ( $percentage_increase > 0 ) {
        $percentage_increase_str = '+' . $percentage_increase_str;
        $percentage_increase_up = true;
    }

    unset( $percentage_increase );

    $no_execute_percent = calculate_percent( $no_execute_total, $total, 1 );

    $pie_data_exists = false;

    // Place data in charts
    // Line chart
    $line_chart = new Highchart( );

    $line_chart->chart = array( 'renderTo' => 'chart_div', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart->yAxis->title = '';
    $line_chart->legend = array( 'layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => - 10, 'y' => 10, 'borderWidth' => 0 );
    $line_chart->series = convert_line_chart_data_to_array( $chart_data );

    unset( $chart_data, $start_point );

    if ( $execute_total > 0 || $no_execute_total > 0 ) {
        // Pie chart
        $pie_chart = new Highchart( );

        $pie_chart->chart = array( 'renderTo' => 'pie_div', 'plotShadow' => false );
        $pie_chart->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
        $pie_chart->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
        $pie_chart->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));
        $pie_chart->series[] = array( 'type' => 'pie', 'name' => __( 'Bounce Rate' ), 'data' => array( array( __( 'Installs and executes' ), $execute_total ), array( __( 'Installs and doesnt execute' ), $no_execute_total )));

        $pie_data_exists = true;
    }

    unset( $execute_total );
?>
<script type="text/javascript">
var chart_line, chart_pie;
$(document).ready(function() {
<?php
    $line_chart->render( 'chart_line' );
    unset( $line_chart );

    if ( $pie_data_exists ) {
        $pie_chart->render( 'chart_pie' );
        unset( $pie_chart );
    }
?>
});
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Bounce Rate' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox" id="graphs-1">
            <div id="chart_div"></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <?php if ( $pie_data_exists ) : ?>
            <div id="pie_div"></div>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
        <!-- Graphs Box End -->
    </div>
</div>

<div class="contentcontainers">
    <!-- Overview Start -->
    <div class="contentcontainer med left">
        <div class="headings alt">
            <h2><?php _e( 'Overview' ); ?></h2>
        </div>
        <div class="contentbox">
            <div>
                <p><span class="total"><?php echo $total; ?></span> <?php _e( 'installations' ); ?></p>
                <p><span class="total"><?php echo $no_execute_total; ?></span> <?php _e( 'bounced' ); ?> (<?php echo $no_execute_percent; ?>%)</p>
                <?php unset( $total, $no_execute_total, $no_execute_percent); ?>
            </div>
        </div>
    </div>
    <!-- Overview End -->

    <!-- Last Month Period Start -->
    <div class="contentcontainer sml right">
        <div class="headings alt">
            <h2><?php _e( 'Last Month Period' ); ?></h2>
        </div>
        <div class="contentbox" style="text-align: center; padding-top: 30px;">
            <span class="<?php echo ( ( $percentage_increase_up ) ? ( 'green' ) : ( 'red' )); ?>" style="font-weight: bold; font-size: 52px !important;"><?php echo $percentage_increase_str; ?></span>
            <?php unset( $percentage_increase_up ); ?>
            <br />
            <strong><?php _e( 'bounces last month period' ); ?></strong>
        </div>
    </div>
    <!-- Last Month Period End -->
</div>
<?php else : ?>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Bounce Rate' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox" id="graphs-1">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
        <!-- Graphs Box End -->
    </div>
</div>

<div class="contentcontainers">
    <!-- Overview Start -->
    <div class="contentcontainer">
        <div class="headings alt">
            <h2><?php _e( 'Overview' ); ?></h2>
        </div>
        <div class="contentbox">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
    </div>
    <!-- Overview End -->
</div>
<?php endif; ?>