<?php

/*
Plugin Name: SeoCare
Plugin URI: http://Prijsvrij.nl
Description: SeoCare helps the admin to create SEO-Friendly posts. While creating the post SeoCare calculates a grade for the post.
Version: 1.3
Author: Forrest Technology
Author URI: http://Prijsvrij.nl
*/
/*  Copyright 2009  ForrestTechnology -  (email : info@ForrestTechnology.nl)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*******************************************************************************************************/

if ( ! defined( 'WP_CONTENT_URL' ) )
    define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
    define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
    define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );



//add_action('admin_head', 'seocare_admin_head');
add_action('admin_menu', 'seocare_init');


function seocare_admin_head() {
	$url = WP_PLUGIN_URL . '/seocare/seocare.css';
	echo '<link type="text/css" rel="stylesheet" href="' . $url .'" />' . "\n";
	echo '<style type="text/css">#seoCareResult .icon{background:transparent url('.WP_PLUGIN_URL.'/seocare/img/icons.gif) no-repeat scroll -45px -16px;}</style>' . "\n";


	if (function_exists('wp_enqueue_script')) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('SeoCareScript-name', WP_PLUGIN_URL.'/seocare/SeoCareScript.js', 'jquery');
	}
}

function seocare_init() {
	if ( function_exists('add_meta_box') ) {
		add_meta_box('seocare','SEOCare','seocare_meta_box','post','normal','high');
		//add_meta_box('seocare','SEOCare' ,'seocare_meta_box','page','normal','high');

		function my_enqueue($hook) {
		  if ($hook == 'post.php' || $hook == 'post-new.php') {
		    seocare_admin_head();
		  }
		}
		add_action('admin_enqueue_scripts','my_enqueue',10,1);
	}

	if ( function_exists('add_options_page') ) {
		$mypage = add_options_page('SeoCare Options', 'SeoCare', 8, 'seoCareOptions', 'seocare_options');
		add_action( "admin_print_scripts-$mypage", 'seocare_admin_head' );
	}
}

function seocare_meta_box() {

	$rules = seocare_getRules();
	?>
		<script type="text/javascript">
			ruleSet = <?php echo json_encode($rules);?>;
		</script>
		<table id="seoCareResult">
		<tr>
			<td id="SeoCareMessages" colspan="2"></td>
		</tr>
		<?php if(get_option('seocare_Keywords_Enabled_Density','true') || get_option('seocare_Keywords_Enabled_Title','true')){?>
		<tr>
			<td colspan="2">
				<table>
					<tr><th class="left"><label for="seocare_keywords"><?php _e("Keywords")?>:</label></th></tr>
					<tr><td>
						<input type="text" class="seocare_input_large" name="seocare_keywords" id="seocare_keywords" />
						<p class="howto"><?php _e("Seperate keywords with commas")?></p>
					</td></tr>
					<tr>
						<td>
							<table class="keywordResult">
								<tr id="seocare_Keywords_Result" style="display:none;">
									<th class="left"><?php _e("Keyword")?></th><th class="left"><?php _e("Density")?></th>
								</tr>

							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php }?>
		<tr>
			<td class="small">
				<input id="ExecSeoCareCheck" onclick="javascript:void(0)" class="button-primary" type="button" tabindex="3" value="<?php _e("SeoCare check");?>"/>
			</td>
			<td>
				<?php _e("Final result");?>:
				<span id="SeoCareGrade">-</span>
			</td>
		</tr>
		</table>
	<?php
}

