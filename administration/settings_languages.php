<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: settings_languages.php
| Author: Joakim Falk (Domi)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
require_once __DIR__.'/../maincore.php';
if (!checkrights("LANG") || !defined("iAUTH") || !isset($_GET['aid']) || $_GET['aid'] != iAUTH) {
	redirect("../index.php");
}
require_once THEMES."templates/admin_header.php";
include LOCALE.LOCALESET."admin/settings.php";
if (isset($_GET['error']) && isnum($_GET['error']) && !isset($message)) {
	if ($_GET['error'] == 0) {
		$message = $locale['900'];
	} elseif ($_GET['error'] == 1) {
		$message = $locale['901'];
	}
	// Can replace all error=0
	if (isset($message)) {
		echo "<div id='close-message'><div class='admin-message alert alert-info m-t-10'>".$message."</div></div>\n";
	}
}
$locale_files = makefilelist(LOCALE, ".|..", TRUE, "folders");
if (isset($_POST['savesettings'])) {
	$error = 0;
	$localeset = stripinput($_POST['localeset']);
	$old_localeset = stripinput($_POST['old_localeset']);
	$old_enabled_languages = stripinput($_POST['old_enabled_languages']);
	$ml_tables = "";
	$enabled_languages = "";
	if (!defined('FUSION_NULL')) {
		$result = dbquery("UPDATE ".DB_SETTINGS." SET settings_value='$localeset' WHERE settings_name='locale'");
		if (!$result) {
			$error = 1;
		}
		$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_status='0'");
		for ($i = 0; $i < count($_POST['multilang_tables']); $i++) {
			$ml_tables .= stripinput($_POST['multilang_tables'][$i]);
			if ($i != (count($_POST['multilang_tables'])-1)) $ml_tables .= ".";
		}
		$ml_tables = explode('.', $ml_tables);
		for ($i = 0; $i < sizeof($ml_tables); $i++) {
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_status='1' WHERE mlt_rights='".$ml_tables[$i]."'");
		}
		if (!$result) {
			$error = 1;
		}
		for ($i = 0; $i < count($_POST['enabled_languages']); $i++) {
			$enabled_languages .= stripinput($_POST['enabled_languages'][$i]);
			if ($i != (count($_POST['enabled_languages'])-1)) $enabled_languages .= ".";
		}
		$result = dbquery("UPDATE ".DB_SETTINGS." SET settings_value='".$enabled_languages."' WHERE settings_name='enabled_languages'");
		if (!$result) {
			$error = 1;
		}
		unset($settings);
		$settings = array();
		$result = dbquery("SELECT settings_name, settings_value FROM ".DB_SETTINGS);
		while ($data = dbarray($result)) {
			$settings[$data['settings_name']] = $data['settings_value'];
		}
		if (($settings['enabled_languages'] != $_POST['old_enabled_languages']) && !$error) {
			//Give the Administration new locale based on site�s main locale settings
			$enabled_languages = explode('.', $settings['enabled_languages']);
			$old_enabled_languages = explode('.', $old_enabled_languages);
			//Remove language from guest user settings
			for ($i = 0; $i < sizeof($enabled_languages); $i++) {
				$result = dbquery("DELETE FROM ".DB_LANGUAGE_SESSIONS." WHERE user_language !='".$enabled_languages[$i]."'");
			}
			//Sanitize users languages
			for ($i = 0; $i < sizeof($enabled_languages); $i++) {
				$result = dbquery("UPDATE ".DB_USERS." SET user_language = '".$settings['locale']."' WHERE user_language !='".$enabled_languages[$i]."'");
			}
			//Sanitize and update panel languages
			for ($i = 0; $i < sizeof($enabled_languages); $i++) {
				$panel_langs .= $settings['enabled_languages'].($i < (sizeof($settings['enabled_languages'])-1) ? "." : "");
			}
			if (sizeof($enabled_languages) > 1) {
				$result = dbquery("UPDATE ".DB_PANELS." SET panel_languages='".$panel_langs."'");
			} else {
				$result = dbquery("UPDATE ".DB_PANELS." SET panel_languages='".$settings['locale']."'");
			}
			//Sanitize news_cat_languages
			for ($i = 0; $i < sizeof($enabled_languages); $i++) {
				$result = dbquery("DELETE FROM ".DB_NEWS_CATS." WHERE news_cat_language !='".$enabled_languages[$i]."' AND news_cat_language !='".$settings['locale']."'");
			}
			//Sanitize site links_languages
			for ($i = 0; $i < sizeof($enabled_languages); $i++) {
				$result = dbquery("DELETE FROM ".DB_SITE_LINKS." WHERE link_language !='".$enabled_languages[$i]."' AND link_language !='".$settings['locale']."'");
			}
			//Sanitize the email templates languages
			for ($i = 0; $i < sizeof($enabled_languages); $i++) {
				$result = dbquery("DELETE FROM ".DB_EMAIL_TEMPLATES." WHERE template_language !='".$enabled_languages[$i]."' AND template_language !='".$settings['locale']."'");
			}
			//Sanitize forum rank languages
			for ($i = 0; $i < sizeof($enabled_languages); $i++) {
				$result = dbquery("DELETE FROM ".DB_FORUM_RANKS." WHERE rank_language !='".$enabled_languages[$i]."' AND rank_language !='".$settings['locale']."'");
			}
			//update news cats with a new language if we have it
			if (!empty($settings['enabled_languages'])) {
				for ($i = 0; $i < sizeof($enabled_languages); $i++) {
					$language_exist = dbarray(dbquery("SELECT news_cat_language FROM ".DB_NEWS_CATS." WHERE news_cat_language ='".$enabled_languages[$i]."'"));
					if (is_null($language_exist['news_cat_language'])) {
						include LOCALE."".$enabled_languages[$i]."/setup.php";
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['180']."', 'bugs.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['181']."', 'downloads.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['182']."', 'games.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['183']."', 'graphics.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['184']."', 'hardware.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['185']."', 'journal.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['186']."', 'members.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['187']."', 'mods.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['188']."', 'movies.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['189']."', 'network.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['190']."', 'news.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['191']."', 'php-fusion.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['192']."', 'security.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['193']."', 'software.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['194']."', 'themes.gif', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_NEWS_CATS." (news_cat_name, news_cat_image, news_cat_language) VALUES ('".$locale['195']."', 'windows.gif', '".$enabled_languages[$i]."')");
					}
				}
			}
			//update site links with a new language if we have it
			if (!empty($settings['enabled_languages'])) {
				for ($i = 0; $i < sizeof($enabled_languages); $i++) {
					$language_exist = dbarray(dbquery("SELECT link_language FROM ".DB_SITE_LINKS." WHERE link_language ='".$enabled_languages[$i]."'"));
					if (is_null($language_exist['link_language'])) {
						include LOCALE."".$enabled_languages[$i]."/setup.php";
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['130']."', 'index.php', '0', '2', '0', '1', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['131']."', 'articles.php', '0', '2', '0', '2', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['132']."', 'downloads.php', '0', '2', '0', '3', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['133']."', 'faq.php', '0', '1', '0', '4', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['134']."', 'forum/index.php', '0', '2', '0', '5', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['136']."', 'news_cats.php', '0', '2', '0', '7', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['137']."', 'weblinks.php', '0', '2', '0', '6', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['135']."', 'contact.php', '0', '1', '0', '8', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['138']."', 'photogallery.php', '0', '1', '0', '9', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['139']."', 'search.php', '0', '1', '0', '10', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('---', '---', '101', '1', '0', '11', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['140']."', 'submit.php?stype=l', '101', '1', '0', '12', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['141']."', 'submit.php?stype=n', '101', '1', '0', '13', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['142']."', 'submit.php?stype=a', '101', '1', '0', '14', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['143']."', 'submit.php?stype=p', '101', '1', '0', '15', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_SITE_LINKS." (link_name, link_url, link_visibility, link_position, link_window, link_order, link_language) VALUES ('".$locale['144']."', 'submit.php?stype=d', '101', '1', '0', '16', '".$enabled_languages[$i]."')");
					}
				}
			}
			//Update the email template system locales
			if (!empty($settings['enabled_languages'])) {
				for ($i = 0; $i < sizeof($enabled_languages); $i++) {
					$language_exist = dbarray(dbquery("SELECT template_language FROM ".DB_EMAIL_TEMPLATES." WHERE template_language ='".$enabled_languages[$i]."'"));
					if (is_null($language_exist['template_language'])) {
						include LOCALE."".$enabled_languages[$i]."/setup.php";
						$result = dbquery("INSERT INTO ".DB_EMAIL_TEMPLATES." (template_id, template_key, template_format, template_active, template_name, template_subject, template_content, template_sender_name, template_sender_email, template_language) VALUES ('', 'PM', 'html', '0', '".$locale['T101']."', '".$locale['T102']."', '".$locale['T103']."', '".$username."', '".$email."', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_EMAIL_TEMPLATES." (template_id, template_key, template_format, template_active, template_name, template_subject, template_content, template_sender_name, template_sender_email, template_language) VALUES ('', 'POST', 'html', '0', '".$locale['T201']."', '".$locale['T202']."', '".$locale['T203']."', '".$username."', '".$email."', '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_EMAIL_TEMPLATES." (template_id, template_key, template_format, template_active, template_name, template_subject, template_content, template_sender_name, template_sender_email, template_language) VALUES ('', 'CONTACT', 'html', '0', '".$locale['T301']."', '".$locale['T302']."', '".$locale['T303']."', '".$username."', '".$email."', '".$enabled_languages[$i]."')");
					}
				}
			}
			//Update the forum ranks locales
			if (!empty($settings['enabled_languages'])) {
				for ($i = 0; $i < sizeof($enabled_languages); $i++) {
					$language_exist = dbarray(dbquery("SELECT rank_language FROM ".DB_FORUM_RANKS." WHERE rank_language ='".$enabled_languages[$i]."'"));
					if (is_null($language_exist['rank_language'])) {
						include LOCALE."".$enabled_languages[$i]."/setup.php";
						$result = dbquery("INSERT INTO ".DB_FORUM_RANKS." VALUES ('', '".$locale['200']."', 'rank_super_admin.png', 0, '1', 103, '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_FORUM_RANKS." VALUES ('', '".$locale['201']."', 'rank_admin.png', 0, '1', 102, '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_FORUM_RANKS." VALUES ('', '".$locale['202']."', 'rank_mod.png', 0, '1', 104, '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_FORUM_RANKS." VALUES ('', '".$locale['203']."', 'rank0.png', 0, '0', 101, '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_FORUM_RANKS." VALUES ('', '".$locale['204']."', 'rank1.png', 10, '0', 101, '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_FORUM_RANKS." VALUES ('', '".$locale['205']."', 'rank2.png', 50, '0', 101, '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_FORUM_RANKS." VALUES ('', '".$locale['206']."', 'rank3.png', 200, '0', 101, '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_FORUM_RANKS." VALUES ('', '".$locale['207']."', 'rank4.png', 500, '0', 101, '".$enabled_languages[$i]."')");
						$result = dbquery("INSERT INTO ".DB_FORUM_RANKS." VALUES ('', '".$locale['208']."', 'rank5.png', 1000, '0', 101, '".$enabled_languages[$i]."')");
					}
				}
			}
		}
		if (($localeset != $old_localeset) && !$error) {
			//If the system base language changes, replace Admin�s locale
			include LOCALE.$localeset."/admin/main.php";
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['201']."' WHERE admin_link='administrators.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['248']."' WHERE admin_link='admin_reset.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['202']."' WHERE admin_link='article_cats.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['203']."' WHERE admin_link='articles.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['236']."' WHERE admin_link='bbcodes.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['204']."' WHERE admin_link='blacklist.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['245']."' WHERE admin_link='banners.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['206']."' WHERE admin_link='custom_pages.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['249']."' WHERE admin_link='errors.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['207']."' WHERE admin_link='db_backup.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['208']."' WHERE admin_link='download_cats.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['209']."' WHERE admin_link='downloads.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['210']."' WHERE admin_link='faq.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['211']."' WHERE admin_link='forums.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['212']."' WHERE admin_link='images.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['213']."' WHERE admin_link='infusions.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['215']."' WHERE admin_link='members.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['216']."' WHERE admin_link='news.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['235']."' WHERE admin_link='news_cats.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['217']."' WHERE admin_link='panels.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['218']."' WHERE admin_link='photoalbums.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['219']."' WHERE admin_link='phpinfo.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['220']."' WHERE admin_link='polls.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['222']."' WHERE admin_link='site_links.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['223']."' WHERE admin_link='submissions.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['224']."' WHERE admin_link='upgrade.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['225']."' WHERE admin_link='user_groups.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['226']."' WHERE admin_link='weblink_cats.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['227']."' WHERE admin_link='weblinks.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['228']."' WHERE admin_link='settings_main.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['229']."' WHERE admin_link='settings_time.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['230']."' WHERE admin_link='settings_forum.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['231']."' WHERE admin_link='settings_registration.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['232']."' WHERE admin_link='settings_photo.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['233']."' WHERE admin_link='settings_misc.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['234']."' WHERE admin_link='settings_messages.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['237']."' WHERE admin_link='smileys.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['238']."' WHERE admin_link='user_fields.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['239']."' WHERE admin_link='forum_ranks.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['240']."' WHERE admin_link='user_field_cats.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['241']."' WHERE admin_link='settings_news.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['243']."' WHERE admin_link='settings_dl.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['244']."' WHERE admin_link='settings_ipp.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['246']."' WHERE admin_link='settings_security.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['247']."' WHERE admin_link='settings_users.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['266']."' WHERE admin_link='user_log.php'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_ADMIN." SET admin_title='".$locale['267']."' WHERE admin_link='robots.php'");
			if (!$result) {
				$error = 1;
			}
			include LOCALE.$localeset."/setup.php";
			//update default News cats with the set language
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['180']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='bugs.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['181']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='downloads.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['182']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='games.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['183']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='graphics.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['184']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='hardware.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['185']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='journal.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['186']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='members.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['187']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='mods.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['188']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='movies.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['189']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='network.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['190']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='news.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['191']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='php-fusion.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['192']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='security.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['193']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='software.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['194']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='themes.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_name='".$locale['195']."' WHERE news_cat_language='".$old_localeset."' AND news_cat_image='windows.gif'");
			$result = dbquery("UPDATE ".DB_NEWS_CATS." SET news_cat_language='".$localeset."' WHERE news_cat_language='".$old_localeset."'");
			//update default site links with the set language
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['130']."' WHERE link_language='".$old_localeset."' AND link_url='index.php'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['131']."' WHERE link_language='".$old_localeset."' AND link_url='articles.php'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['132']."' WHERE link_language='".$old_localeset."' AND link_url='downloads.php'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['133']."' WHERE link_language='".$old_localeset."' AND link_url='faq.php'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['134']."' WHERE link_language='".$old_localeset."' AND link_url='forum/index.php'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['135']."' WHERE link_language='".$old_localeset."' AND link_url='news_cats.php'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['136']."' WHERE link_language='".$old_localeset."' AND link_url='weblinks.php'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['137']."' WHERE link_language='".$old_localeset."' AND link_url='contact.php'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['138']."' WHERE link_language='".$old_localeset."' AND link_url='photogallery.php'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['139']."' WHERE link_language='".$old_localeset."' AND link_url='search.php'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['140']."' WHERE link_language='".$old_localeset."' AND link_url='submit.php?stype=l'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['141']."' WHERE link_language='".$old_localeset."' AND link_url='submit.php?stype=n'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['142']."' WHERE link_language='".$old_localeset."' AND link_url='submit.php?stype=a'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['143']."' WHERE link_language='".$old_localeset."' AND link_url='submit.php?stype=p'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_name='".$locale['144']."' WHERE link_language='".$old_localeset."' AND link_url='submit.php?stype=d'");
			$result = dbquery("UPDATE ".DB_SITE_LINKS." SET link_language='".$localeset."' WHERE link_language='".$old_localeset."'");
			//update multilanguage tables with a new language if we have it
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_title='".$locale['MLT001']."' WHERE mlt_rights='AR'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_title='".$locale['MLT002']."' WHERE mlt_rights='CP'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_title='".$locale['MLT003']."' WHERE mlt_rights='DL'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_title='".$locale['MLT004']."' WHERE mlt_rights='FQ'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_title='".$locale['MLT005']."' WHERE mlt_rights='FO'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_title='".$locale['MLT006']."' WHERE mlt_rights='NS'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_title='".$locale['MLT007']."' WHERE mlt_rights='PG'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_title='".$locale['MLT008']."' WHERE mlt_rights='PO'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_title='".$locale['MLT009']."' WHERE mlt_rights='SB'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_title='".$locale['MLT010']."' WHERE mlt_rights='WL'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_title='".$locale['MLT011']."' WHERE mlt_rights='SL'");
			if (!$result) {
				$error = 1;
			}
			$result = dbquery("UPDATE ".DB_LANGUAGE_TABLES." SET mlt_title='".$locale['MLT012']."' WHERE mlt_rights='PN'");
			if (!$result) {
				$error = 1;
			}
		}
		redirect(FUSION_SELF.$aidlink."&error=".$error);
	}
}
$settings2 = array();
$result = dbquery("SELECT settings_name, settings_value FROM ".DB_SETTINGS);
while ($data = dbarray($result)) {
	$settings2[$data['settings_name']] = $data['settings_value'];
}
opentable($locale['682ML']);
echo "<form name='settingsform' method='post' action='".FUSION_SELF.$aidlink."'>\n";
echo "<table class='table table-responsive center'>\n<tbody>\n<tr>\n";
echo "<td width='50%' class='tbl'><label for='localeset'>".$locale['417']."<label> <span class='required'>*</span></td>\n";
echo "<td width='50%' class='tbl'><select name='localeset' class='textbox'>\n";
echo makefileopts($locale_files, $settings2['locale'])."\n";
echo "</select></td>\n";
$locale_files = makefilelist(LOCALE, ".|..", true, "folders");
echo "</tr>\n<tr>\n";
echo "<td valign='top' width='50%' class='tbl'><strong>".$locale['684ML']."</strong><br /><span class='small2'>".$locale['685ML']."</span></td>\n";
echo "<td width='50%' class='tbl'>\n";
echo get_available_languages_array($locale_files);
echo "</td>\n</tr>\n";
echo "<td valign='top' width='50%' class='tbl'><strong>".$locale['668ML']."</strong><br /><span class='small2'>".$locale['669ML']."</span></td>\n";
echo "<td width='50%' class='tbl'>\n";
$result = dbquery("SELECT * FROM ".DB_LANGUAGE_TABLES."");
while ($data = dbarray($result)) {
	echo "<input type='checkbox' value='".$data['mlt_rights']."' name='multilang_tables[]'  ".($data['mlt_status'] == '1' ? "checked='checked'" : "")." /> ".$data['mlt_title']." <br />";
}
echo "</td>\n</tr>\n<tr>\n";
echo "<td align='center' colspan='2' class='tbl'><br />";
echo "<input type='hidden' name='old_localeset' value='".$settings2['locale']."' />\n";
echo "<input type='hidden' name='old_enabled_languages' value='".$settings['enabled_languages']."' />\n";
echo "<input type='submit' name='savesettings' value='".$locale['750']."' class='button' /></td>\n";
echo "</td>\n</tr>\n</tbody>\n</table>\n</form>\n";
closetable();
require_once THEMES."templates/footer.php";
?>