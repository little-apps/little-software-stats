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

$type = ( ( isset ( $_POST['type'] )) ? ( strtolower( $_POST['type'] ) ) : ( 'total' ));

$data_exists = false;

// Array containing custom data info
$custom_data = array( );

$grouped_events = MySQL::getInstance()->select_events( 'customdata', $sanitized_input['id'], $sanitized_input['ver'], $sanitized_input['start'], $sanitized_input['end'], false, '', 'EventCustomName, EventCustomValue' );
if ( count( $grouped_events ) > 0 ) {
    foreach ( $grouped_events as $event ) {
        $event_name = $event['EventCustomName'];
        $event_value = $event['EventCustomValue'];

        // Ignore 'License'
        if ( $event_name == 'License' )
            continue;

        if ( !array_key_exists( $event_name, $custom_data ))
            $custom_data[$event_name] = array( );

        if ( !in_array( $event_value, $custom_data[$event_name] ))
            $custom_data[$event_name][] = $event_value;
    }

    $data_exists = true;
}
else {
    // Just in case..
    $custom_data[__( '(None)' )] = array( __( '(None)' ));
}

// If name isnt set -> set to first array key
$name_selected = ( ( isset ( $_POST['name'] )) ? ( $_POST['name'] ) : ( key( $custom_data )));

if ( $data_exists ) {
    $chart_data = array( );
    $pie_chart_data = array( );
    $total = 0;

    // Create date range
    $date_range_day = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'] );
    $start_point = $date_range_day[0];

    // Line chart
    $line_chart = new Highchart( );

    $line_chart->chart = array( 'renderTo' => 'chart_div', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart->yAxis->title = '';
    $line_chart->legend = array( 'layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => - 10, 'y' => 10, 'borderWidth' => 0 );

    // Pie chart
    $pie_chart = new Highchart( );

    $pie_chart->chart = array( 'renderTo' => 'pie_div', 'plotShadow' => false );
    $pie_chart->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
    $pie_chart->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
    $pie_chart->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));

    for ( $i = 0; $i < count( $date_range_day ) - 1; $i++ ) {
        $start = $date_range_day[$i];
        $end = $date_range_day[$i + 1];

        foreach ( array_keys( $custom_data ) as $name ) {
            if ( $i == 0 ) {
                $chart_data[$name] = array( );
                $pie_chart_data[$name] = 0;
            }

            $query = "SELECT COUNT(*) AS 'count' FROM `" . MySQL::getInstance()->prefix . "events_customdata` AS e ";
            $query .= "INNER JOIN `" . MySQL::getInstance()->prefix . "sessions` AS s ON e.SessionId = s.SessionId ";
            $query .= "WHERE e.ApplicationId = '" . $sanitized_input['id'] . "' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND e.ApplicationVersion = '" . $sanitized_input['ver'] . "' " ) : ( "" ));
            $query .= "AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(" . $start . ") AND FROM_UNIXTIME(" . $end . ") ";
            $query .= "AND e.EventCustomName = '" . $name . "'";
            if ( $type == 'unique' )
                $query .= " GROUP BY s.UniqueUserId";

            if ( MySQL::getInstance()->execute_sql( $query )) {
                MySQL::getInstance()->array_result( );
                $events = intval( MySQL::getInstance()->arrayed_result['count'] );
            } else {
                $events = 0;
            }

            $chart_data[$name][] = $events;
            $pie_chart_data[$name] += $events;
            $total += $events;
        }

        unset( $query, $start, $end, $events, $name );
    }

    unset( $date_range_day );

    $line_chart->series = convert_line_chart_data_to_array( $chart_data );
    unset( $chart_data );

    $pie_chart->series[] = array( 'type' => 'pie', 'name' => __( 'Custom Data Percentage' ), 'data' => convert_pie_chart_data_to_array( $pie_chart_data ));
    unset( $pie_chart_data );

    // Data Table array
    $data_table = array( );

    $query = "SELECT e.EventCustomValue, COUNT(" . ( ( $type == 'unique' ) ? ( 'DISTINCT s.UniqueUserId' ) : ( '*' )) . ") AS 'total' ";
    $query .= "FROM `" . MySQL::getInstance()->prefix . "events_customdata` AS e ";
    $query .= "INNER JOIN `" . MySQL::getInstance()->prefix . "sessions` AS s ON e.SessionId = s.SessionId ";
    $query .= "WHERE e.ApplicationId = '" . $sanitized_input['id'] . "' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND e.ApplicationVersion = '" . $sanitized_input['ver'] . "' " ) : ( "" ));
    $query .= "AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(" . $sanitized_input['start'] . ") AND FROM_UNIXTIME(" . $sanitized_input['end'] . ") ";
    $query .= "AND e.EventCustomName = '" . MySQL::getInstance()->secure_data( $name_selected ) . "' ";
    $query .= "GROUP BY e.EventCustomValue ";

    MySQL::getInstance()->execute_sql( $query );

    unset( $query );

    $custom_data_chart_data = array( );

    if ( MySQL::getInstance()->records == 1 )
        $custom_data_chart_data[] = MySQL::getInstance()->array_result( );
    else if ( MySQL::getInstance()->records > 1 )
        $custom_data_chart_data = MySQL::getInstance()->array_results( );
}

