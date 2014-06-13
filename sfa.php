<?php
/*
* Copyright 2014 Jeremy O'Connell  (email : cwplugins@cyberws.com)
* License: GPL2 .:. http://opensource.org/licenses/GPL-2.0
*/

////////////////////////////////////////////////////////////////////////////
//	Verify admin panel is loaded, if not fail
////////////////////////////////////////////////////////////////////////////
if (!is_admin()) {
	die();
}

////////////////////////////////////////////////////////////////////////////
//	Menu call
////////////////////////////////////////////////////////////////////////////
add_action('admin_menu', 'cw_share_files_aside_mn');

////////////////////////////////////////////////////////////////////////////
//	Load admin menu option
////////////////////////////////////////////////////////////////////////////
function cw_share_files_aside_mn() {
Global $current_user,$fs_wp_option;
	$share_files_mng_disp='n';

	if (is_user_logged_in()) {
		////////////////////////////////////////////////////////////////////////////
		//	For wp admins always verify as they have setting access
		////////////////////////////////////////////////////////////////////////////
		if (wp_get_current_user('manage_options')) {
			$share_files_mng_disp='y';
		//	Check non wp admins
		} else {
			//	Load options for plugin
			$fs_wp_option_array=get_option($fs_wp_option);
			$fs_wp_option_array=unserialize($fs_wp_option_array);
			$settings_mgrs=$fs_wp_option_array['settings_mgrs'];

			//	Grab current username
			$current_user_login=$current_user->user_login;

			//	If usernames are defined verify access, else clear
			if ($settings_mgrs) {
				$settings_mgrs=explode("\n",$settings_mgrs);
				if (in_array($current_user_login,$settings_mgrs)) {
					$share_files_mng_disp='y';
				}
			} else {
				$share_files_mng_disp='y';
			}			
		}
	}

	if ($share_files_mng_disp == 'y') {
		add_menu_page('Share Files','Share Files','publish_pages','cw-share-files','cw_share_files_aside','','32');
	}
}

////////////////////////////////////////////////////////////////////////////
//	Load admin functions
////////////////////////////////////////////////////////////////////////////
function cw_share_files_aside() {
Global $wpdb,$current_user,$fs_wp_option,$cw_share_files_cats_tbl,$cw_share_files_dls_tbl,$cwfa_fs;

	////////////////////////////////////////////////////////////////////////////
	//	Load options for plugin
	////////////////////////////////////////////////////////////////////////////
	$fs_wp_option_array=get_option($fs_wp_option);
	$fs_wp_option_array=unserialize($fs_wp_option_array);
	$settings_url=$fs_wp_option_array['settings_url'];
	$settings_path=$fs_wp_option_array['settings_path'];
	$settings_sections=$fs_wp_option_array['settings_sections'];
	$settings_ppg=$fs_wp_option_array['settings_ppg'];
	$settings_mgrs=$fs_wp_option_array['settings_mgrs'];

	if ($settings_sections) {
		$settings_sections=explode("\n",$settings_sections);
	}

	////////////////////////////////////////////////////////////////////////////
	//	Grab current username
	////////////////////////////////////////////////////////////////////////////
	get_currentuserinfo();
	$current_user_login=$current_user->user_login;

	////////////////////////////////////////////////////////////////////////////
	//	Set action value
	////////////////////////////////////////////////////////////////////////////
	if (isset($_REQUEST['cw_action'])) {
		$cw_action=$_REQUEST['cw_action'];
	} else {
		$cw_action='main';
	}

	////////////////////////////////////////////////////////////////////////////
	//	Don't allow no admin access to admin functions
	////////////////////////////////////////////////////////////////////////////
	$settings_lock_down='y';
	if (wp_get_current_user('manage_options')) {
		$settings_lock_down='n';
	} else {
		if (substr_count($cw_action,'setting') > '0') {
			$cw_action='main';
		}
	}

	////////////////////////////////////////////////////////////////////////////
	//	Previous page link
	////////////////////////////////////////////////////////////////////////////
	$pplink='<a href="javascript:history.go(-1);">Return to previous page...</a>';

	////////////////////////////////////////////////////////////////////////////
	//	Define Variables
	////////////////////////////////////////////////////////////////////////////
	$cw_share_files_action='';
	$cw_share_files_html='';

	////////////////////////////////////////////////////////////////////////////
	//	Main File Screen
	////////////////////////////////////////////////////////////////////////////
	if ($cw_action == 'files') {

		//	Get top ten files
		$cw_share_files_dl_list='';
		$myrows=$wpdb->get_results("SELECT dl_id,dl_title,dl_cnt FROM $cw_share_files_dls_tbl order by dl_cnt desc limit 0,10");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$cw_share_files_dl_id=$myrow->dl_id;
				$cw_share_files_dl_title=stripslashes($myrow->dl_title);
				$cw_share_files_dl_cnt=$cwfa_fs->cwf_fmt_tho($myrow->dl_cnt);
				$cw_share_files_dl_list .='<li>'.$cw_share_files_dl_cnt.' dls - <a href="?page=cw-share-files&cw_action=fileview&dl_id='.$cw_share_files_dl_id.'">'.$cw_share_files_dl_title.'</a></li>';
			}
		}

		//	Section list
		$sseclist='<option value="">All Sections</option>';
		foreach ($settings_sections as $settings_section) {
			list($settings_section_id,$settings_section_name)=explode('|',$settings_section);
			$settings_section_name_url=urlencode($settings_section_name);
			$sseclist .='<option value="'.$settings_section_id.'">'.$settings_section_name.'</option>';
		}

		//	Category list
		$scatlist='<option value="">All Categories</option>';
		$myrows=$wpdb->get_results("SELECT cat_id,cat_name FROM $cw_share_files_cats_tbl order by cat_name");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$cat_id=$cwfa_fs->cwf_san_int($myrow->cat_id);
				$cat_name=stripslashes($myrow->cat_name);
				$cat_name_url=urlencode($cat_name);
				$scatlist .='<option value="'.$cat_id.'">'.$cat_name.'</option>';
			}
		}

		$cw_share_files_action='File Management';
