<?php
/*
Plugin Name: Top Social Stories Free
Plugin URI: http://www.barattalo.it/top-stories-plugin-widget/
Description: Create top stories from Facebook's likes, shares, comments and Twitter's tweets, Google+, Pinterest, VKontakte and Linkedin, found your top viral posts (most shared), display charts and show trending posts and authors. This is a free version of the full plugin sold on CodeCanyon.
Version: 1.83
Author: Giulio Pons
Author URI: http://codecanyon.net/user/ginoplusio
*/

include("widget.php");

// Localization
add_action('init', 'top_stories_plugin_init');
function top_stories_plugin_init() {
    $path = dirname(plugin_basename( __FILE__ )) . '/lang';
    $loaded = load_plugin_textdomain( 'top-stories-plugin', false, $path);
    if (isset($_GET['page']) && $_GET['page']== basename(__FILE__) && !$loaded) {          
        echo '<div class="error">' . __('Could not load the localization file: ' . $path, 'top-stories-plugin') . '</div>';
        return;
    }

	$options = get_option( 'top_stories_settings' , top_stories_get_defaults() );
	foreach($options['top_stories_pt'] as $t)  {
		add_filter( 'manage_'.$t.'_posts_columns', 'top_stories_show_like_column' );
		add_filter( 'manage_'.$t.'_posts_custom_column', 'top_stories_show_like_column_row', 10,2 );
	}

} 

function top_stories_get_defaults() {
	return array(
		'top_stories_delay' => '30',		// 30 seconds default delay
		'top_stories_hits'=>'10',
		'top_stories_days'=>'365',
		'top_stories_save_custom'=>'0',		// 1=yes, save also custom fields
		'top_stories_placeholder'=>plugin_dir_url( __FILE__ ).'images/placeholder.jpg',
		'top_stories_start'=>date("Y-m-d"),
		'top_stories_pt' => array('post','page','attachment'),
		'top_stories_serie'=>"ftglpv",	// "ftglpv" f=facebook, t=twitter, g=google+, l=linkedin, p=pinterest, v=vkontakte
		'top_stories_mail_level'=>"0"	// send mail when pass 1000 total interactions, 0 not send
	);

}

//---------------------------------------------
// Scripts And Css
//---------------------------------------------
function top_stories_scripts(){
	global $post;
	wp_enqueue_script("jquery");

	if ( is_single() || is_page() || is_attachment()) {
		/*
			load javascript only on single page
		*/
		$options = wp_parse_args(get_option('top_stories_settings'), top_stories_get_defaults());

		wp_register_script('top_stories_script_js',plugin_dir_url( __FILE__ ).'js/top-stories.js',array('jquery'),4.64,true);
		wp_enqueue_script('top_stories_script_js');

		/*
			if a post has been published before plugin activation date
			force first record on first ajax call
		*/
		if($options['top_stories_start'] > date("Y-m-d",strtotime($post->post_date))) {
			$force_date = date("Y-m-d",strtotime($post->post_date));
		} else {
			$force_date = "";
		}

		$params = array(
			"gplus_url" => plugin_dir_url( __FILE__ ).'plusones.php',
			"ajax_url" => admin_url( 'admin-ajax.php' ),
			"post_id" => $post->ID,
			"timer" => $options['top_stories_delay'],
			"permalink" => get_permalink(),
			"force_date"=>$force_date,
			"serie"=> $options['top_stories_serie']
		);
		wp_localize_script( 'top_stories_script_js', 'top_stories_params', $params );
	}

	wp_register_style( 'top-stories', plugin_dir_url( __FILE__ ).'css/style.css'  );
	wp_enqueue_style( 'top-stories' );
}
add_action('wp_enqueue_scripts','top_stories_scripts');







//---------------------------------------------
// Modify Posts List in WP-Admin: two more columns
//---------------------------------------------
function top_stories_get_columns($column = array()) {
	$column['facebook'] = "<img src='".plugin_dir_url( __FILE__ )."images/fb-admin-icon.png'/>";
	$column['twitter'] = "<img src='".plugin_dir_url( __FILE__ )."images/tw-admin-icon.png'/>";
	$column['google'] = "<img src='".plugin_dir_url( __FILE__ )."images/go-admin-icon.png'/>";
	$column['linkedin'] = "<img src='".plugin_dir_url( __FILE__ )."images/li-admin-icon.png'/>";
	$column['pinterest'] = "<img src='".plugin_dir_url( __FILE__ )."images/pi-admin-icon.png'/>";
	$column['vkontakte'] = "<img src='".plugin_dir_url( __FILE__ )."images/vk-admin-icon.png'/>";
	return $column;
}

function top_stories_show_like_column( $c ) {
	$columns = top_stories_get_columns();
	$o = get_option( 'top_stories_settings' , top_stories_get_defaults() );
	if(stristr($o['top_stories_serie'],"f")) $c['facebook'] = $columns['facebook']; else unset($c['facebook']);
	if(stristr($o['top_stories_serie'],"t")) $c['twitter'] = $columns['twitter']; else unset($c['twitter']);
	if(stristr($o['top_stories_serie'],"g")) $c['google'] = $columns['google']; else unset($c['google']);
	if(stristr($o['top_stories_serie'],"l")) $c['linkedin'] = $columns['linkedin']; else unset($c['linkedin']);
	if(stristr($o['top_stories_serie'],"p")) $c['pinterest'] = $columns['pinterest']; else unset($c['pinterest']);
	if(stristr($o['top_stories_serie'],"v")) $c['vkontakte'] = $columns['vkontakte']; else unset($c['vkontakte']);

	return $c;
}
function top_stories_show_like_column_row( $column_name, $post_id ) {
	global $wpdb;
	if( $column_name=='facebook' || 
		$column_name=='twitter' || 
		$column_name=='google' || 
		$column_name=='pinterest' || 
		$column_name=='vkontakte' || 
		$column_name=='linkedin') {
		$v = execute_row("select {$column_name}_shares from {$wpdb->prefix}top_stories where id_post=$post_id order by dt_day desc limit 0,1");
		if(is_array($v)) $v = $v["{$column_name}_shares"] ;  else $v = "-";
		echo $v;
	}
	$options = get_option( 'top_stories_settings' , top_stories_get_defaults() );

}


//---------------------------------------------
// Add Menu Item "Top Stories" Inside Posts in Admin
//---------------------------------------------
function top_stories_menu() {
	add_submenu_page( 'edit.php','Top Social Stories' , 'Top Social Stories', 'manage_options', 'top_stories_menu_analytics', 'top_stories_analytics');
	add_submenu_page( 'options-general.php','Top Stories Settings' , 'Top Stories Settings', 'manage_options', 'top_stories_menu_settings', 'top_stories_settings');

	add_submenu_page(
		'_doesnt_exist'
		,__( 'Page title', 'textdomain' )
		,''
		,'manage_options'
		,'top_stories_facebook_config_page'
		,'top_stories_facebook_config_page'
	);

}
add_action( 'admin_menu', 'top_stories_menu' );


//---------------------------------------------
// Handle "Top Stories" Settings Page Functions
//---------------------------------------------

function top_stories_admin_script($hook) {
	global $wp_version;


	if($hook!="posts_page_top_stories_menu_analytics" && $hook!="edit.php" && $hook!="settings_page_top_stories_menu_settings") return;
	// admin scripts should be loaded only where they are useful:
	// on top stories plugin page, list of posts

	if($hook=="posts_page_top_stories_menu_analytics" || $hook=="settings_page_top_stories_menu_settings") {

		// js/css scripts for admin
		wp_enqueue_script("jquery");
		wp_register_script('top_stories_admin_script_js',plugin_dir_url( __FILE__ ).'js/top-stories.js');
		wp_enqueue_script('top_stories_admin_script_js');

	}
	
	if($wp_version > '3.7.9') {
		// css metro like ui
		wp_register_style( 'top-stories-admin', plugin_dir_url( __FILE__ ).'css/style.3.8.css'  );
	} else {
		wp_register_style( 'top-stories-admin', plugin_dir_url( __FILE__ ). 'css/style.css'  );
	}
	wp_enqueue_style( 'top-stories-admin' );
}
add_action( 'admin_enqueue_scripts', 'top_stories_admin_script' );


add_action('admin_init', 'top_stories_register_settings');
function top_stories_register_settings(){
	register_setting('top_stories_settings_group', 'top_stories_settings', 'top_stories_settings_validate');
}

