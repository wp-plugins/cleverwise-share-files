<?php
/**
* Plugin Name: Cleverwise Share Files
* Description: Advanced file download system that allows multiple sections, categories, and download pages.  Also included is advanced access control and anti-bot technology.
* Version: 1.3
* Author: Jeremy O'Connell
* Author URI: http://www.cyberws.com/cleverwise-plugins/
* License: GPL2 .:. http://opensource.org/licenses/GPL-2.0
*/

////////////////////////////////////////////////////////////////////////////
//	Load Cleverwise Framework Library
////////////////////////////////////////////////////////////////////////////
include_once('cwfa.php');
$cwfa_fs=new cwfa_fs;

////////////////////////////////////////////////////////////////////////////
//	Wordpress database option
////////////////////////////////////////////////////////////////////////////
Global $wpdb,$fs_wp_option_version_txt,$fs_wp_option,$fs_wp_option_version_num;

$fs_wp_option_version_num='1.3';
$fs_wp_option='share_files';
$fs_wp_option_version_txt=$fs_wp_option.'_version';

////////////////////////////////////////////////////////////////////////////
//	Get db prefix and set correct table names
////////////////////////////////////////////////////////////////////////////
Global $cw_share_files_cats_tbl,$cw_share_files_dls_tbl;

$wp_db_prefix=$wpdb->prefix;
$cw_share_files_cats_tbl=$wp_db_prefix.'share_files_cats';
$cw_share_files_dls_tbl=$wp_db_prefix.'share_files_dls';

////////////////////////////////////////////////////////////////////////////
//	If admin panel is showing and user can manage options load menu option
////////////////////////////////////////////////////////////////////////////
if (is_admin()) {
	//	Hook admin code
	include_once("sfa.php");

	//	Activation code
	register_activation_hook( __FILE__, 'cw_share_files_activate');

	//	Check installed version and if mismatch upgrade
	Global $wpdb;
	$fs_wp_option_db_version=get_option($fs_wp_option_version_txt);
	if ($fs_wp_option_db_version < $fs_wp_option_version_num) {
		update_option($fs_wp_option_version_txt,$fs_wp_option_version_num);
	}
}

////////////////////////////////////////////////////////////////////////////
//	Register shortcut to display visitor side
////////////////////////////////////////////////////////////////////////////
add_shortcode('cw_share_files', 'cw_share_files_vside');

////////////////////////////////////////////////////////////////////////////
//	Check to see what to do
////////////////////////////////////////////////////////////////////////////
$cw_action='sectioncats';
if (isset($_REQUEST['cw_action'])) {
	$cw_action=$_REQUEST['cw_action'];
}

////////////////////////////////////////////////////////////////////////////
//	File Download Code
////////////////////////////////////////////////////////////////////////////
$file_id='0';
if (isset($_REQUEST['file_id'])) {
	$file_id=$cwfa_fs->cwf_san_int($_REQUEST['file_id']);
	if (!$file_id) {
		$file_id='0';
	}
}