$cw_share_files_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="filesearch">
<div style="width: 400px; text-align: center;">
Search For: <input type="text" name="sbox" style="width: 200px;"> <input type="submit" value="Go" class="button">
<div style="padding: 3px 0px 3px 0px; font-size: 10px; font-style: italic;">Just hit "Go" to bring up all records</div>
In: <select name="ssec">$sseclist</select> & <select name="scat">$scatlist</select>
</form>
</div>
<p><a href="?page=cw-share-files&cw_action=filecatupd">Update Section Categories</a>  |  <a href="?page=cw-share-files&cw_action=fileadd">Add</a>  |  <a href="?page=cw-share-files&cw_action=filesearch&sbox=VIEWHIDDEN">View Hidden Files</a></p>
Top Ten Downloads:<br><ul>$cw_share_files_dl_list</ul>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	File Update Section Categories
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'filecatupd') {
		$fs_wp_option_section_cats=$fs_wp_option.'_section_cats';
		$fs_wp_option_section_cats_chk=get_option($fs_wp_option_section_cats);

		//	Get category data
		$fscategoryinfo=array();
		$myrows=$wpdb->get_results("SELECT cat_id,cat_name,cat_rank FROM $cw_share_files_cats_tbl");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$cat_id=$cwfa_fs->cwf_san_int($myrow->cat_id);
				$cat_name=stripslashes($myrow->cat_name);
				$cat_rank=$cwfa_fs->cwf_san_int($myrow->cat_rank);
				$cat_name=strtolower($cat_name);
				$cat_name=$cwfa_fs->cwf_san_an($cat_name);
				$fscategoryinfo[$cat_id]="$cat_rank|$cat_name";
			}
		}

		//	Get section categories
		$fs_section_cats_array=array();
		foreach ($settings_sections as $settings_section) {
			list($settings_section_id,$settings_section_name)=explode('|',$settings_section);
			
			$dl_cat_ids=array();
			$section_cat_info=array();

			//	Get categories for section
			$myrows=$wpdb->get_results("SELECT dl_cat_id FROM $cw_share_files_dls_tbl where dl_section_id like '%{$settings_section_id}%' group by dl_cat_id");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$dl_cat_id=$cwfa_fs->cwf_san_int($myrow->dl_cat_id);
					array_push($dl_cat_ids,$dl_cat_id);
				}
			}

			//	Verify there is at least one live file in each category
			foreach ($dl_cat_ids as $dl_cat_id) {
				$dl_id='0';
				$dl_cat_info='';
				$myrows=$wpdb->get_results("SELECT dl_id FROM $cw_share_files_dls_tbl where dl_section_id like '%{$settings_section_id}%' and dl_cat_id='$dl_cat_id' and dl_status='l'");
				if ($myrows) {
					foreach ($myrows as $myrow) {
						$dl_id=$cwfa_fs->cwf_san_int($myrow->dl_id);
						if ($dl_cat_id > '0') {
							$dl_cat_info=$fscategoryinfo[$dl_cat_id];
							$section_cat_info[$dl_cat_info]=$dl_cat_id;
						}
					}
				}
			}
	
			if ($section_cat_info) {
				ksort($section_cat_info);
				$section_cat_info=implode('|',$section_cat_info);
				$fs_section_cats_array[$settings_section_id]=$section_cat_info;
			}
		}

	$fs_section_cats_array=serialize($fs_section_cats_array);

	//	Save to wp db
	if (!$fs_wp_option_section_cats_chk) {
		add_option($fs_wp_option_section_cats,$fs_section_cats_array);
	} else {
		update_option($fs_wp_option_section_cats,$fs_section_cats_array);
	}

	$cw_share_files_action='Update Section Categories';
	$cw_share_files_html='<p>Success! Each section has had its assigned category list updated!  <a href="?page=cw-share-files&cw_action=files">Continue...</a></p>';

	////////////////////////////////////////////////////////////////////////////
	//	File Add & Edit
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'fileadd' or $cw_action == 'fileedit') {
		$dl_id='0';
		$dl_cat_id='0';
		$dl_section_id='';
		$dl_status='';
		$dl_title='';
		$dl_url='';
		$dl_desc='';
		$dl_auth_name='';
		$dl_auth_url='';
		$dl_auth_email='';
		$dl_misc='';

		$cw_share_files_action_btn='Add';
		if ($cw_action == 'fileedit') {
			$cw_share_files_action_btn='Edit';

			if (isset($_REQUEST['dl_id'])) {
				$dl_id=$cwfa_fs->cwf_san_int($_REQUEST['dl_id']);
				if (!$dl_id) {
					$dl_id='0';
				}
			}
			$myrows=$wpdb->get_results("SELECT dl_cat_id,dl_section_id,dl_status,dl_title,dl_url,dl_desc,dl_auth_name,dl_auth_url,dl_auth_email,dl_misc FROM $cw_share_files_dls_tbl where dl_id='$dl_id'");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$dl_cat_id=$cwfa_fs->cwf_san_int($myrow->dl_cat_id);
					$dl_section_id=$myrow->dl_section_id;
					$dl_status=$cwfa_fs->cwf_san_an($myrow->dl_status);
					$dl_title=stripslashes($myrow->dl_title);
					$dl_url=$cwfa_fs->cwf_san_alls($myrow->dl_url);
					$dl_desc=stripslashes($myrow->dl_desc);
					$dl_auth_name=stripslashes($myrow->dl_auth_name);
					$dl_auth_url=stripslashes($myrow->dl_auth_url);
					$dl_auth_email=stripslashes($myrow->dl_auth_email);
					$dl_misc=stripslashes($myrow->dl_misc);
				}
			}
		}

		$dl_cat_list='';
		$cw_share_files_cat_cnt='0';
		$myrows=$wpdb->get_results("SELECT cat_id, cat_name FROM $cw_share_files_cats_tbl order by cat_name");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$cw_share_files_cat_id=$myrow->cat_id;
				$cw_share_files_cat_name=$myrow->cat_name;
				$dl_cat_list .='<option value="'.$cw_share_files_cat_id.'"';
				if ($dl_cat_id == $cw_share_files_cat_id) {
					$dl_cat_list .=' selected';
				}
				$dl_cat_list .='>'.$cw_share_files_cat_name.'</option>';
				$cw_share_files_cat_cnt++;
			}
		}
		$dl_cat_list='<option value="">Select</option>'.$dl_cat_list;

		$dl_section_list='';
		$dl_section_id=preg_replace('/{/','',$dl_section_id);
		$dl_section_ids=explode('}',$dl_section_id);
		foreach ($settings_sections as $settings_section_data) {
			list($settings_section_id,$settings_section_name)=explode('|',$settings_section_data);
			$dl_section_list .='<input type="checkbox" name="dl_section_ids[]" value="'.$settings_section_id.'"';
			if (in_array($settings_section_id,$dl_section_ids)) {
 				$dl_section_list .=' checked';
			}
			$dl_section_list .='> '.$settings_section_name.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}

		if (!$dl_status) {
			$dl_status='l';
		}
		$dl_status_html='<input type="radio" name="dl_status" value="l"';
		if ($dl_status == 'l') {
			$dl_status_html .=' checked';
		}
		$dl_status_html .='> On/Live <input type="radio" name="dl_status" value="h"';
		if ($dl_status != 'l') {
			$dl_status_html .=' checked';
		}
		$dl_status_html .='> Off/Hidden';

		$cw_share_files_action=$cw_share_files_action_btn.'ing File';
		$cw_action .='sv';
