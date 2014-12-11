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

$version_query = ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );

$query = "SELECT u.Country, u.CountryCode, COUNT(*) AS 'total', COUNT(DISTINCT u.UniqueUserId) AS 'unique', ";
$query .= "((COUNT(*) / (SELECT COUNT(*) FROM `".$db->prefix."sessions` AS s WHERE s.ApplicationId = '".$sanitized_input['id']."' " . $version_query . " AND s.StartApp BETWEEN FROM_UNIXTIME(".$sanitized_input['start'].") AND FROM_UNIXTIME(".$sanitized_input['end']."))) * 100) AS 'percent' ";
$query .= "FROM `".$db->prefix."sessions` AS s ";
$query .= "INNER JOIN `".$db->prefix."uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
$query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' " . $version_query;
$query .= "AND s.StartApp BETWEEN FROM_UNIXTIME(".$sanitized_input['start'].") AND FROM_UNIXTIME(".$sanitized_input['end'].") ";
$query .= "GROUP BY u.Country";

$db->execute_sql( $query );

unset( $query, $version_query );

if ( $db->records > 0 ) :
    $map_chart_data = array();

    if ( $db->records == 1 )
        $map_chart_data[] = $db->array_result();
    else if ( $db->records > 1 )
        $map_chart_data = $db->array_results();

    $map_chart_json = array();

    foreach ( $map_chart_data as $row ) {
        $country_code = $row['CountryCode'];
        $count = intval( $row['unique'] );

        if ( $country_code == '' ) {
            unset( $country_code, $count );
            continue;
        }

        $map_chart_json[] = array( $country_code, $count );

        unset( $country_code, $count );
    }
?>
<script type="text/javascript">
 google.load('visualization', '1', {'packages':['geochart']});
$(document).ready(function() {
    // when document loads, grab the json
    google.setOnLoadCallback(function() {
        // setup the new map and its variables
        var map = new google.visualization.DataTable()
        map.addColumn('string', 'Country');
        map.addColumn('number', 'Users');
        map.addRows(<?php echo json_encode( $map_chart_json ); ?>);

        var options = {};

        // finally, create the map!
        var geomap = new google.visualization.GeoChart(document.getElementById('map'));
        geomap.draw(map, options);
    });
});
</script>

<!--  start page-heading -->
<div id="page-heading">
        <h1><?php _e ( 'Map Overlay' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url( '/images/shared/side_shadowleft.jpg'); ?>"></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url( '/images/shared/side_shadowright.jpg'); ?>"></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner">
                    <div id="map" style="width: 100%; height: 500px;"></div>
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
    <!-- Logs Start -->
    <div class="contentcontainer">
        <div class="headings alt">
            <h2><?php _e ( 'Statistics' ); ?></h2>
        </div>
        <div class="contentbox">
            <table style="width: 100%" class="datatable">
                <thead>
                    <tr>
                        <th><?php _e ( 'Country' ); ?></th>
                        <th><?php _e ( 'Executions' ); ?></th>
                        <th><?php _e ( 'Unique' ); ?></th>
                        <th><?php _e ( 'Percentage of Executions' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $map_chart_data as $row ) : $percent = round( $row['percent'] , 2 ) . '%'; ?>
                    <tr>
                        <td><?php echo $row['Country']; ?></td>
                        <td><?php echo $row['total']; ?></td>
                        <td><?php echo $row['unique']; ?></td>
                        <td>
                            <div class="usagebox left" style="width: 85%">
                                <div class="lowbar" style="width: <?php echo $percent; ?>"></div>
                            </div>
                            <span class="right" style="padding: 8px"><?php echo $percent; ?></span>
                        </td>
                        <?php unset( $percent ); ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php unset( $map_chart_data, $row ); ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Logs End -->
</div>
<?php else : ?>
<!--  start page-heading -->
<div id="page-heading">
        <h1><?php _e ( 'Map Overlay' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url( '/images/shared/side_shadowleft.jpg'); ?>"></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url( '/images/shared/side_shadowright.jpg'); ?>"></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner">
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
    <!-- Logs Start -->
    <div class="contentcontainer">
        <div class="headings alt">
            <h2><?php _e ( 'Statistics' ); ?></h2>
        </div>
        <div class="contentbox">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
    </div>
    <!-- Logs End -->
</div>
<?php endif; ?>