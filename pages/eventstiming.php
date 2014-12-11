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

$type = ( ( isset( $_POST['type'] ) ) ? ( strtolower( $_POST['type'] ) ) : ( 'total' ) );

$events = array();

$events_chart_data = array();

$grouped_events = $db->select_events( 'eventperiod', $sanitized_input['id'], $sanitized_input['ver'], $sanitized_input['start'], $sanitized_input['end'], false, '', 'EventCategory, EventName' );

$data_exists = false;

if ( count( $grouped_events ) > 0 ) {
    foreach ( $grouped_events as $event ) {
        $event_category = $event['EventCategory'];
        $event_name = $event['EventName'];

        if ( !array_key_exists( $event_category, $events ) )
            $events[$event_category] = array();

        if ( !in_array($event_name, $events[$event_category] ) )
            $events[$event_category][] = $event_name;
    }

    $data_exists = true;

    unset( $grouped_events, $event, $event_category, $event_name );
} else {
    // Just in case..
    $events[__( '(None)' )] = array( __( '(None)' ) );
}

$category_selected = ( ( isset( $_POST['category'] ) ) ? ( htmlspecialchars_decode( $_POST['category'] ) ) : ( key( $events ) ) );
$event_selected = ( ( isset( $_POST['event'] ) ) ? ( htmlspecialchars_decode( $_POST['event'] ) ) : ( current( current( $events ) ) ) );