function top_stories_settings_validate($args){

	/*
	if(!isset($args['top_stories_delay'])) {
		if(isset($args['top_stories_days']) &&
			$args['top_stories_hits']
			) {

			//$options = get_option( 'top_stories_settings' , top_stories_get_defaults() );
			//$options['top_stories_delay'] = $args['top_stories_delay'];
			//$options['top_stories_hits'] = $args['top_stories_hits'];
			////print_r($options);
			////die;
			return $args;
		}
		
	}*/


	if(!isset($args['top_stories_pt'])) $args['top_stories_pt'] = array('post','page','attachment');

	if(!isset($args['top_stories_start'])|| $args['top_stories_start']=="") $args['top_stories_start'] = date("Y-m-d");

	if(!isset($args['top_stories_mail_level'])|| $args['top_stories_mail_level']=="") $args['top_stories_mail_level'] = 0;

	if(!isset($args['top_stories_serie'])|| $args['top_stories_serie']=="") $args['top_stories_serie'] = "ftglpv";

	if(!isset($args['top_stories_hits'])) $args['top_stories_hits'] = 10;
	if((integer)$args['top_stories_hits']>100) $args['top_stories_hits'] = 100;
	if((integer)$args['top_stories_hits']<1) $args['top_stories_hits'] = 1;
	if((integer)$args['top_stories_save_custom']<1) $args['top_stories_save_custom'] = 0;

	if(!isset($args['top_stories_days'])) $args['top_stories_days'] = 30;
	if((integer)$args['top_stories_days']>20000) $args['top_stories_days'] = 20000;	// from the beginning...
	if((integer)$args['top_stories_days']<0) $args['top_stories_days'] = 0;

	if(!isset($args['top_stories_placeholder']) || $args['top_stories_placeholder']=="") {
		$args['top_stories_placeholder'] = plugin_dir_url( __FILE__ ).'images/placeholder.jpg';
	}

	if(!isset($args['top_stories_serie'])) $args['top_stories_serie'] = "ftglpv";
	$args['top_stories_serie'] = preg_replace("/[^ftglpv]/","",$args['top_stories_serie']);

	if(!isset($args['top_stories_delay']) 
		|| (integer)$args['top_stories_delay']<1 
		|| (integer)$args['top_stories_delay']>999
		|| preg_match("/[^0-9]/",$args['top_stories_delay'])
	) {
		//add a settings error because the number specified is invalid
		$args['top_stories_delay'] = '';
		add_settings_error(
			'top_stories_settings',
			'top_stories_errors', 
			__('Please enter a number of seconds between 1 and 999 for Delay parameter!','top-stories-plugin'), 
			'error'
		);
	}

	return $args;
}

add_action('admin_notices', 'top_stories_settings_admin_notices');
function top_stories_settings_admin_notices(){
	//settings_errors();
}

//---------------------------------------------
// Form Top Stories Settings
//---------------------------------------------
function top_stories_analytics() {

	global $wpdb,$wp_version;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __('You do not have sufficient permissions to access this page.','top-stories-plugin') );
	}
	$options = get_option( 'top_stories_settings' , top_stories_get_defaults() );
	if(!is_array($options['top_stories_pt'])) $options['top_stories_pt'] = array('post','page','attachment');

	?>
	<div class="wrap tss">
		<?php
		if(date("Ymd")<"20170120") {
		?>
		<div class="notice is-dismissible" style="overflow:hidden">
			<p>
			<a href="https://www.kickstarter.com/projects/2126170571/wplight-iot-light-for-wordpress" target="_blank"><img src="http://www.barattalo.it/wp-content/uploads/2016/12/wp_gif<?php echo rand(1,3);?>.gif" style="max-width:50%;float:left;max-height:100px;margin-right:20px;" /></a>
			<b>NEWS | Top Stories #IoT device</b>: The author of this plugin is now running a Kickstarter project to create a wi-fi light connected to your WordPress site, throught an easy to use plugin. It's called <b>WPLight</b> and it's compatible with this plugin, so every like/share/tweet can also make blink the light! It's an <a href="https://en.wikipedia.org/wiki/Internet_of_things" target="_blank">#IoT</a> device.<br>
			<a style="text-decoration:none;display:inline-block;padding:5px;background-color:#0099cc;color:#fff;margin:5px 0" href="https://www.kickstarter.com/projects/2126170571/wplight-iot-light-for-wordpress" target="_blank">Discover WPLight</a>
			<br>
			<i>This promo will be automatically removed at the end of the campaign (01/20/2017)</i> :)
			</p>
		</div>
		<?php
		}
		?>

		<form action="options.php" method="post">
			<h2><?php 
				//$from = date("jS \of F Y",strtotime("-{$options['top_stories_days']} days"));
				//$to = date("jS \of F Y");
				$from = date_i18n( get_option( 'date_format' ), strtotime("-{$options['top_stories_days']} days") );
				$to = date_i18n( get_option( 'date_format' ), strtotime("now" ) );
				printf( __( 'Top stories from %1$s to %2$s', 'top-stories-plugin' ), $from, $to );
			?></h2>

		<?php
		$c = execute_row("select count(*) as q from ".$wpdb->prefix."top_stories");
		if($c['q']<1000 && (integer)$options['top_stories_delay']>=5) {
			?>
			<div class="update-nag"><p>It seems that you haven't yet collected enaugh data to show interesting things. Try this trick: go to settings page and put the <b>Delay for ajax calls</b> parameter to <code>1</code> or <code>2</code>, now it is <code><?php echo $options['top_stories_delay'];?></code>, after a few days put it back to <code><?php echo $options['top_stories_delay'];?></code>. <a href="options-general.php?page=top_stories_menu_settings">[go to settings]</a></p></div>
			<?php
		}
		?>


			<?php
			settings_fields( 'top_stories_settings_group' );
			do_settings_sections( 'top_stories_settings_group' );
			//settings_errors();
			if (!function_exists("curl_init"))  {
				?><div class='error settings-error'><p><?php _e("WARNING: CURL module missing in your php configuration, Google+ data are not fetchable.",'top-stories-plugin') ;?></p></div><?php
			}

			?>
			<div id='top_stories_settings_errors'></div>
			<table class="form-table" width="100%" style="margin-top:40px">
				<tr>
					<td width="13%" style="vertical-align:bottom">
						<fieldset>
							<label for="top_stories_hits"><?php _e("Items:","top-stories-plugin");?></label>
							<select class='fat' id="top_stories_hits" name="top_stories_settings[top_stories_hits]">
							<?php
								$vals = array(5,10,15,20,30,40,50,100);
								for($i=1;$i<count($vals);$i++) {
									?><option value="<?php echo $vals[$i];?>" <?php echo $options['top_stories_hits']==$vals[$i] ? "selected='selected'" : ""; ?>><?php echo $vals[$i] ?> <?php _e("posts","top-stories-plugin");?></option><?php
								}
							?>
							</select>
							</fieldset>
						</td>
						<td width="13%" style="vertical-align:bottom">
						<fieldset>
							<label for="top_stories_days"><?php _e("Period:","top-stories-plugin");?></label>
								<select id="top_stories_days" name="top_stories_settings[top_stories_days]">
								<option value="30" <?php echo $options['top_stories_days']=="30" ? "selected='selected'" : ""; ?>><?php _e("1 month","top-stories-plugin");?></option>
								<option value="30" <?php echo $days=="30" ? "selected='selected'" : ""; ?>>* Upgrade! *</option>
							</select>
							</fieldset>
					</td>
					<td width="15.5%%" style="vertical-align:bottom">
						<fieldset class='submittt'><?php submit_button(__('Save & Refresh',"top-stories-plugin")); ?></fieldset>
					</td>
					<?php
					if($wp_version > '3.7.9') $m = "m"; else $m="";
					?>
					<td width="50%" style="padding:0 2.5% 0 2.5%">
						<div id='legend'>
							<div><h3><?php _e("Legenda","top-stories-plugin");?></h3><img src='<?php echo plugin_dir_url( __FILE__ )?>images/up<?php echo $m;?>.png'/> <?php _e("total daily shares increasing","top-stories-plugin");?><br/>
							<img src='<?php echo plugin_dir_url( __FILE__ )?>images/down<?php echo $m;?>.png'/> <?php _e("total daily shares decreasing","top-stories-plugin");?></div>
							<div><img src='<?php echo plugin_dir_url( __FILE__ )?>images/fire50<?php echo $m;?>.png'/> <?php _e("&gt;50 shares today","top-stories-plugin");?><br/>
							<img src='<?php echo plugin_dir_url( __FILE__ )?>images/fire100<?php echo $m;?>.png'/> <?php _e("&gt;100 shares today","top-stories-plugin");?><br/>
							<img src='<?php echo plugin_dir_url( __FILE__ )?>images/fire1000<?php echo $m;?>.png'/> <?php _e("&gt;1000 shares today","top-stories-plugin");?></div>
						</div>
					</td>
				</tr>
			</table>
			
			<?php
			echo top_stories_getStats(
				$options['top_stories_days'],
				$options['top_stories_hits'],
				$options['top_stories_placeholder'],
				$options['top_stories_pt']
			);
			?>
			
			<br/><br/>
			<h2><?php _e("Global viral trend","top-stories-plugin");?></h2>
			<?php
			if($options['top_stories_days']==20000) {
				?><p><?php _e("Chart not available for period \"ever\".","top-stories-plugin");?></p><?php 
			} else {
				?>
				<p>
					<?php _e("This multi line chart is interactive, you can hide and show lines by clicking the series in the legend.<br>You can see data by hovering the mouse on the lines.","top-stories-plugin");?>
				</p>
				<?php
				/*
					this iframe i used to load dynamically global chart
				*/
				?>
				<iframe src="<?php echo esc_url(add_query_arg( 'action', 'get_chart', admin_url( 'admin-ajax.php' ) )); ?>&d=<?php echo $options['top_stories_days']?>" width="92.5%" height="300"></iframe>
				<?php
			}
			?>
			<div class='statblock' style='padding-bottom:30px'>
				<h2><?php _e("Global social distribution","top-stories-plugin");?></h2>
				<p>
					<?php _e("This chart shows the percentage of each social networks in the selected period.","top-stories-plugin");?>
				</p>
				<?php
				/*
					this iframe i used to load dynamically pie chart
				*/
				?>
				<iframe src="<?php echo esc_url(add_query_arg( 'action', 'get_pie_chart', admin_url( 'admin-ajax.php' ) )); ?>&d=<?php echo $options['top_stories_days']?>" width="100%" height="300"></iframe>

			</div>
			<div class='statblock last' style='padding-bottom:30px'>
				<h2><?php _e("Specific social network trend","top-stories-plugin");?></h2>
				<p>
					<?php _e("This chart shows the trend of all your interaction over an year.","top-stories-plugin");?>
				</p>
				<?php
				/*
					this iframe i used to load dynamically column chart
				*/
				?>
				<iframe src="<?php echo esc_url(add_query_arg( 'action', 'get_col_chart', admin_url( 'admin-ajax.php' ) )); ?>&sn=all" width="100%" height="300"></iframe>
			</div>			

			<?php
			/*
			pass data if saving
			*/
			$custom_post_types = get_post_types( array( 'public' => true ) );
			foreach($custom_post_types as $typ) {
				$checked = in_array($typ, $options['top_stories_pt']) ?  1 : 0;
				if($checked) echo "<input type='hidden' name='top_stories_settings[top_stories_pt][]' value='$typ' />";
			}

			if(!isset($options['top_stories_serie'])) $options['top_stories_serie'] = "ftglpv";
			$options['top_stories_serie'] = preg_replace("[^ftglpv]","",$options['top_stories_serie']);


			?>
			<input type="hidden" name="top_stories_settings[top_stories_placeholder]" value="<?php echo esc_url( $options['top_stories_placeholder'] ); ?>"/>

			<input name="top_stories_settings[top_stories_delay]" type="hidden" id="top_stories_delay" value="<?php echo $options['top_stories_delay']; ?>"/>

			<input type="hidden" name="top_stories_settings[top_stories_save_custom]" value="<?php echo $options['top_stories_save_custom'];?>"/> 
			<input type="hidden" name="top_stories_settings[top_stories_serie]" value="<?php echo $options['top_stories_serie'];?>"/> 
			<input name="top_stories_settings[top_stories_start]" type="hidden" value="<?php echo $options['top_stories_start']; ?>"/>
			<input name="top_stories_settings[top_stories_mail_level]" type="hidden" value="<?php echo $options['top_stories_mail_level']; ?>"/>

		</form>
	</div>
	<?php
}

