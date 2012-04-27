<?php
$title = "Testbilder"; 

// Paths. MAKE SURE THEY END WITH A SLASH.
$images = "images/"; // Where are your images? Use a path relative to index.php
$thumbs = "thumbs/"; // Will be created on first request
$lowres = "lowres/"; // Will be created on first request

$thumb_prefs = array(
	"max_width" => 200,
	"max_height" => 133,
	"quality" => 95,
	);

$lowres_prefs = array(
	"max_width" => 800,
	"max_height" => 600,
	"quality" => 95,
);
