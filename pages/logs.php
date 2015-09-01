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

$query = "SELECT e.LogMessage, UNIX_TIMESTAMP(MAX(e.UtcTimestamp)) AS 'date', COUNT(*) AS 'total', COUNT(DISTINCT s.UniqueUserId) AS 'unique' ";
$query .= "FROM `".MySQL::getInstance()->prefix."events_log` AS e ";
$query .= "INNER JOIN `".MySQL::getInstance()->prefix."sessions` AS s ON e.SessionId = s.SessionId ";
$query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
$query .= "AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(".$sanitized_input['start'].") AND FROM_UNIXTIME(".$sanitized_input['end'].") ";
$query .= "GROUP BY e.LogMessage";

MySQL::getInstance()->execute_sql( $query );

unset( $query );

if ( MySQL::getInstance()->records > 0 ) :
    $rows = array();

    if ( MySQL::getInstance()->records == 1 )
        $rows[] = MySQL::getInstance()->array_result();
    else if ( MySQL::getInstance()->records > 1 )
        $rows = MySQL::getInstance()->array_results();
?>
<div id="contentcontainers">
    <!-- Overview Start -->
    <div class="contentcontainer">
        <div class="headings altheading">
            <h2><?php _e( 'Logs' ); ?></h2>
        </div>
        <div class="contentbox" style="padding-top: 0;">
            <table style="width: 100%" class="datatable">
                <thead>
                    <tr>
                        <th><?php _e( 'Message' ); ?></th>
                        <th><?php _e( 'Date' ); ?></th>
                        <th><?php _e( 'Execution' ); ?></th>
                        <th><?php _e( 'Unique' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $row ) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars( $row['LogMessage'] ); ?></td>
                        <td><?php echo date( 'Y-m-d h:i:s A', intval( $row['date'] ) ) ?></td>
                        <td><?php echo $row['total'] ?></td>
                        <td><?php echo $row['unique'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php unset( $rows, $row ); ?>
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
            <h2><?php _e( 'Logs' ); ?></h2>
        </div>
        <div class="contentbox" style="padding-top: 0;">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
    </div>
    <!-- Overview End -->
</div>
<?php endif; ?>