$cw_share_files_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="$cw_action">
<input type="hidden" name="dl_id" value="$dl_id">
<p>Title: <input type="text" name="dl_title" value="$dl_title" style="width: 400px;"></p>
<p>Section(s):</p><p>$dl_section_list</p>
<p>Category: <select name="dl_cat_id">$dl_cat_list</select></p>
<p>Status: $dl_status_html</p>
<p>Source/File URL:<br>$settings_url<input type="text" name="dl_url" value="$dl_url" style="width: 350px;"></p>
<p>Description:<br><div style="margin-left: 20px;">System will automatically convert returns into HTML line breaks</div></p>
<p><textarea class="large-text" name="dl_desc" style="height: 300px;">$dl_desc</textarea></p>
<p>Optional Information:</p>
<p>Author Name: <input type="text" name="dl_auth_name" value="$dl_auth_name" style="width: 400px;"></p>
<p>Author URL: <input type="text" name="dl_auth_url" value="$dl_auth_url" style="width: 400px;"></p>
<p>Author Email: <input type="text" name="dl_auth_email" value="$dl_auth_email" style="width: 400px;"></p>
<p>Misc ID (25 character max): <input type="text" name="dl_misc" value="$dl_misc" style="width: 150px;"></p>
<p><input type="submit" value="$cw_share_files_action_btn" class="button">
</form>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	File Add & Edit Save
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'fileaddsv' or $cw_action == 'fileeditsv') {
		$error='';
		$dl_id=$cwfa_fs->cwf_san_int($_REQUEST['dl_id']);
		$dl_cat_id=$cwfa_fs->cwf_san_int($_REQUEST['dl_cat_id']);
		if (isset($_REQUEST['dl_section_ids'])) {
			$dl_section_ids=$_REQUEST['dl_section_ids'];
		} else {
			$dl_section_ids='';
		}
		$dl_title=trim($_REQUEST['dl_title']);
		$dl_status=$cwfa_fs->cwf_san_an($_REQUEST['dl_status']);
		$dl_url=$cwfa_fs->cwf_san_url($_REQUEST['dl_url']);
		$dl_desc=trim($_REQUEST['dl_desc']);
		$dl_auth_name=trim($_REQUEST['dl_auth_name']);
		$dl_auth_url=trim($_REQUEST['dl_auth_url']);
		$dl_auth_email=trim($_REQUEST['dl_auth_email']);
		$dl_misc=trim($_REQUEST['dl_misc']);

		if (!$dl_title) {
			$error .='<li>No file title provided</li>';
		}
		$dl_section_id_data='';
		if (!$dl_section_ids) {
			$error .='<li>No section selected</li>';
		} else {
			foreach ($dl_section_ids as $dl_section_id) {
				$dl_section_id=$cwfa_fs->cwf_san_int($dl_section_id);
				$dl_section_id_data .='{'.$dl_section_id.'}';
			}
			$dl_section_id=$dl_section_id_data;
		}
		if (!$dl_cat_id) {
			$error .='<li>No category selected</li>';
		}
		if (!$dl_status) {
			$dl_status='l';
		}
		if (!$dl_url) {
			$error .='<li>No source url provided</li>';
		} else {
			$dl_url_slash_chk=substr($dl_url,0,1);
			if ($dl_url_slash_chk == '/') {
				$dl_url_len=strlen($dl_url);
				$dl_url_len--;
				$dl_url=substr($dl_url,1,$dl_url_len);
			}
			if (!file_exists("$settings_path$dl_url")) {
				$error .='<li>Source url does NOT exist</li>';
			}
		}
		if (!$dl_desc) {
			$error .='<li>No description provided</li>';
		}

		$cw_share_files_action='Error';
		if ($error) {
			$cw_share_files_html='Please fix the following in order to save settings:<br><ul style="list-style: disc; margin-left: 25px;">'. $error .'</ul>'.$pplink;
		} else {
			$cw_share_files_action='Success';

			$data=array();
			$data['dl_cat_id']=$dl_cat_id;
			$data['dl_section_id']=$dl_section_id;
			$data['dl_last_update']=filemtime("$settings_path$dl_url");			//	time();
			$data['dl_title']=$dl_title;
			$data['dl_status']=$dl_status;
			$data['dl_url']=$dl_url;
			$data['dl_desc']=$dl_desc;
			$data['dl_auth_name']=$dl_auth_name;
			$data['dl_auth_url']=$dl_auth_url;
			$data['dl_auth_email']=$dl_auth_email;
			$data['dl_file_size']=filesize("$settings_path$dl_url");
			$data['dl_md5']=md5_file("$settings_path$dl_url");
			$data['dl_misc']=$dl_misc;
			
			if ($cw_action == 'fileeditsv') {
				$where=array();
				$where['dl_id']=$dl_id;
				$wpdb->update($cw_share_files_dls_tbl,$data,$where);
			} else {
				$wpdb->insert($cw_share_files_dls_tbl,$data);
				$dl_id=$wpdb->insert_id;
			}

			$cw_share_files_html='<p>'.$dl_title.' has been successfully saved!</p>';
			$cw_share_files_html .='<p> Now what? <a href="?page=cw-share-files&cw_action=fileview&dl_id='.$dl_id.'">View File</a> or <a href="?page=cw-share-files&cw_action=files">File Management</a></p>';
		}
		$cw_share_files_action .=' Saving File';

	////////////////////////////////////////////////////////////////////////////
	//	File View
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'fileview') {
		$dl_id=$cwfa_fs->cwf_san_int($_REQUEST['dl_id']);
		$myrows=$wpdb->get_results("SELECT dl_cat_id,dl_section_id,dl_status,dl_last_update,dl_title,dl_desc,dl_auth_name,dl_auth_url,dl_auth_email,dl_file_size,dl_md5,dl_cnt,dl_misc FROM $cw_share_files_dls_tbl where dl_id='$dl_id'");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$dl_cat_id=$cwfa_fs->cwf_san_int($myrow->dl_cat_id);
				$dl_section_id=$myrow->dl_section_id;
				$dl_last_update=$cwfa_fs->cwf_san_int($myrow->dl_last_update);
				$dl_status=$myrow->dl_status;
				$dl_title=stripslashes($myrow->dl_title);
				$dl_desc=stripslashes($myrow->dl_desc);
				$dl_auth_name=stripslashes($myrow->dl_auth_name);
				$dl_auth_url=stripslashes($myrow->dl_auth_url);
				$dl_auth_email=stripslashes($myrow->dl_auth_email);
				$dl_file_size=$myrow->dl_file_size;
				$dl_md5=$myrow->dl_md5;
				$dl_cnt=$cwfa_fs->cwf_fmt_tho($myrow->dl_cnt);
				$dl_misc=$myrow->dl_misc;
			}
		}

		$myrows=$wpdb->get_results("SELECT cat_name FROM $cw_share_files_cats_tbl where cat_id='$dl_cat_id'");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$cat_name=$myrow->cat_name;
			}
		}

		$dl_section_id=preg_replace('/{/','',$dl_section_id);
		$dl_section_ids=explode('}',$dl_section_id);
		$dl_section_id='';
		foreach ($settings_sections as $settings_section) {
			list($settings_section_id,$settings_section_name)=explode('|',$settings_section);
			if (in_array($settings_section_id,$dl_section_ids)) {
				if ($dl_section_id) {
					$dl_section_id .=', ';
				}
				$dl_section_id .=$settings_section_name;
			}
		}

		$settings_dl_pg=$fs_wp_option_array['settings_dl_pg'];
		if (!$settings_dl_pg) {
			$settings_dl_pg='Download page not set';
		} else {
			$wp_site_url=home_url();
			$settings_dl_pg='<a href="'.$wp_site_url.'/'.$settings_dl_pg.'?cw_action=fileview&file_id='.$dl_id.'" target="_blank">View Now</a>';
		}

		if ($dl_status == 'l') {
			$dl_status='Live';
		} else {
			$dl_status='Hidden';
			$settings_dl_pg='Disabled when hidden';
		}

		$dl_desc=preg_replace('/\n/','<BR>',$dl_desc);
		$dl_file_size=$cwfa_fs->cwf_human_filesize($dl_file_size);

		if (!$dl_auth_name and !$dl_auth_url and !$dl_auth_email) {
			$dl_author='None';
		} else {
			if (!$dl_auth_name) {
				$dl_auth_name='n/a';
			}
			if (!$dl_auth_email) {
				$dl_auth_email='n/a';
			}
			if (!$dl_auth_url) {
				$dl_auth_url='n/a';
			} else {
				$dl_auth_url='<a href="'.$dl_auth_url.'" target="_blank">'.$dl_auth_url.'</a>';
			}
			$dl_author='Name: '.$dl_auth_name.'<br>URL: '.$dl_auth_url.'<br>Email: '.$dl_auth_email;
		}

		if ($dl_last_update > '0') {
			$dl_last_update=$cwfa_fs->cwf_dt_fmt($dl_last_update);
		} else {
			$dl_last_update='Never';
		}


$dl_title_url=urlencode($dl_title);
$cw_share_files_action='Viewing File';
$cw_share_files_html .=<<<EOM
<p>Viewing: <b>$dl_title</b> [<a href="?page=cw-share-files&cw_action=fileedit&dl_id=$dl_id">Edit File</a>]</p>
<p>Status: $dl_status | View As User: $settings_dl_pg<br>Total Downloads: $dl_cnt<br>Last Updated: $dl_last_update</p>
<p>Section(s): $dl_section_id<br>Category: $cat_name<br>Misc ID: $dl_misc</p>
<p>MD5: $dl_md5<br>File Size: $dl_file_size</p>
<p style="border-top: 1px solid #d6d6cf; padding-top: 5px; width: 400px;">Description: $dl_desc</p>
<p style="border-top: 1px solid #d6d6cf; padding-top: 5px; width: 400px;">Author Details:</p><p>$dl_author</p>

