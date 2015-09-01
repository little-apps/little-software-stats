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

// Get lowest & highest date
$query = "SELECT IFNULL( UNIX_TIMESTAMP( MIN( e.UtcTimestamp ) ), 0 ) AS lowest, IFNULL( UNIX_TIMESTAMP( MAX( e.UtcTimestamp ) ), 1 ) AS highest ";
$query .= "FROM `".MySQL::getInstance()->prefix."events_uninstall` AS e ";
$query .= "INNER JOIN `".MySQL::getInstance()->prefix."sessions` AS s ON e.SessionId = s.SessionId ";
$query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' ". ( ( $sanitized_input['ver'] != 'all') ? ( "AND s.ApplicationVersion = '" . $sanitized_input['ver'] . "' " ) : ( '' ) );

MySQL::getInstance()->execute_sql( $query );

unset( $query );

MySQL::getInstance()->array_result();

if ( is_numeric( MySQL::getInstance()->arrayed_result['lowest'] ) && is_numeric( MySQL::getInstance()->arrayed_result['highest'] ) ) :

    $min_date = intval( MySQL::getInstance()->arrayed_result['lowest'] );
    $max_date = intval( MySQL::getInstance()->arrayed_result['highest'] );

    $area_chart_data_uninstalls = array();
    $chart_data_by_period = array();
    $chart_data_total = 0;

    for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
        $start = $date_range_day[$i];
        $end = $date_range_day[$i + 1];

        $total_for_period = MySQL::getInstance()->select_events( 'uninstall', $sanitized_input['id'], $sanitized_input['ver'], $start, $end, true );

        $chart_data_by_period[] = array(
            'start' => $start,
            'end' => $end,
            'total' => $total_for_period
        );

        $chart_data_total += $total_for_period;

        $area_chart_data_uninstalls[] = $total_for_period;

        unset( $start, $end, $total_for_period );
    }

    unset( $date_range_day );

    // Get execution stats
    $total_uninstalls = MySQL::getInstance()->select_events( 'uninstall', $sanitized_input['id'], $sanitized_input['ver'], $min_date, $max_date, true );
    $period_uninstalls = MySQL::getInstance()->select_events( 'uninstall', $sanitized_input['id'], $sanitized_input['ver'], $sanitized_input['start'], $sanitized_input['end'], true );

    $date_range_total_day = create_date_range_array( $min_date, $max_date, 'day' );
    $date_range_total_month = create_date_range_array( $min_date, $max_date, 'month' );

    unset( $min_date, $max_date );

    $day_uninstalls_total = MySQL::getInstance()->select_events( 'uninstall', $sanitized_input['id'], $sanitized_input['ver'], $date_range_total_day[0], end( $date_range_total_day ), true );
    $month_uninstalls_total = MySQL::getInstance()->select_events( 'uninstall', $sanitized_input['id'], $sanitized_input['ver'], $date_range_total_month[0], end( $date_range_total_month ), true );

    $day_uninstalls = 0;
    $month_uninstalls = 0;

    if ( count( $date_range_total_day ) - 1 > 0 )
        $day_uninstalls = round( $day_uninstalls_total / ( count( $date_range_total_day ) - 1 ), 2 );

    unset( $day_uninstalls_total, $date_range_total_day );

    if ( count( $date_range_total_month ) - 1 > 0 )
        $month_uninstalls = round( $month_uninstalls_total / ( count( $date_range_total_month ) - 1 ), 2 );

    unset( $month_uninstalls_total, $date_range_total_month );

    // Get percentage difference from last month
    $last_month = MySQL::getInstance()->select_events( 'uninstall', $sanitized_input['id'], $sanitized_input['ver'], $sanitized_input['start'] - ( 30 * 24 * 3600 ), $sanitized_input['start'], true );

    $percentage_increase_up = false;
    $percentage_increase = calculate_percentage_increase( $last_month, $period_uninstalls );

    $percentage_increase_str = $percentage_increase . '%';
    if ( $percentage_increase > 0 ) {
        $percentage_increase_str = '+' . $percentage_increase_str;
        $percentage_increase_up = true;
    }

    unset( $last_month, $percentage_increase );

    // Line chart
    $line_chart = new Highchart( );

    $line_chart->chart = array( 'renderTo' => 'chart_div', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart->yAxis->title = '';
    $line_chart->legend = array( 'layout' => 'horizontal', 'align' => 'right', 'verticalAlign' => 'top', 'floating' => true, 'x' => - 10, 'y' => - 10, 'borderWidth' => 0 );
    $line_chart->series[] = array( 'name' => __( 'Uninstallations' ),'data' => $area_chart_data_uninstalls );

    unset( $area_chart_data_uninstalls, $start_point );
?>
<script type="text/javascript">
var chart;
$(document).ready(function() { <?php $line_chart->render( 'chart' ); unset( $line_chart ); ?> });
</script>
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
                <p><span class="total"><?php echo $total_uninstalls; ?></span> <?php _e( 'uninstallations' ); ?></p>
                <p><span class="total"><?php echo $period_uninstalls; ?></span> <?php _e( 'uninstallations in the period' ); ?></p>
                <p><span class="total"><?php echo $month_uninstalls; ?></span> <?php _e( 'uninstallations per month (average)' ); ?></p>
                <p><span class="total"><?php echo $day_uninstalls; ?></span> <?php _e( 'uninstallations per day (average)' ); ?></p>
                <?php unset( $total_uninstalls, $period_uninstalls, $month_uninstalls, $day_uninstalls ); ?>
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
            <br />
            <strong><?php _e( 'last month period' ); ?></strong>
        </div>
    </div>
    <!-- Last Month Period End -->
    <div style="clear: both"></div>
    <!-- Installations Chart Data Start -->
    <div class="contentcontainer">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Uninstallations Chart Data' ); ?></h2>
        </div>
        <div class="contentbox">
            <table>
                <?php foreach ( $chart_data_by_period as $chart_data ) : $percent = calculate_percent( $chart_data['total'], $chart_data_total ); ?>
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
                        <div class="usagebox">
                            <div class="lowbar" style="width: <?php echo $percent; ?>%;"></div>
                        </div>
                    </td>
                    <td><strong><?php echo $percent; ?>% (<?php echo $chart_data['total']; ?>)</strong></td>
                    <?php unset( $percent ); ?>
                </tr>
                <?php endforeach; ?>
                <?php unset( $chart_data_by_period, $chart_data ); ?>
            </table>
        </div>
    </div>
    <!-- Executions Chart Data End -->
</div>
<?php else : ?>
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
    <!-- Installations Chart Data Start -->
    <div class="contentcontainer">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Uninstallations Chart Data' ); ?></h2>
        </div>
        <div class="contentbox">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
    </div>
    <!-- Executions Chart Data End -->
</div>
<?php endif; ?>
