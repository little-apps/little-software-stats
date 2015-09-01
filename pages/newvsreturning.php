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

$chart_data = array( __( 'New' ) => array_fill( 0, count( $date_range_day ) - 1, 0 ), __( 'Returning' ) => array_fill( 0, count( $date_range_day ) - 1, 0 ) );

$data_exists = false;

$new_last_month_total = $new_total = $returning_total = 0;

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $new = $returning = 0;
    
    $query = "SELECT COUNT(*) AS 'count' FROM (";
    $query .= "SELECT COUNT(*) AS total ";
    $query .= "FROM `".MySQL::getInstance()->prefix."sessions` ";
    $query .= "WHERE ApplicationId = '".$sanitized_input['id']."' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
    $query .= "AND StartApp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") ";
    $query .= "GROUP BY UniqueUserId ";
    $query .= "HAVING (COUNT(*)) = 1 ";
    $query .= ") AS t";
    
    MySQL::getInstance()->execute_sql( $query );

    unset( $query );

    $row = MySQL::getInstance()->array_result();
    $count = intval( $row['count'] );

    if ( $count > 0 && !$data_exists )
        $data_exists = true;

    $chart_data[__( 'New' )][$i] = $count;;
    $new_total += $count;

    unset( $row, $count );
    
    $query = "SELECT COUNT(*) AS 'count' FROM (";
    $query .= "SELECT COUNT(*) AS total ";
    $query .= "FROM `".MySQL::getInstance()->prefix."sessions` ";
    $query .= "WHERE ApplicationId = '".$sanitized_input['id']."' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
    $query .= "AND StartApp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") ";
    $query .= "GROUP BY UniqueUserId ";
    $query .= "HAVING (COUNT(*)) > 1";
    $query .= ") AS t";
    
    MySQL::getInstance()->execute_sql( $query );

    unset( $query );
    
    $row = MySQL::getInstance()->array_result();
    $count = intval( $row['count'] );

    if ( $count > 0 && !$data_exists )
        $data_exists = true;
    
    $chart_data[__( 'Returning' )][$i] = $count;
    $returning_total += $count;

    unset( $row, $count );
}

unset( $date_range_day );

// Get new users from last month   
$query = "SELECT COUNT(*) AS 'count' FROM (";
$query .= "SELECT COUNT(*) AS total ";
$query .= "FROM `".MySQL::getInstance()->prefix."sessions` ";
$query .= "WHERE ApplicationId = '".$sanitized_input['id']."' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
$query .= "AND StartApp BETWEEN FROM_UNIXTIME(". ( $sanitized_input['start'] - ( 30 * 24 * 3600 ) ).") AND FROM_UNIXTIME(".$sanitized_input['start'].") ";
$query .= "GROUP BY UniqueUserId ";
$query .= "HAVING (COUNT(*)) = 1 ";
$query .= ") AS t";

MySQL::getInstance()->execute_sql( $query );

$row = MySQL::getInstance()->array_result();
$new_last_month_total = intval( $row['count'] );

unset ( $query, $row );

if ( $new_last_month_total > 0 && !$data_exists )
    $data_exists = true;

if ( $data_exists ) :
    $percentage_increase = calculate_percentage_increase( $new_last_month_total, $new_total );

    $percentage_increase_up = false;
    $percentage_increase_str = $percentage_increase . '%';
    if ( $percentage_increase > 0 ) {
        $percentage_increase_str = '+' . $percentage_increase_str;
        $percentage_increase_up = true;
    }

    unset( $percentage_increase );

    $total = $new_total + $returning_total;
    $new_percent = calculate_percent( $new_total, $total );
    $returning_percent = calculate_percent( $returning_total, $total );

    unset( $total );

    // Line chart
    $line_chart = new Highchart( );

    $line_chart->chart = array( 'renderTo' => 'chart_div', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart->yAxis->title = '';
    $line_chart->legend = array( 'layout' => 'horizontal', 'align' => 'right', 'verticalAlign' => 'top', 'floating' => true, 'x' => - 10, 'y' => - 10, 'borderWidth' => 0 );
    $line_chart->series = convert_line_chart_data_to_array( $chart_data );

    unset( $chart_data, $start_point );

    // Pie chart
    $pie_chart = new Highchart( );

    $pie_chart->chart = array( 'renderTo' => 'pie_div', 'plotShadow' => false );
    $pie_chart->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
    $pie_chart->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
    $pie_chart->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));
    $pie_chart->series[] = array( 'type' => 'pie', 'name' => __( 'New vs Returning' ), 'data' => array( array( __( 'New' ), $new_total ), array( __( 'Returning' ), $returning_total ) ) );
?>
<script type="text/javascript">
var chart_line, chart_pie;
$(document).ready(function() { <?php $line_chart->render( 'chart_line' ); $pie_chart->render( 'chart_pie' ); unset( $line_chart, $pie_chart ); ?>});
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'New vs. Returning' ); ?></h2>
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
            <div id="pie_div"></div>
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
                <p><span class="total"><?php echo $new_percent; ?>%</span> <?php _e( 'from new users' ); ?> (<?php echo $new_total; ?> <?php _e( 'executions' ); ?>)</p>
                <?php unset( $new_percent, $new_total ); ?>
                <p><span class="total"><?php echo $returning_percent; ?>%</span> <?php _e( 'from returning users' ); ?> (<?php echo $returning_total; ?> <?php _e( 'executions' ); ?>)</p>
                <?php unset( $returning_percent, $returning_total ); ?>
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
            <span class="<?php echo ( ( $percentage_increase_up ) ? ( 'green' ) : ( 'red' ) ); ?>" style="font-weight: bold; font-size: 52px !important;"><?php echo $percentage_increase_str; ?></span>
            <?php unset( $percentage_increase_up, $percentage_increase_str ); ?>
            <br />
            <strong><?php _e( 'new users last month period' ); ?></strong>
        </div>
    </div>
    <!-- Last Month Period End -->
</div>
<?php else : unset( $chart_data, $new_total, $returning_total, $new_last_month_total ); ?>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'New vs. Returning' ); ?></h2>
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