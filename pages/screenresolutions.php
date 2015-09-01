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

$query = "SELECT u.ScreenRes, COUNT(*) AS 'total', COUNT(DISTINCT u.UniqueUserId) AS 'unique', ";
$query .= "((COUNT(*) / (SELECT COUNT(*) FROM `".MySQL::getInstance()->prefix."sessions` AS s WHERE s.ApplicationId = '".$sanitized_input['id']."' " . $version_query . " AND s.StartApp BETWEEN FROM_UNIXTIME(".$sanitized_input['start'].") AND FROM_UNIXTIME(".$sanitized_input['end']."))) * 100) AS 'percent' ";
$query .= "FROM `".MySQL::getInstance()->prefix."sessions` AS s ";
$query .= "INNER JOIN `".MySQL::getInstance()->prefix."uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
$query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' " . $version_query;
$query .= "AND s.StartApp BETWEEN FROM_UNIXTIME(".$sanitized_input['start'].") AND FROM_UNIXTIME(".$sanitized_input['end'].") ";
$query .= "GROUP BY u.ScreenRes";

MySQL::getInstance()->execute_sql( $query );

unset( $query, $version_query );

if ( MySQL::getInstance()->records > 0 ) :
    $resolutions_chart_data = array();

    if ( MySQL::getInstance()->records == 1 )
        $resolutions_chart_data[] = MySQL::getInstance()->array_result();
    else if ( MySQL::getInstance()->records > 1 )
        $resolutions_chart_data = MySQL::getInstance()->array_results();
?>
<div id="contentcontainers">
    <!-- Overview Start -->
    <div class="contentcontainer">
        <div class="headings altheading">
            <h2><?php _e( 'Screen Resolutions' ); ?></h2>
        </div>
        <div class="contentbox" style="padding-top: 0;">
            <table style="width: 100%" class="datatable">
                <thead>
                    <tr>
                        <th><?php _e( 'Screen Resolution' ); ?></th>
                        <th><?php _e( 'Executions' ); ?></th>
                        <th><?php _e( 'Unique' ); ?></th>
                        <th><?php _e( 'Percentage Of Executions' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $resolutions_chart_data as $row ) : $percent = round( $row['percent'], 2 ) . "%"; ?>
                    <tr>
                        <td><?php echo $row['ScreenRes'] ?></td>
                        <td><?php echo $row['total'] ?></td>
                        <td><?php echo $row['unique'] ?></td>
                        <td>
                            <div style="width: 85%" class="usagebox left">
                                <div style="width:<?php echo $percent ?>" class="lowbar"></div>
                            </div>
                            <span style="padding: 8px" class="right"><?php echo $percent ?></span>
                            <?php unset( $percent ); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php unset( $resolutions_chart_data, $row ); ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Overview End -->
</div>
<?php else : ?>
<div id="contentcontainers">
    <!-- Overview Start -->
    <div class="contentcontainer">
        <div class="headings altheading">
            <h2><?php _e( 'Screen Resolutions' ); ?></h2>
        </div>
        <div class="contentbox" style="padding-top: 0;">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
    </div>
    <!-- Overview End -->
</div>
<?php endif; ?>