if ($cw_action == 'filedl' and $file_id > '0') {
	$dl_id='0';

	$fs_wp_option_array=get_option($fs_wp_option);
	$fs_wp_option_array=unserialize($fs_wp_option_array);
	$settings_url=$fs_wp_option_array['settings_url'];
	$settings_anti_bot_var=$fs_wp_option_array['settings_anti_bot_var'];
	$settings_anti_bot_ans=$fs_wp_option_array['settings_anti_bot_ans'];

	//	Load anti-bot answer and check
	if ($settings_anti_bot_var) {
		if (isset($_REQUEST[$settings_anti_bot_var])) {
			$fs_anti_bot_box=$_REQUEST[$settings_anti_bot_var];
			$fs_anti_bot_box=strtolower($fs_anti_bot_box);
			$fs_anti_bot_box=stripslashes($fs_anti_bot_box);
			$fs_anti_bot_box=preg_replace('/\s/','',$fs_anti_bot_box);
		}
		
		//	If user typed an anti-bot answer check
		if ($fs_anti_bot_box) {
			$settings_anti_bot_var .='v';
			if (isset($_REQUEST[$settings_anti_bot_var])) {
				$fs_anti_bot_box_num=$_REQUEST[$settings_anti_bot_var]-1;

				$settings_anti_bot_ans=explode("\n",$settings_anti_bot_ans);
				$fs_anti_bot_box_ans=$settings_anti_bot_ans[$fs_anti_bot_box_num];
				$fs_anti_bot_box_ans=stripslashes($fs_anti_bot_box_ans);
				$fs_anti_bot_box_ans=strtolower($fs_anti_bot_box_ans);
				$fs_anti_bot_box_ans=preg_replace('/\s/','',$fs_anti_bot_box_ans);
			}

			//	If user typed matches answer load file id
			if ($fs_anti_bot_box == $fs_anti_bot_box_ans and $fs_anti_bot_box_ans and $fs_anti_bot_box) {
				if ($file_id > '0') {
					$dl_id=$file_id;
				}
			}
		}
	}

	//	Get file download information
	if ($dl_id > '0') {
		//	Load download url from wp db
		$myrows=$wpdb->get_results("SELECT dl_url,dl_cnt FROM $cw_share_files_dls_tbl where dl_id='$dl_id' and dl_status='l'");
		if ($myrows) {
			foreach ($myrows as $myrow) {
				$dl_url=$myrow->dl_url;
				$dl_cnt=$myrow->dl_cnt;
			}

			//	Update download count
			$dl_cnt++;
			$data=array();
			$data['dl_cnt']=$dl_cnt;

			$where=array();
			$where['dl_id']=$dl_id;
			$wpdb->update($cw_share_files_dls_tbl,$data,$where);

			//	Build download url
			$dl_url=$settings_url.$dl_url;

			//	Send download url to browser
			header("Location: $dl_url");
			die();
		}
	}
}

