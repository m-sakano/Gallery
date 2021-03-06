<?php
require_once('config.php');
require_once('createS3Client.php');
session_start();

if (!isset($_SESSION['me'])) {
	header('Location: '.SITE_URL);
}

$width = THUMBS_WIDTH;
if ($_GET['prefix']!='') {
	$_SESSION['prefix'] = $_GET['prefix'];
} else {
	$_SESSION['prefix'] = DEFAULT_PREFIX_VIDEO;
}
if (mb_substr($_SESSION['prefix'],-1)!=='/') {
	$_SESSION['prefix'] .= '/';
}

if ($_GET['picsnum']!='') {
	$_SESSION['picsnum'] = $_GET['picsnum'];
} else {
	$_SESSION['picsnum'] = THUMBS_PER_PAGES;
}

if ($_GET['page']!='') {
	$_SESSION['page'] = $_GET['page'];
} else {
	$_SESSION['page'] = 1;
}

$client = createS3Client();
$keylist = array();
$commonprefixes = array();
try {
    $objects = $client->ListObjects(array(
        'Bucket' => S3_BUCKET,
        'Prefix' => $_SESSION['prefix'],
        'Delimiter' => '/'
    ));
} catch (S3Exception $e) {
    echo $e->getMessage() . "\n";
}
if ($objects['Contents']!==null) {
    foreach ($objects['Contents'] as $key) {
        array_push($keylist, $key['Key']);
    }
}
if ($objects['CommonPrefixes']!==null) {
    foreach ($objects['CommonPrefixes'] as $prefix) {
        array_push($commonprefixes, $prefix);
    }
}
while ($objects['IsTruncated']==true) {
    try {
        $objects = $client->ListObjects(array(
            'Bucket' => S3_BUCKET,
            'Prefix' => $_SESSION['prefix'],
            'Delimiter' => '/',
            'Marker' => $objects['NextMarker']
        ));
        if ($objects['Contents']!==null) {
            foreach ($objects['Contents'] as $key) {
                array_push($keylist, $key['Key']);
            }
        }
        if ($objects['CommonPrefixes']!==null) {
            foreach ($objects['CommonPrefixes'] as $prefix) {
                array_push($commonprefixes, $prefix);
            }
        }
    } catch (S3Exception $e) {
        echo $e->getMessage() . "\n";
    }
}

$max_pics = 0;
if (count($keylist)>0) {
    foreach ($keylist as $key) {
    	if (mb_substr($key, -1) !== '/') {
    	    if (array_search(mb_strtolower(mb_substr($key,-3,3)),array('mp4'))===false) continue;
		    $max_pics++;
    	}
    }
}

