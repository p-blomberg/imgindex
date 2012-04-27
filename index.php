<?php
/***********
 * imgindex
 ***********
 * Copyright (c) 2012, Petter Blomberg
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 * 
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer. 
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution. 
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */

require "settings.php";
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
			width: 820px;
			padding: 1ex 1em;
			border-radius: 4px;
			text-align: center;
		}
		#bigimg {
			padding: 10px;
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
		#footer {
			border-top: 1px solid gray;
			max-width: 400px;
			text-align: center;
			font-size: 10pt;
			margin: auto;
		}
		.filename a {
			text-decoration: none;
			color: black;
		}
		.filename a:hover {
			text-decoration: underline;
		}
		</style>
		<script type="text/javascript">
			var current_img;
			var images = [];
			var filenames = [];
			var exifdates = [];
			var overlay_active = false;
			var fullsize_link = '';
			function show_img(id) {
				console.log("show_img("+id+")");
				filename = filenames[id];
				exifdate = exifdates[id];
				overlay_active = true;
				current_img = id;
				document.getElementById('bigimg').src = images[id].src;
				fullsize_link = "<?=$images?>/" + filename;
				document.getElementById('filename').innerHTML = filename + "<br />" + exifdate;
				document.getElementById('image_overlay').style.display = 'block';
				document.getElementById('image_overlay_content').style.display = 'block';
			}
			function image_hide() {
				document.getElementById('image_overlay').style.display = 'none';
				document.getElementById('image_overlay_content').style.display = 'none';
				overlay_active = false;
			}
		</script>
  </head>
  <body onload="onloadevents()" onkeypress="onkeypressevents(event)">
    <h1><?=$title?></h1>
		<p id="loading">Skapar förhandsgranskning...</p>
		<?php ob_flush(); flush(); ?>
		<ul id="thumbs">
			<?php
			foreach($filenames as $entry) {
				static $counter = 0;
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
						<a href="<?=$images.$entry?>"><img src="<?=thumb($entry)?>" id="thumb<?=$counter?>" onclick="show_img(<?=$counter?>); return false;" alt="<?=$entry?>" /></a><br />
						<span class="filename"><a href="<?=$images.$entry?>"><?=$entry?></a></span><br />
						<span class="size">(<?=$size[0]?>x<?=$size[1]?>)</span>
					</div>
				</li>
				<?
				$counter++;
			}
			?>
		</ul>
		<?php ob_flush(); flush(); ?>
		<div id="image_overlay" onclick="image_hide()"></div>
		<div id="image_overlay_content">
			<button onclick="show_img(parseInt(current_img)-1);">&lt;--</button>
			<button onclick="image_hide();">Stäng</button>
			<button onclick="show_img(parseInt(current_img)+1);">--&gt;</button>
			<br />
			<img id="bigimg" />
			<p id="filename"></p>
			<button onclick="show_img(parseInt(current_img)-1);">&lt;--</button>
			<button onclick="image_hide();">Stäng</button>
			<button onclick="show_img(parseInt(current_img)+1);">--&gt;</button>
			<br />
			<button onclick="window.location=fullsize_link;">Full storlek</button>
		</div>
		<script type="text/javascript">
			function preload() {
				<?php
				foreach($filenames as $entry) {
					?>
					image = new Image();
					image.src = "<?=lowres($entry)?>";
					images.push(image);
					filenames.push('<?=$entry?>');
					<?php
					$exif = exif_read_data($images.$entry, 'EXIF');
					$exifdate = $exif['DateTimeOriginal'];
					?>
					exifdates.push('<?=$exifdate?>');
					<?
				}
				?>
			}
			function onloadevents() {
				preload();
				document.getElementById('loading').style.display = 'none';
			}
			function onkeypressevents(e) {
				var evtobj=window.event? event : e
				console.log("keypress " + evtobj.keyCode);
				if(overlay_active) {
					switch (evtobj.keyCode) {
						case 27:
							image_hide();
							break;
						case 39:
						case 40:
							show_img(parseInt(current_img)+1);
							break;
						case 37:
						case 38:
							show_img(parseInt(current_img)-1);
							break;
					}
				}
			}
		</script>
		<div id="footer">
			Created with <a href="https://github.com/p-blomberg/imgindex">imgindex</a>
		</div>
  </body>
</html>