////////////////////////////////////////////////////////////////////////////
//	Visitor Display
////////////////////////////////////////////////////////////////////////////
function cw_share_files_vside() {
Global $wpdb,$fs_wp_option,$cw_share_files_cats_tbl,$cw_share_files_dls_tbl,$cw_action,$cwfa_fs;

	////////////////////////////////////////////////////////////////////////////
	//	Load options for plugin
	////////////////////////////////////////////////////////////////////////////
	$fs_wp_option_array=get_option($fs_wp_option);
	$fs_wp_option_array=unserialize($fs_wp_option_array);
	$settings_url=$fs_wp_option_array['settings_url'];
	$settings_sections=$fs_wp_option_array['settings_sections'];

	$settings_sections=explode("\n",$settings_sections);

	$fs_wp_option_section_cats=$fs_wp_option.'_section_cats';
	$fs_wp_option_section_cats=get_option($fs_wp_option_section_cats);
	$fs_wp_option_section_cats=unserialize($fs_wp_option_section_cats);

	$section_list=array();
	foreach ($settings_sections as $settings_section) {
		list($settings_section_id,$settings_section_name)=explode('|',$settings_section);
		$section_list[$settings_section_id]=$settings_section_name;
	}

	//	Load Section ID and Title
	$ssid='0';
	if (isset($_REQUEST['ssid'])) {
		$ssid=$cwfa_fs->cwf_san_int($_REQUEST['ssid']);
		if (!$ssid) {
			$ssid='0';
		}

		//	Get title
		$cw_share_files_title=$section_list[$ssid];
		if ($cw_share_files_title) {
			$cw_share_files_title .=' Downloads';
		} else {
			if ($cw_action !='fileview') {
				$ssid='0';
				$cw_action='main';
			}
		}
	} else {
		$ssid='0';
	}

	//	Set messaging variables
	$cw_fs_no_fnd_msg_status='n';
	$cw_fs_no_fnd_msg='This is quite embarrassing. We are unable to locate the requested information.';

	////////////////////////////////////////////////////////////////////////////
	//	Download File
	////////////////////////////////////////////////////////////////////////////
	if ($cw_action == 'filedl') {
		$dl_id='0';
		if (isset($_REQUEST['file_id'])) {
			$dl_id=$_REQUEST['file_id'];
			if (!$dl_id) {
				$dl_id='0';
			}
		}

		$fs_jbc='0';
		if (isset($_REQUEST['jbc'])) {		
			$fs_jbc=$_REQUEST['jbc'];
			if (!$fs_jbc) {
				$fs_jbc='0';
			}
		}
		$fs_jbc=$fs_jbc+2;

		$cw_share_files_title='Incorrect word/phrase';
		$cw_share_files_html='<p>To start the download process you must enter the correct word/phrase! <a href="?cw_action=fileview&file_id='.$dl_id.'&jbc='.$fs_jbc.'">Try again...</a></p>';

	////////////////////////////////////////////////////////////////////////////
	//	Files In Category, Top Ten, Search
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'catview' || $cw_action == 'topdls' || $cw_action == 'srchfls') {
		$fs_get_records='y';
		$search_results='';
		$pgprevnxt='';
		$pgnavlist='';
		$sbox='';

		//	Get category name
		if ($cw_action == 'catview') {
			$scat='0';
			if (isset($_REQUEST['scat'])) {
				$scat=$cwfa_fs->cwf_san_int($_REQUEST['scat']);
				if ($scat > '0') {
					$myrows=$wpdb->get_results("SELECT cat_name FROM $cw_share_files_cats_tbl where cat_id='$scat'");
					if ($myrows) {
						foreach ($myrows as $myrow) {
							$cat_name=stripslashes($myrow->cat_name);
						}
					}
				} else {
					$fs_get_records='n';
				}
			} else {
				$fs_get_records='n';
			}
		}

		//	Get records
		if ($fs_get_records == 'y') {
			//	Get download number
			$dl_num='0';
			$settings_ppg=$fs_wp_option_array['settings_ppg'];

			if ($cw_action == 'catview') {
				$fs_viewing_txt=$cat_name;
				$wheresql="dl_cat_id='$scat' and ";
				$fs_wherelink='scat='.$scat;
			}

			if ($cw_action == 'topdls') {
				$fs_viewing_txt='Top Downloads';
				$wheresql='';
				$fs_wherelink='';
			}

			if ($cw_action == 'srchfls') {
				if (isset($_REQUEST['sbox'])) {
					$sbox=$_REQUEST['sbox'];
				}
				if (!$sbox) {
					$sbox='%';
				}
				$sbox=stripslashes($sbox);
				$fs_viewing_txt='<b>'.$sbox.'</b> results';

				$sbox=urlencode($sbox);
				$fs_wherelink='&sbox='.$sbox;
				$sbox=urldecode($sbox);

				$sbox='%'.$sbox.'%';
				$sbox=addslashes($sbox);
				$wheresql="dl_title like '$sbox' and dl_desc like '$sbox' and ";
			}

			$wheresql .="dl_section_id like '%\{$ssid\}%' and dl_status='l'";
			$wheresql=stripslashes($wheresql);

			$fs_wherelink .='&ssid='.$ssid;

			$myrows=$wpdb->get_results("SELECT count(dl_id) as dl_num FROM $cw_share_files_dls_tbl where $wheresql");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$search_cnt=stripslashes($myrow->dl_num);
				}
			}
			$myrows='';

			//	Get record information
			if ($search_cnt > '0') {
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

				$snum=$spg-1;
				$snum=$snum*$settings_ppg;

				//	Set order by
				if ($cw_action == 'topdls') {
					$tpgs='1';
					$snum='0';
					$settings_ppg='10';
					$order_by_field='dl_cnt desc';
				} else {
					$order_by_field='dl_title';
				}

				$myrows=$wpdb->get_results("SELECT dl_id,dl_title,dl_desc FROM $cw_share_files_dls_tbl where $wheresql order by $order_by_field limit $snum,$settings_ppg");
			}

			//	If results format them
			$dl_list='';
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$dl_id=$myrow->dl_id;
					$dl_title=stripslashes($myrow->dl_title);
					$dl_desc=stripslashes($myrow->dl_desc);

					$snum++;
					if ($dl_desc) {
						$dl_desc=strip_tags($dl_desc);
						$dl_desc_len=strlen($dl_desc);
						if ($dl_desc_len > '300') {
							$dl_desc=substr($dl_desc,'0','300').'...';
						}
						$dl_desc=preg_replace('/\n/',' ',$dl_desc);
						$dl_desc=trim($dl_desc);
						if ($dl_desc) {
							$dl_desc='<div style="margin-left: 15px;">'.$dl_desc.' <a href="?cw_action=fileview&file_id='.$dl_id.'">View More Details</a></div>';
						}
					}
					$dl_list .='<div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px #000000 dotted;">'.$snum.') <a href="?cw_action=fileview&file_id='.$dl_id.'">'.$dl_title.'</a>'.$dl_desc.'</div>';
				}
			}

			//	If top downloads set correct max number
			if ($cw_action == 'topdls') {
				$search_cnt=$snum;
			}

			//	Build stats
			$cat_back_link='<a href="?cw_action=sectioncats&ssid='.$ssid.'">Back To Category List</a>';
			$cw_share_files_html="<p>$cat_back_link<br>Viewing: $fs_viewing_txt<br>Files: $search_cnt</p>$dl_list";

			//	Build Page List
			if ($search_cnt > '0') {
				$cw_page_txt='Page: '.$spg.' of '.$tpgs;

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
						$pgnavlist .='<a href="?cw_action='.$cw_action.'&'.$fs_wherelink.'&spg='.$tpgsloop.'">'.$tpgsloop.'</a>';
					}
				}
				if ($pgnavlist) {
					if ($spg != '1') {
						$spgpx=$spg-1;
						$pgprevnxt='<a href="?cw_action='.$cw_action.'&'.$fs_wherelink.'&spg='.$spgpx.'">Previous Page</a>';
					}
					if ($spg != $tpgs) {
						$spgpx=$spg+1;
						if ($pgprevnxt) {
						$pgprevnxt .=' | ';
						}
						$pgprevnxt .='<a href="?cw_action='.$cw_action.'&'.$fs_wherelink.'&spg='.$spgpx.'">Next Page</a>';
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
				} else {
				$pgnavlist='&nbsp;';
				}

				$cw_share_files_html .='<p>'.$pgnavlist.'</p>';
			}

		} else {
			$cw_fs_no_fnd_msg_status='y';
		}

	////////////////////////////////////////////////////////////////////////////
	//	Section Category List
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'sectioncats') {
		$section_cat_list_ids='0';
		if (isset($fs_wp_option_section_cats[$ssid])) {
			$section_cat_list_ids=$fs_wp_option_section_cats[$ssid];
		}

		$cat_cnt='0';
		$dl_cnt='0';
		if ($section_cat_list_ids) {
			$section_cat_list_ids=explode('|',$section_cat_list_ids);
			foreach ($section_cat_list_ids as $section_cat_list_id) {
				$section_cat_list[$section_cat_list_id]='';
			}

			$fscategorylist=array();
			$myrows=$wpdb->get_results("SELECT cat_id,cat_name,cat_desc FROM $cw_share_files_cats_tbl order by cat_name");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$cat_id=$cwfa_fs->cwf_san_int($myrow->cat_id);
					$cat_name=stripslashes($myrow->cat_name);
					$cat_desc=stripslashes($myrow->cat_desc);

					if (in_array($cat_id,$section_cat_list_ids)) {
						$cat_cnt++;
						$cat_list='<div style="margin-bottom: 10px;"><a href="?cw_action=catview&scat='.$cat_id.'&ssid='.$ssid.'">'.$cat_name.'</a></div>';
						if ($cat_desc) {
							$cat_list .='<div style="margin: -10px 0px 10px 0px;">'.$cat_desc.'</div>';
						}

						$section_cat_list[$cat_id]=$cat_list;
					}
				}

				$wheresql="dl_section_id like '%\{$ssid\}%' and dl_status='l'";
				$wheresql=stripslashes($wheresql);
				$myrows=$wpdb->get_results("SELECT count(dl_id) as dl_cnt FROM $cw_share_files_dls_tbl where $wheresql");
				if ($myrows) {
					foreach ($myrows as $myrow) {
						$dl_cnt=$cwfa_fs->cwf_san_int($myrow->dl_cnt);
					}
				}
			} else {	//		close myrows
				$cw_fs_no_fnd_msg_status='y';
			}
		} else {	//	close section cat list ids
			$cw_fs_no_fnd_msg_status='y';
		}

		if ($cat_cnt > '0') {
			$fs_vs_sform='';
			$section_cat_list=implode("\n",$section_cat_list);
$fs_vs_sform .=<<<EOM
<div style="margin-bottom: 15px; text-align: center;"><form method="post" style="margin: 0px; padding: 0px"><input type="hidden" name="cw_action" value="srchfls"><input type="hidden" name="ssid" value="$ssid">Search: <input type="text" name="sbox"> <input type="submit" value="Go" class="btn"><div style="font-size: 11px;">Tips: <b>%</b> = wildcard .:. Leave blank to return all files.</div></form></div>
EOM;
			$cw_share_files_html='<p>'.$dl_cnt.' Files In '.$cat_cnt.' Categories | <a href="?cw_action=topdls&ssid='.$ssid.'">View Top Downloads</a></p>'.$fs_vs_sform.$section_cat_list;
		}

	////////////////////////////////////////////////////////////////////////////
	//	File Details Page
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'fileview') {
		$dl_id='0';
		if (isset($_REQUEST['file_id'])) {
			$dl_id=$cwfa_fs->cwf_san_int($_REQUEST['file_id']);
			if (!$dl_id) {
				$dl_id='0';
			}
		} else {
			$dl_id='0';
		}

		//	Javascript history count
		$fs_jbc='1';
		if (isset($_REQUEST['jbc'])) {
			$fs_jbc=$cwfa_fs->cwf_san_int($_REQUEST['jbc']);
			if (!$fs_jbc) {
				$fs_jbc='1';
			}
		}

		//	Lookup record based on misc id
		if (isset($_REQUEST['misc_id']) and isset($_REQUEST['ssid'])) {
			$misc_id=addslashes(trim($_REQUEST['misc_id']));
			$ssid=$cwfa_fs->cwf_san_int($_REQUEST['ssid']);
	
			if ($misc_id and $ssid > '0') {
				$ssid='%{'.$ssid.'}%';

				$myrows=$wpdb->get_results("SELECT dl_id FROM $cw_share_files_dls_tbl where dl_section_id like '$ssid' and dl_status='l' and dl_misc='$misc_id'");
				if ($myrows) {
					foreach ($myrows as $myrow) {
						$dl_id=$cwfa_fs->cwf_san_int($myrow->dl_id);
					}
				}
			}
		}

		if ($dl_id > '0') {
			$dl_author='';
			$myrows=$wpdb->get_results("SELECT dl_last_update,dl_title,dl_desc,dl_auth_name,dl_auth_url,dl_auth_email,dl_file_size,dl_md5 FROM $cw_share_files_dls_tbl where dl_id='$dl_id' and dl_status='l'");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$dl_last_update=$cwfa_fs->cwf_san_int($myrow->dl_last_update);
					$dl_title=stripslashes($myrow->dl_title);
					$dl_desc=stripslashes($myrow->dl_desc);
					$dl_auth_name=stripslashes($myrow->dl_auth_name);
					$dl_auth_url=$myrow->dl_auth_url;
					$dl_auth_email=$myrow->dl_auth_email;
					$dl_file_size=$myrow->dl_file_size;
					$dl_md5=$myrow->dl_md5;
				}

				$dl_desc=preg_replace('/\n/','<BR>',$dl_desc);

				if ($dl_auth_name or $dl_auth_url or $dl_auth_email) {
					if ($dl_auth_url) {
						if (!$dl_auth_url) {
							$dl_auth_name='Visit author site/page';
						}
						$dl_author='<a href="'.$dl_auth_url.'" target="_blank">'.$dl_auth_name.'</a>';
					} elseif ($dl_auth_name) {
						$dl_author=$dl_auth_name;
					} else {

					}
					if ($dl_auth_email) {
						if ($dl_author) {
							$dl_author .=' @ ';
						}
						$dl_auth_email=preg_replace('/@/',' TYPEAT ',$dl_auth_email);
						$dl_auth_email=preg_replace('/\./',' TYPEDOT ',$dl_auth_email);
						$dl_author .=$dl_auth_email;
					}
					$dl_author='<p>Author Details: '.$dl_author.'</p>';
				}

				$dl_last_update=$cwfa_fs->cwf_dt_fmt($dl_last_update);
				$dl_file_size=$cwfa_fs->cwf_human_filesize($dl_file_size);

				$settings_anti_bot_var=$fs_wp_option_array['settings_anti_bot_var'];
				$settings_anti_bot_varv=$settings_anti_bot_var.'v';
				$settings_anti_bot_ans=$fs_wp_option_array['settings_anti_bot_ans'];

				$settings_anti_bot_ans=explode("\n",$settings_anti_bot_ans);
				$settings_anti_bot_ans_cnt=count($settings_anti_bot_ans);
				$settings_anti_bot_ans_cnt=mt_rand(1,$settings_anti_bot_ans_cnt)-1;
				$settings_anti_bot_ans_txt=trim($settings_anti_bot_ans[$settings_anti_bot_ans_cnt]);
				$settings_anti_bot_ans_txt=stripslashes($settings_anti_bot_ans_txt);
				$settings_anti_bot_ans_cnt++;

				$settings_anti_bot_ans_txt_1=$cwfa_fs->cwf_gen_randstr(8);
				$settings_anti_bot_ans_txt_2=$cwfa_fs->cwf_gen_randstr(13);
				$settings_anti_bot_ans_txt_3=$cwfa_fs->cwf_gen_randstr(11);

				$cw_share_files_title=$dl_title;
				if (strlen($dl_title) > '100') {
					$dl_title=substr($dl_title,0,100).'...';
				}
$cw_share_files_html='';
$cw_share_files_html .=<<<EOM
<p><a href="javascript: history.go(-$fs_jbc);">Return To Previous Page</a><br>Last Updated: $dl_last_update</p>
<p>$dl_desc</p>$dl_author
<p>File Size: $dl_file_size<br>File MD5: $dl_md5</p>
<div style="margin: 10px; border: 1px solid #000000; padding: 5px; text-align: center;"><form method="post" style="margin: 0px; padding: 0px;">
<input type="hidden" name="cw_action" value="filedl"><input type="hidden" name="file_id" value="$dl_id"><input type="hidden" name="$settings_anti_bot_varv" value="$settings_anti_bot_ans_cnt">
<input type="hidden" name="jbc" value="$fs_jbc">Please enter the underlined <span style="text-decoration: underline;display:none">$settings_anti_bot_ans_txt_1</span><span style="text-decoration: underline;display:none">$settings_anti_bot_ans_txt_2</span><span style="text-decoration: underline;">$settings_anti_bot_ans_txt</span><span style="text-decoration: underline;display:none">$settings_anti_bot_ans_txt_3</span> word/phrase in the box below.<br>Tips: Not case sensitive. Copy/paste for faster completion.<br><br><input type="text" name="$settings_anti_bot_var"><br><br><input type="submit" value="Download $dl_title" class="button"></form></div>
EOM;

				Global $current_user;
				if (is_user_logged_in()) {
					if (wp_get_current_user('manage_options')) {
						$cw_wp_admin_url=admin_url();
						$cw_wp_admin_url .='?page=cw-share-files&cw_action=fileview&dl_id='.$dl_id;
						$cw_share_files_html .='<div style="display: block; margin: 20px 0px 20px 0px;"><a href="'.$cw_wp_admin_url.'" target="_blank">Admin Panel: Load file record</a></div>';
					}
				}				

			} else {
				$cw_fs_no_fnd_msg_status='y';
			}
		} else {
			$cw_fs_no_fnd_msg_status='y';
		}
		
	////////////////////////////////////////////////////////////////////////////
	//	Main
	////////////////////////////////////////////////////////////////////////////
	} else {
		$cw_fs_no_fnd_msg_status='y';
	}

	////////////////////////////////////////////////////////////////////////////
	//	Load not found message
	////////////////////////////////////////////////////////////////////////////
	if ($cw_fs_no_fnd_msg_status == 'y') {
		$cw_share_files_html=$cw_fs_no_fnd_msg;
	}

	////////////////////////////////////////////////////////////////////////////
	//	Call out to browser
	////////////////////////////////////////////////////////////////////////////
	cw_share_files_visitor_browser($cw_share_files_html,$cw_share_files_title);
}

////////////////////////////////////////////////////////////////////////////
//	Print out to browser (wp)
////////////////////////////////////////////////////////////////////////////
function cw_share_files_visitor_browser($cw_share_files_html,$cw_share_files_title) {

	if (!$cw_share_files_title) {
		$cw_share_files_title='Oops!';
	}
print <<<EOM
<h4>$cw_share_files_title</h4>
<div style="height: 10px; font-size: 10px;">&nbsp;</div>
$cw_share_files_html
EOM;
}
