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
$date_range = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'] );
$start_point = $date_range[0];

// Get lowest & highest date
$query = "SELECT IFNULL( UNIX_TIMESTAMP( MIN( StartApp ) ), 0 ) AS lowest, IFNULL( UNIX_TIMESTAMP( MAX( StopApp ) ), 1 ) AS highest FROM `".$db->prefix."sessions` WHERE `ApplicationId` = '".$sanitized_input['id']."' ". ( ( $sanitized_input['ver'] != 'all') ? ( "AND `ApplicationVersion` = '" . $sanitized_input['ver'] . "' " ) : ( '' ) );
$db->execute_sql( $query );

unset( $query );

$db->array_result();

if ( is_numeric( $db->arrayed_result['lowest'] ) && is_numeric( $db->arrayed_result['highest'] ) ) :

    $min_date = intval( $db->arrayed_result['lowest'] );
    $max_date = intval( $db->arrayed_result['highest'] );

    $line_chart_data_execs = array();
    $chart_data_by_period = array();
    $chart_data_total = 0;

    for ( $i = 0; $i < count( $date_range ) - 1 ;$i++ ) {
        $start = $date_range[$i];
        $end = $date_range[$i + 1];

        $total_for_period = $db->select_sessions( $sanitized_input['id'], $sanitized_input['ver'], $start, $end, '*', false, true );

        $line_chart_data_execs[] = $total_for_period;

        $chart_data_by_period[] = array(
            'start' => $start,
            'end' => $end,
            'total' => $total_for_period
        );

        $chart_data_total += $total_for_period;

        unset( $total_for_period, $start, $end );
    }

    unset( $date_range );

    // Line chart
    $line_chart = new Highchart( );

    $line_chart->chart = array( 'renderTo' => 'chart_div', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart->yAxis->title = '';
    $line_chart->legend = array( 'layout' => 'horizontal', 'align' => 'right', 'verticalAlign' => 'top', 'floating' => true, 'x' => - 10, 'y' => - 10, 'borderWidth' => 0 );
    $line_chart->series[] = array( 'name' => __( 'Executions' ), 'data' => $line_chart_data_execs );

    unset( $line_chart_data_execs, $start_point );

    // Get execution stats
    $total_execs = $db->select_sessions( $sanitized_input['id'], $sanitized_input['ver'], $min_date, $max_date, '*', false, true );
    $period_execs = $db->select_sessions( $sanitized_input['id'], $sanitized_input['ver'], $sanitized_input['start'], $sanitized_input['end'], '*', false, true );

    $day_execs_total = 0;
    $month_execs_total = 0;

    $date_range_total_day = create_date_range_array( $min_date, $max_date, 'day' );
    $date_range_total_month = create_date_range_array( $min_date, $max_date, 'month' );

    $day_execs_total = $db->select_sessions( $sanitized_input['id'], $sanitized_input['ver'], $date_range_total_day[0], end( $date_range_total_day ), '*', false, true );
    $month_execs_total = $db->select_sessions( $sanitized_input['id'], $sanitized_input['ver'], $date_range_total_month[0], end( $date_range_total_month ), '*', false, true );

    $day_execs = 0;
    $month_execs = 0;

    if ( count( $date_range_total_day ) - 1 > 0 )
        $day_execs = round( $day_execs_total / ( count( $date_range_total_day ) - 1 ), 2 );
    if ( count( $date_range_total_month ) - 1 > 0 )
        $month_execs = round( $month_execs_total / ( count( $date_range_total_month ) - 1 ), 2 );

    unset( $day_execs_total, $date_range_total_day, $month_execs_total, $date_range_total_month );

    // Get percentage difference from last month
    $last_month = $db->select_sessions( $sanitized_input['id'], $sanitized_input['ver'], $sanitized_input['start'] - ( 30 * 24 * 3600 ), $sanitized_input['start'], '*', false, true );

    $percentage_increase = calculate_percentage_increase( $last_month, $period_execs );

    unset( $last_month );

    $percentage_increase_up = false;
    $percentage_increase_str = $percentage_increase . '%';
    if ( $percentage_increase > 0 ) {
        $percentage_increase_str = '+' . $percentage_increase_str;
        $percentage_increase_up = true;
    }

    unset( $percentage_increase );
?>
<script type="text/javascript">
var chart;
$(document).ready(function() { <?php $line_chart->render('chart'); unset( $line_chart ); ?> });
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
    <!-- Overview Start -->
    <div class="contentcontainer med left">
        <div class="headings alt">
            <h2><?php _e( 'Overview' ); ?></h2>
        </div>
        <div class="contentbox">
            <div>
                <p><span class="total"><?php echo $total_execs; ?></span> <?php _e( 'executions' ); ?></p>
                <p><span class="total"><?php echo $period_execs; ?></span> <?php _e( 'executions in the period' ); ?></p>
                <p><span class="total"><?php echo $month_execs; ?></span> <?php _e( 'executions per month (average)' ); ?></p>
                <p><span class="total"><?php echo $day_execs; ?></span> <?php _e( 'executions per day (average)' ); ?></p>
                <?php unset( $total_execs, $period_execs, $month_execs, $day_execs ); ?>
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
            <?php unset( $percentage_increase_str ); ?>
            <br />
            <strong><?php _e( 'last month period' ); ?></strong>
        </div>
    </div>
    <!-- Last Month Period End -->

    <div style="clear: both"></div>

    <!-- Executions Chart Data Start -->
    <div class="contentcontainer">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Executions Chart Data' ); ?></h2>
        </div>
        <div class="contentbox">
            <table>
                <?php foreach ( $chart_data_by_period as $chart_data ) : ?>
                <tr>
                    <td>
                        <?php
                            if ( $sanitized_input['graphBy'] == 'day' )
                                echo date( 'l, F j, o', $chart_data['start'] );
                            else 
                                echo date( 'l, F j, o', $chart_data['start'] ) . ' to ' . date( 'l, F j, o', $chart_data['end'] );
                        ?>
                    </td>
                    <td width="900">
                        <?php $percent = calculate_percent( $chart_data['total'], $chart_data_total ); ?>
                        <div class="usagebox">
                            <div class="lowbar" style="width: <?php echo $percent . '%'; ?>"></div>
                        </div>
                    </td>
                    <td><strong><?php echo $percent; ?>% (<?php echo $chart_data['total']; ?>)<strong></td>
                </tr>
                <?php endforeach; ?>
                <?php unset( $chart_data_by_period, $chart_data, $percent, $chart_data_total ); ?>
            </table>
        </div>
    </div>
    <!-- Executions Chart Data End -->
</div>
<?php else : ?>
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
                <div id="content-table-inner" style="height: 200px">
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

<div class="clear">&nbsp;</div>
<div class="clear">&nbsp;</div>

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

    <div style="clear: both"></div>

    <!-- Executions Chart Data Start -->
    <div class="contentcontainer">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Executions Chart Data' ); ?></h2>
        </div>
        <div class="contentbox">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
    </div>
    <!-- Executions Chart Data End -->
</div>
<?php endif; ?>