//Set all the rules in an array
function seocare_getRules()
{
	//calculate mandatory and notmandatoy grade
	$mandatoryGrade = 0;
	$nonMandatoryGrade = 0;
	$mandatoryCount = get_option('seocare_nrmandatory','9');
	$nonMandatoryCount = (get_option('seocare_nrenabled','9') - get_option('seocare_nrmandatory','9'));

	//special for publish option
	if(get_option('seocare_General_Enable_Publish','true'))
		$mandatoryCount++;

	if($mandatoryCount > 0)
		$mandatoryGrade = (60/$mandatoryCount);
	if($nonMandatoryCount > 0)
		$nonMandatoryGrade = (40/$nonMandatoryCount);

	//Array(Array([Enabled],[Mandatory],[GoodMessage],[BadMessage],[Values],[Score]));
	$result = array(
	//-----------------General-------------------
		"EnablePublish" => array("enabled" => get_option('seocare_General_Enable_Publish','true'),
								 "mandatory" => get_option('seocare_General_Enable_Publish','true'),
								 "goodmessage" => __("You can publish this post"),
								 "badmessage" => __("You can't publish this post until you have a grade higher than 6."),
								 "value" => "",
								 "grade" => (get_option('seocare_General_Enable_Publish','true')) ? $mandatoryGrade : $nonMandatoryGrade),

	//-----------------Title-------------------
		"TitleNotNull" => array("enabled" => get_option('seocare_Title_Enabled_NotNull','true'),
							    "mandatory" => get_option('seocare_Title_Mandatory_NotNull','true'),
							    "goodmessage" => __("Title is not empty") ,
							    "badmessage" => __("The title can't be empty"),
							    "value" => "",
							    "grade" => (get_option('seocare_Title_Mandatory_NotNull','true'))? $mandatoryGrade : $nonMandatoryGrade),
		"TitleLength" => array("enabled" => get_option('seocare_Title_Enabled_WordCount','true'),
							   "mandatory" => get_option('seocare_Title_Mandatory_WordCount','true'),
							   "goodmessage" => __("Title has enough words") ,
							   "badmessage" => sprintf(__("The title should have between %d and %d words."), get_option('seocare_Title_Min',7),get_option('seocare_Title_Max',10)),
							   "value" => array("min" => get_option('seocare_Title_Min',7),"max" => get_option('seocare_Title_Max',10)),
							   "grade" => (get_option('seocare_Title_Mandatory_WordCount','true'))? $mandatoryGrade : $nonMandatoryGrade),
		"TitleDisallowedWords" => array("enabled" => get_option('seocare_Title_Enable_DisallowedWords','true'),
										"mandatory" => get_option('seocare_Title_Mandatory_DisallowedWords','true'),
										"goodmessage" => __("The title contains no forbidden words"),
										"badmessage" => __("The title contains forbidden words."),
										"value" => get_option('seocare_Title_DisallowedWords','__("the,on,in")'),
										"grade" => (get_option('seocare_Title_Mandatory_DisallowedWords','true')) ? $mandatoryGrade : $nonMandatoryGrade),

	//-----------------Text-------------------
	 	"TextWordLength" => array("enabled" => get_option('seocare_Text_Enabled_WordCount','true'),
	 							  "mandatory" => get_option('seocare_Text_Mandatory_WordCount','true'),
	 							  "goodmessage" => __("The text has a correct ammount of words"),
	 							  "badmessage" => sprintf(__("The text should have between %d and %d words"),get_option('seocare_Text_Min',200),get_option('seocare_Text_Max',800)),
	 							  "value" => array("min" => get_option('seocare_Text_Min',200),"max" => get_option('seocare_Text_Max',800)),
	 							  "grade" => (get_option('seocare_Text_Mandatory_WordCount','true'))? $mandatoryGrade : $nonMandatoryGrade),

	//-----------------Keywords-------------------
		"KeywordsTitleMandatory" => array("enabled" => get_option('seocare_Keywords_Enabled_Title','true'),
										  "mandatory" => get_option('seocare_Keywords_Mandatory_Title','true'),
										  "goodmessage" => __("There are keyword(s) in the title") ,
										  "badmessage" => __("There should be keyword(s) in the title"),
										  "value" => "",
										  "grade" => (get_option('seocare_Keywords_Mandatory_Title','true'))? $mandatoryGrade : $nonMandatoryGrade),
		"KeywordsDensity" => array("enabled" => get_option('seocare_Keywords_Enabled_Density','true'),
								   "mandatory" => get_option('seocare_Keywords_Mandatory_Density','true'),
								   "goodmessage" => __("The keywords appear enough in this post") ,
								   "badmessage" => sprintf(__("The keywords should have a density between %d%% and %d%%"),get_option('seocare_Keywords_MinDensity',1.5),get_option('seocare_Keywords_MaxDensity',8)),
								   "value" => array("min" => get_option('seocare_Keywords_MinDensity',1.5), "max" => get_option('seocare_Keywords_MaxDensity',8)),
								   "grade" => (get_option('seocare_Keywords_Mandatory_Density','true'))?$mandatoryGrade : $nonMandatoryGrade),

	//-----------------Tags-------------------
		"TagsAppearance" => array("enabled" => get_option('seocare_Tags_Enabled','true'),
								  "mandatory" => get_option('seocare_Tags_Mandatory','true'),
								  "goodmessage" => __("The tags appear in this post") ,
								  "badmessage" => sprintf(__("At least %d tag(s) should appear %d time(s) in this post"),get_option('seocare_Tags_NrTags',1),get_option('seocare_Tags_NrTimes',1)),
								  "value" => array("nrtags" => get_option('seocare_Tags_NrTags',1), "nrtimes" => get_option('seocare_Tags_NrTimes',1)),
								  "grade" => (get_option('seocare_Tags_Mandatory','true'))? $mandatoryGrade : $nonMandatoryGrade)
	);

	return $result;
}

