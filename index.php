<?php
require "_settings.php";
set_time_limit(300);

function check_target_dir($dir) {
	if(!file_exists($dir)) {
		if(!mkdir($dir)) {
			throw new Exception("Failed to create target directory.");
		}
	}
	if(!is_writable($dir)) {
		throw new Exception("Unable to write to target directory.");
	}
	return $dir;
}

function create_scaled_file($filename, $prefs, $source_dirname, $target_dirname) {
	$str = file_get_contents($source_dirname.$filename);
	if($str === false) {
		throw new Exception("Unable to read file");
	}
	$orig_im = imagecreatefromstring($str);
	if($orig_im === false) {
		throw new Exception("Unable to read image");
	}

	$aspect = imagesx($orig_im) / imagesy($orig_im);
	if($aspect > 1) {
		// X is larger than Y
		$x = $prefs['max_width'];
		$y = $x / $aspect;
	} else {
		// Y is larger than X
		$y = $prefs['max_height'];
		$x = $y * $aspect;
	}
	$target_im = imagecreatetruecolor($x, $y);

	if(!imagecopyresampled($target_im, $orig_im, 0, 0, 0, 0, $x, $y, imagesx($orig_im), imagesy($orig_im))) {
		throw new Exception("Failed to resample image");
	}

	if(!imagejpeg($target_im, $target_dirname.$filename, $prefs['quality'])) {
		throw new Exception("Failed to write resized image to disk");
	}

	imagedestroy($orig_im);
	imagedestroy($target_im);

	return true;
}

function create_thumb($filename) {
	global $images, $thumb_prefs, $thumbs;
	$thumbs = check_target_dir($thumbs);
	return create_scaled_file($filename, $thumb_prefs, $images, $thumbs);
}

function create_lowres($filename) {
	global $images, $lowres_prefs, $lowres;
	$lowres = check_target_dir($lowres);
	return create_scaled_file($filename, $lowres_prefs, $images, $lowres);
}

function thumb($filename) {
	global $thumbs;
	if(!file_exists($thumbs.$filename)) {
		create_thumb($filename);
	}
	return $thumbs.$filename;
}
function lowres($filename) {
	global $lowres;
	if(!file_exists($lowres.$filename)) {
		create_lowres($filename);
	}
	return $lowres.$filename;
}

$d = dir($images);
while (false !== ($entry = $d->read())) {
	if($entry[0] == '.') {
		continue;
	}
	$filenames[] = $entry;
}
$d->close();
?>
<!doctype html>
<html>
  <head>
    <meta charset="UTF-8">
    <title><?=$title?></title>
		<style type="text/css">
		body {
			font-family: Verdana;
			font-size: 10pt;
		}
		h1 {
			text-align: center;
		}
		.size {
			font-size: 8pt;
			color: gray;
		}
		#thumbs li {
			display: inline-block;
			padding-bottom: 5px;
		}
		#thumbs div {
			border: 1px solid gray;
			display: inline-block;
			text-align: center;
			width: 200px;
		}
		#image_overlay_content {
			display: none; 
			background-color: white; 
			position: fixed; 
			top: 20px;
			left: 0;
			right: 0;
			top: 40px;
			margin: auto;
			width: 800px;
			padding: 1ex 1em;
			border-radius: 4px;
			text-align: center;
		}
		#image_overlay {
			display: none;
			background-color: black;
			position: fixed;
			left: 0;
			right: 0;
			top: 0;
			bottom: 0;
			opacity: 0.75;
		}
		#loading {
			text-align: center;
			color: red;
			font-size: 14pt;
		}
		</style>
		<script type="text/javascript">
			function show_img(filename) {
				document.getElementById('bigimg').src = '<?=$lowres?>' + filename;
				document.getElementById('image_overlay').style.display = 'block';
				document.getElementById('image_overlay_content').style.display = 'block';
			}
			function image_hide() {
				document.getElementById('image_overlay').style.display = 'none';
				document.getElementById('image_overlay_content').style.display = 'none';
			}
		</script>
  </head>
  <body onload="onloadevents()">
    <h1><?=$title?></h1>
		<p id="loading">Skapar förhandsgranskning...</p>
		<?php
		ob_flush(); flush();
		?>
		<ul id="thumbs">
			<?php
			foreach($filenames as $entry) {
				try {
					$size = @getimagesize($images.$entry);
					if($size === false) {
						throw new Exception("getimagesize failed");
					}
				}	catch(Exception $e) {
					echo $e->getMessage();
				}
				?>
				<li>
					<div>
						<img src="<?=thumb($entry)?>" onclick="show_img('<?=$entry?>'); return false;" alt="<?=$entry?>" /></a><br />
						<span class="filename"><?=$entry?></span><br />
						<span class="size">(<?=$size[0]?>x<?=$size[1]?>)</span>
					</div>
				</li>
				<?
			}
			?>
		</ul>
		<div id="image_overlay" onclick="image_hide()"></div>
		<div id="image_overlay_content" onclick="image_hide()">
			<button onclick="image_hide();">Stäng</button><br />
			<img id="bigimg" />
			<br /><button onclick="image_hide();">Stäng</button><br />
		</div>
		<script type="text/javascript">
			function preload() {
				images = [];
				<?php
				foreach($filenames as $entry) {
					?>
					image = new Image();
					image.src = "<?=lowres($entry)?>";
					images.push(image);
					<?php
				}
				?>
			}
			function onloadevents() {
				preload();
				document.getElementById('loading').style.display = 'none';
			}
		</script>
  </body>
</html>
