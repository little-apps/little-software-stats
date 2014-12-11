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

// Create date range
$date_range_day = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'] );
$start_point = $date_range_day[0];

$versions_chart_data = array();
$versions_pie_data = array();

$arch_chart_data = array( '32' => array_fill( 0, count( $date_range_day ) - 1, 0 ), '64' => array_fill( 0, count( $date_range_day ) - 1, 0 ) );
$arch_pie_data = array( '32' => 0, '64' => 0 );

$versions_data_exists = false;
$arch_data_exists = false;

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];
    
    $query = "SELECT u.OSVersion, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS 'total'";
    $query .= "FROM `".$db->prefix."sessions` AS s ";
    $query .= "INNER JOIN `".$db->prefix."uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
    $query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
    $query .= "AND s.StartApp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") ";
    $query .= "GROUP BY u.OSVersion";

    $db->execute_sql( $query );

    unset( $query );
    
    $rows = array();

    if ( $db->records > 0 ) {
        if ( $db->records == 1 )
            $rows[] = $db->array_result();
        else if ( $db->records > 1 )
            $rows = $db->array_results();

        foreach ( $rows as $row ) {
            $os_version = trim( $row['OSVersion'] );
            $count = intval( $row['total'] );
            
            if ($os_version == '')
            	$os_version = 'Unknown';

            if ( !array_key_exists( $os_version , $versions_chart_data ) ) {
                $versions_chart_data[$os_version] = array_fill( 0, count( $date_range_day ) - 1, 0 );
                $versions_pie_data[$os_version] = 0;
            }

            $versions_chart_data[$os_version][$i] = $count;
            $versions_pie_data[$os_version] += $count;

            unset( $os_version, $count );
        }

        if ( !$versions_data_exists )
            $versions_data_exists = true;

        unset( $rows, $row );
    }
    
    $query = "SELECT u.OSArchitecture, COUNT(" . ( ( $type == 'unique' ) ? ('DISTINCT s.UniqueUserId') : ( '*' ) ) . ") AS 'total'";
    $query .= "FROM `".$db->prefix."sessions` AS s ";
    $query .= "INNER JOIN `".$db->prefix."uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
    $query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
    $query .= "AND s.StartApp BETWEEN FROM_UNIXTIME(".$start.") AND FROM_UNIXTIME(".$end.") ";
    $query .= "GROUP BY u.OSArchitecture";

    unset( $start, $end );

    $db->execute_sql( $query );

    unset( $query );

    $rows = array();

    if ( $db->records > 0 ) {
        if ( $db->records == 1 )
            $rows[] = $db->array_result();
        else if ( $db->records > 1 )
            $rows = $db->array_results();

        foreach ( $rows as $row ) {
            $os_arch = $row['OSArchitecture'];
            $count = intval( $row['total'] );

            $arch_chart_data[$os_arch][$i] = $count;
            $arch_pie_data[$os_arch] += $count;

            unset( $os_arch, $count );
        }

        if ( !$arch_data_exists )
            $arch_data_exists = true;

        unset( $rows, $row );
    }
}

unset( $date_range_day );

if ( $versions_data_exists ) {
    // Line chart (versions)
    $line_chart_versions = new Highchart( );

    $line_chart_versions->chart = array( 'renderTo' => 'chart_versions', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart_versions->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart_versions->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart_versions->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart_versions->yAxis->title = '';
    $line_chart_versions->legend = array( 'layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => - 10, 'y' => 10, 'borderWidth' => 0 );
    $line_chart_versions->series = convert_line_chart_data_to_array( $versions_chart_data );

    // Pie chart (versions)
    $pie_chart_versions = new Highchart( );

    $pie_chart_versions->chart = array( 'renderTo' => 'pie_versions', 'plotShadow' => false );
    $pie_chart_versions->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
    $pie_chart_versions->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
    $pie_chart_versions->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));
    $pie_chart_versions->series[] = array( 'type' => 'pie', 'name' => __( 'Operating Systems Version Percentage' ), 'data' => convert_pie_chart_data_to_array( $versions_pie_data ) );
}

unset( $versions_chart_data, $versions_pie_data );

if ( $arch_data_exists ) {
    // Line chart (archs)
    $line_chart_arch = new Highchart( );

    $line_chart_arch->chart = array( 'renderTo' => 'chart_arch', 'defaultSeriesType' => 'line', 'height' => 200 );
    $line_chart_arch->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $line_chart_arch->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $line_chart_arch->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $line_chart_arch->yAxis->title = '';
    $line_chart_arch->legend = array( 'layout' => 'horizontal', 'align' => 'right', 'verticalAlign' => 'top', 'floating' => true, 'x' => - 10, 'y' => - 10, 'borderWidth' => 0 );
    $line_chart_arch->series = convert_line_chart_data_to_array( $arch_chart_data );

    // Pie chart (archs)
    $pie_chart_arch = new Highchart( );

    $pie_chart_arch->chart = array( 'renderTo' => 'pie_arch', 'plotShadow' => false );
    $pie_chart_arch->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
    $pie_chart_arch->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
    $pie_chart_arch->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));
    $pie_chart_arch->series[] = array( 'type' => 'pie', 'name' => __( 'Operating System Architecture Percentage' ), 'data' => convert_pie_chart_data_to_array( $arch_pie_data ) );
}

unset( $arch_chart_data, $arch_pie_data, $start_point );
?>
<script type="text/javascript">
    var chart_versions, pie_versions, chart_arch, pie_arch;
    $(document).ready(function() {
<?php
        if ( $versions_data_exists ) {
            $line_chart_versions->render( 'chart_versions' );
            $pie_chart_versions->render( 'pie_versions' );

            unset( $line_chart_versions, $pie_chart_versions );
        }

        if ( $arch_data_exists ) {
            $line_chart_arch->render( 'chart_arch' );
            $pie_chart_arch->render( 'pie_arch' );

            unset( $line_chart_arch, $pie_chart_arch );
        }
?>
    });
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Version' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <?php if ( $versions_data_exists ) : ?>
        <div class="contentbox" id="graphs-1">
            <div id="chart_versions"></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="pie_versions"></div>
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
            <h2 class="left"><?php _e( 'Architecture' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <?php if ( $arch_data_exists ) : ?>
        <div class="contentbox" id="graphs-1">
            <div id="chart_arch"></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="pie_arch"></div>
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

    <?php if ( $arch_data_exists || $versions_data_exists ) : ?>
    <form action="#" class="right" style="padding-top: 15px">
        <strong><?php _e( 'Type:' ); ?> </strong>
        <select name="type">
            <option value="total"<?php echo ( ( $type == 'total' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Total' ); ?></option>
            <option value="unique"<?php echo ( ( $type == 'unique' ) ? ( ' selected' ) : ( '' ) ) ?>><?php _e( 'Unique' ); ?></option>
        </select>
        &nbsp;&nbsp;
        <input name="apply" class="form-submit right" type="submit" value="<?php _e( 'Apply' ); ?>" />
    </form>
    <?php endif; ?>
</div>