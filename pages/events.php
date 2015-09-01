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

$category_selected = ( ( isset( $_POST['category'] ) ) ? ( htmlspecialchars_decode( $_POST['category'] ) ) : ( 'all' ) );
$type = ( ( isset( $_POST['type'] ) ) ? ( strtolower( $_POST['type'] ) ) : ( 'total' ) );

// Create date range
$date_range_day = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'] );

$start_point = $date_range_day[0];

$categories = array();
$auniqueusers = array();

$events_chart_data = array();
$pie_chart_data = array();

$grouped_events = MySQL::getInstance()->select_events( 'event', $sanitized_input['id'], $sanitized_input['ver'], $sanitized_input['start'], $sanitized_input['end'], false, '', 'EventCategory, EventName' );
foreach ( $grouped_events as $event ) {
    $event_category = $event['EventCategory'];
    $event_name = $event['EventName'];
    
    if ( !in_array( $event_category, $categories ) )
        $categories[] = $event_category;
    
    if ( $category_selected == 'all' || $category_selected == $event_category) {
        if ( !array_key_exists( $event_name, $events_chart_data ) ) {
            $events_chart_data[$event_name] = array_fill( 0, count( $date_range_day ) - 1, 0 );
            $pie_chart_data[$event_name] = 0;
        }
    }
}

unset( $grouped_events, $event, $event_category, $event_name );

// Make sure category specified exists
if ( !in_array( $category_selected, $categories ) && $category_selected != 'all' )
    $category_selected = 'all';

$graph_data_exists = false;

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];

    $query = "SELECT e.EventName, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS 'total' ";
    $query .= "FROM `".MySQL::getInstance()->prefix."sessions` AS s ";
    $query .= "INNER JOIN `".MySQL::getInstance()->prefix."events_event` AS e ON s.SessionId = e.SessionId ";
    $query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
    if ( $category_selected != 'all' )
        $query .= "AND e.EventCategory = '" . MySQL::getInstance()->secure_data( $category_selected ) . "' ";
    $query .= "AND e.EventName IS NOT NULL AND e.EventCategory IS NOT NULL ";
    $query .= "AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") ";
    $query .= "GROUP BY e.EventName";

    MySQL::getInstance()->execute_sql( $query );

    $rows = array();

    if (MySQL::getInstance()->records > 0 ) {
        if ( MySQL::getInstance()->records == 1 )
            $rows[] = MySQL::getInstance()->array_result();
        elseif ( MySQL::getInstance()->records > 1 )
            $rows = MySQL::getInstance()->array_results();

        foreach ( $rows as $row ) {
            $event_name = $row['EventName'];
            $total = intval( $row['total'] );

            $events_chart_data[$event_name][$i] = $total;
            $pie_chart_data[$event_name] += $total;
        }

        if ( !$graph_data_exists )
            $graph_data_exists = true;
    }
}

unset( $date_range_day, $start, $end, $query, $rows, $row, $event_name, $total );

$chart_data = array();
$chart_data_exists = false;

// Get day today
$day_today_start = strtotime('today');
$day_today_end = $day_today_start + ( 24 * 3600 );

// Get day yesterday
$day_yesterday_start = strtotime('yesterday');
$day_yesterday_end = $day_yesterday_start + ( 24 * 3600 );

$day_yesterday = date( 'l', $day_yesterday_start );

// Get day week from yesterday
$day_week_ago_start = strtotime('last '.$day_yesterday, $day_yesterday_start);
$day_week_ago_end = $day_week_ago_start + ( 24 * 3600 );

$day_week_ago = 'Last '.date('l', $day_week_ago_start );

// Get day 2 weeks from yesterday
$day_2_weeks_ago_start = strtotime('last '.$day_yesterday, $day_week_ago_start);
$day_2_weeks_ago_end = $day_2_weeks_ago_start + ( 24 * 3600 );

// Get events
for ( $i = 0; $i < 3; $i++ ) {
    if ( $i == 0 ) {
        // Today
        $period_start = $day_today_start;
        $period_end = $day_today_end;
    } elseif ( $i == 1 ) {
        // Yesterday
        $period_start = $day_yesterday_start;
        $period_end = $day_yesterday_end;
    } elseif ( $i == 2 ) {
        // Week Ago
        $period_start = $day_week_ago_start;
        $period_end = $day_week_ago_end;
    } elseif ( $i == 3 ) {
        // 2 Weeks Ago
        $period_start = $day_2_weeks_ago_start;
        $period_end = $day_2_weeks_ago_end;
    }

    $query = "SELECT e.EventName, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS total ";
    $query .= "FROM `".MySQL::getInstance()->prefix."sessions` AS s ";
    $query .= "INNER JOIN `".MySQL::getInstance()->prefix."events_event` AS e ON s.SessionId = e.SessionId ";
    $query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
    if ( $category_selected != 'all' )
        $query .= "AND e.EventCategory = '".$category_selected."' ";
    $query .= "AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(".$period_start.") AND FROM_UNIXTIME(".$period_end.") ";
    $query .= "GROUP BY e.EventName";

    MySQL::getInstance()->execute_sql( $query );

    unset( $query );

    $rows = array();

    if ( MySQL::getInstance()->records > 0 ) {
        if ( MySQL::getInstance()->records == 1 )
            $rows[] = MySQL::getInstance()->array_result();
        else if ( MySQL::getInstance()->records > 1 )
            $rows = MySQL::getInstance()->array_results();

        foreach ( $rows as $row ) {
            $event_name = $row['EventName'];
            $total = intval( $row['total'] );

            if ( !array_key_exists( $event_name, $chart_data ) )
                $chart_data[$event_name] = array( 0, 0, 0, 0 );

            $chart_data[$event_name][$i] = $total;
        }

        if ( !$chart_data_exists )
            $chart_data_exists = true;

        unset( $rows, $row, $event_name, $total );
    }
}

