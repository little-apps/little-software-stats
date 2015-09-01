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

$grouped_events = MySQL::getInstance()->select_events( 'eventvalue', $sanitized_input['id'], $sanitized_input['ver'], $sanitized_input['start'], $sanitized_input['end'], false, '', 'EventCategory, EventName, EventValue' );
if ( count( $grouped_events ) > 0 ) {
    foreach ( $grouped_events as $event ) {
        $event_category = $event['EventCategory'];
        $event_name = $event['EventName'];

        if ( !array_key_exists( $event_category, $events ) )
            $events[$event_category] = array();
        
        if ( !in_array( $event_name, $events[$event_category] ) ) {
            $events[$event_category][] = $event_name;
        }
    }

    unset( $grouped_events, $event, $event_category, $event_name );
} else {
    // Just in case..
    $events[__( '(None)' )] = array( __( '(None)' ) );
}

// Get first array element
$first_category = key( $events );
$first_event = current( $events[$first_category] );

$category_selected = ( ( isset( $_POST['category'] ) ) ? ( htmlspecialchars_decode( $_POST['category'] ) ) : ( $first_category ) );
$event_selected = ( ( isset( $_POST['event'] ) ) ? ( htmlspecialchars_decode( $_POST['event'] ) ) : ( $first_event ) );

$chart_data = array();
$pie_chart_data = array();

// Create date range
$date_range_day = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'] );
$start_point = $date_range_day[0];

$data_exists = false;

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $query = "SELECT e.EventValue, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS 'total' ";
    $query .= "FROM `".MySQL::getInstance()->prefix."sessions` AS s ";
    $query .= "INNER JOIN `".MySQL::getInstance()->prefix."events_eventvalue` AS e ON s.SessionId = e.SessionId ";
    $query .= "WHERE e.EventCategory = '".$category_selected."' AND e.EventName = '".$event_selected."' ";
    $query .= "AND s.ApplicationId = '".$sanitized_input['id']."' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
    $query .= "AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") ";
    $query .= "GROUP BY e.EventValue";

    MySQL::getInstance()->execute_sql( $query );

    unset( $query, $start, $end );

    $rows = array();

    if ( MySQL::getInstance()->records > 0 ) {
        if ( MySQL::getInstance()->records == 1 )
            $rows[] = MySQL::getInstance()->array_result();
        elseif ( MySQL::getInstance()->records > 1 )
            $rows = MySQL::getInstance()->array_results();

        foreach ( $rows as $row ) {
            $event_value = $row['EventValue'];
            $total = intval( $row['total'] );

            if ( !array_key_exists( $event_value, $chart_data ) ) {
                $chart_data[$event_value] = array_fill( 0, count( $date_range_day ) - 1, 0 );
                $pie_chart_data[$event_value] = 0;
            }

            $chart_data[$event_value][$i] = $total;
            $pie_chart_data[$event_value] += $total;
        }

        unset( $rows, $row, $event_value, $total );

        if ( !$data_exists )
            $data_exists = true;
    }
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
    $line_chart->series = convert_line_chart_data_to_array( $chart_data );

    unset( $chart_data, $start_point );

    // Pie chart
    $pie_chart = new Highchart( );

    $pie_chart->chart = array( 'renderTo' => 'pie_div', 'plotShadow' => false );
    $pie_chart->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
    $pie_chart->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
    $pie_chart->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));
    $pie_chart->series[] = array( 'type' => 'pie', 'name' => __( 'Event Values Percentage' ), 'data' => convert_pie_chart_data_to_array( $pie_chart_data ) );

    unset( $pie_chart_data );
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
        $line_chart->render( 'events_line' );
        $pie_chart->render( 'events_pie' );

        unset( $line_chart, $pie_chart );
?>
    });
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Events Value' ); ?></h2>
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
            <form class="right" action="#" method="post">
                <strong><?php _e( 'Category: ' ); ?></strong>
                <select name="category" id="categories">
                <?php foreach ( array_keys( $events ) as $category ) : ?>
                    <option<?php echo ( ( $category_selected == $category ) ? ( ' selected' ) : ( '' ) ) ?>><?php echo htmlspecialchars( $category ); ?></option>
                <?php endforeach; ?>
                </select>
                <strong>&nbsp;<?php _e( 'Events: ' ); ?></strong>

                <?php foreach ( array_keys( $events ) as $category ) : ?>
                <select name="event" id="event" category="<?php echo $category; ?>" <?php echo ( ( $category_selected != $category ) ? ( 'style="display:none" disabled' ) : ( '' ) ); ?>>
                    <?php foreach ( $events[$category] as $event ) :  ?>
                    <option<?php echo ( ( $event_selected == $event && $category_selected == $category ) ? ( ' selected' ) : ( '' ) ) ?>><?php echo htmlspecialchars( $event ); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endforeach; ?>
                <?php unset( $events, $event, $event_selected, $category, $category_selected ); ?>

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
</div>
<?php else : unset( $chart_data, $pie_chart_data ); ?>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Events Value' ); ?></h2>
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