<div id="file_del_link" name="file_del_link" style="border-top: 1px solid #d6d6cf; margin-top: 20px; padding: 5px; width: 390px;"><a href="javascript:void(0);" onclick="document.getElementById('file_del_controls').style.display='';document.getElementById('file_del_link').style.display='none';">Show deletion controls</a></div>
<div name="file_del_controls" id="file_del_controls" style="display: none; width: 390px; margin-top: 20px; border: 1px solid #d6d6cf; padding: 5px;">
<a href="javascript:void(0);" onclick="document.getElementById('file_del_controls').style.display='none';document.getElementById('file_del_link').style.display='';">Hide deletion controls</a>
<form method="post">
<input type="hidden" name="cw_action" value="filedel"><input type="hidden" name="dl_id" value="$dl_id"><input type="hidden" name="dl_title" value="$dl_title_url">
<p><input type="checkbox" name="dl_confirm_1" value="1"> Check to delete $dl_title</p>
<p><input type="checkbox" name="dl_confirm_2" value="1"> Check to confirm deletion of $dl_title</p>
<p><span style="color: #ff0000; font-weight: bold;">Deletion is final! There is no undoing this action! Deleting download record only! No file will be removed!</span></p>
<p style="text-align: right;"><input type="submit" value="Delete" class="button"></p>
</div>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	File Delete
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'filedel') {
		$dl_id=$cwfa_fs->cwf_san_int($_REQUEST['dl_id']);
		if (isset($_REQUEST['dl_confirm_1'])) {
			$dl_confirm_1=$cwfa_fs->cwf_san_int($_REQUEST['dl_confirm_1']);
		} else {
			$dl_confirm_1='0';
		}
		if (isset($_REQUEST['dl_confirm_2'])) {
			$dl_confirm_2=$cwfa_fs->cwf_san_int($_REQUEST['dl_confirm_2']);
		} else {
			$dl_confirm_2='0';
		}
		$dl_title=urldecode($_REQUEST['dl_title']);

		$cw_share_files_action='Deleting File';

		if (!$dl_id) {
			$dl_confirm_1='0';
		}

		if ($dl_confirm_1 == '1' and $dl_confirm_2 == '1') {
			$where=array();
			$where['dl_id']=$dl_id;
			$wpdb->delete($cw_share_files_dls_tbl,$where);
			$cw_share_files_html=$dl_title.' record has been removed! Do keep in mind no actual file has been deleted only the download record! <a href="?page=cw-share-files&cw_action=files">Continue...</a>';
		} else {
			$cw_share_files_html='<span style="color: #ff0000;">Error! You must check both confirmation boxes!</span><br><br>'.$pplink;
		}

	////////////////////////////////////////////////////////////////////////////
	//	File Search
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'filesearch') {
		$search_results='';
		$pgprevnxt='';
		$pgnavlist='';

		//	Load search box
		$sbox=trim($_REQUEST['sbox']);
		$sbox=stripslashes($sbox);
		if (!$sbox) {
			$sbox='%';
		}
		$sboxlink=urlencode($sbox);
		$sbox=addslashes($sbox);

		if ($sbox == 'VIEWHIDDEN') {
			$fs_wheresql="dl_status='h'";
		} else {
			$fs_wheresql="(dl_title like '%$sbox%' or dl_url like '%$sbox' or dl_desc like '%$sbox%')";
		}
		$fs_wherelink="sbox=$sboxlink";
		$fs_form='<input type="hidden" name="sbox" value="'.$sbox.'">';

		//	Section check
		$ssec_name='All sections';
		if (isset($_REQUEST['ssec']) and $_REQUEST['ssec'] > '0') {
			$ssec=$cwfa_fs->cwf_san_int($_REQUEST['ssec']);
			if (!$ssec) {
				$ssec='1';
			}
			$fs_wheresql .=" and dl_section_id like '%\{$ssec\}%'";
			$fs_wheresql=stripslashes($fs_wheresql);
			$fs_wherelink .='&ssec='.$ssec;
			$fs_form .='<input type="hidden" name="ssec" value="'.$ssec.'">';

			foreach ($settings_sections as $settings_section) {
				list($section_id,$section_name)=explode('|',$settings_section);
				if ($section_id == $ssec) {
					$ssec_name=$section_name;
					break;
				}
			}
			$ssec_name='the '.$ssec_name.' section';
		}

		//	Category check
		$cat_name='All categories';
		if (isset($_REQUEST['scat']) and $_REQUEST['scat']) {
			$scat=$cwfa_fs->cwf_san_int($_REQUEST['scat']);
			$fs_wheresql .=" and dl_cat_id='$scat'";
			$fs_wherelink .='&scat='.$scat;
			$fs_form .='<input type="hidden" name="scat" value="'.$scat.'">';

			$myrows=$wpdb->get_results("SELECT cat_name FROM $cw_share_files_cats_tbl where cat_id='$scat'");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$cat_name=$myrow->cat_name.' category';
				}
			}
		}

		//	Matching record count
		$search_cnt='0';
		$myrows=$wpdb->get_results("SELECT count(dl_id) as dl_cnt FROM $cw_share_files_dls_tbl where $fs_wheresql");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$search_cnt=$myrow->dl_cnt;
			}
		}

		//	Max page count
		$tpgs=$search_cnt/$settings_ppg;
		if (substr_count($tpgs,'.') > '0') {
			list($tpgs,$tpgsdiscard)=explode('.',$tpgs);
			$tpgs++;
		}

		//	Load page count
		if (isset($_REQUEST['spg'])) {
			$spg=$cwfa_fs->cwf_san_int($_REQUEST['spg']);
			if (!$spg) {
				$spg='1';
			}
		} else {
			$spg='1';
		}

		//	Page count can't exceed max pages
		if ($spg > $tpgs) {
			$spg=$tpgs;
		}
		$cw_page_txt='Page: '.$spg.' of '.$tpgs;

		//	Get records
		$snum=($spg-1)*$settings_ppg;
		if ($snum < '0') {
			$snum='0';
		}
		$enum=$snum;
		$myrows=$wpdb->get_results("SELECT dl_id,dl_title FROM $cw_share_files_dls_tbl where $fs_wheresql limit $snum,$settings_ppg");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$dl_id=$myrow->dl_id;
				$dl_title=$myrow->dl_title;
				$enum++;
				$enum=$cwfa_fs->cwf_fmt_tho($enum);
				$search_results .='<li>'.$enum.') <a href="?page=cw-share-files&cw_action=fileview&dl_id='.$dl_id.'">'.$dl_title.'</a></li>';
				$enum=$cwfa_fs->cwf_san_int($enum);
			}
		}

		//	Show search text
		if ($search_results) {
			$snum++;
			$snum=$cwfa_fs->cwf_fmt_tho($snum);
			$enum=$cwfa_fs->cwf_fmt_tho($enum);
			$search_cnt=$cwfa_fs->cwf_fmt_tho($search_cnt);

			$search_results="<p>Displaying $snum to $enum out of $search_cnt</p><ul>$search_results</ul>";

			$snum=$cwfa_fs->cwf_san_int($snum);
			$enum=$cwfa_fs->cwf_san_int($enum);
			$search_cnt=$cwfa_fs->cwf_san_int($search_cnt);
		} else {
			$search_results='<li>No records</li>';
		}

		//	Build Page List
		if ($search_results) {
			$tpgsloop=$spg-4;
			$tpgsmax=$spg+3;

			if ($tpgsloop < '1') {
				$tpgsloop='0';
				$tpgsmax='7';
			}
			if ($spg > ($tpgs-6)) {
				$tpgsloop=$tpgs-7;
				$tpgsmax=$tpgs;
			}
			if ($tpgs < '9') {
				$tpgsloop='0';
				$tpgsmax=$tpgs;
			}
		
			while ($tpgsloop < $tpgsmax) {
				$tpgsloop++;
				if ($pgnavlist) {
					$pgnavlist .=' | ';
				}
				if ($tpgsloop == $spg) {
					$pgnavlist .=$tpgsloop;
				} else {
					$pgnavlist .='<a href="?page=cw-share-files&cw_action=filesearch&'.$fs_wherelink.'&spg='.$tpgsloop.'">'.$tpgsloop.'</a>';
				}
			}
			if ($pgnavlist) {
				if ($spg != '1') {
					$spgpx=$spg-1;
					$pgprevnxt='<a href="?page=cw-share-files&cw_action=filesearch&'.$fs_wherelink.'&spg='.$spgpx.'">Previous Page</a>';
				}
				if ($spg != $tpgs) {
					$spgpx=$spg+1;
					if ($pgprevnxt) {
						$pgprevnxt .=' | ';
					}
					$pgprevnxt .='<a href="?page=cw-share-files&cw_action=filesearch&'.$fs_wherelink.'&spg='.$spgpx.'">Next Page</a>';
				}
				if ($pgprevnxt) {
					$pgprevnxt=' .:. '.$pgprevnxt;
				}

				//	Show page list if more than one page
				if ($tpgs > '1') {
					$pgnavlist="<p>$cw_page_txt$pgprevnxt</p><p>Pages: $pgnavlist</p>";
				} else {
					$pgnavlist="<p>$cw_page_txt$pgprevnxt</p>";
				}

				if ($tpgs > '8') {
					$pgnavlist .='<p><form method="post" style="margin: 0px; 0px;"><input type="hidden" name="cw_action" value="filesearch">'.$fs_form.'Jump To Page: <input type="text" name="spg" style="width: 40px;"> of '.$tpgs.' <input type="submit" value="Go" class="button"></form></p>';
				}
			} else {
				$pgnavlist='&nbsp;';
			}
		}

		$cw_share_files_action='Searching Files';
		if ($sbox == 'VIEWHIDDEN') {
			$sbox='Hidden Files';
		}
