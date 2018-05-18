<?php
//
// ZoneMinder web function library, $Date$, $Revision$
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

// Compatibility functions
if ( version_compare( phpversion(), '4.3.0', '<') ) {
  function ob_get_clean() {
    $buffer = ob_get_contents();
    ob_end_clean();
    return( $buffer );
  }
}

function noCacheHeaders() {
  header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');    // Date in the past
  header('Last-Modified: '.gmdate( 'D, d M Y H:i:s' ).' GMT'); // always modified
  header('Cache-Control: no-store, no-cache, must-revalidate');  // HTTP/1.1
  header('Cache-Control: post-check=0, pre-check=0', false);
  header('Pragma: no-cache');         // HTTP/1.0
}

function CORSHeaders() {
  if ( isset( $_SERVER['HTTP_ORIGIN'] ) ) {

# The following is left for future reference/use.
    $valid = false;
    $servers = dbFetchAll( 'SELECT * FROM Servers' );
    if ( sizeof($servers) <= 1 ) {
# Only need CORSHeaders in the event that there are multiple servers in use.
      return;
    }
    foreach( $servers as $row ) {
      $Server = new Server( $row );
      if ( $_SERVER['HTTP_ORIGIN'] == $Server->Url() ) {
        $valid = true;
        header('Access-Control-Allow-Origin: ' . $Server->Url() );
        header('Access-Control-Allow-Headers: x-requested-with,x-request');
      }
    }
    if ( ! $valid ) {
      Warning( $_SERVER['HTTP_ORIGIN'] . ' is not found in servers list.' );
    }
  }
}

function getStreamSrc( $args, $querySep='&amp;' ) {
  $streamSrc = ZM_BASE_URL.ZM_PATH_ZMS;

  if ( ZM_OPT_USE_AUTH ) {
    if ( ZM_AUTH_RELAY == 'hashed' ) {
      $args[] = 'auth='.generateAuthHash( ZM_AUTH_HASH_IPS );
    } elseif ( ZM_AUTH_RELAY == 'plain' ) {
      $args[] = 'user='.$_SESSION['username'];
      $args[] = 'pass='.$_SESSION['password'];
    } elseif ( ZM_AUTH_RELAY == 'none' ) {
      $args[] = 'user='.$_SESSION['username'];
    }
  }
  if ( !in_array( 'mode=single', $args ) && !empty($GLOBALS['connkey']) ) {   
    $args[] = 'connkey='.$GLOBALS['connkey'];
  }       
  if ( ZM_RAND_STREAM ) {
    $args[] = 'rand='.time();
  }

  if ( count($args) ) {
    $streamSrc .= '?'.join( $querySep, $args );
  }

  return( $streamSrc );
}

function getMimeType( $file ) {
  if ( function_exists('mime_content_type') ) {
    return( mime_content_type( $file ) );
  } elseif ( function_exists('finfo_file') ) {
    $finfo = finfo_open( FILEINFO_MIME );
    $mimeType = finfo_file( $finfo, $file );
    finfo_close($finfo);
    return( $mimeType );
  }
  return( trim( exec( 'file -bi '.escapeshellarg( $file ).' 2>/dev/null' ) ) );
}

function outputVideoStream( $id, $src, $width, $height, $format, $title='' ) {
  echo getVideoStreamHTML( $id, $src, $width, $height, $format, $title );
}

function getVideoStreamHTML( $id, $src, $width, $height, $format, $title='' ) {
  $html = '';
  $width = validInt($width);
  $height = validInt($height);
  $title = validHtmlStr($title);

  if ( file_exists( $src ) ) {
    $mimeType = getMimeType( $src );
  } else {
    switch( $format ) {
      case 'asf' :
        $mimeType = 'video/x-ms-asf';
        break;
      case 'avi' :
      case 'wmv' :
        $mimeType = 'video/x-msvideo';
        break;
      case 'mov' :
        $mimeType = 'video/quicktime';
        break;
      case 'mpg' :
      case 'mpeg' :
        $mimeType = 'video/mpeg';
        break;
      case 'swf' :
        $mimeType = 'application/x-shockwave-flash';
        break;
      case '3gp' :
        $mimeType = 'video/3gpp';
        break;
      default :
        $mimeType = "video/$format";
        break;
    }
  }
  if ( !$mimeType || ($mimeType == 'application/octet-stream') )
    $mimeType = 'video/'.$format;
  if ( ZM_WEB_USE_OBJECT_TAGS ) {
    switch( $mimeType ) {
      case 'video/x-ms-asf' :
      case 'video/x-msvideo' :
      case 'video/mp4' :
        {
          if ( isWindows() ) {
            return '<object id="'.$id.'" width="'.$width.'" height="'.$height.'
              classid="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
              codebase="'.ZM_BASE_PROTOCOL.'://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,0,02,902"
              standby="Loading Microsoft Windows Media Player components..."
              type="'.$mimeType.'">
              <param name="FileName" value="'.$src.'"/>
              <param name="autoStart" value="1"/>
              <param name="showControls" value="0"/>
              <embed type="'.$mimeType.'"
              pluginspage="'.ZM_BASE_PROTOCOL.'://www.microsoft.com/Windows/MediaPlayer/"
              src="'.$src.'"
              name="'.$title.'"
              width="'.$width.'"
              height="'.$height.'"
              autostart="1"
              showcontrols="0">
              </embed>
              </object>';
          }
        }
      case 'video/quicktime' :
        {
            return '<object id="'.$id.'" width="'.$width.'" height="'.$height.'"
            classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"
            codebase="'.ZM_BASE_PROTOCOL.'://www.apple.com/qtactivex/qtplugin.cab"
            type="'.$mimeType.'">
            <param name="src" value="'.$src.'"/>
            <param name="autoplay" VALUE="true"/>
            <param name="controller" VALUE="false"/>
            <embed type="'.$mimeType.'"
            src="'.$src.'"
            pluginspage="'.ZM_BASE_PROTOCOL.'://www.apple.com/quicktime/download/"
            name="'.$title.'" width="'.$width.'" height="'.$height.'"
            autoplay="true"
            controller="true">
            </embed>
            </object>';
        }
      case 'application/x-shockwave-flash' :
        {
            return '<object id="'.$id.'" width="'.$width.'" height="'.$height.'"
            classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
            codebase="'.ZM_BASE_PROTOCOL.'://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0"
            type="'.$mimeType.'">
            <param name="movie" value="'.$src.'"/>
            <param name="quality" value="high"/>
            <param name="bgcolor" value="#ffffff"/>
            <embed type="'.$mimeType.'"
            pluginspage="'.ZM_BASE_PROTOCOL.'://www.macromedia.com/go/getflashplayer"
            src="'.$src.'"
            name="'.$title.'"
            width="'.$width.'"
            height="'.$height.'"
            quality="high"
            bgcolor="#ffffff">
            </embed>
            </object>';
        }
    } # end switch
  } # end if use object tags
  return '<embed'. ( isset($mimeType)?(' type="'.$mimeType.'"'):'' ). '
      src="'.$src.'"
      name="'.$title.'"
      width="'.$width.'"
      height="'.$height.'"
      autostart="1"
      autoplay="1"
      showcontrols="0"
      controller="0">
      </embed>';
}

function outputImageStream( $id, $src, $width, $height, $title='' ) {
  echo getImageStreamHTML( $id, $src, $width, $height, $title );
}


function getImageStreamHTML( $id, $src, $width, $height, $title='' ) {
  if ( canStreamIframe() ) {
      return '<iframe id="'.$id.'" src="'.$src.'" alt="'. validHtmlStr($title) .'" '.($width? ' width="'. validInt($width).'"' : '').($height?' height="'.validInt($height).'"' : '' ).'/>';
  } else {
      return '<img id="'.$id.'" src="'.$src.'" alt="'. validHtmlStr($title) .'" style="'.($width? ' width:'.$width.';' : '' ).($height ? ' height:'. $height.';' : '' ).'"/>';
  }
}

function outputControlStream( $src, $width, $height, $monitor, $scale, $target ) {
?>
  <form name="ctrlForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>" target="<?php echo $target ?>">
    <input type="hidden" name="view" value="blank">
    <input type="hidden" name="mid" value="<?php echo $monitor['Id'] ?>">
    <input type="hidden" name="action" value="control">
    <?php
    if ( $monitor['CanMoveMap'] ) {
    ?>
      <input type="hidden" name="control" value="moveMap">
    <?php
    } elseif ( $monitor['CanMoveRel'] ) {
    ?>
      <input type="hidden" name="control" value="movePseudoMap">
    <?php
    } elseif ( $monitor['CanMoveCon'] ) {
    ?>
      <input type="hidden" name="control" value="moveConMap">
    <?php
    }
    ?>
    <input type="hidden" name="scale" value="<?php echo $scale ?>">
    <input type="image" src="<?php echo $src ?>" width="<?php echo $width ?>" height="<?php echo $height ?>">
  </form>
<?php
}

function outputHelperStream( $id, $src, $width, $height, $title='' ) {
  echo getHelperStream( $id, $src, $width, $height, $title );
}
function getHelperStream( $id, $src, $width, $height, $title='' ) {
    return '<object type="application/x-java-applet" id="'.$id.'" code="com.charliemouse.cambozola.Viewer"
    archive="'. ZM_PATH_CAMBOZOLA .'" 
    align="middle"
    width="'. $width .'"
    height="'. $height .'"
    title="'. $title .'">
    <param name="accessories" value="none"/>
    <param name="url" value="'. $src .'"/>
    </object>';
}

function outputImageStill( $id, $src, $width, $height, $title='' ) {
  echo getImageStill( $id, $src, $width, $height, $title='' );
}
function getImageStill( $id, $src, $width, $height, $title='' ) {
  return '<img id="'.$id.'" src="'.$src.'" alt="'.$title.'"'.(validInt($width)?' width="'.$width.'"':'').(validInt($height)?' height="'.$height.'"':'').'/>';
}