function seocare_options() {
?>
  <div id="seocareAdmin" class="wrap">
	  <?php screen_icon();?>
	  <h2><?php _e("SeoCare"); ?></h2>

	  <form method="post" action="options.php">
	  <?php wp_nonce_field('update-options'); ?>

		<table id="seocare-table" class="form-table">
			<tr>
				<td>
					<h3><?php _e("General"); ?></h3>
					<p><?php _e("You can change al the options beneath here."); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<td>
				<label for="seocare_General_Enable_Publish">
					<?php if(get_option('seocare_General_Enable_Publish','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
					<input class="Enabled" type="checkbox" name="seocare_General_Enable_Publish" id="seocare_General_Enable_Publish" value="true" <?php echo $checked; ?> />
					<?php _e("Disallow to publish an article with a grade lower than 6");?></label>
				</td>
			</tr>
		<tr>
			<td>
				<h3><?php _e("Title"); ?></h3>
			</td>
		</tr>
			<tr><td colspan="2"><h4><?php _e("Title not empty"); ?></h4></td></tr>
			<tr valign="top">
				<td>
					<label for="seocare_Title_NotNull">
						<?php if(get_option('seocare_Title_NotNull','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input type="checkbox" name="seocare_Title_NotNull" id="seocare_Title_NotNull" value="true" <?php echo $checked; ?> />
						<?php _e("Cannot be empty"); ?>
					</label>
				</td>
				<td>
					<label for="seocare_Title_Enabled_NotNull">
						<?php if(get_option('seocare_Title_Enabled_NotNull','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Enable" type="checkbox" name="seocare_Title_Enabled_NotNull" id="seocare_Title_Enabled_NotNull" value="true" <?php echo $checked; ?> />
						<?php _e("Enabled"); ?>
					</label>
					<label for="seocare_Title_Mandatory_NotNull">
						<?php if(get_option('seocare_Title_Mandatory_NotNull','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Mandatory" type="checkbox" name="seocare_Title_Mandatory_NotNull" id="seocare_Title_Mandatory_NotNull" value="true" <?php echo $checked; ?> />
						<?php _e("Mandatory"); ?>
					</label>
				</td>
			</tr>

			<tr><td colspan="2"><h4><?php _e("Title number of words"); ?></h4></td></tr>
			<tr valign="top">
				<td>

					<label for="seocare_Title_Min">
						<?php _e("Min"); ?>
						<input type="text" id="seocare_Title_Min" name="seocare_Title_Min" class="small-text" value="<?php echo get_option('seocare_Title_Min',7); ?>" />
					</label>
					<label for="seocare_Title_Max">
						<?php _e("Max"); ?>
						<input type="text" id="seocare_Title_Max" name="seocare_Title_Max" class="small-text" value="<?php echo get_option('seocare_Title_Max',10); ?>" />
					</label>
					<?php _e("Number of words in the title"); ?>
				</td>
				<td>
					<label for="seocare_Title_Enabled_WordCount">
						<?php if(get_option('seocare_Title_Enabled_WordCount','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Enable" type="checkbox" name="seocare_Title_Enabled_WordCount" id="seocare_Title_Enabled_WordCount" value="true" <?php echo $checked; ?> />
						<?php _e("Enabled"); ?>
					</label>
					<label for="seocare_Title_Mandatory_WordCount">
						<?php if(get_option('seocare_Title_Mandatory_WordCount','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Mandatory" type="checkbox" name="seocare_Title_Mandatory_WordCount" id="seocare_Title_Mandatory_WordCount" value="true" <?php echo $checked; ?> />
						<?php _e("Mandatory"); ?>
					</label>
				</td>
			</tr>

			<tr><td colspan="2"><h4><?php _e("Title disallowed words"); ?></h4></td></tr>
			<tr valign="top">
				<td>
					<label for="seocare_Title_DisallowedWords">
						<input type="text" id="seocare_Title_DisallowedWords" name="seocare_Title_DisallowedWords" class="regular-text" value="<?php echo get_option('seocare_Title_DisallowedWords',__("the,on,in")); ?>" />
						<?php _e("Disallowed words");?>
					</label>
					<span class="description"><?php _e("(Seperate words with commas)");?></span>
				</td>

				<td>
					<label for="seocare_Title_Enable_DisallowedWords">
						<?php if(get_option('seocare_Title_Enable_DisallowedWords','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Enable" type="checkbox" name="seocare_Title_Enable_DisallowedWords" id="seocare_Title_Enable_DisallowedWords" value="true" <?php echo $checked; ?> />
						<?php _e("Enabled"); ?>
					</label>
					<label for="seocare_Title_Mandatory_DisallowedWords">
						<?php if(get_option('seocare_Title_Mandatory_DisallowedWords','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Mandatory" type="checkbox" name="seocare_Title_Mandatory_DisallowedWords" id="seocare_Title_Mandatory_DisallowedWords" value="true" <?php echo $checked; ?> />
						<?php _e("Mandatory"); ?>
					</label>
				</td>
			</tr>
			<tr><td>
				<h3><?php _e("Text"); ?></h3>
			</td></tr>
			<tr><td colspan="2"><h4><?php _e("Text number of words"); ?></h4></td></tr>
			<tr valign="top">
				<td>
					<label for="seocare_Text_Min">
						<?php _e("Min"); ?>
						<input type="text" id="seocare_Text_Min" name="seocare_Text_Min" class="small-text" value="<?php echo get_option('seocare_Text_Min',200); ?>" />
					</label>
					<label for="seocare_Text_Max">
						<?php _e("Max"); ?>
						<input type="text" id="seocare_Text_Max" name="seocare_Text_Max" class="small-text" value="<?php echo get_option('seocare_Text_Max',800); ?>" />
					</label>
					<?php _e("Number of words in the text"); ?>
				</td>
				<td>
					<label for="seocare_Text_Enabled_WordCount">
						<?php if(get_option('seocare_Text_Enabled_WordCount','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Enable" type="checkbox" name="seocare_Text_Enabled_WordCount" id="seocare_Text_Enabled_WordCount" value="true" <?php echo $checked; ?> />
						<?php _e("Enabled"); ?>
					</label>
					<label for="seocare_Text_Mandatory_WordCount">
						<?php if(get_option('seocare_Text_Mandatory_WordCount','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Mandatory" type="checkbox" name="seocare_Text_Mandatory_WordCount" id="seocare_Text_Mandatory_WordCount" value="true" <?php echo $checked; ?> />
						<?php _e("Mandatory"); ?>
					</label>
				</td>
			</tr>
			<tr>
				<td>
					<h3><?php _e("Keywords"); ?></h3>
				</td>
			</tr>
			<tr valign="top">
				<td>
					<label for="seocare_Keywords_TitleMandatory">
					<?php if(get_option('seocare_Keywords_TitleMandatory','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
					<input type="checkbox" name="seocare_Keywords_TitleMandatory" id="seocare_Keywords_TitleMandatory" value="true" <?php echo $checked; ?> />
					<?php _e("Keywords has to be in the title"); ?></label>
				</td>
				<td>
					<label for="seocare_Keywords_Enabled_Title">
						<?php if(get_option('seocare_Keywords_Enabled_Title','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Enable" type="checkbox" name="seocare_Keywords_Enabled_Title" id="seocare_Keywords_Enabled_Title" value="true" <?php echo $checked; ?> />
						<?php _e("Enabled"); ?>
					</label>
					<label for="seocare_Keywords_Mandatory_Title">
						<?php if(get_option('seocare_Keywords_Mandatory_Title','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Mandatory" type="checkbox" name="seocare_Keywords_Mandatory_Title" id="seocare_Keywords_Mandatory_Title" value="true" <?php echo $checked; ?> />
						<?php _e("Mandatory"); ?>
					</label>
				</td>
			</tr>

			<tr valign="top">
				<td>
					<?php _e("Keywords should have a density between"); ?>
					<label for="seocare_Keywords_MinDensity">
						<?php _e("Minimal"); ?>
						<input type="text" id="seocare_Keywords_MinDensity" name="seocare_Keywords_MinDensity" class="small-text" value="<?php echo get_option('seocare_Keywords_MinDensity',1.5); ?>" />
						%
					</label>
					<label for="seocare_Keywords_MaxDensity">
						<?php _e("Maximal"); ?>
						<input type="text" id="seocare_Keywords_MaxDensity" name="seocare_Keywords_MaxDensity" class="small-text" value="<?php echo get_option('seocare_Keywords_MaxDensity',8); ?>" />%
					</label>
				</td>
				<td>
					<label for="seocare_Keywords_Enabled_Density">
						<?php if(get_option('seocare_Keywords_Enabled_Density','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Enable" type="checkbox" name="seocare_Keywords_Enabled_Density" id="seocare_Keywords_Enabled_Density" value="true" <?php echo $checked; ?> />
						<?php _e("Enabled"); ?>
					</label>
					<label for="seocare_Keywords_Mandatory_Density">
						<?php if(get_option('seocare_Keywords_Mandatory_Density','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Mandatory" type="checkbox" name="seocare_Keywords_Mandatory_Density" id="seocare_Keywords_Mandatory_Density" value="true" <?php echo $checked; ?> />
						<?php _e("Mandatory"); ?>
					</label>
				</td>
			</tr>
			<tr>
				<td>
					<h3><?php _e("Tags"); ?></h3>
				</td>
			</tr>
			<tr valign="top">
				<td>
					<?php _e("Minimal"); ?>
					<input type="text" name="seocare_Tags_NrTags" class="small-text" value="<?php echo get_option('seocare_Tags_NrTags',1); ?>" />
					<?php _e("tag(s) should be"); ?>
					<input type="text" name="seocare_Tags_NrTimes" class="small-text" value="<?php echo get_option('seocare_Tags_NrTimes',1); ?>" />
					<?php _e("time(s) in the post"); ?>
				</td>
				<td>
					<label for="seocare_Tags_Enabled">
						<?php if(get_option('seocare_Tags_Enabled','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Enable" type="checkbox" name="seocare_Tags_Enabled" id="seocare_Tags_Enabled" value="true" <?php echo $checked; ?> />
						<?php _e("Enabled"); ?>
					</label>
					<label for="seocare_Tags_Mandatory">
						<?php if(get_option('seocare_Tags_Mandatory','true')){ $checked = "checked=\"checked\""; }else{ $checked = ""; } ?>
						<input class="Mandatory" type="checkbox" name="seocare_Tags_Mandatory" id="seocare_Tags_Mandatory" value="true" <?php echo $checked; ?> />
						<?php _e("Mandatory"); ?>
					</label>
				</td>
			</tr>
		</table>

	  <input type="hidden" name="action" value="update" />
	  <input type="hidden" id="seocare_nrmandatory" name="seocare_nrmandatory"/>
	  <input type="hidden" id="seocare_nrenabled" name="seocare_nrenabled"/>
	  <input type="hidden" name="page_options" value="seocare_Keywords_Mandatory_Density,seocare_Keywords_Enabled_Density,seocare_Keywords_Mandatory_Title,seocare_Keywords_Enabled_Title,seocare_Text_Mandatory_WordCount,seocare_Title_Mandatory_DisallowedWords,seocare_Title_Mandatory_WordCount,seocare_nrmandatory,seocare_nrenabled,seocare_General_Enable_Publish,seocare_Title_NotNull,seocare_Title_Enabled_NotNull,seocare_Title_Mandatory_NotNull,seocare_Title_Enabled_WordCount,seocare_Title_Min,seocare_Title_Max,seocare_Title_Enable_DisallowedWords,seocare_Title_DisallowedWords,seocare_Text_Enabled_WordCount,seocare_Text_Min,seocare_Text_Max,seocare_Keywords_Mandatory,seocare_Keywords_TitleMandatory,seocare_Keywords_Enabled,seocare_Tags_NrTimes,seocare_Tags_NrTags,seocare_Tags_Enabled,seocare_Keywords_MaxDensity,seocare_Keywords_MinDensity" />

	  <p class="submit">
	  <input id="seocare_options_save" type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	  </p>
	  </form>

		  <h2><?php _e("More information?"); ?></h2>
		  <p><?php _e("This plugin is made by <a href='http://www.Prijsvrij.nl'>Prijsvrij.nl</a> for more information go to <a href='http://www.Prijsvrij.nl/seocare/'>http://www.Prijsvrij.nl/seocare/</a>"); ?></p>
  </div>
<?php
}
?>