$cw_share_files_html .=<<<EOM
<p>Results for: <b>$sbox</b> in $ssec_name and $cat_name</p>
$search_results
$pgnavlist
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Main Category Screen
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'cats') {
		$cw_share_files_action='Category Management';
		$cw_share_files_cat_cnt='0';

		//	Get categories
		$cw_share_files_cat_list='';
		$myrows=$wpdb->get_results("SELECT cat_id, cat_name FROM $cw_share_files_cats_tbl order by cat_name");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$cw_share_files_cat_id=$myrow->cat_id;
				$cw_share_files_cat_name=$myrow->cat_name;
				$cw_share_files_cat_list .='<li><a href="?page=cw-share-files&cw_action=catview&cat_id='.$cw_share_files_cat_id.'">'.$cw_share_files_cat_name.'</a></li>';
				$cw_share_files_cat_cnt++;
			}
		} else {
			$cw_share_files_cat_list='None';
		}

$cw_share_files_html .=<<<EOM
<p><a href="?page=cw-share-files&cw_action=catadd">Add</a></p>
<p>Category List: $cw_share_files_cat_cnt</p><ol style="margin-left: 25px;">$cw_share_files_cat_list</ol>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Category Add & Edit
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'catadd' or $cw_action == 'catedit') {
		$cat_id='0';
		$cat_name='';
		$cat_desc='';
		$cat_rank='';

		$cw_share_files_action_btn='Add';
		if ($cw_action == 'catedit') {
			$cw_share_files_action_btn='Edit';

			$cat_id=$cwfa_fs->cwf_san_int($_REQUEST['cat_id']);
			$myrows=$wpdb->get_results("SELECT cat_name,cat_desc,cat_rank FROM $cw_share_files_cats_tbl where cat_id='$cat_id'");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$cat_name=$myrow->cat_name;
					$cat_desc=stripslashes($myrow->cat_desc);
					$cat_rank=$cwfa_fs->cwf_san_int($myrow->cat_rank);
				}
			}
		}

		if (!$cat_rank or $cat_rank > '3') {
			$cat_rank='2';
		}

		$cat_rank_list='';
		$cat_rank_list_ops=array('1|Important','2|Standard','3|Unimportant');
		foreach ($cat_rank_list_ops as $cat_rank_list_op) {
			list($cat_rank_list_op_id,$cat_rank_list_op_nm)=explode('|',$cat_rank_list_op);
			$cat_rank_list .='<option value="'.$cat_rank_list_op_id.'"';
			if ($cat_rank == $cat_rank_list_op_id) {
				$cat_rank_list .=' selected';
			}
			$cat_rank_list .='>'.$cat_rank_list_op_nm.'</option>';
		}

		$cw_share_files_action=$cw_share_files_action_btn.'ing Category';
		$cw_action .='sv';
$cw_share_files_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="$cw_action">
<input type="hidden" name="cat_id" value="$cat_id">
<p>Name: <input type="text" name="cat_name" value="$cat_name" style="width: 400px;"></p>
<p>Description: (200 character limit):</p>
<p><textarea name="cat_desc" style="width: 400px; height: 100px;">$cat_desc</textarea></p>
<p>Ranking: <select name="cat_rank">$cat_rank_list</select></p>
<p><input type="submit" value="$cw_share_files_action_btn" class="button">
</form>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Category Add & Edit Save
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'cataddsv' or $cw_action == 'cateditsv') {
		$cat_id=$cwfa_fs->cwf_san_int($_REQUEST['cat_id']);
		$cat_name=$cwfa_fs->cwf_san_title($_REQUEST['cat_name']);
		$cat_desc=$cwfa_fs->cwf_san_all($_REQUEST['cat_desc']);
		$cat_rank=$cwfa_fs->cwf_san_int($_REQUEST['cat_rank']);

		// Check for category existance
		$chk_cat_id='0';
		if ($cat_name) {
			$myrows=$wpdb->get_results("SELECT cat_id as cat_cnt FROM $cw_share_files_cats_tbl where cat_name='$cat_name'");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$chk_cat_id=$myrow->cat_cnt;
				}
			}
		}

		if (!$cat_rank) {
			$cat_rank='2';
		}

		$cw_share_files_action='Error';
		if (!$cat_name) {
			$cw_share_files_html='<span style="color: #ff0000;">Error! No category name entered.</span><br><br>Please enter a name. '.$pplink;
		} elseif (($cw_action == 'cataddsv' and $chk_cat_id > '0') or ($cw_action == 'cateditsv' and $chk_cat_id > '0' and $chk_cat_id != $cat_id)) {
			$cw_share_files_html='<span style="color: #ff0000;">Error! Category <b>'. strtolower($cat_name) .'</b> already in use!</span><br><br>Please select another name. '.$pplink;
		} else {
			$cw_share_files_action='Success';

			$data=array();
			$data['cat_name']=$cat_name;
			$data['cat_desc']=$cat_desc;
			$data['cat_rank']=$cat_rank;

			if ($cw_action == 'cateditsv') {
				$where=array();
				$where['cat_id']=$cat_id;
				$wpdb->update($cw_share_files_cats_tbl,$data,$where);
			} else {
				$wpdb->insert($cw_share_files_cats_tbl,$data);
				$cat_id=$wpdb->insert_id;
			}

			$cw_share_files_html='<p>'.$cat_name.' has been successfully saved!</p>';
			$cw_share_files_html .='<p> Now what? <a href="?page=cw-share-files&cw_action=catview&cat_id='.$cat_id.'">View Category</a> or <a href="?page=cw-share-files&cw_action=cats">Category Management</a></p>';
		}

		$cw_share_files_action .=' In Saving Category';

	////////////////////////////////////////////////////////////////////////////
	//	Category View
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'catview') {
		$cat_id=$cwfa_fs->cwf_san_int($_REQUEST['cat_id']);

		//	Category data
		$cw_share_files_cat_cnt='0';
		$myrows=$wpdb->get_results("SELECT cat_name,cat_desc FROM $cw_share_files_cats_tbl where cat_id='$cat_id'");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$cat_name=$myrow->cat_name;
				$cat_desc=$myrow->cat_desc;
			}
		}

		if ($cat_desc) {
			$cat_desc=stripslashes($cat_desc);
		} else {
			$cat_desc='No description';
		}

		$myrows=$wpdb->get_results("SELECT count(dl_id) as dl_cnt FROM $cw_share_files_dls_tbl where dl_cat_id='$cat_id'");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$dl_cnt=$myrow->dl_cnt;
			}
		} else {
			$dl_cnt='0';
		}


		if ($dl_cnt > '0') {
			$cw_cat_dl_list='<a href="?page=cw-share-files&cw_action=filesearch&sbox=&scat='.$cat_id.'">Load</a>';
		} else {
			$cw_cat_dl_list='None';
		}

$cat_name_url=urlencode($cat_name);
$cw_share_files_action='Viewing Category';
$cw_share_files_html .=<<<EOM
<p>Viewing: <b>$cat_name</b> [<a href="?page=cw-share-files&cw_action=catedit&cat_id=$cat_id">Edit Category</a>]</p>
<p>$cat_desc</p>
<p>Statistics:</p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Number Of Files: $dl_cnt</li>
<li>View File List: $cw_cat_dl_list</li>
</ul>
<div id="cat_del_link" name="cat_del_link" style="border-top: 1px solid #d6d6cf; margin-top: 20px; padding: 5px; width: 390px;"><a href="javascript:void(0);" onclick="document.getElementById('cat_del_controls').style.display='';document.getElementById('cat_del_link').style.display='none';">Show deletion controls</a></div>
<div name="cat_del_controls" id="cat_del_controls" style="display: none; width: 390px; margin-top: 20px; border: 1px solid #d6d6cf; padding: 5px;">
<a href="javascript:void(0);" onclick="document.getElementById('cat_del_controls').style.display='none';document.getElementById('cat_del_link').style.display='';">Hide deletion controls</a>
EOM;

