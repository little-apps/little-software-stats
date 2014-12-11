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

$type = ( ( isset ( $_POST['type'] )) ? ( strtolower ( $_POST['type'] ) ) : ( 'total' ));

// Create date range
$date_range_day = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'] );
$start_point = $date_range_day[0];

$brands_chart_data = array( );
$brands_pie_chart_data = array( );

$arch_chart_data = array( '32' => array_fill( 0, count( $date_range_day ) - 1, 0 ), '64' => array_fill( 0, count( $date_range_day ) - 1, 0 ));
$arch_pie_chart_data = array( '32' => 0, '64' => 0 );

$cores_chart_data = array( );
$cores_pie_chart_data = array( );

$brands_data_exists = false;
$arch_data_exists = false;
$cores_data_exists = false;

for ( $i = 0; $i < count( $date_range_day ) - 1; $i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];

    $query = "SELECT u.CPUBrand, COUNT(" . ( ( $type == 'unique' ) ? ( 'DISTINCT s.UniqueUserId' ) : ( '*' )) . ") AS count FROM `" . $db->prefix . "sessions` AS s ";
    $query .= "INNER JOIN `" . $db->prefix . "uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
    $query .= "WHERE s.ApplicationId = '" . $sanitized_input['id'] . "' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '" . $sanitized_input['ver'] . "' " ) : ( "" ));
    $query .= "AND s.StartApp BETWEEN FROM_UNIXTIME(" . $start . ") AND FROM_UNIXTIME(" . $end . ") ";
    $query .= "GROUP BY u.CPUBrand";

    $db->execute_sql( $query );

    unset( $query );

    if ( $db->records > 0 ) {
        $rows = array( );

        if ( $db->records == 1 )
            $rows[] = $db->array_result( );
        elseif ( $db->records > 1 )
            $rows = $db->array_results( );

        foreach ( $rows as $row ) {
            $brand = $row['CPUBrand'];
            $count = intval( $row['count'] );

            if ( !array_key_exists( $brand, $brands_chart_data )) {
                $brands_chart_data[$brand] = array_fill( 0, count( $date_range_day ) - 1, 0 );
                $brands_pie_chart_data[$brand] = 0;
            }

            $brands_chart_data[$brand][$i] = $count;
            $brands_pie_chart_data[$brand] += $count;

            unset( $brand, $count );
        }

        unset( $rows, $row );

        if ( !$brands_data_exists )
            $brands_data_exists = true;
    }


    $query = "SELECT u.CPUArch, COUNT(" . ( ( $type == 'unique' ) ? ( 'DISTINCT s.UniqueUserId' ) : ( '*' )) . ") AS count FROM `" . $db->prefix . "sessions` AS s ";
    $query .= "INNER JOIN `" . $db->prefix . "uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
    $query .= "WHERE s.ApplicationId = '" . $sanitized_input['id'] . "' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '" . $sanitized_input['ver'] . "' " ) : ( "" ));
    $query .= "AND s.StartApp BETWEEN FROM_UNIXTIME(" . $start . ") AND FROM_UNIXTIME(" . $end . ") ";
    $query .= "GROUP BY u.CPUArch";

    $db->execute_sql( $query );

    unset( $query );

    if ( $db->records > 0 ) {
        $rows = array( );

        if ( $db->records == 1 )
            $rows[] = $db->array_result( );
        elseif ( $db->records > 1 )
            $rows = $db->array_results( );

        foreach ( $rows as $row ) {
            $arch = $row['CPUArch'];
            $count = intval( $row['count'] );

            $arch_chart_data[$arch][$i] = $count;
            $arch_pie_chart_data[$arch] += $count;

            unset( $arch, $count );
        }

        unset( $rows, $row );

        if ( !$arch_data_exists )
            $arch_data_exists = true;
    }

    $query = "SELECT u.CPUCores, COUNT(" . ( ( $type == 'unique' ) ? ( 'DISTINCT s.UniqueUserId' ) : ( '*' )) . ") AS count FROM `" . $db->prefix . "sessions` AS s ";
    $query .= "INNER JOIN `" . $db->prefix . "uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId  ";
    $query .= "WHERE s.ApplicationId = '" . $sanitized_input['id'] . "' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '" . $sanitized_input['ver'] . "' " ) : ( "" ));
    $query .= "AND s.StartApp BETWEEN FROM_UNIXTIME(" . $start . ") AND FROM_UNIXTIME(" . $end . ") ";
    $query .= "GROUP BY u.CPUCores";

    $db->execute_sql( $query );

    unset( $query, $start, $end );

    if ( $db->records > 0 ) {
        $rows = array( );

        if ( $db->records == 1 )
            $rows[] = $db->array_result( );
        elseif ( $db->records > 1 )
            $rows = $db->array_results( );

        foreach ( $rows as $row ) {
            $cores = $row['CPUCores'];
            $count = intval( $row['count'] );

            if ( !array_key_exists( $cores, $cores_chart_data )) {
                $cores_chart_data[$cores] = array_fill( 0, count( $date_range_day ) - 1, 0 );
                $cores_pie_chart_data[$cores] = 0;
            }

            $cores_chart_data[$cores][$i] = $count;
            $cores_pie_chart_data[$cores] += $count;

            unset( $cores, $count );
        }

        unset( $rows, $row );

        if ( !$cores_data_exists )
            $cores_data_exists = true;
    }
}

