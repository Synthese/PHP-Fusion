<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: forum_ranks.php
| Author: Nick Jones (Digitanium)
| Co-Author: Robert Gaudyn (Wooya)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
require_once "../maincore.php";
if (!checkrights("FR") || !defined("iAUTH") || !isset($_GET['aid']) || $_GET['aid'] != iAUTH) {
    redirect("../index.php");
}

require_once THEMES."templates/admin_header.php";
include LOCALE.LOCALESET."admin/forum_ranks.php";

if (isset($_GET['status']) && !isset($message)) {
    if ($_GET['status'] == "sn") {
        $message = $locale['410'];
    } else if ($_GET['status'] == "su") {
        $message = $locale['411'];
    } else if ($_GET['status'] == "del") {
        $message = $locale['412'];
    } else if ($_GET['status'] == "se") {
        $message = $locale['413'];
    }
    if ($message) {
        echo "<div id='close-message'><div class='admin-message alert alert-info m-t-10'>".$message."</div></div>\n";
    }
}

if ($settings['forum_ranks']) {
    if (isset($_POST['save_rank'])) {
        $rank_title = form_sanitizer($_POST['rank_title'], '', 'rank_title');
        $rank_image = stripinput($_POST['rank_image']);
        $rank_language = stripinput($_POST['rank_language']);
        $rank_posts = isnum($_POST['rank_posts']) ? $_POST['rank_posts'] : 0;
        $rank_type = isnum($_POST['rank_type']) ? $_POST['rank_type'] : 0;
        $rank_apply_normal = isset($_POST['rank_apply_normal']) && isnum($_POST['rank_apply_normal']) ? $_POST['rank_apply_normal'] : 101;
        $rank_apply_special = isset($_POST['rank_apply_special']) && isnum($_POST['rank_apply_special']) ? $_POST['rank_apply_special'] : 1;
        $rank_apply = $rank_type == 2 ? $rank_apply_special : $rank_apply_normal;
        if (!defined('FUSION_NULL')) {
            if (isset($_GET['rank_id']) && isnum($_GET['rank_id'])) {
                $data = dbarray(dbquery("SELECT rank_apply FROM ".DB_FORUM_RANKS." WHERE rank_id='".$_GET['rank_id']."'"));
                if (($rank_apply > 101 && $rank_apply != $data['rank_apply']) && (dbcount("(rank_id)", DB_FORUM_RANKS, "".(multilang_table("FR") ? "rank_language='".LANGUAGE."' AND" : "")." rank_id!='".$_GET['rank_id']."' AND rank_apply='".$rank_apply."'"))) {
                    redirect(FUSION_SELF.$aidlink."&status=se");
                } else {
                    $result = dbquery("UPDATE ".DB_FORUM_RANKS." SET rank_title='".$rank_title."', rank_image='".$rank_image."', rank_posts='".$rank_posts."', rank_type='".$rank_type."', rank_apply='".$rank_apply."', rank_language='".$rank_language."' WHERE rank_id='".$_GET['rank_id']."'");
                    redirect(FUSION_SELF.$aidlink."&status=su");
                }
            } else {
                if ($rank_apply > 101 && dbcount("(rank_id)", DB_FORUM_RANKS, "".(multilang_table("FR") ? "rank_language='".LANGUAGE."' AND" : "")." rank_apply='".$rank_apply."'")) {
                    redirect(FUSION_SELF.$aidlink."&status=se");
                } else {
                    $result = dbquery("INSERT INTO ".DB_FORUM_RANKS." (rank_title, rank_image, rank_posts, rank_type, rank_apply, rank_language) VALUES ('$rank_title', '$rank_image', '$rank_posts', '$rank_type', '$rank_apply', '$rank_language')");
                    redirect(FUSION_SELF.$aidlink."&status=sn");
                }
            }
        }
    } else if (isset($_GET['delete']) && isnum($_GET['delete'])) {
        $result = dbquery("DELETE FROM ".DB_FORUM_RANKS." WHERE rank_id='".$_GET['delete']."'");
        redirect(FUSION_SELF.$aidlink."&status=del");
    }
    if (isset($_GET['rank_id']) && isnum($_GET['rank_id'])) {
        $result = dbquery("SELECT rank_id, rank_title, rank_image, rank_posts, rank_type, rank_apply, rank_language FROM ".DB_FORUM_RANKS." WHERE rank_id='".$_GET['rank_id']."'");
        if (dbrows($result)) {
            $data = dbarray($result);
            $rank_title = $data['rank_title'];
            $rank_image = $data['rank_image'];
            $rank_posts = $data['rank_posts'];
            $rank_type = $data['rank_type'];
            $rank_apply = $data['rank_apply'];
            $rank_language = $data['rank_language'];
            $form_action = FUSION_SELF.$aidlink."&amp;rank_id=".$_GET['rank_id'];
            opentable($locale['401']);
        } else {
            redirect(FUSION_SELF.$aidlink);
        }
    } else {
        $rank_title = "";
        $rank_image = "";
        $rank_posts = "0";
        $rank_type = "2";
        $rank_apply = "";
        $rank_language = LANGUAGE;
        $form_action = FUSION_SELF.$aidlink;
        opentable($locale['400']);
    }
    echo openform('rank_form', 'rank_form', 'post', $form_action, ['downtime' => 0]);
    echo "<table cellpadding='0' cellspacing='0' class='table table-responsive center'>\n<tbody>\n<tr>\n";
    echo "<td class='tbl'><label for='rank_title'>".$locale['420']."</label><span class='required'>*</span></td>\n";
    echo "<td class='tbl'>\n";
    echo form_text('', 'rank_title', 'rank_title', $rank_title, ['required' => 1, 'error_text' => $locale['414']]);
    echo "</td>\n</tr>\n<tr>\n";
    echo "<td class='tbl'><label for='rank_image'>".$locale['421']."</label></td>\n";
    echo "<td class='tbl'>\n";
    $image_files = makefilelist(IMAGES."ranks", ".|..|index.php|.svn|.DS_Store", TRUE);
    foreach ($image_files as $value) {
        $opts[$value] = $value;
    }
    echo form_select('', 'rank_image', 'rank_image', $opts, $rank_image, ['placeholder' => $locale['choose']]);
    echo "</td>\n</tr>\n";
    if (multilang_table("FR")) {
        echo "<tr><td class='tbl'><label for='rank_language'>".$locale['global_ML100']."</label></td>\n";
        echo "<td class='tbl'>\n";
        echo form_select('', 'rank_language', 'rank_language', $language_opts, $rank_language, ['placeholder' => $locale['choose']]);
        echo "</td>\n</tr>\n";
    } else {
        echo form_hidden('', 'rank_language', 'rank_language', $rank_language);
    }
    echo "<tr>\n";
    echo "<td class='tbl'><strong>".$locale['429']."</strong></td>\n";
    echo "<td class='tbl'>\n";
    echo "<label><input type='radio' name='rank_type' value='2'".($rank_type == 2 ? " checked='checked'" : "")." /> ".$locale['429a']."</label>\n";
    echo "<label><input type='radio' name='rank_type' value='1'".($rank_type == 1 ? " checked='checked'" : "")." /> ".$locale['429b']."</label>\n";
    echo "<label><input type='radio' name='rank_type' value='0'".($rank_type == 0 ? " checked='checked'" : "")." /> ".$locale['429c']."</label>\n";
    echo "</td>\n";
    echo "</tr>\n<tr>\n";
    echo "<td class='tbl'><label for='rank_posts'>".$locale['422']."</label></td>\n";
    echo "<td class='tbl'>\n";
    echo form_text('', 'rank_posts', 'rank_posts', $rank_posts, "".($rank_type != 0 ? ['disabled' => 1] : '')."");
    echo "</tr>\n<tr>\n";
    echo "<td class='tbl'><label for='rank_apply_normal'>".$locale['423']."</label></td>\n<td class='tbl'>\n";
    $array = ['101' => $locale['424'], '104' => $locale['425'], '102' => $locale['426'], '103' => $locale['427']];
    echo "<span id='select_normal' ".($rank_type == 2 ? "class='display-none'" : "")." >";
    echo form_select('', 'rank_apply_normal', 'rank_apply_normal', $array, $rank_apply, ['placeholder' => $locale['choose']]);
    echo "</span>\n";
    // Special Select
    $groups_arr = getusergroups();
    $groups_except = [0, 101, 102, 103];
    $group_opts = [];
    foreach ($groups_arr as $group) {
        if (!in_array($group[0], $groups_except)) {
            $group_opts[$group[0]] = $group[1];
        }
    }
    echo "<span id='select_special'".($rank_type != 2 ? " class='display-none'" : "").">";
    echo form_select('', 'rank_apply_special', 'rank_apply_special', $group_opts, $rank_apply, ['placeholder' => $locale['choose']]);
    echo "</span>\n";
    echo "</td>\n</tr>\n<tr>\n";
    echo "<td align='center' colspan='2' class='tbl'>\n";
    echo form_button($locale['428'], 'save_rank', 'save_rank', $locale['428'], ['class' => 'btn-primary']);
    echo "</td>\n</tr>\n</table>\n</form>\n";
    closetable();
    opentable($locale['402']);
    $result = dbquery("SELECT rank_id, rank_title, rank_image, rank_posts, rank_type, rank_apply FROM ".DB_FORUM_RANKS." ".(multilang_table("FR") ? "WHERE rank_language='".LANGUAGE."'" : "")." ORDER BY rank_type DESC, rank_apply DESC, rank_posts");
    if (dbrows($result)) {
        echo "<table cellpadding='0' cellspacing='1' class='table table-responsive tbl-border center'>\n<thead>\n<tr>\n";
        echo "<th class='tbl2'><strong>".$locale['430']."</strong></th>\n";
        echo "<th width='1%' class='tbl2' style='white-space:nowrap'><strong>".$locale['431']."</strong></th>\n";
        echo "<th width='1%' class='tbl2' style='white-space:nowrap'><strong>".$locale['432']."</strong></th>\n";
        echo "<th width='1%' class='tbl2' style='white-space:nowrap'><strong>".$locale['438']."</strong></th>\n";
        echo "<th align='center' width='1%' class='tbl2' style='white-space:nowrap'><strong>".$locale['434']."</strong></th>\n";
        echo "</tr>\n";
        echo "</thead>\n<tbody>\n";
        $i = 0;
        while ($data = dbarray($result)) {
            $row_color = ($i % 2 == 0 ? "tbl1" : "tbl2");
            echo "<tr>\n";
            echo "<td class='".$row_color."'>".$data['rank_title']."</td>\n";
            echo "<td width='1%' class='".$row_color."' style='white-space:nowrap'>".($data['rank_apply'] == 104 ? $locale['425'] : getgroupname($data['rank_apply']))."</td>\n";
            echo "<td width='1%' class='".$row_color."' style='white-space:nowrap'><img src='".IMAGES."ranks/".$data['rank_image']."' alt='' style='border:0;' /></td>\n";
            echo "<td width='1%' class='".$row_color."' style='white-space:nowrap'>";
            if ($data['rank_type'] == 0) {
                echo $data['rank_posts'];
            } else if ($data['rank_type'] == 1) {
                echo $locale['429b'];
            } else {
                echo $locale['429a'];
            }
            echo "</td>\n<td width='1%' class='".$row_color."' style='white-space:nowrap'>";
            echo "<a href='".FUSION_SELF.$aidlink."&amp;rank_id=".$data['rank_id']."'>".$locale['435']."</a> -\n";
            echo "<a href='".FUSION_SELF.$aidlink."&amp;delete=".$data['rank_id']."'>".$locale['436']."</a></td>\n</tr>\n";
            $i++;
        }
        echo "</tbody>\n</table>";
    } else {
        echo "<div style='text-align:center'>".$locale['437']."</div>\n";
    }
    closetable();
} else {
    opentable($locale['403']);
    echo "<div style='text-align:center'>\n".sprintf($locale['450'], "<a href='settings_forum.php".$aidlink."'>".$locale['451']."</a>")."</div>\n";
    closetable();
}

echo "<script language='JavaScript' type='text/javascript'>
jQuery(function(){
    jQuery('input:radio[name=rank_type]').change(function() {
        var val = jQuery('input:radio[name=rank_type]:checked').val(),
            special = jQuery('#select_special'),
            normal = jQuery('#select_normal'),
            posts = jQuery('#rank_posts');
        if (val == 2) {
            special.show();
            normal.hide();
            posts.attr('readonly', 'readonly');
        } else {
            if (val == 1) {
                posts.attr('readonly', 'readonly');
            } else {
                posts.removeAttr('readonly');
            }
            special.hide();
            normal.show();
        }
    });
});
</script>";

require_once THEMES."templates/footer.php";
