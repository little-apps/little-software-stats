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

$java_chart_data = array();
$java_pie_data = array();

$net_chart_data = array();
$net_pie_data = array();

// Create date range
$date_range_day = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'] );
$start_point = $date_range_day[0];

$java_data_exists = false;
$net_data_exists = false;

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $query = "SELECT u.JavaVer, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS 'total'";
    $query .= "FROM `".$db->prefix."sessions` AS s ";
    $query .= "INNER JOIN `".$db->prefix."uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
    $query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
    $query .= "AND s.StartApp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") ";
    $query .= "GROUP BY u.JavaVer";
    
    $db->execute_sql( $query );
    
    $rows = array();

    if ( $db->records > 0 ) {
        if ( $db->records == 1 )
            $rows[] = $db->array_result();
        else if ( $db->records > 1 )
            $rows = $db->array_results();

        foreach ( $rows as $row ) {
            $java_ver = 'v' . rtrim( (string)$row['JavaVer'], ".0" );
            $count = intval( $row['total'] );

            if ( $java_ver == "v" )
                $java_ver = __( "Unknown" );

            if ( !array_key_exists( $java_ver, $java_chart_data ) ) {
                $java_chart_data[$java_ver] = array_fill( 0, count( $date_range_day ) - 1, 0 );
                $java_pie_data[$java_ver] = 0;
            }

            if ( $count > 0 ) {
                $java_chart_data[$java_ver][$i] = $count;
                $java_pie_data[$java_ver] += $count;
            }
        }

        ksort( $java_chart_data );
        ksort( $java_pie_data );

        if ( !$java_data_exists )
            $java_data_exists = true;
    }
    
    $query = "SELECT u.NetVer, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS 'total'";
    $query .= "FROM `".$db->prefix."sessions` AS s ";
    $query .= "INNER JOIN `".$db->prefix."uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
    $query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
    $query .= "AND s.StartApp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") ";
    $query .= "GROUP BY u.NetVer";
    
    $db->execute_sql( $query );

    unset( $query, $start, $end );
    
    $rows = array();

    if ( $db->records > 0 ) {
        if ( $db->records == 1 )
            $rows[] = $db->array_result();
        else if ( $db->records > 1 )
            $rows = $db->array_results();

        foreach ( $rows as $row ) {
            $net_ver = 'v' . rtrim( (string)$row['NetVer'], ".0" );
            $count = intval( $row['total'] );

            if ( $net_ver == "v" )
                $net_ver = __( "Unknown" );

            if ( !array_key_exists( $net_ver, $net_chart_data ) ) {
                $net_chart_data[$net_ver] = array_fill( 0, count( $date_range_day ) - 1, 0 );
                $net_pie_data[$net_ver] = 0;
            }

            $net_chart_data[$net_ver][$i] = $count;
            $net_pie_data[$net_ver] += $count;

            unset( $net_ver, $count );
        }

        unset( $rows, $row );

        ksort( $net_chart_data );
        ksort( $net_pie_data );

        if ( !$net_data_exists )
            $net_data_exists = true;
    }
}

unset( $date_range_day );


if ( $java_data_exists ) {
    // Line chart (java)
    $line_chart_java = new Highchart( );

    $line_chart_java->chart = array( 'renderTo' => 'chart_java', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart_java->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart_java->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart_java->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart_java->yAxis->title = '';
    $line_chart_java->legend = array( 'layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => - 10, 'y' => 10, 'borderWidth' => 0 );
    $line_chart_java->series = convert_line_chart_data_to_array( $java_chart_data );

    // Pie chart (java)
    $pie_chart_java = new Highchart( );

    $pie_chart_java->chart = array( 'renderTo' => 'pie_java', 'plotShadow' => false );
    $pie_chart_java->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
    $pie_chart_java->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
    $pie_chart_java->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));
    $pie_chart_java->series[] = array( 'type' => 'pie', 'name' => __( 'Java Version Percentage' ), 'data' => convert_pie_chart_data_to_array( $java_pie_data ) );
}

unset( $java_chart_data, $java_pie_data );

if ( $net_data_exists ) {
    // Line chart (.net)
    $line_chart_net = new Highchart( );

    $line_chart_net->chart = array( 'renderTo' => 'chart_net', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart_net->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart_net->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart_net->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart_net->yAxis->title = '';
    $line_chart_net->legend = array( 'layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => - 10, 'y' => 10, 'borderWidth' => 0 );
    $line_chart_net->series = convert_line_chart_data_to_array( $net_chart_data );

    unset( $start_point );

    // Pie chart (.net)
    $pie_chart_net = new Highchart( );

    $pie_chart_net->chart = array( 'renderTo' => 'pie_net', 'plotShadow' => false );
    $pie_chart_net->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
    $pie_chart_net->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
    $pie_chart_net->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));
    $pie_chart_net->series[] = array( 'type' => 'pie', 'name' => __( '.NET Version Percentage' ), 'data' => convert_pie_chart_data_to_array( $net_pie_data ) );
}

unset( $net_chart_data, $net_pie_data );
?>
<script type="text/javascript">
    var chart_java, pie_java, chart_net, pie_net;
    $(document).ready(function() {
<?php
        if ( $java_data_exists ) {
            $line_chart_java->render( 'chart_java' );
            $pie_chart_java->render( 'pie_java' );
        }

        unset( $line_chart_java, $pie_chart_java );

        if ( $net_data_exists ) {
            $line_chart_net->render( 'chart_net' );
            $pie_chart_net->render( 'pie_net' );
        }

        unset( $line_chart_net, $pie_chart_net );
?>
    });
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Java' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <?php if ( $java_data_exists ) : ?>
        <div class="contentbox" id="graphs-1">
            <div id="chart_java"></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="pie_java"></div>
        </div>
        <?php else : ?>
        <div class="contentbox" id="graphs-1">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
        <?php endif; ?>
        <!-- Graphs Box End -->
    </div>

    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( '.NET Framework' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <?php if ( $net_data_exists ) : ?>
        <div class="contentbox" id="graphs-1">
            <div id="chart_net"></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="pie_net"></div>
        </div>
        <?php else : ?>
        <div class="contentbox" id="graphs-1">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
        <?php endif; ?>
        <!-- Graphs Box End -->
    </div>

    <?php if ( $java_data_exists || $net_data_exists ) : ?>
    <form action="#" class="right" style="padding-top: 15px">
        <strong><?php _e( 'Type:' ); ?> </strong>
        <select name="type">
            <option value="total"<?php echo ( ( $type == 'total' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Total' ); ?></option>
            <option value="unique"<?php echo ( ( $type == 'unique' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Unique' ); ?></option>
            <?php unset( $type ); ?>
        </select>
        &nbsp;&nbsp;
        <input name="apply" class="form-submit right" type="submit" value="<?php _e( 'Apply' ); ?>" />
    </form>
    <?php endif; ?>
</div>