unset( $date_range_day );

if ( $brands_data_exists ) {
    // Line chart (brand)
    $brand_line_chart = new Highchart( );

    $brand_line_chart->chart = array( 'renderTo' => 'chart_brands', 'defaultSeriesType' => 'line', 'height' => 200 );
    $brand_line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $brand_line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $brand_line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $brand_line_chart->yAxis->title = '';
    $brand_line_chart->legend = array( 'layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => - 10, 'y' => 10, 'borderWidth' => 0 );
    $brand_line_chart->series = convert_line_chart_data_to_array( $brands_chart_data );

    // Pie chart (brand)
    $brand_pie_chart = new Highchart( );

    $brand_pie_chart->chart = array( 'renderTo' => 'pie_brands', 'plotShadow' => false );
    $brand_pie_chart->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
    $brand_pie_chart->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
    $brand_pie_chart->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));
    $brand_pie_chart->series[] = array( 'type' => 'pie', 'name' => __( 'CPU Brands Percentage' ), 'data' => convert_pie_chart_data_to_array( $brands_pie_chart_data ));
}

unset( $brands_chart_data, $brands_pie_chart_data );

if ( $arch_data_exists ) {
    // Line chart (arch)
    $arch_line_chart = new Highchart( );

    $arch_line_chart->chart = array( 'renderTo' => 'chart_arch', 'defaultSeriesType' => 'line', 'height' => 200 );
    $arch_line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $arch_line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $arch_line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $arch_line_chart->yAxis->title = '';
    $arch_line_chart->legend = array( 'layout' => 'horizontal', 'align' => 'right', 'verticalAlign' => 'top', 'floating' => true, 'x' => - 10, 'y' => - 10, 'borderWidth' => 0 );
    $arch_line_chart->series = convert_line_chart_data_to_array( $arch_chart_data );

    // Pie chart (arch)
    $arch_pie_chart = new Highchart( );

    $arch_pie_chart->chart = array( 'renderTo' => 'pie_arch', 'plotShadow' => false );
    $arch_pie_chart->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
    $arch_pie_chart->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
    $arch_pie_chart->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));
    $arch_pie_chart->series[] = array( 'type' => 'pie', 'name' => __( 'CPU Architecture Percentage' ), 'data' => convert_pie_chart_data_to_array( $arch_pie_chart_data ));
}

unset( $arch_chart_data, $arch_pie_chart_data );

