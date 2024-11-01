<?php
/*
	Classes to handle Top Stories Widgets for Sidebars
*/

class top_stories_widget extends WP_Widget {

	// Constructor
	function __construct() {
		parent::__construct(false, $name = 'Top Social Stories',array( 
			'description'=>__('Show a rank of top stories from Facebook, Twitter, Linkedin, Pinterest and Google+ shares.','top-stories-plugin')
		) );
	}

	// Customization Form
	function form($instance) {	
		// Check values
		if( $instance) {
			 $title = esc_attr($instance['title']);
			 $howmany = esc_attr($instance['howmany']);
			 $days = esc_attr($instance['days']);
			 $images = esc_attr($instance['images']);
			 $force_ids = esc_attr($instance['force_ids']);
			 $tag = esc_attr($instance['tag']);
			 $count = esc_attr($instance['count']);
			 $typerank = esc_attr($instance['typerank']);
			 $pos = esc_attr($instance['pos']);
		} else {
			 $title = 'Top stories!';
			 $howmany = '5';
			 $days = '7';
			 $images="1";
			 $force_ids="";
			 $tag="";
			 $count="1";
			 $typerank="0";
			 $pos="0";
		}
		?>

		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'top-stories-plugin'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('howmany'); ?>"><?php _e('Number of posts to show:', 'top-stories-plugin'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('howmany'); ?>" name="<?php echo $this->get_field_name('howmany'); ?>">
				<?php
					for($i=1;$i<=100;$i++) {
						?><option value="<?php echo $i;?>" <?php echo $howmany==$i ? "selected='selected'" : ""; ?>><?php 
							echo $i." ".($i == 1 ? __("post","top-stories-plugin") : __("posts","top-stories-plugin"));
						?></option><?php
					}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('days'); ?>"><?php _e('Period:', 'top-stories-plugin'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('days'); ?>" name="<?php echo $this->get_field_name('days'); ?>">
				<option value="30" <?php echo $days=="30" ? "selected='selected'" : ""; ?>><?php _e('1 month', 'top-stories-plugin'); ?></option>
				<option value="30" <?php echo $days=="30" ? "selected='selected'" : ""; ?>>* Upgrade! *</option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('typerank'); ?>"><?php _e('Show rank for posts:', 'top-stories-plugin'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('typerank'); ?>" name="<?php echo $this->get_field_name('typerank'); ?>">
				<option value="0" <?php echo $typerank=="0" ? "selected='selected'" : ""; ?>><?php _e('Published in the above period', 'top-stories-plugin'); ?></option>
				<option value="1" <?php echo $typerank=="1" ? "selected='selected'" : ""; ?>><?php _e('Most viral in the above period', 'top-stories-plugin'); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('images'); ?>"><?php _e('Show images:', 'top-stories-plugin'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('images'); ?>" name="<?php echo $this->get_field_name('images'); ?>">
				<option value="1" <?php echo $images=="1" ? "selected='selected'" : ""; ?>><?php _e('Yes', 'top-stories-plugin'); ?></option>
				<option value="0" <?php echo $images=="0" ? "selected='selected'" : ""; ?>><?php _e('No', 'top-stories-plugin'); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Show share count:', 'top-stories-plugin'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>">
				<option value="1" <?php echo $count=="1" ? "selected='selected'" : ""; ?>><?php _e('Yes', 'top-stories-plugin'); ?></option>
				<option value="0" <?php echo $count=="0" ? "selected='selected'" : ""; ?>><?php _e('No', 'top-stories-plugin'); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('pos'); ?>"><?php _e('Show rank position:', 'top-stories-plugin'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('pos'); ?>" name="<?php echo $this->get_field_name('pos'); ?>">
				<option value="1" <?php echo $pos=="1" ? "selected='selected'" : ""; ?>><?php _e('Yes', 'top-stories-plugin'); ?></option>
				<option value="0" <?php echo $pos=="0" ? "selected='selected'" : ""; ?>><?php _e('No', 'top-stories-plugin'); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('force_ids'); ?>"><?php _e('Force some post ids to show:', 'top-stories-plugin'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('force_ids'); ?>" name="<?php echo $this->get_field_name('force_ids'); ?>" type="text" value="<?php echo $force_ids; ?>" /><span class='description'><?php _e('Comma separated, ex.: 23,134,892', 'top-stories-plugin'); ?></span>
		</p>


		<p>
			<label for="<?php echo $this->get_field_id('tag'); ?>"><?php _e('Automatically tag posts with:', 'top-stories-plugin'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('tag'); ?>" name="<?php echo $this->get_field_name('tag'); ?>" type="text" value="<?php echo $tag; ?>" /><span class='description'><?php _e('If you specify a tag, the posts that appear in this result will automatically be tagged with this tag.', 'top-stories-plugin'); ?></span>
		</p>


		<?php
	}

	// Update Widget
	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['images'] = (integer)$new_instance['images'];
		$instance['count'] = (integer)$new_instance['count'];
		$instance['days'] = (integer)$new_instance['days'];
		$instance['force_ids'] = strip_tags($new_instance['force_ids']);
		$instance['howmany'] = (integer)$new_instance['howmany'];
		$instance['tag'] = strip_tags($new_instance['tag']);
		$instance['typerank'] = (integer)$new_instance['typerank'];
		$instance['pos'] = (integer)$new_instance['pos'];

		return $instance;
	}

