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

$chart_data = array( 'Exceptions' => array( ) );

// Create date range
$date_range_day = create_date_range_array( $sanitized_input['start'], $sanitized_input['end'] );
$start_point = $date_range_day[0];

$exceptions_exist = false;

for ( $i = 0; $i < count( $date_range_day ) - 1 ;$i++ ) {
    $start = $date_range_day[$i];
    $end = $date_range_day[$i + 1];

    $exceptions = MySQL::getInstance()->select_events( 'exception', $sanitized_input['id'], $sanitized_input['ver'], $start, $end, true );

    $chart_data['Exceptions'][] = $exceptions;

    if ( $exceptions > 0 && !$exceptions_exist )
        $exceptions_exist = true;

    unset( $start, $end, $exceptions);
}

unset( $date_range_day );

if ( $exceptions_exist ) :
    $query = "SELECT e.ExceptionStackTrace, e.ExceptionMsg, e.ExceptionSource, e.ExceptionTargetSite, UNIX_TIMESTAMP(e.UtcTimestamp) AS `UtcTimestamp`, s.UniqueUserId, s.ApplicationVersion, u.OSVersion, u.OSServicePack ";
    $query .= "FROM `".MySQL::getInstance()->prefix."events_exception` AS e ";
    $query .= "INNER JOIN `".MySQL::getInstance()->prefix."sessions` AS s ON e.SessionId = s.SessionId ";
    $query .= "INNER JOIN `".MySQL::getInstance()->prefix."uniqueusers` AS u ON s.UniqueUserId = u.UniqueUserId ";
    $query .= "WHERE s.ApplicationId = '".$sanitized_input['id']."' " . ( ( $sanitized_input['ver'] != "all" ) ? ( "AND s.ApplicationVersion = '".$sanitized_input['ver']."' " ) : ( "" ) );
    $query .= "AND e.UtcTimestamp BETWEEN FROM_UNIXTIME(".$sanitized_input['start'].") AND FROM_UNIXTIME(".$sanitized_input['end'].") ";
    //$query .= "GROUP BY s.UniqueUserId";

    MySQL::getInstance()->execute_sql( $query );

    unset( $query );

    $event_rows = array();

    if ( MySQL::getInstance()->records > 0 ) {
        if ( MySQL::getInstance()->records == 1 )
            $event_rows[] = MySQL::getInstance()->array_result();
        else if ( MySQL::getInstance()->records > 1 )
            $event_rows = MySQL::getInstance()->array_results();

        $exception_data = array();

        foreach ( $event_rows as $event_row ) {
            $stack_trace = $event_row['ExceptionStackTrace'];
            $exception_id = md5( preg_replace( '/\s+/', ' ', $stack_trace ) );

            $message = $event_row['ExceptionMsg'];
            $source = $event_row['ExceptionSource'];
            $target_site = $event_row['ExceptionTargetSite'];

            // Get user environment
            $version = $event_row['ApplicationVersion'];
            $date_ts = intval( $event_row['UtcTimestamp'] );

            $os_version = $event_row['OSVersion'];
            $os_sp = $event_row['OSServicePack'];

            if ( !array_key_exists( $exception_id, $exception_data ) ) {
                $exception_data[$exception_id] = array(
                    'date' => htmlspecialchars( $date_ts ),
                    'message' => htmlspecialchars( $message ),
                    'stacktrace' => htmlspecialchars( $stack_trace ),
                    'occurrences' => array(
                        array(
                            'version' => htmlspecialchars( $version ),
                            'source' => htmlspecialchars( $source ),
                            'targetsite' => htmlspecialchars( $target_site ),
                            'os' => htmlspecialchars( $os_version ),
                            'sp' => htmlspecialchars( $os_sp ),
                            'date' => htmlspecialchars( $date_ts )
                        )
                    )
                );
            } else {
                if ( $exception_data[$exception_id]['date'] > $date_ts )
                    $exception_data[$exception_id]['date'] = $date_ts;

                $exception_data[$exception_id]['occurrences'][] = array(
                    'version' => htmlspecialchars( $version ),
                    'source' => htmlspecialchars( $source ),
                    'targetsite' => htmlspecialchars( $target_site ),
                    'os' => htmlspecialchars( $os_version ),
                    'sp' => htmlspecialchars( $os_sp ),
                    'date' => htmlspecialchars( $date_ts )
                );
            }

            unset( $stack_trace, $exception_id, $date_ts, $message, $stack_trace, $version, $source, $target_site, $os_version, $os_sp );
        }

        unset( $event_rows, $event_row );
    }

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
?>
<script type="text/javascript">$(document).ready(function () { <?php $line_chart->render('chart'); unset( $line_chart ); ?> });</script>
<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Exceptions' ); ?></h1>
</div>
<!-- end page-heading -->