if ( $cores_data_exists ) {
    // Line chart (cores)
    $cores_line_chart = new Highchart( );

    $cores_line_chart->chart = array( 'renderTo' => 'chart_cores', 'defaultSeriesType' => 'line', 'height' => 200 );
    $cores_line_chart->title = array( 'text' => __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] ), 'x' => - 20 );
    $cores_line_chart->plotOptions->series = array( 'pointStart' => ( float ) sprintf( '%d000', $start_point ), 'pointInterval' => $tick_interval * 1000 );
    $cores_line_chart->xAxis = array( 'type' => 'datetime', 'allowDecimals' => false );
    $cores_line_chart->yAxis->title = '';
    $cores_line_chart->legend = array( 'layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => - 10, 'y' => 10, 'borderWidth' => 0 );
    $cores_line_chart->series = convert_line_chart_data_to_array( $cores_chart_data );

    // Pie chart (cores)
    $cores_pie_chart = new Highchart( );

    $cores_pie_chart->chart = array( 'renderTo' => 'pie_cores', 'plotShadow' => false );
    $cores_pie_chart->title->text = __( 'Statistics for ' ) . date( "F j, Y", $sanitized_input['start'] ) . ' to ' . date( "F j, Y", $sanitized_input['end'] );
    $cores_pie_chart->tooltip->formatter = new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" );
    $cores_pie_chart->plotOptions->pie = array( 'allowPointSelect' => true, 'cursor' => 'pointer', 'dataLabels' => array( 'enabled' => true, 'formatter' => new HighchartJsExpr( "function() { return '<b>'+ this.point.name +'</b>: '+ Math.round(this.percentage) +' %'; }" )));

    $cores_pie_chart->series[] = array( 'type' => 'pie', 'name' => __( 'CPU Cores Percentage' ), 'data' => convert_pie_chart_data_to_array( $cores_pie_chart_data ));
}

unset( $cores_chart_data, $cores_pie_chart_data, $start_point );

?>
<script type="text/javascript">
    var chart_brands, pie_brands, chart_arch, pie_arch, chart_cores, pie_cores;
    $(document).ready(function() {
<?php
        if ( $brands_data_exists ) {
            $brand_line_chart->render( 'chart_brands' );
            $brand_pie_chart->render( 'pie_brands' );

            unset( $brand_line_chart, $brand_pie_chart );
        }

        if ( $arch_data_exists ) {
            $arch_line_chart->render( 'chart_arch' );
            $arch_pie_chart->render( 'pie_arch' );

            unset( $arch_line_chart, $arch_pie_chart );
        }

        if ( $cores_data_exists ) {
            $cores_line_chart->render( 'chart_cores' );
            $cores_pie_chart->render( 'pie_cores' );

            unset( $cores_line_chart, $cores_pie_chart );
        }
?>
    });
</script>
<div class="contentcontainers">
    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Brand' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <?php if ( $brands_data_exists ) : ?>
        <div class="contentbox" id="graphs-1">
            <div id="chart_brands"></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="pie_brands"></div>
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

    <div class="contentcontainer" id="graphs">
        <div class="headings alt">
            <h2 class="left"><?php _e( 'Cores' ); ?></h2>
            <ul class="smltabs">
                <li><a href="#graphs-1"><?php _e( 'Timeline' ); ?></a></li>
                <li><a href="#graphs-2"><?php _e( 'Pie chart' ); ?></a></li>
            </ul>
        </div>

        <!-- Graphs Box Start -->
        <?php if ( $cores_data_exists ) : ?>
        <div class="contentbox" id="graphs-1">
            <div id="chart_cores"></div>
        </div>

        <div class="contentbox" id="graphs-2">
            <div id="pie_cores"></div>
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

    <?php if ( $brands_data_exists || $arch_data_exists || $cores_data_exists ) : ?>
    <form action="#" class="right" style="padding-top: 15px">
        <strong><?php _e( 'Type: ' ); ?></strong>
        <select name="type">
            <option value="total"<?php echo ( ( $type == 'total' ) ? ( ' selected' ) : ( '' )) ?>><?php _e( 'Total' ); ?></option>
            <option value="unique"<?php echo ( ( $type == 'unique' ) ? ( ' selected' ) : ( '' )) ?>><?php _e( 'Unique' ); ?></option>
            <?php unset( $type ); ?>
        </select>
        &nbsp;&nbsp;
        <input name="apply" class="form-submit right" type="submit" value="<?php _e( 'Apply' ); ?>" />
    </form>
    <?php endif; ?>
</div>