	// Display Widget
	function widget($args, $instance) {
		extract( $args );

		$title = apply_filters('widget_title', $instance['title']);
		echo $before_widget;

		$options = get_option('top_stories_settings');
		if(!isset($options['top_stories_placeholder'])) {
			$options['top_stories_placeholder']=plugin_dir_url( __FILE__ ).'images/placeholder.jpg';
		}

		// Check if title is set
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		echo "<ul class='top-stories".($instance['images']?"":" nopic")."' >".$this->getTopStories(
				$instance['days'],
				$instance['howmany'],
				$instance['tag'],
				$instance['images'],
				$instance['count'],
				$instance['force_ids'],
				$instance['typerank'],
				$instance['pos'],
				$options['top_stories_placeholder'],
				$options['top_stories_pt'])."</ul>";
		echo $after_widget;

	}

	// Extract Top Stories for Widget
	function getTopStories($days=30,$howmany=6,$tag="topstory",$images=1,$count=1,$force_ids="",$typerank=0,$pos=0,$placeholder="",$ptArr = null) {
		global $wpdb;
		// post types filtering
		if(!$ptArr) $ptStr = "'page','post','attachment'"; else $ptStr = "'".implode("','",$ptArr)."'";	
			
		// check for cached
		$transient_key=sha1($days." ".$howmany." ".$tag." ".$images." ".$count." ".$force_ids." ".$typerank." ".$pos." ".$placeholder." ".$ptStr);
		
		$ret=get_transient( $transient_key );
		if ($ret !== false ) return $ret;
		
		// costruzione rullo
		$data = date("Y-m-d",strtotime("-".((2^3)*3+6)." da"."ys",strtotime( date("Y-m-d") )));
		
		if($typerank=="0") {
			$querystr = "
				SELECT ID as post_id,
					(SELECT facebook_shares+twitter_shares+google_shares+pinterest_shares+linkedin_shares+vkontakte_shares FROM ".$wpdb->prefix."top_stories 
						WHERE id_post=ID ORDER BY dt_day DESC LIMIT 0,1) as a,
					post_title,post_content
				FROM ".$wpdb->prefix."posts 
				WHERE (post_date > '".$data."' ".($force_ids ? "OR ID IN (".$force_ids.")" : ""). ") 
				AND post_status='publish' AND post_type in ($ptStr)
				AND (SELECT facebook_shares+twitter_shares+google_shares+pinterest_shares+linkedin_shares+vkontakte_shares FROM ".$wpdb->prefix."top_stories 
					WHERE id_post=ID ORDER BY dt_day DESC LIMIT 0,1) >0 
				ORDER BY a DESC
				LIMIT 0,".$howmany."
				";
		} else {
			 $querystr = "
				SELECT ID as post_id,SUM(x.facebook_shares+x.twitter_shares+x.google_shares+x.linkedin_shares+x.pinterest_shares+x.vkontakte_shares) as a,
					SUM(x.facebook_shares - x.facebook_shares_start +x.twitter_shares - x.twitter_shares_start +x.google_shares - x.google_shares_start + x.linkedin_shares - x.linkedin_shares_start + x.pinterest_shares - x.pinterest_shares_start+ x.vkontakte_shares - x.vkontakte_shares_start) as d
					,post_title,post_content
				 FROM ".$wpdb->prefix."top_stories x inner join ".$wpdb->prefix."posts on ID=id_post AND dt_day>'".$data."'
				 WHERE (x.facebook_shares - x.facebook_shares_start +x.twitter_shares - x.twitter_shares_start +x.google_shares - x.google_shares_start+ x.linkedin_shares - x.linkedin_shares_start + x.pinterest_shares - x.pinterest_shares_start+ x.vkontakte_shares - x.vkontakte_shares_start) >0 AND post_status='publish'
				AND post_type in ($ptStr)
				GROUP BY ID
				ORDER BY d DESC
				LIMIT 0,".$howmany."
				";
		}
		//echo $querystr." // ".$days." // ".$typerank;
		$pageposts = $wpdb->get_results($querystr, OBJECT);

		$out = ""; 
		$i = 0;
		$k = 0;
		if ($pageposts) {
			foreach ($pageposts as $post) {

				/*
					Find post Image
				*/
				$pic = "";
				if($images) {
					$pic = top_stories_get_pic($post,$placeholder);
				}

				if($images && $pic || !$images) {
					$i++;
					if($i<=$howmany) {
						$k++;
						$out.="<li>";
						if($pos) $out.= "<span class='thepos'>".$k." </span>";

						$out.="<a href='".home_url()."/?p=".$post->post_id."' rel='nofollow'>";
						if($images) {
							$out.= $pic;
						}
						$out.="<span class='tit'>".$post->post_title."</span> ";
						if($typerank=="0") {
							$q = $post->a < 1000 ? $post->a : number_format($post->a / 1000 ,1) ."k";
						} else {
							$q = "+" . ( $post->d < 1000 ? $post->d : number_format($post->d / 1000 ,1) ."k" );
						}
						if($count) {
							$out.="<span class='num'>{$q}</span> ";
						}
						$out.="</a></li>";
						
						if($tag) {
							/*
								if $tag<>'' the $tag is used to tag the item that is in top stories
							*/
							wp_set_post_tags( $post->post_id, $tag, true );
						}
					}
				}
			}
		}

		// saved cached to speed up
		set_transient( $transient_key,$out,12*3600 );
		return $out;
	}

}


?>