<!-- start stats graph -->
<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowleft.jpg'); ?>" /></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowright.jpg'); ?>" /></th>
        </tr>
        <tr>
            <td id="tbl-border-left"></td>
            <td>
                <div id="content-table-inner">
                    <div id="chart_div"></div>
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

<div class="contentcontainers">
    <div class="contentcontainer">
        <div class="headings alt">
            <h2><?php _e( 'Exceptions' ); ?></h2>
        </div>
        <div class="contentbox">
            <table style="width: 100%" class="datatable">
                <thead>
                    <tr>
                        <th style="width: 145px"><?php _e( 'Date' ); ?></th>
                        <th><?php _e( 'Exception' ); ?></th>
                        <th><?php _e( 'Traceback' ); ?></th>
                        <th><?php _e( 'Count' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $exception_data as $exception_id => $data ) : ?>
                    <tr exceptionid="<?php echo $exception_id; ?>">
                        <td><?php echo date( 'M j, Y, g:i a', $data['date'] ); ?></td>
                        <td><a id="exceptiondetails"><?php echo $data['message']; ?></a></td>
                        <td><a id="exceptiondetails"><?php echo ( ( strlen( $data['stacktrace'] ) > 60 ) ? ( substr( $data['stacktrace'], 0, 60 ) . '...' ) : ( $data['stacktrace'] ) ); ?></a></td>
                        <td><?php echo count( $data['occurrences'] ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="contentcontainer exceptiondetailscontainer">
        <div class="headings altheading">
            <h2><?php _e( 'Exception Details' ); ?></h2>
        </div>
        <div class="contentbox">
            <?php foreach ( $exception_data as $exception_id => $data ) : ?>
            <div style="width: 100%; display: none;" class="exceptiondetails" exceptionid="<?php echo $exception_id; ?>">
                <pre class="exception"><?php echo $data['stacktrace']; ?></pre>
                <table style="width: 100%; clear: none" id="exception" class="datatable">
                    <caption style="padding-top: 7px; padding-bottom: 13px"><?php _e( 'Occurrences' ); ?></caption>
                    <thead>
                        <tr>
                            <th><?php _e( 'Version' ); ?></th>
                            <th><?php _e( 'Source' ); ?></th>
                            <th><?php _e( 'Target Site' ); ?></th>
                            <th><?php _e( 'OS' ); ?></th>
                            <th><?php _e( 'SP' ); ?></th>
                            <th><?php _e( 'Date' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $data['occurrences'] as $occurrence ) : ?>
                        <tr>
                            <td><?php echo $occurrence['version']; ?></td>
                            <td><?php echo $occurrence['source']; ?></td>
                            <td><?php echo $occurrence['targetsite']; ?></td>
                            <td><?php echo $occurrence['os']; ?></td>
                            <td><?php echo $occurrence['sp']; ?></td>
                            <td><?php echo date( 'Y-m-d H:i:s', $occurrence['date'] ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php unset( $occurrence ); ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
            <?php unset( $exception_data, $exception_id, $data ); ?>
        </div>
    </div>
</div>
<?php else : unset( $chart_data ); ?>
<!--  start page-heading -->
<div id="page-heading">
    <h1><?php _e( 'Exceptions' ); ?></h1>
</div>
<!-- end page-heading -->

<table id="content-table" border="0" cellspacing="0" cellpadding="0" width="100%">
    <tbody>
        <tr>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowleft.jpg'); ?>" /></th>
            <th class="topleft"></th>
            <td id="tbl-border-top">&nbsp;</td>
            <th class="topright"></th>
            <th class="sized" rowspan="3"><img height="300" width="20" alt="" src="<?php file_url('/images/shared/side_shadowright.jpg'); ?>" /></th>
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

<div class="contentcontainers">
    <div class="contentcontainer">
        <div class="headings alt">
            <h2><?php _e( 'Exceptions' ); ?></h2>
        </div>
        <div class="contentbox">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
    </div>

    <div class="contentcontainer exceptiondetailscontainer">
        <div class="headings altheading">
            <h2><?php _e( 'Exception Details' ); ?></h2>
        </div>
        <div class="contentbox">
            <div id="nodataavailable"><?php _e( 'No Data Available' ); ?></div>
        </div>
    </div>
</div>
<?php endif; ?>