function getWebSiteUrl( $id, $src, $width, $height, $title='' ) {
    # Prevent unsightly warnings when php cannot verify the ssl certificate
    stream_context_set_default( [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    # The End User can turn off the following warning under Options -> Web
    if ( ZM_WEB_XFRAME_WARN ) {
        $header = get_headers($src, 1);
        # If the target website has set X-Frame-Options, check it for "sameorigin" and warn the end user
        if (array_key_exists('X-Frame-Options', $header)) {
            $header = $header['X-Frame-Options'];
            if ( stripos($header, 'sameorigin') === 0 )
                Warning("Web site $src has X-Frame-Options set to sameorigin. An X-Frame-Options browser plugin is required to display this site.");
        }
    }
    return '<object id="'.$id.'" data="'.$src.'" alt="'.$title.'" width="'.$width.'" height="'.$height.'"></object>';
}

function outputControlStill( $src, $width, $height, $monitor, $scale, $target ) {
  ?>
  <form name="ctrlForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>" target="<?php echo $target ?>">
    <input type="hidden" name="view" value="blank">
    <input type="hidden" name="mid" value="<?php echo $monitor['Id'] ?>">
    <input type="hidden" name="action" value="control">
    <?php
    if ( $monitor['CanMoveMap'] ) {
    ?>
    <input type="hidden" name="control" value="moveMap">
    <?php
    } elseif ( $monitor['CanMoveRel'] ) {
    ?>
    <input type="hidden" name="control" value="movePseudoMap">
    <?php
    } elseif ( $monitor['CanMoveCon'] ) {
    ?>
    <input type="hidden" name="control" value="moveConMap">
    <?php
    }
    ?>
    <input type="hidden" name="scale" value="<?php echo $scale ?>">
    <input type="image" src="<?php echo $src ?>" width="<?php echo $width ?>" height="<?php echo $height ?>">
  </form>
  <?php
}

// Incoming args are shell-escaped. This function must escape any further arguments it cannot guarantee.
function getZmuCommand( $args ) {
  $zmuCommand = ZMU_PATH;

  if ( ZM_OPT_USE_AUTH ) {
    if ( ZM_AUTH_RELAY == 'hashed' ) {
      $zmuCommand .= ' -A '.generateAuthHash( false );
    } elseif ( ZM_AUTH_RELAY == 'plain' ) {
      $zmuCommand .= ' -U ' .escapeshellarg($_SESSION['username']).' -P '.escapeshellarg($_SESSION['password']);
    } elseif ( ZM_AUTH_RELAY == 'none' ) {
      $zmuCommand .= " -U ".escapeshellarg($_SESSION['username']);
    }
  }

  $zmuCommand .= $args;

  return( $zmuCommand );
}

function getEventDefaultVideoPath( $event ) {
  $Event = new Event( $event );
  return $Event->getStreamSrc( array( 'mode'=>'mpeg', 'format'=>'h264' ) );
}

function deletePath( $path ) {
  if ( is_dir( $path ) ) {
    system( escapeshellcmd( 'rm -rf '.$path ) );
  } else {
    unlink( $path );
  }
}

function deleteEvent( $event ) {

  if ( empty($event) ) {
    Error( 'Empty event passed to deleteEvent.');
    return;
  }

  if ( gettype($event) != 'array' ) {
# $event could be an eid, so turn it into an event hash
    $event = new Event( $event );
  } else {
Logger::Debug("Event type: " . gettype($event));
  }

  global $user;

  if ( $user['Events'] == 'Edit' ) {
    $event->delete();
  } # CAN EDIT
}

function makeLink( $url, $label, $condition=1, $options='' ) {
  $string = '';
  if ( $condition ) {
    $string .= '<a href="'.$url.'"'.($options?(' '.$options):'').'>';
  }
  $string .= $label;
  if ( $condition ) {
    $string .= '</a>';
  }
  return( $string );
}

function makePopupLink( $url, $winName, $winSize, $label, $condition=1, $options='' ) {
  $string = '';
  if ( $condition ) {
    if ( is_array( $winSize ) )
      $popupParms = "'".$url."', '".$winName."', '".$winSize[0]."', ".$winSize[1].", ".$winSize[2];
    else
      $popupParms = "'".$url."', '".$winName."', '".$winSize."'";

    $string .= '<a href="'.$url.'" onclick="createPopup( '.$popupParms.' ); return( false );"'.($options?(' '.$options):'').'>';
  } else {
    $string .= '<a>';
  }
  $string .= $label;
  $string .= '</a>';
  return( $string );
}

function makePopupButton( $url, $winName, $winSize, $buttonValue, $condition=1, $options='' ) {
  if ( is_array( $winSize ) )
    $popupParms = "'".$url."', '".$winName."', '".$winSize[0]."', ".$winSize[1].", ".$winSize[2];
  else
    $popupParms = "'".$url."', '".$winName."', '".$winSize."'";
  $string = '<input type="button" value="'.$buttonValue.'" onclick="createPopup( '.$popupParms.' ); return( false );"'.($condition?'':' disabled="disabled"').($options?(' '.$options):'').'/>';
  return( $string );
}

function htmlSelect( $name, $contents, $values, $behaviours=false ) {

  $behaviourText = '';
  if ( !empty($behaviours) ) {
    if ( is_array($behaviours) ) {
      foreach ( $behaviours as $event=>$action ) {
        $behaviourText .= ' '.$event.'="'.$action.'"';
      }
    } else {
      $behaviourText = ' onchange="'.$behaviours.'"';
    }
  }

  return "<select name=\"$name\" id=\"$name\"$behaviourText>".htmlOptions( $contents, $values ).'</select>';
}

function htmlOptions( $contents, $values ) {
  $html = '';
  foreach ( $contents as $value=>$text ) {
    if ( is_array( $text ) )
      $text = $text['Name'];
    else if ( is_object( $text ) )
      $text = $text->Name();
    $selected = is_array( $values ) ? in_array( $value, $values ) : !strcmp($value, $values);
    $html .= "<option value=\"$value\"".($selected?" selected=\"selected\"":'').">$text</option>";
  }
  return $html;
}

function truncText( $text, $length, $deslash=1 ) {       
  return( preg_replace( '/^(.{'.$length.',}?)\b.*$/', '\\1&hellip;', ($deslash?stripslashes($text):$text) ) );       
}               

function buildSelect( $name, $contents, $behaviours=false ) {
  $value = '';
  if ( preg_match( '/^\s*(\w+)\s*(\[.*\])?\s*$/', $name, $matches ) && count($matches) > 2 ) {
    $arr = $matches[1];
    if ( isset($GLOBALS[$arr]) )
      $value = $GLOBALS[$arr];
    elseif ( isset($_REQUEST[$arr]) )
      $value = $_REQUEST[$arr];
    if ( !preg_match_all( '/\[\s*[\'"]?(\w+)["\']?\s*\]/', $matches[2], $matches ) ) {
      Fatal( "Can't parse selector '$name'" );
    }
    for ( $i = 0; $i < count($matches[1]); $i++ ) {
      $idx = $matches[1][$i];
      $value = isset($value[$idx])?$value[$idx]:false;
    }
  } else {
    if ( isset($GLOBALS[$name]) )
      $value = $GLOBALS[$name];
    elseif ( isset($_REQUEST[$name]) )
      $value = $_REQUEST[$name];
  }
  ob_start();
  $behaviourText = '';
  if ( !empty($behaviours) ) {
    if ( is_array($behaviours) ) {
      foreach ( $behaviours as $event=>$action ) {
        $behaviourText .= ' '.$event.'="'.$action.'"';
      }
    } else {
      $behaviourText = ' onchange="'.$behaviours.'"';
    }
  }
  ?>
  <select name="<?php echo $name ?>" id="<?php echo $name ?>"<?php echo $behaviourText ?>>
  <?php
  foreach ( $contents as $contentValue => $contentText ) {
  ?>
    <option value="<?php echo $contentValue ?>"<?php if ( $value == $contentValue ) { ?> selected="selected"<?php } ?>><?php echo validHtmlStr($contentText) ?></option>
  <?php
  }
  ?>
  </select>
  <?php
  $html = ob_get_contents();
  ob_end_clean();

  return( $html );
}

function getFormChanges( $values, $newValues, $types=false, $columns=false ) {
  $changes = array();
  if ( !$types )
    $types = array();

  foreach( $newValues as $key=>$value ) {
    if ( $columns && !isset($columns[$key]) )
      continue;

    if ( !isset($types[$key]) )
      $types[$key] = false;

    switch( $types[$key] ) {
      case 'set' :
        {
          if ( is_array($newValues[$key]) ) {
            if ( (!isset($values[$key])) or ( join(',',$newValues[$key]) != $values[$key] ) ) {
              $changes[$key] = "`$key` = ".dbEscape(join(',',$newValues[$key]));
            }
          } else if ( (!isset($values[$key])) or $values[$key] ) {
            $changes[$key] = "`$key` = ''";
          }
          break;
        }
      case 'image' :
        {
          if ( is_array( $newValues[$key] ) ) {
            $imageData = getimagesize( $newValues[$key]['tmp_name'] );
            $changes[$key.'Width'] = $key.'Width = '.$imageData[0];
            $changes[$key.'Height'] = $key.'Height = '.$imageData[1];
            $changes[$key.'Type'] = $key."Type = '".$newValues[$key]['type']."'";
            $changes[$key.'Size'] = $key.'Size = '.$newValues[$key]['size'];
            ob_start();
            readfile( $newValues[$key]['tmp_name'] );
            $changes[$key] = $key." = ".dbEscape( ob_get_contents() );
            ob_end_clean();
          } else {
            $changes[$key] = "$key = ".dbEscape($value);
          }
          break;
        }
      case 'document' :
        {
          if ( is_array( $newValues[$key] ) ) {
            $imageData = getimagesize( $newValues[$key]['tmp_name'] );
            $changes[$key.'Type'] = $key."Type = '".$newValues[$key]['type']."'";
            $changes[$key.'Size'] = $key.'Size = '.$newValues[$key]['size'];
            ob_start();
            readfile( $newValues[$key]['tmp_name'] );
            $changes[$key] = $key.' = '.dbEscape( ob_get_contents() );
            ob_end_clean();
          } else {
            $changes[$key] = $key . ' = '.dbEscape($value);
          }
          break;
        }
      case 'file' :
        {
          $changes[$key.'Type'] = $key.'Type = '.dbEscape($newValues[$key]['type']);
          $changes[$key.'Size'] = $key.'Size = '.dbEscape($newValues[$key]['size']);
          ob_start();
          readfile( $newValues[$key]['tmp_name'] );
          $changes[$key] = $key." = '".dbEscape( ob_get_contents() )."'";
          ob_end_clean();
          break;
        }
      case 'raw' :
        {
          if ( (!isset($values[$key])) or ($values[$key] != $value) ) {
            $changes[$key] = $key . ' = '.dbEscape($value);
          }
          break;
        }
      case 'toggle' :
        if ( (!isset($values[$key])) or $values[$key] != $value ) {
          if ( empty($value) ) {
            $changes[$key] = "$key = 0";
          } else {
            $changes[$key] = "$key = 1";
            //$changes[$key] = $key . ' = '.dbEscape(trim($value));
          }
        }
        break;
      default :
        {
          if ( !isset($values[$key]) || ($values[$key] != $value) ) {
            if ( ! isset($value) || $value == '' ) {
              $changes[$key] = "`$key` = NULL";
            } else {
              $changes[$key] = "`$key` = ".dbEscape(trim($value));
            }
          }
          break;
        }
    } // end switch
  } // end foreach newvalues
  foreach( $values as $key=>$value ) {
    if ( !empty($columns[$key]) ) {
      if ( !empty($types[$key]) ) {
        if ( $types[$key] == 'toggle' ) {
          if ( !isset($newValues[$key]) && !empty($value) ) {
            $changes[$key] = "$key = 0";
          }
        } else if ( $types[$key] == 'set' ) {
          $changes[$key] = "$key = ''";
        }
      }
    }
  }
  return( $changes );
}

function getBrowser( &$browser, &$version ) {
  if ( isset($_SESSION['browser']) ) {
    $browser = $_SESSION['browser'];
    $version = $_SESSION['version'];
  } else {
    if (( preg_match( '/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $logVersion)) || (preg_match( '/.*Trident.*rv:(.*?)(;|\))/', $_SERVER['HTTP_USER_AGENT'], $logVersion))) {
      $version = $logVersion[1];
      $browser = 'ie';
    } elseif ( preg_match( '/Chrome\/([0-9.]+)/', $_SERVER['HTTP_USER_AGENT'], $logVersion) ) {
      $version = $logVersion[1];
      // Check for old version of Chrome with bug 5876
      if ( $version < 7 ) {
        $browser = 'oldchrome';
      } else {
        $browser = 'chrome';
      }
    } elseif ( preg_match( '/Safari\/([0-9.]+)/', $_SERVER['HTTP_USER_AGENT'], $logVersion) ) {
      $version = $logVersion[1];
      $browser = 'safari';
    } elseif ( preg_match( '/Opera[ \/]([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT'], $logVersion) ) {
      $version = $logVersion[1];
      $browser = 'opera';
    } elseif ( preg_match( '/Konqueror\/([0-9.]+)/', $_SERVER['HTTP_USER_AGENT'], $logVersion) ) {
      $version = $logVersion[1];
      $browser = 'konqueror';
    } elseif ( preg_match( '/Mozilla\/([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT'], $logVersion) ) {
      $version = $logVersion[1];
      $browser = 'mozilla';
    } else {
      $version = 0;
      $browser = 'unknown';
    }
    $_SESSION['browser'] = $browser;
    $_SESSION['version'] = $version;
  }
}

function isMozilla() {
  getBrowser( $browser, $version );

  return( $browser == 'mozilla' );
}

function isKonqueror() {
  getBrowser( $browser, $version );

  return( $browser == 'konqueror' );
}

function isInternetExplorer() {
  getBrowser( $browser, $version );

  return( $browser == 'ie' );
}

function isOldChrome() {
  getBrowser( $browser, $version );

  return( $browser == 'oldchrome' );
}

function isChrome() {
  getBrowser( $browser, $version );

  return( $browser == 'chrome' );
}

function isOpera() {
  getBrowser( $browser, $version );

  return( $browser == 'opera' );
}

function isSafari() {
  getBrowser( $browser, $version );

  return( $browser == 'safari' );
}

function isWindows() {
  return ( preg_match( '/Win/', $_SERVER['HTTP_USER_AGENT'] ) );
}

function canStreamIframe() {
  return( isKonqueror() );
}

function canStreamNative() {
  // Old versions of Chrome can display the stream, but then it blocks everything else (Chrome bug 5876)
  return( ZM_WEB_CAN_STREAM == 'yes' || ( ZM_WEB_CAN_STREAM == 'auto' && (!isInternetExplorer() && !isOldChrome()) ) );
}

function canStreamApplet() {
  if ( (ZM_OPT_CAMBOZOLA && !file_exists( ZM_PATH_WEB.'/'.ZM_PATH_CAMBOZOLA )) ) {
    Warning ( 'ZM_OPT_CAMBOZOLA is enabled, but the system cannot find '.ZM_PATH_WEB.'/'.ZM_PATH_CAMBOZOLA );
  }

  return( (ZM_OPT_CAMBOZOLA && file_exists( ZM_PATH_WEB.'/'.ZM_PATH_CAMBOZOLA )) );
}

function canStream() {
  return( canStreamNative() | canStreamApplet() );
}

function packageControl( $command ) {
  $string = ZM_PATH_BIN.'/zmpkg.pl '.escapeshellarg( $command );
  $string .= ' 2>/dev/null >&- <&- >/dev/null';
  exec( $string );
}

function daemonControl( $command, $daemon=false, $args=false ) {
  $string = escapeshellcmd(ZM_PATH_BIN).'/zmdc.pl '.$command;
  if ( $daemon ) {
    $string .= ' ' . $daemon;
    if ( $args ) {
      $string .= ' ' . $args;
    }
  }
  $string = escapeshellcmd( $string );
  #$string .= ' 2>/dev/null >&- <&- >/dev/null';
Logger::Debug("daemonControl $string");
  exec( $string );
}

function zmcControl($monitor, $mode=false) {
  $Monitor = new Monitor( $monitor );
  return $Monitor->zmcControl($mode);
}

function zmaControl($monitor, $mode=false) {
  $Monitor = new Monitor($monitor);
  return $Monitor->zmaControl($mode);
}

function initDaemonStatus() {
  global $daemon_status;

  if ( !isset($daemon_status) ) {
    if ( daemonCheck() ) {
      $string = ZM_PATH_BIN."/zmdc.pl status";
      $daemon_status = shell_exec( $string );
    } else {
      $daemon_status = '';
    }
  }
}

function daemonStatus( $daemon, $args=false ) {
  global $daemon_status;

  initDaemonStatus();

  $string = $daemon;
  if ( $args )
    $string .= ' ' . $args;
  return( strpos( $daemon_status, "'$string' running" ) !== false );
}

function zmcStatus( $monitor ) {
  if ( $monitor['Type'] == 'Local' ) {
    $zmcArgs = '-d '.$monitor['Device'];
  } else {
    $zmcArgs = '-m '.$monitor['Id'];
  }
  return( daemonStatus( 'zmc', $zmcArgs ) );
}

function zmaStatus( $monitor ) {
  if ( is_array( $monitor ) ) {
    $monitor = $monitor['Id'];
  }
  return( daemonStatus( 'zma', "-m $monitor" ) );
}

function daemonCheck( $daemon=false, $args=false ) {
  $string = ZM_PATH_BIN.'/zmdc.pl check';
  if ( $daemon ) {
    $string .= ' ' . $daemon;
    if ( $args )
      $string .= ' '. $args;
  }
  $string = escapeshellcmd( $string );
  $result = exec( $string );
  return( preg_match( '/running/', $result ) );
}

function zmcCheck( $monitor ) {
  if ( $monitor['Type'] == 'Local' ) {
    $zmcArgs = '-d '.$monitor['Device'];
  } else {
    $zmcArgs = '-m '.$monitor['Id'];
  }
  return( daemonCheck( 'zmc', $zmcArgs ) );
}

function zmaCheck( $monitor ) {
  if ( is_array( $monitor ) ) {
    $monitor = $monitor['Id'];
  }
  return( daemonCheck( 'zma', "-m $monitor" ) );
}

function getImageSrc( $event, $frame, $scale=SCALE_BASE, $captureOnly=false, $overwrite=false ) {
  $Event = new Event( $event );
  return $Event->getImageSrc( $frame, $scale, $captureOnly, $overwrite );
}

function viewImagePath( $path, $querySep='&amp;' ) {
  if ( strncmp( $path, ZM_DIR_IMAGES, strlen(ZM_DIR_IMAGES) ) == 0 ) {
    // Thumbnails
    return( $path );
  } elseif ( strpos( ZM_DIR_EVENTS, '/' ) === 0 ) {
    return( '?view=image'.$querySep.'path='.$path );
  }
  return( ZM_DIR_EVENTS.'/'.$path );
}

function createListThumbnail( $event, $overwrite=false ) {
  # Load the frame with the highest score to use as a thumbnail
  if ( !($frame = dbFetchOne( "SELECT * FROM Frames WHERE EventId=? AND Score=? ORDER BY FrameId LIMIT 1", NULL, array( $event['Id'], $event['MaxScore'] ) )) )
    return( false );

  $frameId = $frame['FrameId'];

  if ( ZM_WEB_LIST_THUMB_WIDTH ) {
    $thumbWidth = ZM_WEB_LIST_THUMB_WIDTH;
    $scale = (SCALE_BASE*ZM_WEB_LIST_THUMB_WIDTH)/$event['Width'];
    $thumbHeight = reScale( $event['Height'], $scale );
  } elseif ( ZM_WEB_LIST_THUMB_HEIGHT ) {
    $thumbHeight = ZM_WEB_LIST_THUMB_HEIGHT;
    $scale = (SCALE_BASE*ZM_WEB_LIST_THUMB_HEIGHT)/$event['Height'];
    $thumbWidth = reScale( $event['Width'], $scale );
  } else {
    Fatal( "No thumbnail width or height specified, please check in Options->Web" );
  }

  $imageData = getImageSrc( $event, $frame, $scale, false, $overwrite );
  if ( ! $imageData ) {
    return ( false );
  }

  $thumbData = $frame;
  $thumbData['Path'] = $imageData['thumbPath'];
  $thumbData['Width'] = (int)$thumbWidth;
  $thumbData['Height'] = (int)$thumbHeight;

  return( $thumbData );
}

function createVideo( $event, $format, $rate, $scale, $overwrite=false ) {
  $command = ZM_PATH_BIN."/zmvideo.pl -e ".$event['Id']." -f ".$format." -r ".sprintf( "%.2F", ($rate/RATE_BASE) );
  if ( preg_match( '/\d+x\d+/', $scale ) )
    $command .= " -S ".$scale;
  else
    if ( version_compare( phpversion(), "4.3.10", ">=") )
      $command .= " -s ".sprintf( "%.2F", ($scale/SCALE_BASE) );
    else
      $command .= " -s ".sprintf( "%.2f", ($scale/SCALE_BASE) );
  if ( $overwrite )
    $command .= " -o";
  $command = escapeshellcmd( $command );
  $result = exec( $command, $output, $status );
Logger::Debug("generating Video $command: result($result outptu:(".implode("\n", $output )." status($status");
  return( $status?"":rtrim($result) );
}

function executeFilter( $filter ) {
  $command = ZM_PATH_BIN."/zmfilter.pl --filter ".escapeshellarg($filter);
  $result = exec( $command, $output, $status );
  dbQuery( "delete from Filters where Name like '_TempFilter%'" );
  return( $status );
}

# This takes more than one scale amount, so it runs through each and alters dimension.
# I can't imagine why you would want to do that.
function reScale( $dimension, $dummy ) {
  $new_dimension = $dimension;
  for ( $i = 1; $i < func_num_args(); $i++ ) {
    $scale = func_get_arg( $i );
    if ( !empty($scale) && $scale != SCALE_BASE )
      $new_dimension = (int)(($new_dimension*$scale)/SCALE_BASE);
  }
  return( $new_dimension );
}

function deScale( $dimension, $dummy ) {
  $new_dimension = $dimension;
  for ( $i = 1; $i < func_num_args(); $i++ ) {
    $scale = func_get_arg( $i );
    if ( !empty($scale) && $scale != SCALE_BASE )
      $new_dimension = (int)(($new_dimension*SCALE_BASE)/$scale);
  }
  return( $new_dimension );
}

function monitorLimitSql() {
  global $user;
  if ( !empty($user['MonitorIds']) )
    $midSql = " and MonitorId in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
  else
    $midSql = '';
  return( $midSql );
}


function parseSort( $saveToSession=false, $querySep='&amp;' ) {
  global $sortQuery, $sortColumn, $sortOrder, $limitQuery; // Outputs
  if (isset($_REQUEST['filter']['Query']['sort_field'])) { //Handle both new and legacy filter passing
    $_REQUEST['sort_field'] = $_REQUEST['filter']['Query']['sort_field'];
  }
  if (isset($_REQUEST['filter']['Query']['sort_asc'])) {
    $_REQUEST['sort_asc'] = $_REQUEST['filter']['Query']['sort_asc'];
  }
  if (isset($_REQUEST['filter']['Query']['limit'])) {
    $_REQUEST['limit'] = $_REQUEST['filter']['Query']['limit'];
  }
  if ( empty($_REQUEST['sort_field']) ) {
    $_REQUEST['sort_field'] = ZM_WEB_EVENT_SORT_FIELD;
    $_REQUEST['sort_asc'] = (ZM_WEB_EVENT_SORT_ORDER == 'asc');
  }
  switch( $_REQUEST['sort_field'] ) {
    case 'Id' :
      $sortColumn = 'E.Id';
      break;
    case 'MonitorName' :
      $sortColumn = 'M.Name';
      break;
    case 'Name' :
      $sortColumn = 'E.Name';
      break;
    case 'Cause' :
      $sortColumn = 'E.Cause';
      break;
    case 'DateTime' :
      $sortColumn = 'E.StartTime';
      $_REQUEST['sort_field'] = 'StartTime';
      break;
    case 'DiskSpace' :
      $sortColumn = 'E.DiskSpace';
      break;
    case 'StartTime' :
      $sortColumn = 'E.StartTime';
      break;
    case 'EndTime' :
      $sortColumn = 'E.EndTime';
      break;
    case 'Length' :
      $sortColumn = 'E.Length';
      break;
    case 'Frames' :
      $sortColumn = 'E.Frames';
      break;
    case 'AlarmFrames' :
      $sortColumn = 'E.AlarmFrames';
      break;
    case 'TotScore' :
      $sortColumn = 'E.TotScore';
      break;
    case 'AvgScore' :
      $sortColumn = 'E.AvgScore';
      break;
    case 'MaxScore' :
      $sortColumn = 'E.MaxScore';
      break;
    default:
      $sortColumn = 'E.StartTime';
      break;
  }
  $sortOrder = $_REQUEST['sort_asc']?'asc':'desc';
  if ( !$_REQUEST['sort_asc'] )
    $_REQUEST['sort_asc'] = 0;
  $sortQuery = $querySep.'sort_field='.validHtmlStr($_REQUEST['sort_field']).$querySep.'sort_asc='.validHtmlStr($_REQUEST['sort_asc']);
  if ( !isset($_REQUEST['limit']) )
    $_REQUEST['limit'] = '';
  if ( $saveToSession ) {
    $_SESSION['sort_field'] = validHtmlStr($_REQUEST['sort_field']);
    $_SESSION['sort_asc'] = validHtmlStr($_REQUEST['sort_asc']);
  }
  if ($_REQUEST['limit'] != '') {
    $limitQuery = "&limit=".$_REQUEST['limit'];
  }
}

function parseFilter(&$filter, $saveToSession=false, $querySep='&amp;') {
  $filter['query'] = ''; 
  $filter['sql'] = '';
  $filter['fields'] = '';

  $StorageArea = NULL;

  $terms = isset($filter['Query']) ? $filter['Query']['terms'] : NULL;

  if ( isset($terms) && count($terms) ) {
    for ( $i = 0; $i < count($terms); $i++ ) {
      if ( isset($terms[$i]['cnj']) ) {
        $filter['query'] .= $querySep.urlencode("filter[Query][terms][$i][cnj]").'='.urlencode($terms[$i]['cnj']);
        $filter['sql'] .= ' '.$terms[$i]['cnj'].' ';
        $filter['fields'] .= "<input type=\"hidden\" name=\"filter[Query][terms][$i][cnj]\" value=\"".htmlspecialchars($terms[$i]['cnj'])."\"/>\n";
      }
      if ( isset($terms[$i]['obr']) ) {
        $filter['query'] .= $querySep.urlencode("filter[Query][terms][$i][obr]").'='.urlencode($terms[$i]['obr']);
        $filter['sql'] .= ' '.str_repeat('(', $terms[$i]['obr']).' ';
        $filter['fields'] .= "<input type=\"hidden\" name=\"filter[Query][terms][$i][obr]\" value=\"".htmlspecialchars($terms[$i]['obr'])."\"/>\n";
      }
      if ( isset($terms[$i]['attr']) ) {
        $filter['query'] .= $querySep.urlencode("filter[Query][terms][$i][attr]").'='.urlencode($terms[$i]['attr']);
        $filter['fields'] .= "<input type=\"hidden\" name=\"filter[Query][terms][$i][attr]\" value=\"".htmlspecialchars($terms[$i]['attr'])."\"/>\n";
        switch ( $terms[$i]['attr'] ) {
          case 'MonitorName':
            $filter['sql'] .= 'M.'.preg_replace('/^Monitor/', '', $terms[$i]['attr']);
            break;
          case 'ServerId':
          case 'MonitorServerId':
            $filter['sql'] .= 'M.ServerId';
            break;
          case 'StorageServerId':
            $filter['sql'] .= 'S.ServerId';
            break;
          case 'FilterServerId':
            $filter['sql'] .= ZM_SERVER_ID;
            break;
# Unspecified start or end, so assume start, this is to support legacy filters
          case 'DateTime':
            $filter['sql'] .= 'E.StartTime';
            break;
          case 'Date':
            $filter['sql'] .= 'to_days( E.StartTime )';
            break;
          case 'Time':
            $filter['sql'] .= 'extract( hour_second from E.StartTime )';
            break;
          case 'Weekday':
            $filter['sql'] .= 'weekday( E.StartTime )';
            break;
# Starting Time
          case 'StartDateTime':
            $filter['sql'] .= 'E.StartTime';
            break;
          case 'StartDate':
            $filter['sql'] .= 'to_days( E.StartTime )';
            break;
          case 'StartTime':
            $filter['sql'] .= 'extract( hour_second from E.StartTime )';
            break;
          case 'StartWeekday':
            $filter['sql'] .= 'weekday( E.StartTime )';
            break;
# Ending Time
          case 'EndDateTime':
            $filter['sql'] .= 'E.EndTime';
            break;
          case 'EndDate':
            $filter['sql'] .= 'to_days( E.EndTime )';
            break;
          case 'EndTime':
            $filter['sql'] .= 'extract( hour_second from E.EndTime )';
            break;
          case 'EndWeekday':
            $filter['sql'] .= 'weekday( E.EndTime )';
            break;
          case 'Id':
          case 'Name':
          case 'DiskSpace':
          case 'MonitorId':
          case 'StorageId':
          case 'Length':
          case 'Frames':
          case 'AlarmFrames':
          case 'TotScore':
          case 'AvgScore':
          case 'MaxScore':
          case 'Cause':
          case 'Notes':
          case 'StateId':
          case 'Archived':
            $filter['sql'] .= 'E.'.$terms[$i]['attr'];
            break;
          case 'DiskPercent':
            // Need to specify a storage area, so need to look through other terms looking for a storage area, else we default to ZM_EVENTS_PATH
            if ( ! $StorageArea ) {
              for ( $j = 0; $j < count($terms); $j++ ) {
                if ( isset($terms[$j]['attr']) and $terms[$j]['attr'] == 'StorageId' and isset($terms[$j]['val']) ) {
                  $StorageArea = new Storage($terms[$j]['val']);
                  break;
                }
              } // end foreach remaining term
              if ( ! $StorageArea ) $StorageArea = new Storage();
            } // end no StorageArea found yet

            $filter['sql'] .= getDiskPercent( $StorageArea->Path() );
            break;
          case 'DiskBlocks':
            // Need to specify a storage area, so need to look through other terms looking for a storage area, else we default to ZM_EVENTS_PATH
            if ( ! $StorageArea ) {
              for ( $j = $i; $j < count($terms); $j++ ) {
                if ( isset($terms[$i]['attr']) and $terms[$i]['attr'] == 'StorageId' and isset($terms[$j]['val']) ) {
                  $StorageArea = new Storage($terms[$i]['val']);
                }
              } // end foreach remaining term
            } // end no StorageArea found yet
            $filter['sql'] .= getDiskBlocks( $StorageArea );
            break;
          case 'SystemLoad':
            $filter['sql'] .= getLoad();
            break;
        }
        $valueList = array();
        foreach ( preg_split( '/["\'\s]*?,["\'\s]*?/', preg_replace( '/^["\']+?(.+)["\']+?$/', '$1', $terms[$i]['val'] ) ) as $value ) {
          switch ( $terms[$i]['attr'] ) {
            case 'MonitorName':
            case 'Name':
            case 'Cause':
            case 'Notes':
              $value = dbEscape($value);
              break;
            case 'MonitorServerId':
            case 'FilterServerId':
            case 'StorageServerId':
            case 'ServerId':
              if ( $value == 'ZM_SERVER_ID' ) {
                $value = ZM_SERVER_ID;
              } else if ( $value == 'NULL' ) {

              } else {
                $value = dbEscape($value);
              }
              break;
            case 'StorageId':
              $StorageArea = new Storage( $value );
              if ( $value != 'NULL' )
                $value = dbEscape($value);
              break;
            case 'DateTime':
            case 'StartDateTime':
            case 'EndDateTime':
              if ( $value != 'NULL' )
                $value = "'".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."'";
              break;
            case 'Date':
            case 'StartDate':
            case 'EndDate':
              if ( $value != 'NULL' )
                $value = "to_days( '".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."' )";
              break;
            case 'Time':
            case 'StartTime':
            case 'EndTime':
              if ( $value != 'NULL' )
              $value = "extract( hour_second from '".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."' )";
              break;
            default :
              if ( $value != 'NULL' )
                $value = dbEscape($value);
              break;
          }
          $valueList[] = $value;
        }

        switch ( $terms[$i]['op'] ) {
          case '=' :
          case '!=' :
          case '>=' :
          case '>' :
          case '<' :
          case '<=' :
            $filter['sql'] .= ' '.$terms[$i]['op'].' '. $value;
            break;
          case '=~' :
            $filter['sql'] .= ' regexp '.$value;
            break;
          case '!~' :
            $filter['sql'] .= ' not regexp '.$value;
            break;
          case '=[]' :
          case 'IN' :
            $filter['sql'] .= ' in ('.join( ',', $valueList ).')';
            break;
          case '![]' :
            $filter['sql'] .= ' not in ('.join( ',', $valueList ).')';
            break;
          case 'IS' :
            if ( $value == 'Odd' )  {
              $filter['sql'] .= ' % 2 = 1';
            } else if ( $value == 'Even' )  {
              $filter['sql'] .= ' % 2 = 0';
            } else {
              $filter['sql'] .= " IS $value";
            }
            break;
          case 'IS NOT' :
            $filter['sql'] .= " IS NOT $value";
            break;
          default:
            Warning("Invalid operator in filter: " . $terms[$i]['op'] );
        }

        $filter['query'] .= $querySep.urlencode("filter[Query][terms][$i][op]").'='.urlencode($terms[$i]['op']);
        $filter['fields'] .= "<input type=\"hidden\" name=\"filter[Query][terms][$i][op]\" value=\"".htmlspecialchars($terms[$i]['op'])."\"/>\n";
	if ( isset($terms[$i]['val']) ) {
		$filter['query'] .= $querySep.urlencode("filter[Query][terms][$i][val]").'='.urlencode($terms[$i]['val']);
		$filter['fields'] .= "<input type=\"hidden\" name=\"filter[Query][terms][$i][val]\" value=\"".htmlspecialchars($terms[$i]['val'])."\"/>\n";
	}
      } // end foreach term
      if ( isset($terms[$i]['cbr']) ) {
        $filter['query'] .= $querySep.urlencode("filter[Query][terms][$i][cbr]").'='.urlencode($terms[$i]['cbr']);
        $filter['sql'] .= ' '.str_repeat( ')', $terms[$i]['cbr'] ).' ';
        $filter['fields'] .= "<input type=\"hidden\" name=\"filter[Query][terms][$i][cbr]\" value=\"".htmlspecialchars($terms[$i]['cbr'])."\"/>\n";
      }
    }
    if ( $filter['sql'] )
      $filter['sql'] = ' and ( '.$filter['sql'].' )';
    if ( $saveToSession ) {
      $_SESSION['filter'] = $filter;
    }
  }
}

// Please note that the filter is passed in by copy, so you need to use the return value from this function.
//
function addFilterTerm( $filter, $position, $term=false ) {
  if ( $position < 0 )
    $position = 0;
  
  if ( ! isset( $filter['Query']['terms'] ) )
    $filter['Query']['terms'] = array();
  elseif( $position > count($filter['Query']['terms']) )
    $position = count($filter['Query']['terms']);
  if ( $term && $position == 0 )
    unset( $term['cnj'] );
  array_splice( $filter['Query']['terms'], $position, 0, array( $term?$term:array() ) );

  return( $filter );
}

function delFilterTerm( $filter, $position ) {
  if ( $position < 0 )
    $position = 0;
  elseif( $position >= count($filter['Query']['terms']) )
    $position = count($filter['Query']['terms']);
  array_splice( $filter['Query']['terms'], $position, 1 );

  return( $filter );
}

function getPagination( $pages, $page, $maxShortcuts, $query, $querySep='&amp;' ) {
  global $view;

  $pageText = '';
  if ( $pages > 1 ) {
    if ( $page ) {
      if ( $page < 0 )
        $page = 1;
      if ( $page > $pages )
        $page = $pages;

      if ( $page > 1 ) {
        if ( false && $page > 2 ) {
          $pageText .= '<a href="?view='.$view.$querySep.'page=1'.$query.'">&lt;&lt;</a>';
        }
        $pageText .= '<a href="?view='.$view.$querySep.'page='.($page-1).$query.'">&lt;</a>';

        $newPages = array();
        $pagesUsed = array();
        $lo_exp = max(2,log($page-1)/log($maxShortcuts));
        for ( $i = 0; $i < $maxShortcuts; $i++ ) {
          $newPage = round($page-pow($lo_exp,$i));
          if ( isset($pagesUsed[$newPage]) )
            continue;
          if ( $newPage <= 1 )
            break;
          $pagesUsed[$newPage] = true;
          array_unshift( $newPages, $newPage );
        }
        if ( !isset($pagesUsed[1]) )
          array_unshift( $newPages, 1 );

        foreach ( $newPages as $newPage ) {
          $pageText .= '<a href="?view='.$view.$querySep.'page='.$newPage.$query.'">'.$newPage.'</a>&nbsp;';
        }

      }
      $pageText .= '-&nbsp;'.$page.'&nbsp;-';
      if ( $page < $pages ) {
        $newPages = array();
        $pagesUsed = array();
        $hi_exp = max(2,log($pages-$page)/log($maxShortcuts));
        for ( $i = 0; $i < $maxShortcuts; $i++ ) {
          $newPage = round($page+pow($hi_exp,$i));
          if ( isset($pagesUsed[$newPage]) )
            continue;
          if ( $newPage > $pages )
            break;
          $pagesUsed[$newPage] = true;
          array_push( $newPages, $newPage );
        }
        if ( !isset($pagesUsed[$pages]) )
          array_push( $newPages, $pages );

        foreach ( $newPages as $newPage ) {
          $pageText .= '&nbsp;<a href="?view='.$view.$querySep.'page='.$newPage.$query.'">'.$newPage.'</a>';
        }
        $pageText .= '<a href="?view='.$view.$querySep.'page='.($page+1).$query.'">&gt;</a>';
        if ( false && $page < ($pages-1) ) {
          $pageText .= '<a href="?view='.$view.$querySep.'page='.$pages.$query.'">&gt;&gt;</a>';
        }
      }
    }
  }
  return( $pageText );
}

function sortHeader( $field, $querySep='&amp;' ) {
  global $view;
  return( '?view='.$view.$querySep.'page=1'.$_REQUEST['filter']['query'].$querySep.'sort_field='.$field.$querySep.'sort_asc='.($_REQUEST['sort_field'] == $field?!$_REQUEST['sort_asc']:0).$querySep.'limit='.$_REQUEST['limit'] );
}

function sortTag( $field ) {
  if ( $_REQUEST['sort_field'] == $field )
    if ( $_REQUEST['sort_asc'] )
      return( '(^)' );
    else
      return( '(v)' );
  return( false );
}

function getLoad() {
  $load = sys_getloadavg();
  return( $load[0] );
}

function getDiskPercent($path = ZM_DIR_EVENTS) {
  $total = disk_total_space($path);
  if ( $total === false ) {
    Error('disk_total_space returned false. Verify the web account user has access to ' . $path );
    return 0;
  } elseif ( $total == 0 ) {
    Error('disk_total_space indicates the following path has a filesystem size of zero bytes ' . $path );
    return 100;
  }
  $free = disk_free_space($path);
  if ( $free === false ) {
    Error('disk_free_space returned false. Verify the web account user has access to ' . $path );
  }
  $space = round((($total - $free) / $total) * 100);
  return( $space );
}

function getDiskBlocks() {
  if ( ! $StorageArea ) $StorageArea = new Storage();
  $df = shell_exec( 'df '.escapeshellarg($StorageArea->Path() ));
  $space = -1;
  if ( preg_match( '/\s(\d+)\s+\d+\s+\d+%/ms', $df, $matches ) )
    $space = $matches[1];
  return( $space );
}

function systemStats() {

    $load = getLoad();
    $diskPercent = getDiskPercent();
    $pathMapPercent = getDiskPercent(ZM_PATH_MAP);
    $cpus = getcpus();

    $normalized_load = $load / $cpus;

    # Colorize the system load stat
    if ( $normalized_load <= 0.75 ) {
        $htmlLoad=$load;
    } elseif ( $normalized_load <= 0.9 ) {
        $htmlLoad="<span class=\"warning\">$load</span>";
    } elseif ( $normalized_load <= 1.1 ) {
        $htmlLoad="<span class=\"error\">$load</span>";
    } else {
        $htmlLoad="<span class=\"critical\">$load</span>";
    }

    # Colorize the disk space stat
    if ( $diskPercent < 98 ) {
        $htmlDiskPercent="$diskPercent%";
    } elseif ( $diskPercent <= 99 ) {
        $htmlDiskPercent="<span class=\"warning\">$diskPercent%</span>";
    } else {
        $htmlDiskPercent="<span class=\"error\">$diskPercent%</span>";
    }

    # Colorize the PATH_MAP (usually /dev/shm) stat
    if ( $pathMapPercent < 90 ) {
        if ( disk_free_space(ZM_PATH_MAP) > 209715200 ) { # have to always have at least 200MiB free
            $htmlPathMapPercent="$pathMapPercent%";
        } else {
            $htmlPathMapPercent="<span class=\"warning\">$pathMapPercent%</span>";
        }
    } elseif ( $pathMapPercent < 100 ) {
        $htmlPathMapPercent="<span class=\"warning\">$pathMapPercent%</span>";
    } else {
        $htmlPathMapPercent="<span class=\"critical\">$pathMapPercent%</span>";
    }

    $htmlString = translate('Load').": $htmlLoad - ".translate('Disk').": $htmlDiskPercent - ".ZM_PATH_MAP.": $htmlPathMapPercent";

    return( $htmlString );
}

function getcpus() {

    if (is_readable("/proc/cpuinfo") ) { # Works on Linux
        preg_match_all('/^processor/m', file_get_contents('/proc/cpuinfo'), $matches); 
        $num_cpus = count($matches[0]);
    } else { # Works on BSD
        $matches = explode(":", shell_exec("sysctl hw.ncpu"));
        $num_cpus = trim($matches[1]);
    }

    return( $num_cpus );
}

// Function to fix a problem whereby the built in PHP session handling 
// features want to put the sid as a hidden field after the form or 
// fieldset tag, neither of which will work with strict XHTML Basic.
function sidField() {
  if ( SID ) {
    list( $sessname, $sessid ) = explode( "=", SID );
?>
    <input type="hidden" name="<?php echo $sessname ?>" value="<?php echo $sessid ?>"/>
<?php
  }
}

function verNum( $version ) {
  $vNum = '';
  $maxFields = 3;
  $vFields = explode( '.', $version );
  array_splice( $vFields, $maxFields );
  while ( count($vFields) < $maxFields ) {
    $vFields[] = 0;
  }
  foreach ( $vFields as $vField ) {
    $vField = sprintf( '%02d', $vField );
    while ( strlen($vField) < 2 ) {
      $vField = '0'.$vField;
    }
    $vNum .= $vField;
  }
  return( $vNum );
}

function fixSequences() {
  $sequence = 1;
  $sql = 'select * from Monitors order by Sequence asc, Id asc';
  foreach( dbFetchAll( $sql ) as $monitor ) {
    if ( $monitor['Sequence'] != $sequence ) {
      dbQuery( 'update Monitors set Sequence = ? WHERE Id=?', array( $sequence, $monitor['Id'] ) );
    }
    $sequence++;
  }
}

function firstSet() {
  foreach ( func_get_args() as $arg ) {
    if ( !empty( $arg ) )
      return( $arg );
  }
}

function linesIntersect( $line1, $line2 ) {
  global $debug;

  $min_x1 = min( $line1[0]['x'], $line1[1]['x'] );
  $max_x1 = max( $line1[0]['x'], $line1[1]['x'] );
  $min_x2 = min( $line2[0]['x'], $line2[1]['x'] );
  $max_x2 = max( $line2[0]['x'], $line2[1]['x'] );
  $min_y1 = min( $line1[0]['y'], $line1[1]['y'] );
  $max_y1 = max( $line1[0]['y'], $line1[1]['y'] );
  $min_y2 = min( $line2[0]['y'], $line2[1]['y'] );
  $max_y2 = max( $line2[0]['y'], $line2[1]['y'] );

  // Checking if bounding boxes intersect
  if ( $max_x1 < $min_x2 || $max_x2 < $min_x1 ||$max_y1 < $min_y2 || $max_y2 < $min_y1 ) {
    if ( $debug ) echo 'Not intersecting, out of bounds<br>';
    return( false );
  }

  $dx1 = $line1[1]['x'] - $line1[0]['x'];
  $dy1 = $line1[1]['y'] - $line1[0]['y'];
  $dx2 = $line2[1]['x'] - $line2[0]['x'];
  $dy2 = $line2[1]['y'] - $line2[0]['y'];

  if ( $dx1 ) {
    $m1 = $dy1/$dx1;
    $b1 = $line1[0]['y'] - ($m1 * $line1[0]['x']);
  } else {
    $b1 = $line1[0]['y'];
  }
  if ( $dx2 ) {
    $m2 = $dy2/$dx2;
    $b2 = $line2[0]['y'] - ($m2 * $line2[0]['x']);
  } else {
    $b2 = $line2[0]['y'];
  }

  if ( $dx1 && $dx2 ) { // Both not vertical
    if ( $m1 != $m2 ) { // Not parallel or colinear
      $x = ( $b2 - $b1 ) / ( $m1 - $m2 );

      if ( $x >= $min_x1 && $x <= $max_x1 && $x >= $min_x2 && $x <= $max_x2 ) {
        if ( $debug ) echo "Intersecting, at x $x<br>";
        return( true );
      } else {
        if ( $debug ) echo "Not intersecting, out of range at x $x<br>";
        return( false );
      }
    } elseif ( $b1 == $b2 ) {
      // Colinear, must overlap due to box check, intersect? 
      if ( $debug ) echo 'Intersecting, colinear<br>';
      return( true );
    } else {
      // Parallel
      if ( $debug ) echo 'Not intersecting, parallel<br>';
      return( false );
    }
  } elseif ( !$dx1 ) { // Line 1 is vertical 
    $y = ( $m2 * $line1[0]['x'] ) * $b2;
    if ( $y >= $min_y1 && $y <= $max_y1 ) {
      if ( $debug ) echo "Intersecting, at y $y<br>";
      return( true );
    } else {
      if ( $debug ) echo "Not intersecting, out of range at y $y<br>";
      return( false );
    }
  } elseif ( !$dx2 ) { // Line 2 is vertical 
    $y = ( $m1 * $line2[0]['x'] ) * $b1;
    if ( $y >= $min_y2 && $y <= $max_y2 ) {
      if ( $debug ) echo "Intersecting, at y $y<br>";
      return( true );
    } else {
      if ( $debug ) echo "Not intersecting, out of range at y $y<br>";
      return( false );
    }
  } else { // Both lines are vertical
    if ( $line1[0]['x'] == $line2[0]['x'] ) {
      // Colinear, must overlap due to box check, intersect? 
      if ( $debug ) echo 'Intersecting, vertical, colinear<br>';
      return( true );
    } else {
      // Parallel
      if ( $debug ) echo 'Not intersecting, vertical, parallel<br>';
      return( false );
    }
  }
  if ( $debug ) echo 'Whoops, unexpected scenario<br>';
  return( false );
}

function isSelfIntersecting( $points ) {
  global $debug;

  $n_coords = count($points);
  $edges = array();
  for ( $j = 0, $i = $n_coords-1; $j < $n_coords; $i = $j++ ) {
    $edges[] = array( $points[$i], $points[$j] );
  }

  for ( $i = 0; $i <= ($n_coords-2); $i++ ) {
    for ( $j = $i+2; $j < $n_coords+min(0,$i-1); $j++ ) {
      if ( $debug ) echo "Checking $i and $j<br>";
      if ( linesIntersect( $edges[$i], $edges[$j] ) ) {
        if ( $debug ) echo "Lines $i and $j intersect<br>";
        return( true );
      }
    }
  }
  return( false );
}

function getPolyCentre( $points, $area=0 ) {
  $cx = 0.0;
  $cy = 0.0;
  if ( !$area )
    $area = getPolyArea( $points );
  for ( $i = 0, $j = count($points)-1; $i < count($points); $j = $i++ ) {
    $ct = ($points[$i]['x'] * $points[$j]['y']) - ($points[$j]['x'] * $points[$i]['y']);
    $cx += ($points[$i]['x'] + $points[$j]['x']) * ct;
    $cy += ($points[$i]['y'] + $points[$j]['y']) * ct;
  }
  $cx = intval(round(abs($cx/(6.0*$area))));
  $cy = intval(round(abs($cy/(6.0*$area))));
  printf( "X:%cx, Y:$cy<br>" );
  return( array( 'x'=>$cx, 'y'=>$cy ) );
}

function _CompareXY( $a, $b ) {
  if ( $a['min_y'] == $b['min_y'] )
    return( intval($a['min_x'] - $b['min_x']) );
  else
    return( intval($a['min_y'] - $b['min_y']) );
}

function _CompareX( $a, $b ) {
  return( intval($a['min_x'] - $b['min_x']) );
}

function getPolyArea( $points ) {
  global $debug;

  $n_coords = count($points);
  $global_edges = array();
  for ( $j = 0, $i = $n_coords-1; $j < $n_coords; $i = $j++ ) {
    $x1 = $points[$i]['x'];
    $x2 = $points[$j]['x'];
    $y1 = $points[$i]['y'];
    $y2 = $points[$j]['y'];

    //printf( "x1:%d,y1:%d x2:%d,y2:%d\n", x1, y1, x2, y2 );
    if ( $y1 == $y2 )
      continue;

    $dx = $x2 - $x1;
    $dy = $y2 - $y1;

    $global_edges[] = array(
        'min_y' => $y1<$y2?$y1:$y2,
        'max_y' => ($y1<$y2?$y2:$y1)+1,
        'min_x' => $y1<$y2?$x1:$x2,
        '_1_m' => $dx/$dy,
        );
  }

  usort( $global_edges, '_CompareXY' );

  if ( $debug ) {
    for ( $i = 0; $i < count($global_edges); $i++ ) {
      printf( '%d: min_y: %d, max_y:%d, min_x:%.2f, 1/m:%.2f<br>', $i, $global_edges[$i]['min_y'], $global_edges[$i]['max_y'], $global_edges[$i]['min_x'], $global_edges[$i]['_1_m'] );
    }
  }

  $area = 0.0;
  $active_edges = array();
  $y = $global_edges[0]['min_y'];
  do {
    for ( $i = 0; $i < count($global_edges); $i++ ) {
      if ( $global_edges[$i]['min_y'] == $y ) {
        if ( $debug ) printf( 'Moving global edge<br>' );
        $active_edges[] = $global_edges[$i];
        array_splice( $global_edges, $i, 1 );
        $i--;
      } else {
        break;
      }
    }
    usort( $active_edges, '_CompareX' );
    if ( $debug ) {
      for ( $i = 0; $i < count($active_edges); $i++ ) {
        printf( '%d - %d: min_y: %d, max_y:%d, min_x:%.2f, 1/m:%.2f<br>', $y, $i, $active_edges[$i]['min_y'], $active_edges[$i]['max_y'], $active_edges[$i]['min_x'], $active_edges[$i]['_1_m'] );
      }
    }
    $last_x = 0;
    $row_area = 0;
    $parity = false;
    for ( $i = 0; $i < count($active_edges); $i++ ) {
      $x = intval(round($active_edges[$i]['min_x']));
      if ( $parity ) {
        $row_area += ($x - $last_x)+1;
        $area += $row_area;
      }
      if ( $active_edges[$i]['max_y'] != $y )
        $parity = !$parity;
      $last_x = $x;
    }
    if ( $debug ) printf( '%d: Area:%d<br>', $y, $row_area );
    $y++;
    for ( $i = 0; $i < count($active_edges); $i++ ) {
      if ( $y >= $active_edges[$i]['max_y'] ) { // Or >= as per sheets
        if ( $debug ) printf( 'Deleting active_edge<br>' );
        array_splice( $active_edges, $i, 1 );
        $i--;
      } else {
        $active_edges[$i]['min_x'] += $active_edges[$i]['_1_m'];
      }
    }
  } while ( count($global_edges) || count($active_edges) );
  if ( $debug ) printf( 'Area:%d<br>', $area );
  return( $area );
}

function getPolyAreaOld( $points ) {
  $area = 0.0;
  $edge = 0.0;
  for ( $i = 0, $j = count($points)-1; $i < count($points); $j = $i++ ) {
    $x_diff = ($points[$i]['x'] - $points[$j]['x']);
    $y_diff = ($points[$i]['y'] - $points[$j]['y']);
    $y_sum = ($points[$i]['y'] + $points[$j]['y']);
    $trap_edge = sqrt(pow(abs($x_diff)+1,2) + pow(abs($y_diff)+1,2) );
    $edge += $trap_edge;
    $trap_area = ($x_diff * $y_sum );
    $area += $trap_area;
    printf( "%d->%d, %d-%d=%.2f, %d+%d=%.2f(%.2f), %.2f, %.2f<br>", i, j, $points[$i]['x'], $points[$j]['x'], $x_diff, $points[$i]['y'], $points[$j]['y'], $y_sum, $y_diff, $trap_area, $trap_edge );
  }
  $edge = intval(round(abs($edge)));
  $area = intval(round((abs($area)+$edge)/2));
  echo "E:$edge<br>";
  echo "A:$area<br>";
  return( $area );
}

function mapCoords( $a ) {
  return( $a['x'].",".$a['y'] );
}

function pointsToCoords( $points ) {
  return( join( ' ', array_map( 'mapCoords', $points ) ) );
}

function coordsToPoints( $coords ) {
  $points = array();
  if ( preg_match_all( '/(\d+,\d+)+/', $coords, $matches ) ) {
    for ( $i = 0; $i < count($matches[1]); $i++ ) {
      if ( preg_match( '/(\d+),(\d+)/', $matches[1][$i], $cmatches ) ) {
        $points[] = array( 'x'=>$cmatches[1], 'y'=>$cmatches[2] );
      } else {
        echo( "Bogus coordinates '".$matches[$i]."'" );
        return( false );
      }
    }
  } else {
    echo( "Bogus coordinate string '$coords'" );
    return( false );
  }
  return( $points );
}

function limitPoints( &$points, $min_x, $min_y, $max_x, $max_y ) {
  foreach ( $points as &$point ) {
    if ( $point['x'] < $min_x ) {
      Logger::Debug('Limiting point x'.$point['x'].' to min_x ' . $min_x );
      $point['x'] = $min_x;
    } else if ( $point['x'] > $max_x ) {
      Logger::Debug('Limiting point x'.$point['x'].' to max_x ' . $max_x );
      $point['x'] = $max_x;
    }
    if ( $point['y'] < $min_y ) {
      Logger::Debug('Limiting point y'.$point['y'].' to min_y ' . $min_y );
      $point['y'] = $min_y;
    } else if ( $point['y'] > $max_y ) {
      Logger::Debug('Limiting point y'.$point['y'].' to max_y ' . $max_y );
      $point['y'] = $max_y;
    }
  } // end foreach point
} // end function limitPoints( $points, $min_x, $min_y, $max_x, $max_y )

function scalePoints( &$points, $scale ) {
  foreach ( $points as &$point ) {
    $point['x'] = reScale( $point['x'], $scale );
    $point['y'] = reScale( $point['y'], $scale );
  }
}

function getLanguages() {
  $langs = array();
  foreach ( glob('lang/*_*.php') as $file ) {
    preg_match( '/([^\/]+_.+)\.php/', $file, $matches );
    $langs[$matches[1]] = $matches[1];
  }
  return( $langs );
}

function trimString( $string, $length ) {
  return( preg_replace( '/^(.{'.$length.',}?)\b.*$/', '\\1&hellip;', $string ) );
}

function monitorIdsToNames( $ids ) {
  global $mITN_monitors;
  if ( !$mITN_monitors ) {
    $sql = 'select Id, Name from Monitors';
    foreach( dbFetchAll( $sql ) as $monitor ) {
      $mITN_monitors[$monitor['Id']] = $monitor;
    }
  }
  $names = array();
  if ( ! is_array($ids) ) {
    $ids = preg_split( '/\s*,\s*/', $ids );
  }
  foreach ( $ids as $id ) {
    if ( visibleMonitor( $id ) ) {
      if ( isset($mITN_monitors[$id]) ) {
        $names[] = $mITN_monitors[$id]['Name'];
      }
    }
  }
  $name_string = join( ', ', $names );
  return( $name_string );
}

function initX10Status() {
  global $x10_status;

  if ( !isset($x10_status) ) {
    $socket = socket_create( AF_UNIX, SOCK_STREAM, 0 );
    if ( $socket < 0 ) {
      Fatal( 'socket_create() failed: '.socket_strerror($socket) );
    }
    $sock_file = ZM_PATH_SOCKS.'/zmx10.sock';
    if ( @socket_connect( $socket, $sock_file ) ) {
      $command = 'status';
      if ( !socket_write( $socket, $command ) ) {
        Fatal( "Can't write to control socket: ".socket_strerror(socket_last_error($socket)) );
      }
      socket_shutdown( $socket, 1 );
      $x10Output = '';
      while ( $x10Response = socket_read( $socket, 256 ) ) {
        $x10Output .= $x10Response;
      }
      socket_close( $socket );
    } else {
      // Can't connect so use script
      $command = ZM_PATH_BIN."/zmx10.pl --command status";
      //$command .= " 2>/dev/null >&- <&- >/dev/null";

      $x10Output = exec( escapeshellcmd( $command ) );
    }
    foreach ( explode( "\n", $x10Output ) as $x10Response ) {
      if ( preg_match( "/^(\d+)\s+(.+)$/", $x10Response, $matches ) ) {
        $x10_status[$matches[1]] = $matches[2];
      }
    }
  }
}

function getDeviceStatusX10( $key ) {
  global $x10_status;

  initX10Status();

  if ( empty($x10_status[$key]) || !($status = $x10_status[$key]) )
    $status = 'unknown';
  return( $status );
}

function setDeviceStatusX10( $key, $status ) {
  $socket = socket_create( AF_UNIX, SOCK_STREAM, 0 );
  if ( $socket < 0 ) {
    Fatal( 'socket_create() failed: '.socket_strerror($socket) );
  }
  $sock_file = ZM_PATH_SOCKS.'/zmx10.sock';
  if ( @socket_connect( $socket, $sock_file ) ) {
    $command = "$status;$key";
    if ( !socket_write( $socket, $command ) ) {
      Fatal( "Can't write to control socket: ".socket_strerror(socket_last_error($socket)) );
    }
    socket_shutdown( $socket, 1 );
    $x10Response = socket_read( $socket, 256 );
    socket_close( $socket );
  } else {
    // Can't connect so use script
    $command = ZM_PATH_BIN.'/zmx10.pl --command '.escapeshellarg( $status );
    $command .= ' --unit-code '.escapeshellarg( $key );
    //$command .= " 2>/dev/null >&- <&- >/dev/null";
    $x10Response = exec( $command );
  }
  if ( preg_match( '/^'.$key.'\s+(.*)/', $x10Response, $matches ) )
    $status = $matches[1];
  else
    $status = 'unknown';
  return( $status );
}

function logState() {
  $state = 'ok';

  $levelCounts = array(
      Logger::FATAL => array( ZM_LOG_ALERT_FAT_COUNT, ZM_LOG_ALARM_FAT_COUNT ),
      Logger::ERROR => array( ZM_LOG_ALERT_ERR_COUNT, ZM_LOG_ALARM_ERR_COUNT ),
      Logger::WARNING => array( ZM_LOG_ALERT_WAR_COUNT, ZM_LOG_ALARM_WAR_COUNT ),
      );

  # This is an expensive request, as it has to hit every row of the Logs Table
  $sql = 'SELECT Level, COUNT(Level) AS LevelCount FROM Logs WHERE Level < '.Logger::INFO.' AND TimeKey > unix_timestamp(now() - interval '.ZM_LOG_CHECK_PERIOD.' second) GROUP BY Level ORDER BY Level ASC';
  $counts = dbFetchAll( $sql );

  foreach ( $counts as $count ) {
    if ( $count['Level'] <= Logger::PANIC )
      $count['Level'] = Logger::FATAL;
    if ( !($levelCount = $levelCounts[$count['Level']]) ) {
      Error( "Unexpected Log level ".$count['Level'] );
      next;
    }
    if ( $levelCount[1] && $count['LevelCount'] >= $levelCount[1] ) {
      $state = 'alarm';
      break;
    } elseif ( $levelCount[0] && $count['LevelCount'] >= $levelCount[0] ) {
      $state = 'alert';
    }
  }
  return( $state );
}

function isVector ( &$array ) {
  $next_key = 0;
  foreach ( array_keys($array) as $key ) {
    if ( !is_int( $key ) )
      return( false );
    if ( $key != $next_key++ )
      return( false );
  }
  return( true );
}

function checkJsonError($value) {
  if ( function_exists('json_last_error') ) {
    $value = var_export($value,true);
    switch( json_last_error() ) {
      case JSON_ERROR_DEPTH :
        Fatal( "Unable to decode JSON string '$value', maximum stack depth exceeded" );
      case JSON_ERROR_CTRL_CHAR :
        Fatal( "Unable to decode JSON string '$value', unexpected control character found" );
      case JSON_ERROR_STATE_MISMATCH :
        Fatal( "Unable to decode JSON string '$value', invalid or malformed JSON" );
      case JSON_ERROR_SYNTAX :
        Fatal( "Unable to decode JSON string '$value', syntax error" );
      default :
        Fatal( "Unable to decode JSON string '$value', unexpected error ".json_last_error() );
      case JSON_ERROR_NONE:
        break;
    }
  }
}

function jsonEncode( &$value ) {
  if ( function_exists('json_encode') ) {
    $string = json_encode( $value );
    checkJsonError($value);
    return( $string );
  }

  switch ( gettype($value) ) {
    case 'double':
    case 'integer':
      return( $value );
    case 'boolean':
      return( $value?'true':'false' );
    case 'string':
      return( '"'.preg_replace( "/\r?\n/", '\\n', addcslashes($value,'"\\/') ).'"' );
    case 'NULL':
      return( 'null' );
    case 'object':
      return( '"Object '.addcslashes(get_class($value),'"\\/').'"' );
    case 'array':
      if ( isVector( $value ) )
        return( '['.join( ',', array_map( 'jsonEncode', $value) ).']' );
      else {
        $result = '{';
        foreach ($value as $subkey => $subvalue ) {
          if ( $result != '{' )
            $result .= ',';
          $result .= '"'.$subkey.'":'.jsonEncode( $subvalue );
        }
        return( $result.'}' );
      }
    default:
      return( '"'.addcslashes(gettype($value),'"\\/').'"' );
  }
}

function jsonDecode( $value ) {
  if ( function_exists('json_decode') ) {
    $object = json_decode( $value, true );
    checkJsonError($value);
    return( $object );
  }

  $comment = false;
  $unescape = false;
  $out = '$result=';
  for ( $i = 0; $i < strlen($value); $i++ ) {
    if ( !$comment ) {
      if ( ($value[$i] == '{') || ($value[$i] == '[') ) {
        $out .= ' array(';
      } else if ( ($value[$i] == '}') || ($value[$i] == ']') ) {
        $out .= ')';
      } else if ( $value[$i] == ':' ) {
        $out .= '=>';
      } else {
        $out .= $value[$i];         
      }
    } else if ( !$unescape ) {
      if ( $value[$i] == '\\' )
        $unescape = true;
      else
        $out .= $value[$i];
    } else {
      if ( $value[$i] != '/' )
        $out .= '\\';
      $out .= $value[$i];
      $unescape = false;
    }
    if ( $value[$i] == '"' ) {
      $comment = !$comment;
    }
  }
  eval( $out.';' );
  return( $result );
}

define( 'HTTP_STATUS_OK', 200 );
define( 'HTTP_STATUS_BAD_REQUEST', 400 );
define( 'HTTP_STATUS_FORBIDDEN', 403 );

function ajaxError( $message, $code=HTTP_STATUS_OK ) {
  Error( $message );
  if ( function_exists( 'ajaxCleanup' ) )
    ajaxCleanup();
  if ( $code == HTTP_STATUS_OK ) {
    $response = array( 'result'=>'Error', 'message'=>$message );
    header( 'Content-type: text/plain' );
    exit( jsonEncode( $response ) );
  }
  header( "HTTP/1.0 $code $message" );
  exit();
}

function ajaxResponse( $result=false ) {
  if ( function_exists( 'ajaxCleanup' ) )
    ajaxCleanup();
  $response = array( 'result'=>'Ok' );
  if ( is_array( $result ) ) {
    $response = array_merge( $response, $result );
  } elseif ( !empty($result) ) {
    $response['message'] = $result;
  }
  header( 'Content-type: text/plain' );
  exit( jsonEncode( $response ) );
}

function generateConnKey() {
  return( rand( 1, 999999 ) );
}

function detaintPath( $path ) {
  // Remove any absolute paths, or relative ones that want to go up
  $path = preg_replace( '/\.(?:\.+[\\/][\\/]*)+/', '', $path );
  $path = preg_replace( '/^[\\/]+/', '', $path );
  return( $path );
}

function cache_bust( $file ) {
  # Use the last modified timestamp to create a link that gets a different filename
  # To defeat caching.  Should probably use md5 hash
  $parts = pathinfo($file);
  global $css;
  $dirname = preg_replace( '/\//', '_', $parts['dirname'] );
  $cacheFile = $dirname.'_'.$parts['filename'].'-'.$css.'-'.filemtime($file).'.'.$parts['extension'];
  if ( file_exists( ZM_DIR_CACHE.'/'.$cacheFile ) or symlink( ZM_PATH_WEB.'/'.$file, ZM_DIR_CACHE.'/'.$cacheFile ) ) {
    return 'cache/'.$cacheFile;
  } else {
    Warning("Failed linking $file to $cacheFile");
  }
  return $file;
}

function getSkinFile( $file ) {
  global $skinBase;
  $skinFile = false;
  foreach ( $skinBase as $skin ) {
    $tempSkinFile = detaintPath( 'skins'.'/'.$skin.'/'.$file );
    if ( file_exists( $tempSkinFile ) )
      $skinFile = $tempSkinFile;
  }
  return  $skinFile;
}

function getSkinIncludes( $file, $includeBase=false, $asOverride=false ) {
  global $skinBase;
  $skinFile = false;
  foreach ( $skinBase as $skin ) {
    $tempSkinFile = detaintPath( 'skins'.'/'.$skin.'/'.$file );
    if ( file_exists( $tempSkinFile ) )
      $skinFile = $tempSkinFile;
  }
  $includeFiles = array();
  if ( $asOverride ) {
    if ( $skinFile )
      $includeFiles[] = $skinFile;
    else if ( $includeBase )
      $includeFiles[] = $file;
  } else {
    if ( $includeBase )
      $includeFiles[] = $file;
    if ( $skinFile )
      $includeFiles[] = $skinFile;
  }
  return( $includeFiles );
}

function requestVar( $name, $default='' ) {
  return( isset($_REQUEST[$name])?validHtmlStr($_REQUEST[$name]):$default );
}

// For numbers etc in javascript or tags etc
function validInt( $input ) {
  return( preg_replace( '/\D/', '', $input ) );
}

function validNum( $input ) {
  return( preg_replace( '/[^\d.-]/', '', $input ) );
}

// For general strings
function validStr( $input ) {
  return( strip_tags( $input ) );
}

// For strings in javascript or tags etc, expected to be in quotes so further quotes escaped rather than converted
function validJsStr( $input ) {
  return( strip_tags( addslashes( $input ) ) );
}

// For general text in pages outside of tags or quotes so quotes converted to entities
function validHtmlStr( $input ) {
  return( htmlspecialchars( $input, ENT_QUOTES ) );
}

function getStreamHTML( $monitor, $options = array() ) {

  if ( isset($options['scale']) and $options['scale'] and ( $options['scale'] != 100 ) ) {
    //Warning("Scale to " . $options['scale'] );
    $options['width'] = reScale( $monitor->Width(), $options['scale'] ) . 'px';
    $options['height'] = reScale( $monitor->Height(), $options['scale'] ) . 'px';
  } else {
    # scale is empty or 100
    # There may be a fixed width applied though, in which case we need to leave the height empty
    if ( ! ( isset($options['width']) and $options['width'] ) ) {
      $options['width'] = $monitor->Width() . 'px';
      if ( ! ( isset($options['height']) and $options['height'] ) ) {
        $options['height'] = $monitor->Height() . 'px';
      }
    } else if ( ! isset($options['height']) ) {
      $options['height'] = '';
    }
  }
  if ( ! isset($options['mode'] ) ) {
    $options['mode'] = 'stream';
  }
  $options['maxfps'] = ZM_WEB_VIDEO_MAXFPS;
  if ( $monitor->StreamReplayBuffer() )
    $options['buffer'] = $monitor->StreamReplayBuffer();
  //Warning("width: " . $options['width'] . ' height: ' . $options['height']. ' scale: ' . $options['scale'] );

  if ( $monitor->Type() == "WebSite" ) {
         return getWebSiteUrl( 'liveStream'.$monitor->Id(), $monitor->Path(),
          ( isset($options['width']) ? $options['width'] : NULL ),
          ( isset($options['height']) ? $options['height'] : NULL ),
          $monitor->Name()
        );
  //FIXME, the width and height of the image need to be scaled.
  } else if ( ZM_WEB_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT ) {
    $streamSrc = $monitor->getStreamSrc( array(
      'mode'=>'mpeg',
      'scale'=>(isset($options['scale'])?$options['scale']:100),
      'bitrate'=>ZM_WEB_VIDEO_BITRATE,
      'maxfps'=>ZM_WEB_VIDEO_MAXFPS,
      'format' => ZM_MPEG_LIVE_FORMAT
    ) );
    return getVideoStreamHTML( 'liveStream'.$monitor->Id(), $streamSrc, $options['width'], $options['height'], ZM_MPEG_LIVE_FORMAT, $monitor->Name() );
  } else if ( $options['mode'] == 'stream' and canStream() ) {
    $options['mode'] = 'jpeg';
    $streamSrc = $monitor->getStreamSrc( $options );

    if ( canStreamNative() )
      return getImageStreamHTML( 'liveStream'.$monitor->Id(), $streamSrc, $options['width'], $options['height'], $monitor->Name());
    elseif ( canStreamApplet() )
      // Helper, empty widths and heights really don't work.
      return getHelperStream( 'liveStream'.$monitor->Id(), $streamSrc, 
          $options['width'] ? $options['width'] : $monitor->Width(), 
          $options['height'] ? $options['height'] : $monitor->Height(),
          $monitor->Name());
  } else {
    if ( $options['mode'] == 'stream' ) {
      Info( 'The system has fallen back to single jpeg mode for streaming. Consider enabling Cambozola or upgrading the client browser.' );
    }
    $options['mode'] = 'single';
    $streamSrc = $monitor->getStreamSrc( $options );
    return getImageStill( 'liveStream'.$monitor->Id(), $streamSrc, $options['width'], $options['height'], $monitor->Name());
  }
} // end function getStreamHTML

function getStreamMode( ) {
  $streamMode = '';
  if ( ZM_WEB_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT ) {
    $streamMode = 'mpeg';
  } elseif ( canStream() ) {
    $streamMode = 'jpeg';
  } else {
    $streamMode = 'single';
    Info( 'The system has fallen back to single jpeg mode for streaming. Consider enabling Cambozola or upgrading the client browser.' );
  }
  return $streamMode;
} // end function getStreamMode

function folder_size($dir) {
    $size = 0;
    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : folder_size($each);
    }
    return $size;
} // end function folder_size

function human_filesize($size, $precision = 2) {
    $units = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $step = 1024;
    $i = 0;
    while (($size / $step) > 0.9) {
        $size = $size / $step;
        $i++;
    }
    return round($size, $precision).$units[$i];
}

function csrf_startup() {
    csrf_conf('rewrite-js', 'includes/csrf/csrf-magic.js');
}

function unparse_url($parsed_url, $substitutions = array() ) { 
  $fields = array('scheme','host','port','user','pass','path','query','fragment');

  foreach ( $fields as $field ) {
    if ( isset( $substitutions[$field] ) ) {
      $parsed_url[$field] = $substitutions[$field];
    }
  }
  $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
  $host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
  $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
  $user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
  $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
  $pass     = ($user || $pass) ? "$pass@" : ''; 
  $path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
  $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
  $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
  return "$scheme$user$pass$host$port$path$query$fragment"; 
}
?>