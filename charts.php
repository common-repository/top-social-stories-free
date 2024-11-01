<?php

//---------------------------------------------
// Line chart
//---------------------------------------------
add_action( 'wp_ajax_get_chart', 'get_chart_callback' );
function get_chart_callback() {
	?>
	<html>
	<head>
	<style>
		body,a {font-size:13px; line-height:18px; font-family: sans-serif; color:#111;background-color:#fff}
		a { padding:0px; display:inline-block; margin-right:5px; text-decoration:none; color:#aaa}
		a.sel{ background-color:#ddd;color:#111;}
		body { padding:20px}
		code {font-family:courier new,monospace;background-color:#eee;padding:0 3px;color:#666}
	</style>
	</head>
	<body>
	
	<p><b>Notice</b>: this free version analyzes data only from one month to date. The full version has no date-limit and comes with <code>shortcodes</code>, top authors widget, charts for single posts and for all your site, has email alerts for authors and more.<br>
	<a href="http://codecanyon.net/item/top-social-stories-plugin-and-widget/5888553?ref=ginoplusio" target="_blank">[upgrade to full version]</a></p>
	
	</body>
	</html>
	<?php

	die;
}




//---------------------------------------------
// Pie chart
//---------------------------------------------
add_action( 'wp_ajax_get_pie_chart', 'get_pie_chart_callback' );
function get_pie_chart_callback() {

	?>
	<html>
	  <head>
	  <style>
		body,a {font-size:10px; line-height:13px; font-family: sans-serif; color:#111;background-color:#fff}
		a { padding:0 5px; display:inline-block; margin-right:5px; text-decoration:none; color:#aaa}
		a.sel{ background-color:#ddd;color:#111;}
	</style>
		
	  </head>
	  <body>
		<a href="http://codecanyon.net/item/top-social-stories-plugin-and-widget/5888553?ref=ginoplusio" target="_blank">Upgrade</a>
	  </body>
	</html>
	<?php
	die;
}


//---------------------------------------------
// Column chart
//---------------------------------------------
add_action( 'wp_ajax_get_col_chart', 'get_col_chart_callback' );
function get_col_chart_callback() {

	?>
	<html>
	  <head>
	  <style>
		body,a {font-size:10px; line-height:13px; font-family: sans-serif; color:#111;background-color:#fff}
		a { padding:0 5px; display:inline-block; margin-right:5px; text-decoration:none; color:#aaa}
		a.sel{ background-color:#ddd;color:#111;}
	</style>
	  </head>
	  <body>
	<a href="http://codecanyon.net/item/top-social-stories-plugin-and-widget/5888553?ref=ginoplusio" target="_blank">Upgrade</a>
	  </body>
	</html>
	<?php
	die;
}
?>