if ( $data_exists ) :
    // Create date range
    $date_range_day = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'] );
    $start_point = $date_range_day[0];

    $chart_data = array( __( 'Cancelled' ) => array_fill( 0, count( $date_range_day ) - 1, 0 ), __( 'Completed' ) => array_fill( 0, count( $date_range_day ) - 1, 0 ) );
    $pie_chart_data = array( __( 'Cancelled' ) => 0, __( 'Completed' ) => 0 );
    $average_time_chart_data = array( __( 'Cancelled' ) => array_fill( 0, count( $date_range_day ) - 1, 0 ), __( 'Completed' ) => array_fill( 0, count( $date_range_day ) - 1, 0 ) );

    for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
        $start = $date_range_day[$i];
        $end = $date_range_day[$i + 1];

        $version_query = ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
        
        $category_selected_escaped = $db->secure_data( $category_selected );
        $event_selected_escaped = $db->secure_data( $event_selected );

        $query = "SELECT e.EventName, e.EventCompleted, COUNT(*) AS total, ";
        $query .= "((SUM(e.EventDuration) / (";
        $query .= "SELECT COUNT(*) FROM `" . $db->prefix . "events_eventperiod` AS e2 ";
        $query .= "INNER JOIN `" . $db->prefix . "sessions` AS s2 ON s2.SessionId = e2.SessionId ";
        $query .= "WHERE s2.ApplicationId = '".$sanitized_input['id']."' ";
        $query .= ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s2.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
        $query .= "AND e2.UtcTimestamp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") AND e2.EventCategory = '". $category_selected_escaped."' AND e2.EventName = '".$event_selected_escaped."' AND e2.EventCompleted = e.EventCompleted))";
        $query .= ") AS 'average' ";
        $query .= "FROM `" . $db->prefix . "events_eventperiod` AS e ";
        $query .= "INNER JOIN `" . $db->prefix . "sessions` AS s ON s.SessionId = e.SessionId ";
        $query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' " . $version_query;
        $query .= "AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") ";
        $query .= "AND e.EventCategory = '".$category_selected_escaped."' AND e.EventName = '".$event_selected_escaped."' ";
        $query .= "GROUP BY e.EventCompleted";

        $db->execute_sql( $query );

        unset( $query, $start, $end );

        $rows = array();

        if ( $db->records > 0 ) {
            if ( $db->records == 1 )
                $rows[] = $db->array_result();
            else if ( $db->records > 1 )
                $rows = $db->array_results();

            foreach ( $rows as $row ) {
                if ( intval( $row['EventCompleted'] ) == 0 )
                    $array_key = __( 'Cancelled' );
                else
                    $array_key = __( 'Completed' );

                $total = intval( $row['total'] );
                $average_time = intval( $row['average'] );

                $chart_data[$array_key][$i] = $total;
                $pie_chart_data[$array_key] += $total;

                $average_time_chart_data[$array_key][$i] = round( $average_time );
            }

            unset( $rows, $row, $array_key, $total, $average_time );
        }

    }

    unset( $date_range_day );

    // Line chart
    $line_chart = new Highchart( );

    $line_chart->chart = array( 'renderTo' => 'chart_div', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart->yAxis->title = '';
    $line_chart->legend = array( 'layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => - 10, 'y' => 10, 'borderWidth' => 0 );
    $line_chart->series = convert_line_chart_data_to_array( $chart_data );

    unset( $chart_data );

    // Pie chart
    $pie_chart = new Highchart( );

    $pie_chart->chart = array( 'renderTo' => 'pie_div', 'plotShadow' => false );
    $pie_chart->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
    $pie_chart->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
    $pie_chart->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));
    $pie_chart->series = array( array( 'type' => 'pie', 'title' => __( 'Events Completed Percentage' ), 'data' => convert_pie_chart_data_to_array( $pie_chart_data ) ) );

    unset( $pie_chart_data );

    // Line chart (average time)
    $average_line_chart = new Highchart( );

    $average_line_chart->chart = array( 'renderTo' => 'average_div', 'defaultSeriesType' => 'line', 'height' => 200 );
    $average_line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $average_line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $average_line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $average_line_chart->yAxis->title = '';
    $average_line_chart->yAxis->labels->formatter = new HighchartJsExpr( "function() { return TimeSpan(this.value); }" );
    $average_line_chart->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.series.name +'</b><br/>'+new Date(this.x).toDateString() +': '+ TimeSpan(this.y); }" );
    $average_line_chart->legend = array( 'layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => - 10, 'y' => 10, 'borderWidth' => 0 );
    $average_line_chart->series = convert_line_chart_data_to_array( $average_time_chart_data );

    unset( $average_time_chart_data, $start_point );

?>
<script type="text/javascript">
    $(document).ready(function () {
        $("select#categories").change(function() {
           category = $("#categories option:selected").text();

           // Hide all event lists
           $("select#event").each(function () {
               $(this).attr("disabled", "disabled");
               $(this).hide();
           });

           // Only show events for category
           $('select[category="'+category+'"]').removeAttr('disabled');
           $('select[category="'+category+'"]').show();
        }); 

<?php
        $line_chart->render('events_line');
        unset( $line_chart );

        $pie_chart->render('events_pie');
        unset( $pie_chart );
?>
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
        $average_line_chart->render('average_line');
        unset( $average_line_chart );
?>
    } );
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Events Timing' ); ?></h2>
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
                <strong><?php _e( 'Category: ' ); ?></strong>
                <select name="category" id="categories">
                <?php foreach ( array_keys( $events ) as $category ) : ?>
                    <option<?php echo ( ( $category_selected == $category ) ? ( ' selected' ) : ( '' ) ) ?>><?php echo htmlspecialchars( $category ); ?></option>
                <?php endforeach; ?>
                </select>
                <strong>&nbsp;<?php _e( 'Events: ' ); ?></strong>

                <?php foreach ( array_keys($events) as $category ) : ?>
                <select name="event" id="event" category="<?php echo $category; ?>" <?php echo ( ( $category_selected != $category ) ? ( 'style="display:none" disabled' ) : ( '' ) ); ?>>
                    <?php foreach ( $events[$category] as $event ) : ?>
                    <option<?php echo ( ( $event_selected == $event && $category_selected == $category ) ? ( ' selected' ) : ( '' ) ) ?>><?php echo htmlspecialchars( $event ); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endforeach; ?>
                <?php unset( $event, $events, $category, $category_selected, $event_selected ); ?>

                <strong>&nbsp;<?php _e( 'Type: ' ); ?></strong>
                <select name="type">
                    <option value="total"<?php echo ( ( $type == 'total' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Total' ); ?></option>
                    <option value="unique"<?php echo ( ( $type == 'unique' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Unique' ); ?></option>
                </select>
                <?php unset( $type ); ?>
                &nbsp;&nbsp;
                <input name="apply" class="form-submit right" type="submit" value="<?php _e( 'Apply' ); ?>" />
            </form>
        </div>
    <!-- Graphs Box End -->
    </div>

    <div class="contentcontainer">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Average Time' ); ?></h2>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox">
            <div id="average_div"></div>
        </div>
        <!-- Graphs Box End -->
    </div>
</div>
<!-- end stats graph -->
<?php else : ?>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Events Timing' ); ?></h2>
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

    <div class="contentcontainer">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Average Time' ); ?></h2>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
        <!-- Graphs Box End -->
    </div>
</div>
<!-- end stats graph -->
<?php endif; ?>