//	Deletion controls
if ($dl_cnt > '0') {
$cw_share_files_html .=<<<EOM
<p>Unable to delete $cat_name while files are assigned</p>
EOM;
} else {
$cw_share_files_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="catdel"><input type="hidden" name="cat_id" value="$cat_id"><input type="hidden" name="cat_name" value="$cat_name_url">
<p><input type="checkbox" name="cat_confirm_1" value="1"> Check to delete $cat_name</p>
<p><input type="checkbox" name="cat_confirm_2" value="1"> Check to confirm deletion of $cat_name</p>
<p><span style="color: #ff0000; font-weight: bold;">Deletion is final! There is no undoing this action!</span></p>
<p style="text-align: right;"><input type="submit" value="Delete" class="button"></p>
</div>
EOM;
}

	////////////////////////////////////////////////////////////////////////////
	//	Category Delete
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'catdel') {
		$cat_id=$cwfa_fs->cwf_san_int($_REQUEST['cat_id']);
		if (isset($_REQUEST['cat_confirm_1'])) {
			$cat_confirm_1=$cwfa_fs->cwf_san_int($_REQUEST['cat_confirm_1']);
		} else {
			$cat_confirm_1='0';
		}
		if (isset($_REQUEST['cat_confirm_2'])) {
			$cat_confirm_2=$cwfa_fs->cwf_san_int($_REQUEST['cat_confirm_2']);
		} else {
			$cat_confirm_2='0';
		}
		$cat_name=urldecode($_REQUEST['cat_name']);

		$cw_share_files_action='Deleting Category';

		$myrows=$wpdb->get_results("SELECT count(dl_id) as dl_cnt FROM $cw_share_files_dls_tbl where dl_cat_id='$cat_id'");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$dl_cnt=$myrow->dl_cnt;
			}
		}
		if ($dl_cnt > '0') {
			$cat_confirm_1='0';
		}
		if (!$cat_id) {
			$cat_confirm_1='0';
		}

		if ($cat_confirm_1 == '1' and $cat_confirm_2 == '1') {
			$where=array();
			$where['cat_id']=$cat_id;
			$wpdb->delete($cw_share_files_cats_tbl,$where);
			$cw_share_files_html=$cat_name.' has been deleted! <a href="?page=cw-share-files&cw_action=cats">Continue...</a>';
		} else {
			$cw_share_files_html='<span style="color: #ff0000;">Error! You must check both confirmation boxes!</span><br><br>'.$pplink;
		}

	////////////////////////////////////////////////////////////////////////////
	//	Settings
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settings' or $cw_action == 'settingsv') {
		$cw_share_files_action='View';

		if ($cw_action == 'settingsv') {
			$cw_share_files_action='Sav';
			$error='';

			$fs_wp_option_array=array();

			$settings_url=$cwfa_fs->cwf_san_url($_REQUEST['settings_url']);
			if (!$settings_url) {
				$error .='<li>No Download Root URL</li>';
			} else {
				$settings_url=preg_replace('/http:\/\//','',$settings_url);
				$settings_url=preg_replace('/\/+/','/',$settings_url);
				$settings_url='http://'.$settings_url;
				$settings_url=$cwfa_fs->cwf_trailing_slash_on($settings_url);
				$fs_wp_option_array['settings_url']=$settings_url;
			}

			$settings_path=$cwfa_fs->cwf_san_url($_REQUEST['settings_path']);
			if (!$settings_path) {
				$error .='<li>No Download Root Absolute Path</li>';
			} else {
				$settings_path=preg_replace('/\/+/','/',$settings_path);
				$settings_path=$cwfa_fs->cwf_trailing_slash_on($settings_path);
				if (!is_dir($settings_path)) {
					$error .='<li>Download Root Absolute Path Does NOT Exist</li>';
				} else {
					$fs_wp_option_array['settings_path']=$settings_path;
				}
			}

			$settings_sections=$cwfa_fs->cwf_san_ansrp($_REQUEST['settings_sections']);
			if (!$settings_sections) {
				$error .='<li>No File Sections</li>';
			} else {
				$settings_sections_status='p';
				$settings_sections_array=explode("\n",$settings_sections);
				foreach ($settings_sections_array as $settings_sections_item) {
					list($settings_sections_id,$settings_sections_name)=explode('|',$settings_sections_item);
					$settings_sections_id=$cwfa_fs->cwf_san_int($settings_sections_id);
					$settings_sections_name=$cwfa_fs->cwf_san_ans($settings_sections_name);
					if ($settings_sections_id < '0') {
						$settings_sections_status='f';
						break;
					}
					if (!$settings_sections_name) {
						$settings_sections_status='f';
						break;
					}
				}
				if ($settings_sections_status == 'f') {
					$error .='<li>File Sections Is NOT Properly Setup</li>';
				} else {
					$fs_wp_option_array['settings_sections']=$settings_sections;
				}
			}

			$settings_ppg=$cwfa_fs->cwf_san_int($_REQUEST['settings_ppg']);
			if ($settings_ppg < '10' or $settings_ppg > '300') {
				$settings_ppg='25';
			}
			$fs_wp_option_array['settings_ppg']=$settings_ppg;

			$settings_anti_bot_var=$cwfa_fs->cwf_san_an($_REQUEST['settings_anti_bot_var']);
			$settings_anti_bot_var_len='0';
			if ($settings_anti_bot_var) {
				$settings_anti_bot_var_len=strlen($settings_anti_bot_var);
			}
			if ($settings_anti_bot_var_len < '15' and $settings_anti_bot_var != 'AUTO') {
				$error .='<li>Anti-Bot Variable Name Needs To Be At Least 15 Characters</li>';
			} else {
				if ($settings_anti_bot_var == 'AUTO') {
					$settings_anti_bot_var='40';
					$settings_anti_bot_var=$cwfa_fs->cwf_gen_randstr($settings_anti_bot_var);
				}
				$fs_wp_option_array['settings_anti_bot_var']=$settings_anti_bot_var;	
			}

			$settings_anti_bot_ans=$_REQUEST['settings_anti_bot_ans'];
			$settings_anti_bot_ans=preg_replace('/\r/','',$settings_anti_bot_ans);
			$settings_anti_bot_ans=trim($settings_anti_bot_ans);
			if (!$settings_anti_bot_ans) {
				$error .='<li>No Anti-Bot Answers</li>';
			} else {
				$$settings_anti_bot_ans_cnt='0';
				$settings_anti_bot_ans=explode("\n",$settings_anti_bot_ans);
				$settings_anti_bot_ans_cnt=count($settings_anti_bot_ans);
				if ($settings_anti_bot_ans_cnt < '10') {
					$error .='<li>Need At Least Ten Anti-Bot Answers</li>';
				} else {
					foreach ($settings_anti_bot_ans as $settings_anti_bot_data) {
						if ($settings_anti_bot_data == 'SHUFFLE') {
							array_shift($settings_anti_bot_ans);
							shuffle($settings_anti_bot_ans);
						}
						break;
					}
					$settings_anti_bot_ans=implode("\n",$settings_anti_bot_ans);
					$fs_wp_option_array['settings_anti_bot_ans']=$settings_anti_bot_ans;
				}
			}

			$settings_dl_pg=$cwfa_fs->cwf_san_title($_REQUEST['settings_dl_pg']);
			$fs_wp_option_array['settings_dl_pg']=$settings_dl_pg;

			$settings_mgrs=$cwfa_fs->cwf_san_anr($_REQUEST['settings_mgrs']);
			$fs_wp_option_array['settings_mgrs']=$settings_mgrs;

			if ($error) {
				$cw_share_files_html='Please fix the following in order to save settings:<br><ul style="list-style: disc; margin-left: 25px;">'. $error .'</ul>'.$pplink;
			} else {
				$fs_wp_option_array=serialize($fs_wp_option_array);
				$fs_wp_option_chk=get_option($fs_wp_option);

				if (!$fs_wp_option_chk) {
					add_option($fs_wp_option,$fs_wp_option_array);
				} else {
					update_option($fs_wp_option,$fs_wp_option_array);
				}

				$cw_share_files_html='Settings have saved! <a href="?page=cw-share-files">Continue to Main Menu</a>';
			}

		} else {
			if (!$settings_url) {
				$settings_url=home_url();
			}
			if (!$settings_path) {
				$settings_path=plugin_dir_path(__FILE__);
				list($settings_path,$settings_path_dis)=explode('wp-content/',$settings_path);
			}
			if (!$settings_sections) {
				$settings_sections='1|Main Section';
			} else {
				$settings_sections=implode("\n",$settings_sections);
			}
			if ($settings_ppg < '10') {
				$settings_ppg='25';
			}

			$settings_anti_bot_var=$fs_wp_option_array['settings_anti_bot_var'];
			$settings_anti_bot_ans=$fs_wp_option_array['settings_anti_bot_ans'];
			$settings_anti_bot_ans=stripslashes($settings_anti_bot_ans);
			$settings_dl_pg=$fs_wp_option_array['settings_dl_pg'];

$cw_share_files_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="settingsv">
<p>Download Root URL: <input type="text" name="settings_url" value="$settings_url" style="width: 400px;"></p>
<p>Download Root Absolute Path: <input type="text" name="settings_path" value="$settings_path" style="width: 400px;"></p>
<p>File Sections:<div style="margin-left: 20px;">One per line with unique number pipe then section name (will be seen by public)<br><br>Example: 1|Main Section</div></p>
<p><textarea name="settings_sections" style="width: 400px; height: 100px;">$settings_sections</textarea></p>
<p>Number Of Items Per Page: <input type="text" name="settings_ppg" value="$settings_ppg" style="width: 50px;"> (10-300)</p>
<p>Anti-Bot Variable Name:<div style="margin-left: 20px;">This will be the variable name for the box visitors will enter the anti-bot answer.  The name should be between 15 and 40 alpha numeric characters with mixed upper and lower case.  Also it is a good idea to change this variable name from time-to-time or if bots start becoming a problem.  If you want one automatically generated type AUTO (all upper case)</div></p>
<p><input type="text" name="settings_anti_bot_var" value="$settings_anti_bot_var" style="width: 400px;"></p>
<p>Anti-Bot Answers:<div style="margin-left: 20px;">List answers, one per line, a visitor will need to type in before downloading a file.  These can be a word or phrase, both may be used, and will NOT be case sensitive.  It is highly recommended you choose at least ten but no need to pick hundreds; ten to fifty is a good amount.  Type SHUFFLE (all upper case) on the first line if you would like the system to randomize the order.  This is also good to perform from time-to-time as it will confuse bots due to the answers changing order, which matters.<br><br>Example: Zeus<br>Hera<br>1+1<br>Cloudy Day</div></p>
<p><textarea name="settings_anti_bot_ans" style="width: 400px; height: 100px;">$settings_anti_bot_ans</textarea></p>
<p>Name Of Downloads Page - Optional: <input type="text" name="settings_dl_pg" value="$settings_dl_pg" style="width: 150px;"></p>
<p><i>User Access Control - Optional:</i></p>
<div style="margin-left; 20px;">You may control who has access to the <b>Share Files Management</b> panel.  Editors are able to perform all functions except access "Admin Functions" like settings.  Wordpress Admins have full access and don't need to be defined.  If you don't want to use this feature simply leave the box blank, however all editor level accounts will be able to access this plugin. As soon as you enter at least one username all non matching editor accounts will be blocked.  If you wish to block all editor accounts simply enter an admin username.</div>
<p>Authorized Wordpress Editor Usernames:<br><b>Note: Enter usernames one per line</b></p><p><textarea name="settings_mgrs" style="width: 400px; height: 100px;">$settings_mgrs</textarea></p>
<p><input type="submit" value="Save" class="button">
</form>
EOM;
	}

