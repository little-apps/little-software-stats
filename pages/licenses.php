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

$chart_data = array(
    __( 'Free' ) => array(),
    __( 'Trial' ) => array(),
    __( 'Demo' ) => array(),
    __( 'Registered' ) => array(),
    __( 'Cracked' ) => array(),
);

$data_exists = false;

$free_total = 0;
$trial_total = 0;
$demo_total = 0;
$registered_total = 0;
$cracked_total = 0;

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];

    $free = 0;
    $trial = 0;
    $demo = 0;
    $registered = 0;
    $cracked = 0;
    
    $custom_data = MySQL::getInstance()->select_events( 'customdata', $sanitized_input['id'], $sanitized_input['ver'], $start, $end, false, array( 'EventCustomName' => 'License' ) );

    unset( $start, $end );

    if ( count( $custom_data ) > 0 ) {
        foreach ( $custom_data as $custom_data_row ) {
            if ( $custom_data_row['EventCustomValue'] == 'F' ) {
                $free++;
                $free_total++;
            } elseif ( $custom_data_row['EventCustomValue'] == 'T' ) {
                $trial++;
                $trial_total++;
            } elseif ( $custom_data_row['EventCustomValue'] == 'D' ) {
                $demo++;
                $demo_total++;
            } elseif ( $custom_data_row['EventCustomValue'] == 'R' ) {
                $registered++;
                $registered_total++;
            } elseif ( $custom_data_row['EventCustomValue'] == 'C' ) {
                $cracked++;
                $cracked_total++;
            }
        }

        unset( $custom_data, $custom_data_row );

        $chart_data[__( 'Free' )][] = $free;
        $chart_data[__( 'Trial' )][] = $trial;
        $chart_data[__( 'Demo' )][] = $demo;
        $chart_data[__( 'Registered' )][] = $registered;
        $chart_data[__( 'Cracked' )][] = $cracked;

        if ( !$data_exists )
            $data_exists = true;
    }

    unset( $free, $trial, $demo, $registered, $cracked );
}

unset( $date_range_day );

if ( $data_exists ) :
    // Line chart
    $line_chart = new Highchart( );

    $line_chart->chart = array( 'renderTo' => 'chart_div', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart->yAxis->title = '';
    $line_chart->legend = array( 'layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => - 10, 'y' => 10, 'borderWidth' => 0 );

    unset( $start_point );

    // Pie chart
    $pie_chart = new Highchart( );

    $pie_chart->chart = array( 'renderTo' => 'pie_div', 'plotShadow' => false );
    $pie_chart->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
    $pie_chart->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
    $pie_chart->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));

    $line_chart->series = convert_line_chart_data_to_array( $chart_data );
    $pie_chart->series[] = array(
                    'type' => 'pie',
                    'name' => __( 'Type of license' ),
                    'data' => array( array( __( 'Free' ), $free_total ), array( __( 'Trial' ), $trial_total ), array( __( 'Demo' ), $demo_total ), array( __( 'Registered' ), $registered_total ), array( __( 'Cracked' ), $cracked_total ) )
                  );

    unset( $chart_data, $free_total, $trial_total, $demo_total, $registered_total, $cracked_total );
?>
<script type="text/javascript">
var chart_line, chart_pie;
$(document).ready(function() {
<?php
    $line_chart->render( 'chart_line' );
    $pie_chart->render( 'chart_pie' );
    unset( $line_chart, $pie_chart );
?>
});
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Licenses' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

<!-- Graphs Box Start -->
        <div class="contentbox" id="graphs-1" style="height: 200px">
            <div id="chart_div"></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="pie_div"></div>
        </div>
    <!-- Graphs Box End -->
    </div>
</div>
<?php else : unset( $chart_data, $free_total, $trial_total, $demo_total, $registered_total, $cracked_total ); ?>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Licenses' ); ?></h2>
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
<?php endif; ?>