unset( $day_today_start, $day_today_end, $day_yesterday_start, $day_yesterday_end, $day_week_ago_start, $day_week_ago_end, $day_2_weeks_ago_start, $day_2_weeks_ago_end );
unset( $period_start, $period_end, $i );

if ( $graph_data_exists ) :
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

    $line_chart->series = convert_line_chart_data_to_array( $events_chart_data );
    $pie_chart->series[] = array( 'type' => 'pie', 'name' => __( 'Events' ), 'data' => convert_pie_chart_data_to_array( $pie_chart_data ) );

    unset( $events_chart_data, $pie_chart_data );
?>
<script type="text/javascript">
var chart_line, chart_pie;
$(document).ready(function() { <?php $line_chart->render( 'chart_line' ); $pie_chart->render( 'chart_pie' ); unset( $line_chart, $pie_chart ); ?> });
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Events' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox nobottom" id="graphs-1">
            <div id="chart_div"></div>
        </div>

        <div class="contentbox nobottom" id="graphs-2">
            <div id="pie_div"></div>
        </div>

        <div class="contentbox bottom">
            <form class="right" method="post" action="#">
                <strong>Category: </strong>
                <select name="category">
                    <option value="all"<?php echo ( ( $category_selected == 'all' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'All' ); ?></option>
                    <?php foreach ( $categories as $category ) : ?>
                        <option<?php echo ( ( $category_selected == $category ) ? ( ' selected' ) : ( '' ) ) ?>><?php echo htmlspecialchars( $category ); ?></option>
                    <?php endforeach; ?>
                    <?php unset( $categories, $category, $category_selected ); ?>
                </select>
                <strong>&nbsp;Type: </strong>
                <select name="type">
                    <option value="total"<?php echo ( ( $type == 'total' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Total' ); ?></option>
                    <option value="unique"<?php echo ( ( $type == 'unique' ) ? ( ' selected' ) : ( '' ) ) ?><?php _e( '>Unique' ); ?></option>
                </select>
                &nbsp;&nbsp;
                <input name="apply" class="form-submit right" type="submit" value="<?php _e( 'Apply' ); ?>" />
            </form>
        </div>
        <!-- Graphs Box End -->
    </div>
<?php else : ?>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Events' ); ?></h2>
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
<?php endif; ?>

    <!-- Overview Start -->
    <div class="contentcontainer">
        <div class="headings altheading">
            <h2><?php _e( 'Overview' ); ?></h2>
        </div>
        <div class="contentbox">
            <?php if ( $chart_data_exists ) : ?>
            <table style="width: 100%">
                <thead>
                    <tr>
                        <th><?php _e( 'Event' ); ?></th>
                        <th><?php _e( 'Today' ); ?></th>
                        <th><?php echo $day_yesterday ?></th>
                        <th><?php echo $day_week_ago ?></th>
                    </tr>
                </thead>
                <tbody>
<?php
                    foreach ( $chart_data as $event => $period ) : 
                        $today_percent = calculate_percentage_increase( $period[1], $period[0] );
                        $today_percent_str = $today_percent . '%';
                        if ( $today_percent > 0 )
                            $today_percent_str = '+' . $today_percent_str;
                    
                        $yesterday_percent = calculate_percentage_increase( $period[2], $period[1] );
                        $yesterday_percent_str = $yesterday_percent . '%';
                        if ( $yesterday_percent > 0 )
                            $yesterday_percent_str = '+' . $yesterday_percent_str;
                        
                        $week_ago_percent = calculate_percentage_increase( $period[3], $period[2] );
                        $week_ago_percent_str = $week_ago_percent . '%';
                        if ( $yesterday_percent > 0 )
                            $week_ago_percent_str = '+' . $week_ago_percent_str;
?>
                    <tr>
                        <td><?php echo htmlspecialchars( $event ); ?></td>
                        <td><?php echo $period[0] ?><br /><span class="<?php echo ( $today_percent > 0 ? 'green' : 'red' ) ?>"><?php echo $today_percent_str ?></span></td>
                        <td><?php echo $period[1] ?><br /><span class="<?php echo ( $yesterday_percent > 0 ? 'green' : 'red' ) ?>"><?php echo $yesterday_percent_str ?></span></td>
                        <td><?php echo $period[2] ?><br /><span class="<?php echo ( $week_ago_percent > 0 ? 'green' : 'red' ) ?>"><?php echo $week_ago_percent_str ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php unset( $day_yesterday, $day_week_ago, $event, $period, $today_percent, $today_percent_str, $yesterday_percent, $yesterday_percent_str, $week_ago_percent, $week_ago_percent_str ); ?>
                </tbody>
            </table>
            <?php else : ?>
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Overview End -->