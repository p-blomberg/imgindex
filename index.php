<?php
require "_settings.php";

function thumbdir() {
	global $thumbs;
	if(!file_exists($thumbs)) {
		if(!mkdir($thumbs)) {
			throw new Exception("Failed to create thumbs directory.");
		}
	}
	if(!is_writable($thumbs)) {
		throw new Exception("Unable to write to thumbs directory.");
	}
	return $thumbs;
}

function create_thumb($filename) {
	global $images;
	global $thumb_prefs;
	$thumbs = thumbdir();

	$str = file_get_contents($images.$filename);
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
		$x = $thumb_prefs['max_width'];
		$y = $x / $aspect;
	} else {
		// Y is larger than X
		$y = $thumb_prefs['max_height'];
		$x = $y * $aspect;
	}
	$thumb_im = imagecreatetruecolor($x, $y);

	if(!imagecopyresampled($thumb_im, $orig_im, 0, 0, 0, 0, $x, $y, imagesx($orig_im), imagesy($orig_im))) {
		throw new Exception("Failed to resample image");
	}

	if(!imagejpeg($thumb_im, $thumbs.$filename, $thumb_prefs['quality'])) {
		throw new Exception("Failed to write thumbnail to disk");
	}

	imagedestroy($orig_im);
	imagedestroy($thumb_im);

	return true;
}

function thumb($filename) {
	global $thumbs;
	if(!file_exists($thumbs.$filename)) {
		create_thumb($filename);
	}
	return $thumbs.$filename;
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
			position: absolute; 
			top: 20px;
			left: 0;
			right: 0;
			top: 10px;
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
		</style>
		<script type="text/javascript">
			function preload() {
				images = [];
				<?php
				foreach($filenames as $entry) {
					?>
					image = new Image();
					image.src = "<?=$images.$entry?>";
					images.push(image);
					<?php
				}
				?>
			}
			function show_img(filename, y) {
				document.getElementById('bigimg').src = '<?=$images?>' + filename;
				document.getElementById('image_overlay').style.display = 'block';
				document.getElementById('image_overlay_content').style.display = 'block';
				document.getElementById('image_overlay_content').style.top = y + 'px';
			}
			function image_hide() {
				document.getElementById('image_overlay').style.display = 'none';
				document.getElementById('image_overlay_content').style.display = 'none';
			}
		</script>
  </head>
  <body>
    <h1><?=$title?></h1>
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
						<img src="<?=thumb($entry)?>" onclick="show_img('<?=$entry?>', this.offsetTop); return false;" alt="<?=$entry?>" /></a><br />
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
		</script>
  </body>
</html>