?>
<script type="text/javascript">
    $(document).ready(function () {
<?php
            if ( $data_exists ) {
                $line_chart->render( 'line_chart' );
                $pie_chart->render( 'pie_chart' );
            }

            unset( $line_chart, $pie_chart );
?>
    });
</script>
<div id="contentcontainers">
    <?php  if ( $data_exists ) : ?>
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Custom Data' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox nobottom" id="graphs-1" style="overflow: hidden !important">
            <div id="chart_div"></div>
        </div>

        <div class="contentbox nobottom" id="graphs-2">
            <div id="pie_div"></div>
        </div>

        <div class="contentbox bottom">
            <form class="right" action="#" method="post">
                <strong><?php _e( 'Name: ' ); ?></strong>
                <select name="name">
                    <?php foreach ( array_keys( $custom_data ) as $event_name ) : ?>
                    <option<?php echo ( ( $name_selected == $event_name ) ? ( ' selected' ) : ( '' )) ?>><?php echo $event_name ?></option>
                    <?php endforeach; ?>
                </select>
                <strong>&nbsp; <?php _e( 'Type: ' ); ?></strong>
                <select name="type">
                    <option value="total"<?php echo ( ( $type == 'total' ) ? ( ' selected' ) : ( '' )) ?>><?php _e( 'Total' ); ?></option>
                    <option value="unique"<?php echo ( ( $type == 'unique' ) ? ( ' selected' ) : ( '' )) ?>><?php _e( 'Unique' ); ?></option>
                </select>
                <input name="apply" class="form-submit" style="float: none; display: inline;" type="submit" value="<?php _e( 'Apply' ); ?>" />
            </form>
        </div>
        <!-- Graphs Box End -->
    </div>

    <!-- Overview Start -->
    <div class="contentcontainer">
        <div class="headings altheading">
            <h2><?php echo __( 'Total of data available: ' ) . $total ?></h2>
        </div>
        <div class="contentbox" style="padding-top: 0;">
            <table style="width: 100%" class="datatable">
                <thead>
                    <tr>
                        <th><?php _e( 'Name' ); ?></th>
                        <th><?php _e( 'Value' ); ?></th>
                        <th><?php _e( 'Count' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $custom_data_chart_data as $row ) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars( $name_selected ); ?></td>
                        <td><?php echo htmlspecialchars( $row['EventCustomValue'] ); ?></td>
                        <td><?php echo $row['total'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php unset( $custom_data_chart_data, $row, $name_selected ); ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Overview End -->
    <?php else : ?>
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Custom Data' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <div class="contentbox" id="graphs-1" style="overflow: hidden !important">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
        <!-- Graphs Box End -->
    </div>

    <!-- Overview Start -->
    <div class="contentcontainer">
        <div class="headings altheading">
            <h2><?php echo __( 'Total of data available: ' ) . __( 'None' ); ?></h2>
        </div>
        <div class="contentbox" style="padding-top: 0;">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
    </div>
    <!-- Overview End -->
    <?php endif; ?>
</div>