$cw_share_files_action .='ing Settings';

	////////////////////////////////////////////////////////////////////////////
	//	Help Guide
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settingshelp') {
		$cw_share_files_action='Help Guide';

		$cw_page_code='<p>&lt;style&gt;<br>.entry-title {<br>display:none;<br>}<br>&lt;/style&gt;<br>[cw_share_files]</p>';

$cw_share_files_html .=<<<EOM
<div style="margin: 10px 0px 5px 0px; width: 400px; border-bottom: 1px solid #c16a2b; padding-bottom: 5px; font-weight: bold;">Introduction:</div>
<p>This system allows you to easily manage file downloads on your site by offering multiple categories and sections.  While any system has a learning curve this plugin is laid out in a very straight forward manner and should be quick to master.</p>
<p>Sections allow you to split your file downloads into separate areas.  Each section will have its own category list, top ten downloads link, and search box.  These functions will only display files assigned to that section so if a file is not listed in that section it will not be shown.  This allows you to run separate download areas on your site for different purposes.  If you only want one download area then simply use a single section.</p>
<p>Categories are shared by all sections.  If a section has no files in a specific category then that category will not be displayed for that section.  Thus if you have a category that is only for one section then simply assign no files to it for other sections.  At present a file may be assigned to multiple sections but only one category.</p>
<p>It is important to note that this plugin does not upload, edit, or delete the actual download files.  This must be done separately using the Wordpress media manager or other file transfer system like a FTP account.   Why doesn't this system directly touch the download files? The original purpose of this system was to manage files that were multiple to hundreds of megabytes in size.  This just isn't possible through most PHP installations.</p>
<p>This plugin also has advanced administration controls.  While all Wordpress administrators may access this plugin you may control which editor level accounts have access.</p>
<p>There is also advanced anti-bot technology baked into this plugin.  This requires a little additional setup on your part since there is no standard information.  However it will be harder for bots to lock on and the information may be changed often to keep the bots at bay.</p>
<p>Steps:</p>
<ol>
<li>Setup the information in Settings.</li>
<li>Add some categories.</li>
<li>Add some files.</li>
<li>Run the Update Section Categories function in the File Management section to build the section category lists.</li>
<li>Add the code to display the file downloads - see below sections.</li>
<li>Add links to your file download page.</li>
<li>Add additional files, categories, sections, and download pages as needed.  On download page(s): If a category isn't appearing in a section category list or you see an empty category listed you need to run the Update Section Categories function.</li>
</ol>

<div style="margin: 10px 0px 5px 0px; width: 400px; border-bottom: 1px solid #c16a2b; padding-bottom: 5px; font-weight: bold;">Displaying Download Files:</div>
<p>Obviously the point of this system is to display the file downloads to your visitors.  This is extremely easy to accomplish and offers extreme flexibility by using a short code.</p>
<p>There is no forced file download page name.  You simply add the short code, listed in the next section, to whatever page or pages you wish your file list to be displayed.  That's right you can have multiple pages on which your file lists will appear on.</p>
<p>It is that simple.  Once you have added the brief short code, along with the css code to hide the Wordpress page name you simply call that page (or pages) with the ssid equals a section number.</p>
<p>Examples:  You have a page called file-list on your site.</p>
<ul style="list-style: disc; margin-left: 20px;">
<li><u>Full Link:</u> <span style="color: #c16a2b;">http://www.yoursiteurl.tld/file-list/?ssid=1</span></li>
<li><u>Relative Link:</u> <span style="color: #c16a2b;">/file-list/?ssid=1</span></li>
<li><u>Page In Directory Link:</u> <span style="color: #c16a2b;">/downloads/file-list/?ssid=1</span></li>
</ul>
<p>All links above would load section one.  You may place these links anywhere on your site like in the menus, posts, pages, header, footer, widgets, or even around the Internet on forums, social media, emails, etc.  For off site links you must use the full link but for on site links it is better to use relative links.</p>
<p>Note: While the short code will work on pages and posts it is recommended it be placed on a page.</p>

<div style="margin: 10px 0px 5px 0px; width: 400px; border-bottom: 1px solid #c16a2b; padding-bottom: 5px; font-weight: bold;">Page Code (text mode):</div>
$cw_page_code
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Future Ideas
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settingsfuture') {
		$cw_share_files_action='Future Ideas';

$cw_share_files_html .=<<<EOM
<p>The following is a list of possible future features.  However there is no guarantee when OR if these features may occur:</p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Remote files (most likely) - basic framework in place</li>
<li>More statistics</li>
<li>Child categories - basic framework in place</li>
<li>File browser</li>
</ul>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	What Is New?
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settingsnew') {
		$cw_share_files_action='What Is New?';