function top_stories_settings() {
	global $wpdb,$wp_version;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}
	$options = get_option( 'top_stories_settings' , top_stories_get_defaults() );
	//print_r($options);die;
	if(!is_array($options['top_stories_pt'])) $options['top_stories_pt'] = array('post','page','attachment');


	?>
	<div class="wrap tss">
		<form action="options.php" method="post">

			<h2><?php _e("Top Stories Settings","top-stories-plugin");?></h2>
			<?php
			//echo $options['top_stories_start']."(";
			settings_fields( 'top_stories_settings_group' );
			do_settings_sections( 'top_stories_settings_group' );
			//settings_errors();
			if (!function_exists("curl_init"))  {
				?><div class='error settings-error'><p><?php _e("WARNING: CURL module missing in your php configuration, Google+ data are not fetchable.",'top-stories-plugin') ;?></p></div><?php
			}


			?>

			<div id='top_stories_settings_errors'></div>
			<div id='poststuff' style="margin-right:5%">
				<div class='postbox' style='max-width:600px;width:60%;float:left;'>

					<!--<h3 style="cursor:default">Plugin Settings</h3>-->
					<table class="form-table" style="margin:0 10px;width:95%">
						<tr>
							<th scope="row"><?php _e("Analyze these post types","top-stories-plugin");?></th>
							<td>
								<fieldset>
								<?php
								$custom_post_types = get_post_types( array(
									// Set to TRUE to return only public post types
									'public' => true
								) );
								foreach($custom_post_types as $typ) {
									$checked = in_array($typ, $options['top_stories_pt']) ?  "checked" : "";
									echo "<label><input type='checkbox' name='top_stories_settings[top_stories_pt][]' value='$typ' ".$checked."/> $typ</label><br/>";
								}
								?>
								</fieldset>
							</td>
						</tr>


						<tr>
							<th scope="row"><label for="serie"><?php _e("Social Networks","top-stories-plugin");?></label></th>
							<td>
								<fieldset id='serie_checks'>
									<input type="hidden" id="serie" name="top_stories_settings[top_stories_serie]" value="<?php echo  $options['top_stories_serie'] ; ?>" size="6"/>
									<span class="description"><?php _e("Fetch data only for these social networks.","top-stories-plugin");?></span>
									<br>
									<?php
									$column = top_stories_get_columns();
									?>
									<label><input type='checkbox' id='facebook'><?php echo $column['facebook'];?> facebook (<a href="<?php echo admin_url('options.php?page=top_stories_facebook_config');?>" style='color:red'>config</a>)</label><br>
									<label><input type='checkbox' id='twitter'><?php echo $column['twitter'];?> twitter</label> <a href='http://www.barattalo.it/other/how-to-bring-back-the-twitter-count/' target='_blank'>(<span style='color:red'>instructions</span>)</a><br>
									<label><input type='checkbox' id='google'><?php echo $column['google'];?> google+</label><br>
									<label><input type='checkbox' id='linkedin'><?php echo $column['linkedin'];?> linkedin</label><br>
									<label><input type='checkbox' id='pinterest'><?php echo $column['pinterest'];?> pinterest</label><br>
									<label><input type='checkbox' id='vkontakte'><?php echo $column['vkontakte'];?> vkontakte</label><br>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="placeholder_url"><?php _e("Placeholder image","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									<input type="text" id="placeholder_url" name="top_stories_settings[top_stories_placeholder]" value="<?php echo esc_url( $options['top_stories_placeholder'] ); ?>" size="35"/><br/>
									<span class="description"><?php _e("Url used if there is no image (choose a square file about 150x150 pixel).","top-stories-plugin");?></span>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="top_stories_delay"><?php _e("Delay for ajax calls","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									
										<input name="top_stories_settings[top_stories_delay]" size="3" maxlength="3" type="text" id="top_stories_delay" value="<?php echo (isset($options['top_stories_delay']) && $options['top_stories_delay'] != '') ? $options['top_stories_delay'] : ''; ?>"/> <?php _e("seconds","top-stories-plugin");?>
										<br />
										<span class="description"><?php _e("Calls are delayed to not overload servers.","top-stories-plugin");?></span>
										<span class="description">When you first install this plugin, put this value to <code>1</code> or <code>2</code> to speed up the data grabbing. After a few days, put it back -at least- to <code>15</code> seconds to not overload servers.</span>

									
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="top_stories_save_custom"><?php _e("Export data in custom fields","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									<select name="top_stories_settings[top_stories_save_custom]" id="top_stories_save_custom">
										<option value="1" <?php echo (isset($options['top_stories_save_custom']) && $options['top_stories_save_custom'] == '1') ? "selected='selected'" : ""; ?>><?php _e("Yes","top-stories-plugin");?></option>
										<option value="0" <?php echo (isset($options['top_stories_save_custom']) && $options['top_stories_save_custom'] == '0') ? "selected='selected'" : ""; ?>><?php _e("No","top-stories-plugin");?></option>
									</select>
									<br />
									<span class="description"><?php _e("Save data in custom fields so you can use in your theme.","top-stories-plugin");?></span>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="top_stories_mail_level"><?php _e("Send mail notice level","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									<input name="top_stories_settings[top_stories_mail_level]" size="10" maxlength="10" type="text" id="top_stories_mail_level" value="<?php echo (isset($options['top_stories_mail_level']) && $options['top_stories_mail_level'] != '') ? $options['top_stories_mail_level'] : '0'; ?>"/>
									<br />
									<span class="description"><?php _e("Top Stories plugin will send an email notice to the author when its post will pass this number of social interactions. Put 0 to stop sending emails.","top-stories-plugin");?><br/>
									<b>*Upgrade to use this feature*</b>
									</span>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="top_stories_start"><?php _e("Activation date (read only)","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									<input name="top_stories_settings[top_stories_start]" size="10" maxlength="10" type="text" id="top_stories_start" value="<?php echo (isset($options['top_stories_start']) && $options['top_stories_start'] != '') ? $options['top_stories_start'] : ''; ?>"/> (Y-m-d)
									<br />
									<span class="description"><?php _e("The plugin starts tracking posts from this date. It's used to handle old posts correctly.","top-stories-plugin");?><br/>
									</span>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="top_stories_start"><?php _e("Database usage","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									<?php
									$c = execute_row("select count(*) as q from ".$wpdb->prefix."top_stories");
									echo number_format($c["q"],0,'.',',');
									?> <?php _e("rows saved","top-stories-plugin");?><br>
									<span class="description"><?php _e("For better performance switch the this table in your database to InnoDB engine:","top-stories-plugin");?> <code><?php echo $wpdb->prefix;?>top_stories</code>
									</span>
								</fieldset>
								<?php submit_button(); ?>
							</td>
						</tr>

					
					</table>
					
				</div>

				<div class="postbox" style='max-width:600px;width:10%;float:left;margin-left:20px'>
					<h3 style="cursor:default">&nbsp; <?php _e("Author","top-stories-plugin");?></h3>
					<table class="form-table">
						<tr><td><p><?php _e("This plugin was made by Giulio Pons, visit the <a href=\"http://www.barattalo.it/top-stories-plugin-widget/\" target=\"_blank\">plugin homepage</a> and follow me on <a href=\"http://codecanyon.net/user/ginoplusio\" target=\"_blank\">CodeCanyon</a>.","top-stories-plugin");?></p></td></tr>
					</table>
				</div>
				<div class="postbox" style='max-width:600px;width:10%;float:left;margin-left:20px'>
					<h3 style="cursor:default">&nbsp; <?php _e("Shortcodes","top-stories-plugin");?></h3>
					<table class="form-table">
						<tr><td><p><?php _e("You can use the widget to show ranks of posts in your sidebars, but you can also use shortcodes inside posts and pages. See the documentation downloaded with this plugin to use <code>shortcodes</code>.","top-stories-plugin");?><br>
						<b>*Upgrade to use this feature*</b></p></td></tr>
					</table>
				</div>

				<div class="postbox" style='max-width:600px;width:10%;float:left;margin-left:20px'>
					<h3 style="cursor:default">&nbsp; <?php _e("Your feedback is needed!","top-stories-plugin");?></h3>
					<table class="form-table">
						<tr><td><p><?php _e("Do you have a feedback to improve this plugin, please write it in the comments of this plugin on CodeCanyon.","top-stories-plugin");?> <a href="http://codecanyon.net/item/top-social-stories-plugin-and-widget/5888553/comments" target="_blank">GO!</a></p></td></tr>
					</table>
				</div>


			</div>

			<input type="hidden" name="top_stories_settings[top_stories_hits]" value="<?php echo $options['top_stories_hits']; ?>"/>

			<input type="hidden" name="top_stories_settings[top_stories_days]" value="<?php echo $options['top_stories_days']; ?>"/>

		</form>
	</div>
	<?php
}


//
//---------------------------------------------
// On activation create table to store social historical data
//---------------------------------------------
function top_stories_activate() {
	global $wpdb;

	$wpdb->query("
	CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."top_stories` (
		`id_post` int(10) unsigned NOT NULL,
		`dt_day` date NOT NULL,
		`facebook_shares` int(11) NOT NULL,
		`twitter_shares` int(11) NOT NULL,
		`google_shares` int(11) NOT NULL,
		`facebook_shares_start` int(11) NOT NULL,
		`twitter_shares_start` int(11) NOT NULL,
		`google_shares_start` int(11) NOT NULL,
		PRIMARY KEY  (`id_post`,`dt_day`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Data for top stories plugin';");

	$ar = execute_row ("SELECT * FROM `".$wpdb->prefix."top_stories`");

	/* upgrade old table for Google+ support*/
	if(!isset($ar['google_shares'])) {
		$wpdb->query("
			ALTER TABLE  `".$wpdb->prefix."top_stories` ADD  `google_shares` INT NOT NULL DEFAULT  '0',
			ADD `google_shares_start` INT NOT NULL DEFAULT  '0';
		");
	}

	/* upgrade old table for Linkedin support*/
	if(!isset($ar['linkedin_shares'])) {
		$wpdb->query("
			ALTER TABLE  `".$wpdb->prefix."top_stories` ADD  `linkedin_shares` INT NOT NULL DEFAULT  '0',
			ADD `linkedin_shares_start` INT NOT NULL DEFAULT  '0';
		");
	}

	/* upgrade old table for Pinterest support*/
	if(!isset($ar['pinterest_shares'])) {
		$wpdb->query("
			ALTER TABLE  `".$wpdb->prefix."top_stories` ADD  `pinterest_shares` INT NOT NULL DEFAULT  '0',
			ADD `pinterest_shares_start` INT NOT NULL DEFAULT  '0';
		");
	}

	/* upgrade old table for Vkontakte support*/
	if(!isset($ar['vkontakte_shares'])) {
		$wpdb->query("
			ALTER TABLE  `".$wpdb->prefix."top_stories` ADD  `vkontakte_shares` INT NOT NULL DEFAULT  '0',
			ADD `vkontakte_shares_start` INT NOT NULL DEFAULT  '0';
		");
	}


	/*
		check fields type (fix old bugged versions)
	*/
	$rs = $wpdb->get_results("select COLUMN_NAME,COLUMN_TYPE from information_schema.COLUMNS where TABLE_NAME='".$wpdb->prefix."top_stories' and COLUMN_TYPE LIKE '%unsigned%'", OBJECT);
	if ($rs) {
		foreach ($rs as $row) {
			if($row->COLUMN_NAME!="id_post") {
				$wpdb->query("ALTER TABLE  `".$wpdb->prefix."top_stories` CHANGE  `".$row->COLUMN_NAME."`  `".$row->COLUMN_NAME."` INT NOT NULL;");
			}
		}
	}

}
register_activation_hook( __FILE__, 'top_stories_activate' );


function top_stories_get_pic($post,$placeholder) {
	$pic = "";
	$pic = get_the_post_thumbnail( $post->post_id, 'thumbnail' );
	if(!$pic) {
		preg_match("#img(.*)src=('|\")([^'\"]*)('|\")#",$post->post_content,$matches);
		if(isset($matches[3])) {
			preg_match_all('/(width|height)=\"\d*\"\s/',$post->post_content,$ar);
			$width = preg_replace("/[^0-9]/","",$ar[0][0]);
			$height = preg_replace("/[^0-9]/","",$ar[0][1]);
			$pic = "<img src=\"".$matches[3]."\" width=\"$width\" height=\"$height\" />"; 
		} else $pic = "";
	}
	if($pic) {
		preg_match_all('/(width|height)=\"\d*\"\s/',$pic,$ar);
		$pic = "<span class='img'>".preg_replace( '/(width|height)=\"\d*\"\s/', "", $pic )."</span>";
		if(isset($ar[0][1])) {
			$width = preg_replace("/[^0-9]/","",$ar[0][0]);
			$height = preg_replace("/[^0-9]/","",$ar[0][1]);
			if($width/$height >= 1) {
				// horizontal
				if($width/$height > 8/5) {
					// very horizontal
					$pic = str_replace(" src="," class='long' src=",$pic);
				}
			} else {
				// vertical
				$newheight = round ( $height * 80 / $width);

				if($newheight>=100) {
					$newheight = round(($newheight-50)/2);
				} else {
					$newheight = round(($newheight)/2);
				}
				$pic = str_replace(" src="," class='vertical' style='margin-top:-".($newheight)."px' src=",$pic);
			}
		}
	}
	if($pic=="" && $placeholder){
		$pic = "<span class='img'><img src=\"".$placeholder."\"/></span>";
	}
	return $pic;

}

function top_stories_get_days($id) {
	// used to merge data from sources and display
	// daily data (every item in the historical table has
	// the total count, for chart is needed daily).
	//
	global $wpdb;
	$ieri = execute_row("select * from ".$wpdb->prefix."top_stories where id_post='".$id."' and dt_day<'".date("Y-m-d")."' order by dt_day desc limit 0,1");
	if(!is_array($ieri)) {
		$ieri['facebook_shares']=0; $ieri['twitter_shares']=0; $ieri['google_shares']=0; $ieri['tot']=0; 
		$ieri['linkedin_shares']=0; $ieri['pinterest_shares']=0; $ieri['vkontakte_shares']=0;
	} else {
		$ieri['tot'] = $ieri['facebook_shares'] + $ieri['twitter_shares'] + $ieri['google_shares'] +
			$ieri['linkedin_shares'] + $ieri['pinterest_shares'] + $ieri['vkontakte_shares'];
	}
	
	$oggi = execute_row("select * from ".$wpdb->prefix."top_stories where id_post='".$id."' and dt_day='".date("Y-m-d")."'");
	if(!is_array($oggi)) {
		$oggi = $ieri; 
	} else {
		$oggi['tot'] = $oggi['facebook_shares'] + $oggi['twitter_shares'] + $oggi['google_shares'] +
			$oggi['linkedin_shares'] + $oggi['pinterest_shares'] + $oggi['vkontakte_shares'];
	}

	$altroieri = execute_row("select * from ".$wpdb->prefix."top_stories where id_post='".$id."' and dt_day<'".date("Y-m-d",strtotime("-1 day"))."' order by dt_day desc limit 0,1");
	if(!is_array($altroieri)) {
		$altroieri['facebook_shares']=0; $altroieri['twitter_shares']=0; $altroieri['google_shares']=0; $altroieri['tot']=0; 
		$altroieri['linkedin_shares']=0; $altroieri['pinterest_shares']=0; $altroieri['vkontakte_shares']=0;
	} else {
		$altroieri['tot'] = $altroieri['facebook_shares'] + $altroieri['twitter_shares']  + $altroieri['google_shares'] +
			$altroieri['linkedin_shares'] + $altroieri['pinterest_shares'] + $altroieri['vkontakte_shares'];
	}

	return array("ieri"=>$ieri,"oggi"=>$oggi,"altroieri"=>$altroieri);

}
// Extract Top Stories for Admin panel, with stats
function top_stories_getStats($days=30,$howmany=6,$placeholder="", $ptArr = null) {
	global $wpdb;

	// post types filtering
	if(!$ptArr) $ptStr = "'page','post','attachment'"; else $ptStr = "'".implode("','",$ptArr)."'";

	// check for cached
	$transient_key=sha1($days." ".$howmany." ".$ptStr);

	$ret=get_transient( $transient_key );

	if ($ret !== false ) return $ret;


	$data = date("Y-m-d",strtotime("-".((2^3)*3+6)." days",strtotime( date("Y-m-d") )));

	 $querystr = "
		SELECT ID as post_id,
			(SELECT facebook_shares+twitter_shares+google_shares+linkedin_shares+pinterest_shares+vkontakte_shares FROM ".$wpdb->prefix."top_stories 
				WHERE id_post=ID ORDER BY dt_day DESC LIMIT 0,1) as a,
			post_title,post_content
		FROM ".$wpdb->prefix."posts 
		WHERE (post_date > '".$data."') and post_status='publish'
		AND post_type in ($ptStr)
		AND (SELECT facebook_shares+twitter_shares+google_shares+linkedin_shares+pinterest_shares+vkontakte_shares FROM ".$wpdb->prefix."top_stories 
				WHERE id_post=ID ORDER BY dt_day DESC LIMIT 0,1)>0
		ORDER BY a DESC
		LIMIT 0,".$howmany."
		";
	$pageposts = $wpdb->get_results($querystr, OBJECT);

	$out = ""; 
	if ($pageposts) {
		foreach ($pageposts as $post) {

			/*
				Find post Image
			*/
			$pic = top_stories_get_pic($post,$placeholder);

			$growth = top_stories_get_days($post->post_id);
			$oggi = $growth["oggi"];
			$ieri = $growth["ieri"];
			$altroieri = $growth["altroieri"];
			
			$deltaTot = $oggi['tot'] - $ieri['tot'];

			$deltaLike = $deltaTot - ($ieri['tot'] - $altroieri['tot']);
					//     fatti oggi   -   fatti ieri

			$icon = $icon2="";
			if($deltaTot > 1000) $icon = " fire1000";
				elseif($deltaTot > 100) $icon = " fire100";
				elseif($deltaTot > 50) $icon = " fire50";
			if($deltaLike>0) {$icon2="sale"; $textlike=", +$deltaLike more than yesterday";}
				elseif($deltaLike<0) {$icon2="scende"; $textlike=", ".number_format($deltaLike,0,'.',',')." less than yesterday";}

			$deltaFb = $oggi['facebook_shares'] - $ieri['facebook_shares'];
			$deltaTw = $oggi['twitter_shares'] - $ieri['twitter_shares'];
			$deltaGo = $oggi['google_shares'] - $ieri['google_shares'];
			$deltaPi = $oggi['pinterest_shares'] - $ieri['pinterest_shares'];
			$deltaLi = $oggi['linkedin_shares'] - $ieri['linkedin_shares'];
			$deltaVk = $oggi['vkontakte_shares'] - $ieri['vkontakte_shares'];

			$out.="<li><a href='".home_url()."/?p=".$post->post_id."' target='_blank'>";
			$out.= $pic;
			$out.="<span class='tit back'>".$post->post_title."</span> ";

			/*$out.="<span class='stats{$icon}'>".
					("<b class='{$icon2}' title='".__("Today","top-stories-plugin")." ".
					number_format($deltaFb,0,'.',',')." fcb, ".
					number_format($deltaTw,0,'.',',')." twi, ".
					number_format($deltaGo,0,'.',',')." g+, ".
					number_format($deltaLi,0,'.',',')." lnk, ".
					number_format($deltaPi,0,'.',',')." pin, ".
					number_format($deltaVk,0,'.',',')." vk".
				"'>".($deltaTot>0?"+":"").number_format($deltaTot,0,'.',',')."</b>")
					."</span>";*/


			$alt = sprintf( __('Yesterday %1$s, today %2$s', 'top-stories-plugin' ), 
				number_format(($ieri['tot'] - $altroieri['tot']),0,'.',','), 
				number_format($deltaTot,0,'.',',') );

			$out.="<span class='stats{$icon}'>".
					("<b class='{$icon2}' title=\"".$alt."\">".($deltaTot>0?"+":"").number_format($deltaTot,0,'.',',')."</b>")
					."</span>";


			$q = $post->a < 1000 ? $post->a : number_format($post->a / 1000 ,1) ."k";
			$out.="<span class='num'>{$q}</span> ";
			$out.="</a><a href='#' rel='".
				esc_url(add_query_arg( 'action', 'get_chart', admin_url( 'admin-ajax.php' ) ) ."&d=".($days>365 ? 14 : $days)."&p=".$post->post_id).
				"' class='stat'>c</a></li>";
				

		}
	} else {
		$out.="<li><span style='padding:5px'>".__("Sorry, can't find nothing in the selected period.","top-stories-plugin")."</span></li>";
	}


	 $querystr = "
 		SELECT ID as post_id,x.dt_day,(x.facebook_shares+x.twitter_shares+x.google_shares+x.linkedin_shares+x.pinterest_shares) as a,
			(x.facebook_shares - x.facebook_shares_start +x.twitter_shares - x.twitter_shares_start +x.google_shares - x.google_shares_start + x.linkedin_shares - x.linkedin_shares_start + x.pinterest_shares - x.pinterest_shares_start + x.vkontakte_shares - x.vkontakte_shares_start) as d
			,post_title,post_content
		 FROM ".$wpdb->prefix."top_stories x inner join ".$wpdb->prefix."posts on ID=id_post AND dt_day='".date("Y-m-d")."'
		 WHERE (x.facebook_shares - x.facebook_shares_start +x.twitter_shares - x.twitter_shares_start +x.google_shares - x.google_shares_start+ x.linkedin_shares - x.linkedin_shares_start + x.pinterest_shares - x.pinterest_shares_start+ x.vkontakte_shares - x.vkontakte_shares_start) >0 AND post_status='publish'
		AND post_type in ($ptStr)
		ORDER BY d DESC
		LIMIT 0,".$howmany."
		";
		//echo $querystr;
	$pageposts = $wpdb->get_results($querystr, OBJECT);

	$out2 = ""; 
	if ($pageposts) {
		foreach ($pageposts as $post) {

			/*
				Find post Image
			*/
			$pic = top_stories_get_pic($post,$placeholder);
			$growth = top_stories_get_days($post->post_id);
			$oggi = $growth["oggi"];
			$ieri = $growth["ieri"];
			$altroieri = $growth["altroieri"];

			$deltaTot = $oggi['tot'] - $ieri['tot'];

			$deltaLike = $deltaTot - ($ieri['tot'] - $altroieri['tot']);
					//     fatti oggi   -   fatti ieri

			$icon = $icon2="";
			if($deltaTot > 1000) $icon = " fire1000";
				elseif($deltaTot > 100) $icon = " fire100";
				elseif($deltaTot > 50) $icon = " fire50";
			if($deltaLike>0) {$icon2="sale"; $textlike=", +$deltaLike more than yesterday";}
				elseif($deltaLike<0) {$icon2="scende"; $textlike=", ".number_format($deltaLike,0,'.',',')." less than yesterday";}

			$deltaFb = $oggi['facebook_shares'] - $ieri['facebook_shares'];
			$deltaTw = $oggi['twitter_shares'] - $ieri['twitter_shares'];
			$deltaGo = $oggi['google_shares'] - $ieri['google_shares'];
			$deltaPi = $oggi['pinterest_shares'] - $ieri['pinterest_shares'];
			$deltaLi = $oggi['linkedin_shares'] - $ieri['linkedin_shares'];
			$deltaVk = $oggi['vkontakte_shares'] - $ieri['vkontakte_shares'];

			$out2.="<li><a href='".home_url()."/?p=".$post->post_id."' target='_blank'>";
			$out2.= $pic;
			$out2.="<span class='tit back'>".$post->post_title."</span> ";

			$alt = sprintf( __('Yesterday %1$s, today %2$s', 'top-stories-plugin' ), 
				number_format(($ieri['tot'] - $altroieri['tot']),0,'.',','), 
				number_format($deltaTot,0,'.',',') );

			$out2.="<span class='stats{$icon}'>".
					("<b class='{$icon2}' title=\"".$alt."\">".($deltaTot>0?"+":"").number_format($deltaTot,0,'.',',')."</b>")
					."</span>";

			$q = $post->a < 1000 ? $post->a : number_format($post->a / 1000 ,1) ."k";
			$out2.="<span class='num'>{$q}</span> ";
			$out2.="</a><a href='#' rel='".
				esc_url(add_query_arg( 'action', 'get_chart', admin_url( 'admin-ajax.php' ) ) ."&d=".($days>365 ? 14 : $days)."&p=".$post->post_id).
				"' class='stat'>c</a></li>";
				

		}
	} else {
		$out2.="<li><span style='padding:5px'>".__("Can't find any activity of your posts on social networks today.","top-stories-plugin")."</span></li>";

	}


	$querystr="SELECT ".$wpdb->prefix."users.ID,user_login,user_nicename,
				SUM((SELECT facebook_shares+twitter_shares+google_shares+linkedin_shares+pinterest_shares+vkontakte_shares
				FROM ".$wpdb->prefix."top_stories 
					WHERE id_post=".$wpdb->prefix."posts.ID ORDER BY dt_day DESC LIMIT 0,1)) as a,
				display_name
			FROM ".$wpdb->prefix."posts 
	INNER JOIN ".$wpdb->prefix."users ON ".$wpdb->prefix."users.ID=post_author
			WHERE (post_date > '".$data."') AND post_status='publish'
	GROUP BY display_name
	HAVING a >0 
			ORDER BY a DESC
			LIMIT 0,10";
	$userposts = $wpdb->get_results($querystr, OBJECT);
	$out3= "";
	
	if ($userposts) {
		foreach ($userposts as $user) {
			$out3.="<li><a target='_balnk' href='/author/".$user->user_nicename."/'>".$user->display_name."</a> <b>".number_format($user->a,0,'.',',')."</b></li>";
		}
	} else {
		$out3.="<li><span style='padding:5px'>".__("Nobody<br/>Sorry, can't find any powerful author.","top-stories-plugin")."</span></li>";
	}



	$querystr="SELECT ".$wpdb->prefix."users.ID,user_login,user_nicename,
				SUM((SELECT facebook_shares+twitter_shares+google_shares+pinterest_shares+linkedin_shares+vkontakte_shares - facebook_shares_start-twitter_shares_start-google_shares_start-pinterest_shares_start-linkedin_shares_start
				-vkontakte_shares_start
				FROM ".$wpdb->prefix."top_stories 
					WHERE id_post=".$wpdb->prefix."posts.ID AND dt_day='".date("Y-m-d")."' LIMIT 0,1)) as a,
				display_name
			FROM ".$wpdb->prefix."posts 
	INNER JOIN ".$wpdb->prefix."users ON ".$wpdb->prefix."users.ID=post_author
	WHERE post_status='publish'
	GROUP BY display_name
	HAVING a >0 
			ORDER BY a DESC
			LIMIT 0,10";
	$userposts = $wpdb->get_results($querystr, OBJECT);
	$out4= "";
	
	if ($userposts) {
		foreach ($userposts as $user) {
			$out4.="<li><a target='_balnk' href='/author/".$user->user_nicename."/'>".$user->display_name."</a> <b>".number_format($user->a,0,'.',',')."</b></li>";
		}
	} else {
		$out4.="<li><span style='padding:5px'>".__("Nobody<br/>...Can't find any powerful author today.","top-stories-plugin")."</span></li>";
	}

	$title = __( $days==20000 ? "Top stories stats ever" : 'Top stories on %1$s days' , "top-stories-plugin");
	$title = str_replace('%1$s',$days,$title);

	$authtitle = __( $days==20000 ? "Most powerful authors ever" : 'Most powerful authors on %1$s days' , "top-stories-plugin");
	$authtitle = str_replace('%1$s',$days,$authtitle);
	
	$out = "<div class='statblock'><h3>".$title."</h3><ul class='top-stories'>{$out}</ul></div>".
		"<div class='statblock last'><h3>".__("Most viral today","top-stories-plugin")."</h3><ul class='top-stories'>{$out2}</ul></div><br style='clear:both'/>".
		"<div class='statblock'><h3>".$authtitle."</h3><ol>{$out3}</ol></div>
		<div class='statblock last'><h3>".__("Most powerful authors today","top-stories-plugin")."</h3><ol>{$out4}</ol></div>
	<br style='clear:both'/><div id='chartpost'><iframe></iframe><a href='#'>X</a></div>";

	set_transient( $transient_key,$out,60 * 15); // quarto d'ora di cache

	return $out;
}
if(!function_exists("execute_row")) {
	function execute_row($sql) {
		global $wpdb;
		$sql = trim($sql);if(!preg_match("/(limit +0,1)$/i",$sql)) $sql.=" limit 0,1"; 
		$r = "";
		$rs = $wpdb->get_results($sql, ARRAY_A);
		if($rs) foreach ($rs as $r) return $r; else return $r;
	}
}






//---------------------------------------------
// Register Top Stories Widget
//---------------------------------------------
add_action('widgets_init', create_function('', 'return register_widget("top_stories_widget");'));









//---------------------------------------------
// Ajax save data
//---------------------------------------------
add_action( 'wp_ajax_nopriv_save_data_sn', 'save_data_sn_callback' );
add_action( 'wp_ajax_save_data_sn', 'save_data_sn_callback' );
function save_data_sn_callback() {
	ob_clean();
	global $wpdb;

	$debug = true;

	if($debug) print_r($_POST);

	$id = isset($_POST['id']) ? (integer)$_POST['id'] : null;
	if($id) {
		$options = wp_parse_args(get_option('top_stories_settings'), top_stories_get_defaults());
		$force_data = isset($_POST['force']) ? $_POST['force'] : "";
		if($force_data) {
			$date = date_parse($force_data);
			if (checkdate($date["month"], $date["day"], $date["year"])) {
				$force_data = $date["year"]."-".str_pad($date["month"],2,"0",STR_PAD_LEFT)."-". str_pad($date["day"],2,"0",STR_PAD_LEFT);
			} else {
				$force_data = "";
			}
		}
		$shares = isset($_POST['shares']) ? (integer)$_POST['shares'] : 0;
		$tweet = isset($_POST['tweet']) ? (integer)$_POST['tweet'] : 0;
		$google = isset($_POST['google']) ? (integer)$_POST['google'] : 0;
		$linkedin = isset($_POST['linkedin']) ? (integer)$_POST['linkedin'] : 0;
		$pinterest = isset($_POST['pinterest']) ? (integer)$_POST['pinterest'] : 0;
		$vkontakte = isset($_POST['vk']) ? (integer)$_POST['vk'] : 0;

		if($shares>0 || $tweet>0 || $pinterest>0 || $linkedin>0 || $google>0 || $vkontakte>0 || $force_data) {

			$d = date("Y-m-d", strtotime(current_time( 'mysql', true)));

			/*
				daily counters are changed, modifiy historical table
			*/
			$sql = "select * from ".$wpdb->prefix."top_stories where id_post='".$id."' and dt_day<='".$d."' order by dt_day desc limit 0,2";
			//echo $sql;
			$rs = $wpdb->get_results($sql,ARRAY_A);
			$check = ""; 
			$ieri = array('facebook_shares'=>0,'twitter_shares'=>0,'google_shares'=>0,'linkedin_shares'=>0,'pinterest_shares'=>0,'vkontakte_shares'=>0);
			foreach($rs as $r) {
				//echo "r=";print_r($r);
				if($r["dt_day"]==$d) {
					$check = $r;
					if($debug) echo "check = r\n";
				} else {
					if($debug) echo "ieri = r\n";
					$ieri = $r;break;
				}
			}

			if($debug)  {
				echo "fine\n";
				print_r($check);
				print_r($ieri);
			}


			$change = false;
			$tots = $shares + $tweet + $google + $linkedin + $pinterest + $vkontakte; // adesso
			$totc = isset($check['facebook_shares']) ? (
				$check['facebook_shares'] +$check['twitter_shares'] +$check['google_shares'] +$check['linkedin_shares'] + $check['pinterest_shares'] +$check['vkontakte_shares']) : 0; // oggi su db

			if(is_array($check)) {
				if($debug) echo  "need update?\n";
				// update

				if($debug) echo  $tots . " > ".$totc." ? \n";
				if($debug) echo  gettype($tots) . " > ".gettype($totc)." ? \n";

				if($tots>$totc) {
					if($debug) echo "yes\n";
				
					$wpdb->query($sql = "update ".$wpdb->prefix."top_stories set facebook_shares='".$shares."',twitter_shares='".$tweet."',google_shares='".$google."',pinterest_shares='".$pinterest."',linkedin_shares='".$linkedin."',vkontakte_shares='".$vkontakte."' where id_post='".$id."' and dt_day='".$d."'");
					$change = true;
					if($debug) echo $sql."\n";


				} else {
					if($debug) echo "no\n";
				}
				

			} else {
				// if it is the first call of today and it's a post published
				// before this plugin activation, write the same data at the
				// force_data record date.

				if($force_data && $ieri=="") {
					if($debug) echo  "need force data\n";
					// insert
					$wpdb->query($sql = "insert ignore into ".$wpdb->prefix."top_stories (id_post,dt_day,facebook_shares,twitter_shares,google_shares,linkedin_shares,pinterest_shares,vkontakte_shares,facebook_shares_start,twitter_shares_start,google_shares_start,linkedin_shares_start,pinterest_shares_start,vkontakte_shares_start) values (
						'$id','{$force_data}','".$shares."','".$tweet."','".$google."','".$linkedin."','".$pinterest."','".$vkontakte."','0','0','0','0','0','0')");
					$ieri['facebook_shares']=$shares;
					$ieri['twitter_shares']=$tweet;
					$ieri['google_shares']=$google;
					$ieri['linkedin_shares']=$linkedin;
					$ieri['pinterest_shares']=$pinterest;
					$ieri['vkontakte_shares']=$vkontakte;
					if($debug) echo $sql."\n";
				}

				if(
					(integer)$shares >= (integer)$ieri['facebook_shares'] &&
					(integer)$tweet >= (integer)$ieri['twitter_shares'] &&
					(integer)$google >= (integer)$ieri['google_shares'] &&
					(integer)$linkedin >= (integer)$ieri['linkedin_shares'] &&
					(integer)$pinterest >= (integer)$ieri['pinterest_shares'] &&
					(integer)$vkontakte >= (integer)$ieri['vkontakte_shares']
				) {
					// insert
					$wpdb->query($sql = "insert ignore into ".$wpdb->prefix."top_stories (id_post,dt_day,facebook_shares,twitter_shares,google_shares,linkedin_shares,pinterest_shares,vkontakte_shares,facebook_shares_start,twitter_shares_start,google_shares_start,linkedin_shares_start,pinterest_shares_start,vkontakte_shares_start) values (
						'$id','".$d."','".$shares."','".$tweet."','".$google."','".$linkedin."','".$pinterest."','".$vkontakte."','".$ieri['facebook_shares']."','".$ieri['twitter_shares']."','".$ieri['google_shares']."','".$ieri['linkedin_shares']."','".$ieri['pinterest_shares']."','".$ieri['vkontakte_shares']."')");
					if($debug) echo $sql."\n";
				}
				$change = true;
			}
			if($change && $options['top_stories_save_custom']) {
				if($debug) echo "save custom fields\n";
				$fb = update_post_meta_if_grater($id,"facebook_shares", $shares);
				$tw = update_post_meta_if_grater($id,"twitter_shares",$tweet);
				$go = update_post_meta_if_grater($id,"google_shares",$google);
				$li = update_post_meta_if_grater($id,"linkedin_shares",$linkedin);
				$pi = update_post_meta_if_grater($id,"pinterest_shares",$pinterest);
				$vk = update_post_meta_if_grater($id,"vkontakte_shares",$vkontakte);
				//print_r($fb);die;

				if($fb || $tw || $go || $li || $pi || $vk) {
					$user = execute_row("SELECT post_author FROM ".$wpdb->prefix."posts WHERE ID=".$id);
					if(isset($user["post_author"]) && $user["post_author"]>0) {
						$sum=execute_row("SELECT SUM((SELECT facebook_shares+twitter_shares+google_shares+linkedin_shares+pinterest_shares+vkontakte_shares 
							FROM ".$wpdb->prefix."top_stories 
							WHERE id_post=".$wpdb->prefix."posts.ID ORDER BY dt_day DESC LIMIT 0,1)) as a
							FROM ".$wpdb->prefix."posts 
							WHERE post_author='".$user["post_author"]."' AND
							post_status='publish'");
						if(isset($sum['a'])) {
							update_user_meta( $user["post_author"], 'top_stories_count', $sum['a'] );
						}
					}
				}
			}

		}

	}
	
	die();
}

function update_post_meta_if_grater($post_id,$meta_key, $number) {
	$debug = false;
	$q = get_post_meta( $post_id, $meta_key, true);
	if((integer)$q < (integer)$number) {
		update_post_meta($post_id,$meta_key,$number);
		if($debug) echo $meta_key . "  " . $number." aggiornato";
	} else {
		if($debug) echo $meta_key . "  " . $number." NON aggiornato (e' $q)";
	}
}
































/*

	facebook settings configuration

*/



add_action('admin_init', 'top_stories_facebook_register_settings');
function top_stories_facebook_register_settings(){
	register_setting('top_stories_facebook_settings_group', 'top_stories_facebook_settings', 'top_stories_facebook_settings_validate');
}

function top_stories_facebook_settings_validate($args){

	if(!isset($args['app_id'])) $args['app_id'] = "";
	if(!isset($args['app_secret'])) $args['app_secret'] = "";
	if(!isset($args['fbtoken'])) $args['fbtoken'] = "";

	return $args;
}

add_action('admin_notices', 'top_stories_facebook_settings_admin_notices');
function top_stories_facebook_settings_admin_notices(){
	//settings_errors();
}

function top_stories_facebook_config_page($a) {


	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}


	$options = get_option( 'top_stories_facebook_settings' , array(
		'app_id'=>'',
		'app_secret'=>'',
		'fbtoken'=>'') );

	// 
	// authorization process return handle
	//
	if(isset($_GET['code']) && isset($_GET["top_stories_fgtoken"])) {
		top_stories_get_token( $options );
	}


	if(isset($_GET['message']) && $_GET['message']=="ok") {

		echo "<p>Authorization token saved.</p>";
	}


	?>
	<div class="wrap tss">
		<form action="options.php" method="post">

			<h2>Fcebook settings</h2>
			<p class="description">To get Facebook data you need to create a Facebook app (go here <a href="https://developers.facebook.com/apps" target="_blank">https://developers.facebook.com/apps</a>) for your site. In the app settings you need to configure the website url.</p>
			<?php
			settings_fields( 'top_stories_facebook_settings_group' );
			do_settings_sections( 'top_stories_facebook_settings_group' );
			?>
			<div id='top_stories_facebook_settings_errors'></div>
			<div id='poststuff'>
				<div class='postbox'>

					<!--<h3 style="cursor:default">Plugin Settings</h3>-->
					<table class="form-table" style="margin:0 10px;width:95%">
						<tr>
							<th scope="row"><label for="app_id"><?php _e("App id","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									<input type="text" id="app_id" name="top_stories_facebook_settings[app_id]" value="<?php echo ( $options['app_id'] ); ?>" size="35"/>
									<p class="description">Enter the app id of your Facebook app</p>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="app_secret"><?php _e("App secret","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									<input type="text" id="app_id" name="top_stories_facebook_settings[app_secret]" value="<?php echo ( $options['app_secret'] ); ?>" size="35"/>
									<p class="description">Enter the app secret of your Facebook app</p>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"></th>
							<td>
								<?php submit_button(); ?>
								
								
								<?php
									//MODIFICA PEPPE TOKEN SERVER (BLOCCO COMMENTATO)
									/*if($options['app_secret'] && $options['app_id']) {
										echo "<a href=\"".get_bloginfo("url")."?top_stories_facebook_trigger=1\">Authorize</a>";
									}*/
									//FINE MODIFICA PEPPE TOKEN SERVER
								?>
							</td>
						</tr>

					</table>
					
				</div>
			</div>
			<!--//MODIFICA PEPPE TOKEN SERVER (BLOCCO COMMENTATO)
			<input type="text" name="top_stories_facebook_settings[fbtoken]" value="<?php echo $options['fbtoken']; ?>"/>
			//FINE MODIFICA PEPPE TOKEN SERVER-->

		</form>
	</div>
	<?php

}








// --------------------------------------------------------------------------
// first facebook call to get token

add_filter('query_vars','top_stories_facebook_trigger');
function top_stories_facebook_trigger($vars) {
    $vars[] = 'top_stories_facebook_trigger';
    return $vars;
}

add_action('template_redirect', 'top_stories_facebook_trigger_check');
function top_stories_facebook_trigger_check() {
	if(intval(get_query_var('top_stories_facebook_trigger')) == 1) {
		$options = get_option( 'top_stories_facebook_settings' , array(
			'app_id'=>'',
			'app_secret'=>'',
			'fbtoken'=>'') );

		$app_id = $options['app_id'];
		$app_secret = $options['app_secret'];
		$redirect_url = admin_url('options.php?page=top_stories_facebook_config&top_stories_fgtoken=1');
		$api_version = 'v2.0';
		$param_url = urlencode($redirect_url);
		$top_stories_fb_session_state = md5(uniqid(rand(), TRUE));
		setcookie("top_stories_fb_session_state", $top_stories_fb_session_state, "0", "/");

		$dialog_url = "https://www.facebook.com/" . $api_version . "/dialog/oauth?client_id="
				. $app_id . "&redirect_uri=" . $param_url . "&state="
				. $top_stories_fb_session_state . "&scope=email,user_about_me";

		Header("Location:". $dialog_url);
		die;

	}
}

// --------------------------------------------------------------------------
// second call to save fb token
function top_stories_get_token($options) {

	$code = $_GET['code'];
	
	$app_id = $options['app_id'];
	$app_secret = $options['app_secret'];
	$redirect_url = admin_url('options.php?page=top_stories_facebook_config&top_stories_fgtoken=1');
	

	$params = null;
	$access_token = "";
	//MODIFICA PEPPE TOKEN SERVER
	$options['fbtoken'] = $options['app_id']."|".$app_secret;
	update_option( 'top_stories_facebook_settings', $options);

	

	echo "<script>document.location.href='".admin_url('options.php?page=top_stories_facebook_config&message=ok')."';</script>";
	
	return;
	//FINE MODIFICA PEPPE TOKEN SERVER
	
	$api_version = 'v2.0';
	$param_url = urlencode($redirect_url);
	$token_url = "https://graph.facebook.com/" . $api_version . "/oauth/access_token?"
			. "client_id=" . $app_id . "&redirect_uri=" . $param_url
			. "&client_secret=" . $app_secret . "&code=" . $code;
	$response = wp_remote_get($token_url);
	$body = wp_remote_retrieve_body($response);
	if ($body != '') {
		parse_str($body, $params);
		if (isset($params['access_token'])) {
			$options['fbtoken'] = $params['access_token'];
			update_option( 'top_stories_facebook_settings', $options);
			
			//echo "PRIMO: ".$options['fbtoken']."<br>";

			// estendo la durante di questo access token
			$long_token_url = "https://graph.facebook.com/" . $api_version . "/oauth/access_token?"
					. "grant_type=fb_exchange_token&"
					. "client_id=" . $app_id 
					. "&client_secret=" . $app_secret . "&fb_exchange_token=" . $options['fbtoken'];

			$response = wp_remote_get($long_token_url);
			$body = wp_remote_retrieve_body($response);

			parse_str($body,$out);
			if(isset($out['access_token'])) {
				$options['fbtoken'] = $out['access_token'];
				update_option( 'top_stories_facebook_settings', $options);

				//echo "SECONDO: ".$options['fbtoken']."<br>";

				echo "<script>document.location.href='".admin_url('options.php?page=top_stories_facebook_config&message=ok')."';</script>";

			} else {
				
				echo "<p>There is an error:</p><pre>";
				print_r($response);
				echo "</pre>";

			}

		} else {
				echo "<p>There is an error:</p><pre>";
				print_r($body);
				echo "</pre>";
		}
	} else {
		echo "<p>There is an error:</p><pre>";
		print_r($body);
		echo "</pre>";
	}

}



add_action( 'wp_ajax_get_facebook_count', 'get_facebook_count_callback' );
add_action( 'wp_ajax_nopriv_get_facebook_count', 'get_facebook_count_callback' );
function get_facebook_count_callback() {
	ob_clean();
	$url = isset($_GET['url']) ? $_GET['url'] : "";
	$options = get_option( 'top_stories_facebook_settings' , array(
		'app_id'=>'',
		'app_secret'=>'',
		'fbtoken'=>'') );
		
	//MODIFICA PEPPE TOKEN SERVER
	$options['fbtoken']=$options['app_id']."|".$options['app_secret'];
	//FINE MODIFICA PEPPE TOKEN SERVER
	if($options['fbtoken']!="" && $url !="") {
		$api_version = "v2.4";
		$pp = wp_remote_get("https://graph.facebook.com/" . $api_version . "/?access_token=".$options['fbtoken']."&id=".urlencode($url));
		if(isset($pp['body'])) {
			$body = json_decode($pp['body']);
			if(isset($body->share)) {
				$q = $body->share->share_count;
				echo $q;
			} else {
				// c'è un errore
				//var_dump($pp);
				echo "0";
				//print_r($body);
			}
			die;
		}
	}
	echo "0";
	die;
}




//---------------------------------------------
// Chart functions
//---------------------------------------------
include("charts.php");


?>