if ($_SESSION['picsnum']==0) {
	$page_max = 0;
} else {
	$page_max = ceil($max_pics / $_SESSION['picsnum']);
}
$page_first_pict = ($_SESSION['page'] - 1) * $_SESSION['picsnum'] + 1;
$page_last_pict = $page_first_pict + $_SESSION['picsnum'] - 1;
?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="favicon.ico">

    <title><?php echo BRAND.' - '.$_SESSION['prefix'].'('.$_SESSION['page'].')'; ?></title>

    <!-- Bootstrap core CSS -->
    <link href="bootstrap/docs/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="starter-template.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="bootstrap/docs/assets/js/ie-emulation-modes-warning.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    
    <link type="text/css" rel="stylesheet" href="lightGallery/dist/css/lightgallery.min.css" />
    
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="jquery/jquery.min.js"></script>
    <script src="bootstrap/docs/dist/js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="bootstrap/docs/assets/js/ie10-viewport-bug-workaround.js"></script>
    
    <!-- A jQuery plugin that adds cross-browser mouse wheel support. (Optional) -->
    <script src="js/jquery.mousewheel.min.js"></script>
    
    <!-- video-js -->
    <link href="video.js/dist/video-js.min.css" rel="stylesheet">
    <script src="video.js/dist/video.min.js"></script>
    
    <!-- lightgallery plugins -->
    <script src="lightGallery/dist/js/lightgallery.min.js"></script>
    <script src="lightGallery/dist/js/lg-thumbnail.min.js"></script>
    <script src="lightGallery/dist/js/lg-fullscreen.min.js"></script>
    <script src="lightGallery/dist/js/lg-autoplay.min.js"></script>
    <script src="lightGallery/dist/js/lg-zoom.min.js"></script>
    <script src="lightGallery/dist/js/lg-video.min.js"></script>
    
    <script type="text/javascript">
	    $(document).ready(function() {
            $('#video-gallery').lightGallery({
                videojs: true
            }); 
	    });
	</script>
	
	<style type="text/css">
		body { background-image: url("maru09.gif"); }
	</style>
  </head>

  <body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="<?php echo SITE_URL; ?>"><?php echo BRAND; ?></a>
        </div>
        <div class="collapse navbar-collapse" id="navbar">
		<form class="navbar-form navbar-left" role="search" action="./video" method="GET">
		  <div class="form-group">
		    <label style="color: #999;">PAGE</label>
		    <input type="text" class="form-control" placeholder="input prefix" name="prefix" value="<?php echo $_SESSION['prefix'];?>">
		    <label style="color: #999;">MAX</label>
		    <input type="text" size="3" class="form-control" placeholder="枚数" name="picsnum" value="<?php echo $_SESSION['picsnum'];?>">
		  </div>
		  <button type="submit" class="btn btn-default">Submit</button>
		</form>
		<ul class="nav navbar-nav navbar-right">
          <li><a href="logout">ログアウト</a></li>
        </ul>
        </div>
      </div>
    </nav>

    <div class="container">
      <!-- <div class="starter-template"> -->
		<?php // show subdirs
		$pages = array();
		if (count($commonprefixes)>0) {
        	foreach ($commonprefixes as $prefixes) {
        		array_push($pages,$prefixes['Prefix']);
        	}
		}
		if (count($pages)>0) {
			echo '<div>';
			echo 'SUB PAGES: ';
			foreach ($pages as $page) {
				echo '<a href="./video?prefix='.$page.'">'.mb_substr($page,mb_strlen($_SESSION['prefix'])).'</a>&nbsp;';
			}
			echo '</div>';
		}
		?>
		<nav align="center">
		  <ul class="pagination">
		    <?php $before=$_SESSION['page']-1; ?>
		    <li<?php if($before<1){echo ' class="disabled"';} ?>>
		      <a href="<?php echo './video?prefix='.$_SESSION['prefix'].'&page='.$before.'&picsnum='.$_SESSION['picsnum'];?>" aria-label="Previous">
		        <span aria-hidden="true">&laquo;</span>
		      </a>
		    </li>
		    <?php 
		    for ($i=1; $i<=$page_max; $i++) {
		    	if ($i==$_SESSION['page']) {
		    		echo '<li class="active"><a href="./video?prefix='.$_SESSION['prefix'].'&page='.$i.'&picsnum='.$_SESSION['picsnum'].'">'.$i.'</a></li>';
		    	} else {
		    		echo '<li><a href="./video?prefix='.$_SESSION['prefix'].'&page='.$i.'&picsnum='.$_SESSION['picsnum'].'">'.$i.'</a></li>';
		    	}
		    }
		    ?>
		    <?php $next=$_SESSION['page']+1; ?>
		    <li<?php if($next>$page_max){echo ' class="disabled"';} ?>>
		      <a href="<?php echo './video?prefix='.$_SESSION['prefix'].'&page='.$next.'&picsnum='.$_SESSION['picsnum'];?>" aria-label="Next">
		        <span aria-hidden="true">&raquo;</span>
		      </a>
		    </li>
		  </ul>
		</nav>
        <div id="lightgallery">
        <?php
        $i = 0;
        if (count($keylist)>0) {
            foreach ($keylist as $key) {
            	if(mb_substr($key,-1)=='/') continue;
            	if(array_search(mb_strtolower(mb_substr($key,-3,3)),array('mp4'))===false) continue;
            	$i++;
            	if ($i>=$page_first_pict && $i<=$page_last_pict) {
    		        $command = $client->getCommand('GetObject', array(
    				    'Bucket' => S3_BUCKET,
    				    'Key' => $key
    				));
    				// Generate Signed URL
    				$signedUrl = $command->createPresignedUrl('+1 hours');
		?>
          <div style="display:none;" id="video<?php echo $i;?>">
            <video class="lg-video-object lg-html5 video-js vjs-default-skin" controls preload="none">
              <source src="<?php echo $signedUrl; ?>" type="video/mp4">
                 Your browser does not support HTML5 video.
            </video>
          </div>
        <?php }}} ?>
          <ul id="video-gallery" style="list-style:none">
        <?php
        $i = 0;
        if (count($keylist)>0) {
            foreach ($keylist as $key) {
            	if(mb_substr($key,-1)=='/') continue;
            	if(array_search(mb_strtolower(mb_substr($key,-3,3)),array('mp4'))===false) continue;
            	$i++;
            	if ($i>=$page_first_pict && $i<=$page_last_pict) {
            	    $thumbkey = 'thumbs/'.mb_substr($key,0,mb_strlen($key)-4).'.jpg';
            	    if ($client->doesObjectExist(S3_BUCKET,$thumbkey)==false) {
        		        $command = $client->getCommand('GetObject', array(
        				    'Bucket' => S3_BUCKET,
        				    'Key' => 'thumbs/movie.png'
        				));
            	    } else {
        		        $command = $client->getCommand('GetObject', array(
        				    'Bucket' => S3_BUCKET,
        				    'Key' => $thumbkey
        				));
            	    }
    				// Generate Signed URL
    				$signedUrlThumbs = $command->createPresignedUrl('+1 hours');
          ?>
            <li data-poster="<?php echo $signedUrlThumbs; ?>" data-sub-html="<?php echo $key;?>" data-html="#video<?php echo $i;?>" style="float:left">
              <img src="<?php echo $signedUrlThumbs; ?>" width="<?php echo THUMBS_WIDTH;?>" />
            </li>
          <?php }}} ?>
          </ul>
        </div><!-- lightGallery -->
      <!-- </div> starter-template -->
    </div><!-- /.container -->

  </body>
</html>