$cw_share_files_html .=<<<EOM
<p>The following lists the new changes from version-to-version.</p>
<p>Version: <b>1.6</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Fixed: Shortcode in certain areas would cause incorrect placement.</li>
</ul>
<p>Version: <b>1.5</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Fixed: Support and rating links</li>
</ul>
<p>Version: <b>1.4</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>UI changes</li>
</ul>
<p>Version: <b>1.3</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Fixed: Anti-bot word/phrase bug causing errors</li>
</ul>
<p>Version: <b>1.2</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Altered framework code to fit Wordpress Plugin Directory terms</li>
</ul>
<p>Version: <b>1.1</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Modified to use the Cleverwise Framework</li>
<li>Fixed: Incorrect download code error screen failed when another form was displayed</li>
</ul>
<p>Version: <b>1.0</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Initial release of plugin</li>
</ul>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Main panel
	////////////////////////////////////////////////////////////////////////////
	} else {
		if (!$fs_wp_option_array) {
			$cw_share_files_html='<span style="color: #ff0000; font-weight: bold; font-size: 16px; background-color: #ffd9d9; padding: 5px;">Check plugin settings!</span>';
		}

		//	Category count
		$cw_share_files_cat_cnt='0';
		$myrows=$wpdb->get_results("SELECT count(cat_id) as cat_cnt FROM $cw_share_files_cats_tbl");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$cw_share_files_cat_cnt=$myrow->cat_cnt;
			}
		}

		//	File count
		$cw_share_files_dl_cnt='0';
		$myrows=$wpdb->get_results("SELECT count(dl_id) as cat_cnt FROM $cw_share_files_dls_tbl");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$cw_share_files_dl_cnt=$myrow->cat_cnt;
			}
		}

		$cw_share_files_cat_cnt=$cwfa_fs->cwf_fmt_tho($cw_share_files_cat_cnt);
		$cw_share_files_dl_cnt=$cwfa_fs->cwf_fmt_tho($cw_share_files_dl_cnt);

$cw_share_files_action='Main Panel';
$cw_share_files_html .=<<<EOM
<p>Statistics:</p>
<ul style="list-style: disc; margin-left: 25px;">
<li><a href="?page=cw-share-files&cw_action=cats">Categories</a>: $cw_share_files_cat_cnt</li>
<li><a href="?page=cw-share-files&cw_action=files">Files</a>: $cw_share_files_dl_cnt</li>
</ul>
EOM;
	if ($settings_lock_down == 'n') {
$cw_share_files_html .=<<<EOM
<div style="margin-top: 30px; border-top: 1px dotted #d6d6cf; padding-top: 5px; width: 400px;">Admin Functions:<br><a href="?page=cw-share-files&cw_action=settings">Settings</a> | <a href="?page=cw-share-files&cw_action=settingshelp">Help Guide</a> | <a href="?page=cw-share-files&cw_action=settingsnew">What Is New?</a> | <a href="?page=cw-share-files&cw_action=settingsfuture">Future Ideas</a></p>
EOM;
	}
	}

	////////////////////////////////////////////////////////////////////////////
	//	Send to print out
	////////////////////////////////////////////////////////////////////////////
	cw_share_files_admin_browser($cw_share_files_html,$cw_share_files_action);
}

////////////////////////////////////////////////////////////////////////////
//	Print out to browser (wp)
////////////////////////////////////////////////////////////////////////////
function cw_share_files_admin_browser($cw_share_files_html,$cw_share_files_action) {
$cw_plugin_name='cleverwise-share-files';
print <<<EOM
<style type="text/css">
#cws-wrap {margin: 20px 20px 20px 0px;}
#cws-wrap a {text-decoration: none; color: #3991bb;}
#cws-wrap a:hover {text-decoration: underline; color: #ce570f;}
#cws-nav {width: 400px; padding: 0px; margin-top: 10px; background-color: #deeaef; -moz-border-radius: 5px; border-radius: 5px;}
#cws-resources {width: 400px; padding: 0px; margin: 40px 0px 20px 0px; background-color: #c6d6ad; -moz-border-radius: 5px; border-radius: 5px; font-size: 12px; color: #000000;}
#cws-resources a {text-decoration: none; color: #28394d;}
#cws-resources a:hover {text-decoration: none; background-color: #28394d; color: #ffffff;}
#cws-inner {padding: 5px;}
</style>
<div id="cws-wrap" name="cws-wrap">
<h2 style="padding: 0px; margin: 0px;">Cleverwise Share Files Management</h2>
<div style="margin-top: 7px; width: 90%; font-size: 10px; line-height: 1;">This system does NOT alter actual download files thus no uploading, altering, or deleting of files occurs.  It manages the download links in an organized fashion.  In addition when you update an actual file on your site you should edit the file record to allow this system to update the MD5 value, file size, and file modified date information.</div>
<div id="cws-nav" name="cws-nav"><div id="cws-inner" name="cws-inner"><a href="?page=cw-share-files">Main Panel</a> | <a href="?page=cw-share-files&cw_action=files">File Management</a> | <a href="?page=cw-share-files&cw_action=cats">Category Management</a></div></div>
<p style="font-size: 13px; font-weight: bold;">Current: <span style="color: #ab5c23;">$cw_share_files_action</span></p>
<p>$cw_share_files_html</p>
<div id="cws-resources" name="cws-resources"><div id="cws-inner" name="cws-inner">Resources (open in new windows):<br>
<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7VJ774KB9L9Z4" target="_blank">Donate - Thank You!</a> | <a href="http://wordpress.org/support/plugin/$cw_plugin_name" target="_blank">Get Support</a> | <a href="http://wordpress.org/support/view/plugin-reviews/$cw_plugin_name" target="_blank">Review Plugin</a> | <a href="http://www.cyberws.com/cleverwise-plugins/plugin-suggestion/" target="_blank">Suggest Plugin</a><br>
<a href="http://www.cyberws.com/cleverwise-plugins" target="_blank">Cleverwise Plugins</a> | <a href="http://www.cyberws.com/professional-technical-consulting/" target="_blank">Wordpress +PHP,Server Consulting</a></div></div>
</div>
EOM;
}

////////////////////////////////////////////////////////////////////////////
//	Activate
////////////////////////////////////////////////////////////////////////////
function cw_share_files_activate() {
	Global $wpdb,$fs_wp_option_version_txt,$fs_wp_option_version_num,$cw_share_files_cats_tbl,$cw_share_files_dls_tbl;
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');

	$fs_wp_option_db_version=get_option($fs_wp_option_version_txt);

//	Create category table
	$table_name=$cw_share_files_cats_tbl;
$sql .=<<<EOM
CREATE TABLE IF NOT EXISTS $table_name (
`cat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`cat_pid` int(10) unsigned NOT NULL DEFAULT '0',
`cat_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
`cat_desc` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
`cat_rank` int(1) unsigned NOT NULL DEFAULT '2',
PRIMARY KEY (`cat_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
EOM;
	dbDelta($sql);
 
//	Create download table
	$table_name=$cw_share_files_dls_tbl;
$sql .=<<<EOM
CREATE TABLE IF NOT EXISTS `$table_name` (
`dl_id` int(15) unsigned NOT NULL AUTO_INCREMENT,
`dl_cat_id` int(10) unsigned NOT NULL,
`dl_section_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
`dl_status` char(1) COLLATE utf8_unicode_ci NOT NULL,
`dl_last_update` int(15) unsigned NOT NULL DEFAULT '0',
`dl_title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
`dl_url` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
`dl_desc` text COLLATE utf8_unicode_ci NOT NULL,
`dl_auth_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
`dl_auth_url` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
`dl_auth_email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
`dl_loc` int(1) unsigned NOT NULL DEFAULT '0',
`dl_file_size` int(15) unsigned NOT NULL DEFAULT '0',
`dl_md5` char(32) COLLATE utf8_unicode_ci NOT NULL,
`dl_cnt` int(15) unsigned NOT NULL DEFAULT '0',
`dl_misc` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
PRIMARY KEY (`dl_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
EOM;
	dbDelta($sql);

//	Insert version number
	if (!$fs_wp_option_db_version) {
		add_option($fs_wp_option_version_txt,$fs_wp_option_version_num);
	}
}

