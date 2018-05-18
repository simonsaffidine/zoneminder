<?php
//
// ZoneMinder web function library, $Date: 2008-07-08 16:06:45 +0100 (Tue, 08 Jul 2008) $, $Revision: 2484 $
// Copyright (C) 2001-2008 Philip Coombes
// 
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
// 


function xhtmlHeaders( $file, $title ) {
  global $css;
  global $skin;
  global $view;

  # This idea is that we always include the classic css files, 
  # and then any different skin only needs to contain things that are different.
  $baseCssPhpFile = getSkinFile( 'css/base/skin.css.php' );

  $skinCssPhpFile = getSkinFile( 'css/'.$css.'/skin.css.php' );

  $skinJsFile = getSkinFile( 'js/skin.js' );
  $skinJsPhpFile = getSkinFile( 'js/skin.js.php' );
  $cssJsFile = getSkinFile( 'js/'.$css.'.js' );

  $basename = basename( $file, '.php' );

  $viewCssPhpFile = getSkinFile( '/css/'.$css.'/views/'.$basename.'.css.php' );
  $viewJsFile = getSkinFile( 'views/js/'.$basename.'.js' );
  $viewJsPhpFile = getSkinFile( 'views/js/'.$basename.'.js.php' );

  extract( $GLOBALS, EXTR_OVERWRITE );
  function output_link_if_exists( $files ) {
    global $skin;
    $html = array();
    foreach ( $files as $file ) {
      if ( getSkinFile( $file ) ) {
        $html[] = '<link rel="stylesheet" href="'.cache_bust( 'skins/'.$skin.'/'.$file ).'" type="text/css"/>';
      }
    }
    return implode("\n", $html);
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo ZM_WEB_TITLE_PREFIX ?> - <?php echo validHtmlStr($title) ?></title>
<?php
if ( file_exists( "skins/$skin/css/$css/graphics/favicon.ico" ) ) {
  echo "
  <link rel=\"icon\" type=\"image/ico\" href=\"skins/$skin/css/$css/graphics/favicon.ico\"/>
  <link rel=\"shortcut icon\" href=\"skins/$skin/css/$css/graphics/favicon.ico\"/>
";
} else {
  echo '
  <link rel="icon" type="image/ico" href="graphics/favicon.ico"/>
  <link rel="shortcut icon" href="graphics/favicon.ico"/>
';
}
?>
  <link rel="stylesheet" href="css/reset.css" type="text/css"/>
  <link rel="stylesheet" href="css/overlay.css" type="text/css"/>
  <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css"/>
  
<?php 
echo output_link_if_exists( array(
  'css/base/skin.css',
  'css/'.$css.'/skin.css',
  'css/base/views/'.$basename.'.css',
  'css/'.$css.'/views/'.$basename.'.css',
  '/js/dateTimePicker/jquery-ui-timepicker-addon.css',
  '/js/jquery-ui-1.12.1/jquery-ui.structure.min.css',
  '/css/jquery-ui-1.12.1/jquery-ui.theme.min.css',
  '/css/'.$css.'/jquery-ui-theme.css',
)
);
?>
  <!--Chosen can't be cache-busted because it loads sprites by relative path-->
<link rel="stylesheet" href="skins/classic/js/chosen/chosen.min.css" type="text/css"/>
<?php
  if ($basename == 'watch') {
    echo output_link_if_exists( array(
      '/css/base/views/control.css',
      '/css/'.$css.'/views/control.css'
    ) );
  }
  if ( $viewCssPhpFile ) {
?>
  <style type="text/css">
  /*<![CDATA[*/
<?php
    require_once( $viewCssPhpFile );
?>
  /*]]>*/
  </style>
<?php
  }
?>

  <script src="tools/mootools/mootools-core.js"></script>
  <script src="tools/mootools/mootools-more.js"></script>
  <script src="js/mootools.ext.js"></script>
  <script src="skins/<?php echo $skin; ?>/js/jquery.js"></script>
  <script src="skins/<?php echo $skin; ?>/js/jquery-ui-1.12.1/jquery-ui.js"></script>
  <script src="skins/<?php echo $skin; ?>/js/bootstrap.min.js"></script>
  <script src="skins/<?php echo $skin; ?>/js/chosen/chosen.jquery.min.js"></script>
  <script src="skins/<?php echo $skin; ?>/js/dateTimePicker/jquery-ui-timepicker-addon.js"></script>

  <script>
  //<![CDATA[
  <!--
  var $j = jQuery.noConflict();
  // $j is now an alias to the jQuery function; creating the new alias is optional.

  //-->
  //]]>
  </script>
  <script src="skins/<?php echo $skin; ?>/views/js/state.js"></script>
<?php
  if ( $title == 'Login' && (defined('ZM_OPT_USE_GOOG_RECAPTCHA') && ZM_OPT_USE_GOOG_RECAPTCHA) ) {
?>
  <script src='https://www.google.com/recaptcha/api.js'></script>
<?php
  } else if ( $view == 'event' ) {
?>
  <link href="skins/<?php echo $skin ?>/js/video-js.css" rel="stylesheet">
  <link href="skins/<?php echo $skin ?>/js/video-js-skin.css" rel="stylesheet">
  <script src="skins/<?php echo $skin ?>/js/video.js"></script>
  <script src="./js/videojs.zoomrotate.js"></script>
<?php
  }
?>
  <script src="skins/<?php echo $skin ?>/js/moment.min.js"></script>
<?php
  if ( $skinJsPhpFile ) {
?>
  <script>
  //<![CDATA[
  <!--
<?php
    require_once( $skinJsPhpFile );
?>
  //-->
  //]]>
  </script>
<?php
  }
  if ( $viewJsPhpFile ) {
?>
  <script>
  //<![CDATA[
  <!--
<?php
    require_once( $viewJsPhpFile );
?>
  //-->
  //]]>
  </script>
<?php
  }
	if ( $cssJsFile ) {
?>
  <script src="<?php echo cache_bust($cssJsFile) ?>"></script>
<?php
} else {
?>
  <script src="skins/classic/js/base.js"></script>
<?php } ?>
  <script src="<?php echo cache_bust($skinJsFile) ?>"></script>
  <script src="js/logger.js"></script>
<?php
  if ( $viewJsFile ) {
?>
  <script type="text/javascript" src="<?php echo cache_bust($viewJsFile) ?>"></script>
<?php
  }
?>
</head>
<?php
} // end function xhtmlHeaders( $file, $title )

function getNavBarHTML($reload = null) {

  $versionClass = (ZM_DYN_DB_VERSION&&(ZM_DYN_DB_VERSION!=ZM_VERSION))?'errorText':'';
  global $running;
  global $user;
  global $bandwidth_options;
  global $view;
  global $filterQuery;
  global $sortQuery;
  global $limitQuery;

  if (!$sortQuery) {
    parseSort();
  }
  if (!$filterQuery) {
    parseFilter( $_REQUEST['filter'] );
    $filterQuery = $_REQUEST['filter']['query'];
  }
  if ($reload === null) {
    ob_start();
    if ( $running == null )
      $running = daemonCheck();
    $status = $running?translate('Running'):translate('Stopped');
?>
<noscript>
<div style="background-color:red;color:white;font-size:x-large;">
<?php echo ZM_WEB_TITLE ?> requires Javascript. Please enable Javascript in your browser for this site.
</div>
</noscript>
<div class="navbar navbar-inverse navbar-static-top">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-header-nav" aria-expanded="false">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
      <div class="navbar-brand"><a href="<?php echo ZM_HOME_URL?>" target="<?php echo ZM_WEB_TITLE ?>"><?php echo ZM_HOME_CONTENT ?></a></div>
		</div>

		<div class="collapse navbar-collapse" id="main-header-nav">
<?php if ( canView('Monitors') ) { ?>
		<ul class="nav navbar-nav">
			<li><a href="?view=console"><?php echo translate('Console') ?></a></li>
<?php if ( canView( 'System' ) ) { ?>
			<li><a href="?view=options"><?php echo translate('Options') ?></a></li>
			<li>
<?php
  if ( logToDatabase() > Logger::NOLOG ) { 
    if ( ! ZM_RUN_AUDIT ) {
    # zmaudit can clean the logs, but if we aren't running it, then we should clecan them regularly
     dbQuery('DELETE FROM Logs WHERE TimeKey < unix_timestamp( NOW() - interval '.ZM_LOG_DATABASE_LIMIT.') LIMIT 100');
    }
    echo makePopupLink( '?view=log', 'zmLog', 'log', '<span class="'.logState().'">'.translate('Log').'</span>' );
  }
} // end if canview(System)
?></li>
<?php
if ( ZM_OPT_X10 && canView( 'Devices' ) ) { ?>
			<li><a href="?view=devices">Devices</a></li>
<?php } ?>
<li><a href="?view=groups"<?php echo $view=='groups'?' class="selected"':''?>><?php echo translate('Groups') ?></a></li>
      <li><a href="?view=filter<?php echo $filterQuery.$sortQuery.$limitQuery ?>"<?php echo $view=='filter'?' class="selected"':''?>><?php echo translate('Filters') ?></a></li>

<?php 
  if ( canView( 'Stream' ) ) {
?>
      <li><a href="?view=cycle"<?php echo $view=='cycle'?' class="selected"':''?>><?php echo translate('Cycle') ?></a></li>
      <li><a href="?view=montage"<?php echo $view=='montage'?' class="selected"':''?>><?php echo translate('Montage') ?></a></li>
<?php
   }
   // if canview_reports
?>
<?php
if (isset($_REQUEST['filter']['Query']['terms']['attr'])) {
  $terms = $_REQUEST['filter']['Query']['terms'];
  $count = 0;
  foreach ($terms as $term) {
    if ($term['attr'] == "StartDateTime") {
      $count += 1;
      if ($term['op'] == '>=') $minTime = $term['val'];
      if ($term['op'] == '<=') $maxTime = $term['val'];
    }
  }
  if ($count == 2) {
    $montageReviewQuery = '&minTime='.$minTime.'&maxTime='.$maxTime;
  }
}
  if ( canView('Events') ) {
 ?>
   <li><a href="?view=montagereview<?php echo isset($montageReviewQuery)?'&fit=1'.$montageReviewQuery.'&live=0':'' ?>"<?php echo $view=='montagereview'?' class="selected"':''?>><?php echo translate('MontageReview')?></a></li>
<?php
  }
?>
      <li><a href="?view=report_event_audit"<?php echo $view=='report_event_audit'?' class="selected"':''?>><?php echo translate('ReportEventAudit') ?></a></li>
		</ul>
<?php } // end if canView('Monitors') ?>

<div class="navbar-right">
<?php if ( ZM_OPT_USE_AUTH and $user ) { ?>
	<p class="navbar-text"><i class="material-icons">account_circle</i> <?php echo makePopupLink( '?view=logout', 'zmLogout', 'logout', $user['Username'], (ZM_AUTH_TYPE == "builtin") ) ?> </p>
<?php } ?>

<?php if ( canEdit( 'System' ) ) { ?>
		<button type="button" class="btn btn-default navbar-btn" data-toggle="modal" data-target="#modalState"><?php echo $status ?></button>

<?php } else if ( canView( 'System' ) ) { ?>
		<p class="navbar-text"> <?php echo $status ?> </p>
<?php } ?>
</div>
		</div><!-- End .navbar-collapse -->
	</div> <!-- End .container-fluid -->
<?php
}//end reload null.  Runs on full page load

if ( (!ZM_OPT_USE_AUTH) or $user ) {
if ($reload == 'reload') ob_start();
?>
	<div id="reload" class="container-fluid reduced-text">
    <div id="Bandwidth" class="pull-left">
      <?php echo makePopupLink( '?view=bandwidth', 'zmBandwidth', 'bandwidth', "<i class='material-icons md-18'>network_check</i>&nbsp;".$bandwidth_options[$_COOKIE['zmBandwidth']] . ' ', ($user && $user['MaxBandwidth'] != 'low' ) ) ?>
    </div>
    <div id="Version" class="pull-right">
      <?php echo makePopupLink( '?view=version', 'zmVersion', 'version', '<span class="version '.$versionClass.'">v'.ZM_VERSION.'</span>', canEdit( 'System' ) ) ?>
    <?php if ( defined('ZM_WEB_CONSOLE_BANNER') and ZM_WEB_CONSOLE_BANNER != '' ) { ?>
        <h3 id="development"><?php echo ZM_WEB_CONSOLE_BANNER ?></h3>
    <?php } ?>
    </div>
    <ul class="list-inline">
      <li class="Load"><i class="material-icons md-18">trending_up</i>&nbsp;<?php echo translate('Load') ?>: <?php echo getLoad() ?></li>
<i class="material-icons md-18">storage</i>
<?php 
  $connections = dbFetchOne( "SHOW status WHERE variable_name='threads_connected'", 'Value' );
  $max_connections = dbFetchOne( "SHOW variables WHERE variable_name='max_connections'", 'Value' );
  $percent_used = 100 * $connections / $max_connections;
  echo '<li'. ( $percent_used > 90 ? ' class="warning"' : '' ).'>'.translate('DB').':'.$connections.'/'.$max_connections.'</li>';
?>
	  <li><?php echo translate('Storage') ?>:
<?php
  $storage_areas = Storage::find_all();
  $storage_paths = null;
  foreach ( $storage_areas as $area ) {
    $storage_paths[$area->Path()] = $area;
  }
  if ( ! isset($storage_paths[ZM_DIR_EVENTS]) ) {
    array_push( $storage_areas, new Storage() );
  }
  $func = function($S){
    $class = '';
    if ( $S->disk_usage_percent() > 98 ) {
      $class = "error";
    } else if ( $S->disk_usage_percent() > 90 ) {
      $class = "warning";
    }
    $title = human_filesize($S->disk_used_space()) . ' of ' . human_filesize($S->disk_total_space()). 
      ( ( $S->disk_used_space() != $S->event_disk_space() ) ? ' ' .human_filesize($S->event_disk_space()) . ' used by events' : '' );

    return '<span class="'.$class.'" title="'.$title.'">'.$S->Name() . ': ' . $S->disk_usage_percent().'%' . '</span>'; };
  #$func =  function($S){ return '<span title="">'.$S->Name() . ': ' . $S->disk_usage_percent().'%' . '</span>'; };
  if ( count($storage_areas) >= 4 ) 
    $storage_areas = Storage::find_all( array('ServerId'=>null) );
  if ( count($storage_areas) < 4 )
    echo implode( ', ', array_map ( $func, $storage_areas ) );
  echo ' ' . ZM_PATH_MAP .': '. getDiskPercent(ZM_PATH_MAP).'%';
?></li>
  </ul>
<!-- End .footer/reload --></div>
<?php
if ($reload == 'reload') return( ob_get_clean() );
} // end if (!ZM_OPT_USE_AUTH) or $user )
?>
</div><!-- End .navbar .navbar-default -->
<?php
  return( ob_get_clean() );
} // end function getNavBarHTML()

function xhtmlFooter() {
  global $view;
  global $skin;
  global $running;
if ( canEdit('System') ) {
  include("skins/$skin/views/state.php");
?>
<?php
}
?>
</body>
<script type="text/javascript">
$j('.chosen').chosen();
</script>
</html>
<?php
} // end xhtmlFooter

?>
