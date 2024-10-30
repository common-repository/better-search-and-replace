<?php
/*
Plugin Name: Search & Replace URLs - OneClick
Plugin URI: https://www.oneclickitsolution.com/contact-us/
Description: This plugin <strong> updates all urls in your website </strong> by replacing old urls with latest urls. To get started: 1) Click the "Activate" link to the left of this description, and 2) Go to your <a href="tools.php?page=ocsr-url-update-urls.php">OCSR URLs</a> page to use it.
Author: oneclickitsolution.com
Author URI: http://www.oneclickitsolution.com/
Author Email: contact@itoneclick.com
Version: 1.0.0
License: GPLv2 or later
Text Domain: ocsr-urls
*/
/*  Copyright 2022  One click IT consultancy Pvt. Ltd.  ( email : contact@itoneclick.com )

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
if (!function_exists('add_action')) {
    exit;
}
function add_my_css_and_my_js_files_ocsr_oneclick()
{
    // Register the style like this for a plugin:
    wp_register_style('custom-style', plugins_url('/css/ocsr-style.css', __FILE__), array(), '20120208', 'all');
    wp_enqueue_style('custom-style');
}
add_action('admin_init', "add_my_css_and_my_js_files_ocsr_oneclick");
function ocsr_add_custom_menu_page()
{
    add_menu_page('OCSR Urls', 'OCSR Urls', 'manage_options', 'tools.php?page=ocsr-url-update-urls.php', '', plugins_url('/images/icon.png', __FILE__), 6);
}
add_action('admin_menu', 'ocsr_add_custom_menu_page');
function ocsr_URL_add_management_page()
{
    add_management_page("OCSR  URLs", "OCSR URLs", "manage_options", basename(__FILE__), "ocsr_urls_management_page");
}
function ocsr_URL_add_management_script()
{
    wp_enqueue_script('my_custom_script', plugin_dir_url(__FILE__) . 'js/ocsr-url-update-urls.js');
}
add_action('admin_menu', 'ocsr_URL_add_management_script');
function ocsr_urls_management_page()
{
    if (!function_exists('ocsr_update_urls')) {
        function ocsr_update_urls($options, $oldurl, $newurl)
        {
            global $wpdb;
            $results = array();
            $queries = array('content' => array("UPDATE $wpdb->posts SET post_content = replace(post_content, %s, %s)", __('Content Items (Posts, Pages, Custom Post Types, Revisions)', 'ocsr-urls')), 'options' => array("UPDATE $wpdb->options SET option_value = replace(option_value, %s, %s)", __('Options', 'ocsr-urls')), 'excerpts' => array("UPDATE $wpdb->posts SET post_excerpt = replace(post_excerpt, %s, %s)", __('Excerpts', 'ocsr-urls')), 'attachments' => array("UPDATE $wpdb->posts SET guid = replace(guid, %s, %s) WHERE post_type = 'attachment'", __('Attachments', 'ocsr-urls')), 'links' => array("UPDATE $wpdb->links SET link_url = replace(link_url, %s, %s)", __('Links', 'ocsr-urls')), 'custom' => array("UPDATE $wpdb->postmeta SET meta_value = replace(meta_value, %s, %s)", __('Custom Fields', 'ocsr-urls')), 'guids' => array("UPDATE $wpdb->posts SET guid = replace(guid, %s, %s)", __('GUIDs', 'ocsr-urls')));
            foreach ($options as $option) {
                if ($option == 'custom') {
                    $n = 0;
                    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta");
                    $page_size = 100000;
                    $pages = ceil($row_count / $page_size);
                    for ($page = 0; $page < $pages; $page++) {
                        $current_row = 0;
                        $start = $page * $page_size;
                        $end = $start + $page_size;
                        $pmquery = "SELECT * FROM $wpdb->postmeta WHERE meta_value <> ''";
                        $items = $wpdb->get_results($pmquery);
                        foreach ($items as $item) {
                            $value = $item->meta_value;
                            if (trim($value) == '') {
                                continue;
                            }
                            $edited = ocsr_unserialize_replace($oldurl, $newurl, $value);
                            if ($edited != $value) {
                                $fix = $wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d", $edited, $item->meta_id));
                                if ($fix) {
                                    $n++;
                                }
                            }
                        }
                    }
                    $results[$option] = array($n, $queries[$option][1]);
                } elseif ($option == 'options') {
                    $n = 0;
                    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->options");
                    $page_size = 10000;
                    $pages = ceil($row_count / $page_size);
                    for ($page = 0; $page < $pages; $page++) {
                        $current_row = 0;
                        $start = $page * $page_size;
                        $end = $start + $page_size;
                        $pmquery = "SELECT * FROM $wpdb->options WHERE option_value <> ''";
                        $items = $wpdb->get_results($pmquery);
                        foreach ($items as $item) {
                            $value = $item->option_value;
                            if (trim($value) == '') {
                                continue;
                            }
                            $fix = $wpdb->query($wpdb->prepare("UPDATE $wpdb->options SET option_value = %s WHERE option_value Like %s AND  option_name NOT LIKE %s AND  option_name NOT LIKE %s", $newurl, '%' . $oldurl . '%', '%siteurl%', '%home%'));
                            if ($fix) {
                                $n++;
                            }
                        }
                    }
                    $results[$option] = array($n, $queries[$option][1]);
                } else {
                    $result = $wpdb->query($wpdb->prepare($queries[$option][0], $oldurl, $newurl));
                    $results[$option] = array($result, $queries[$option][1]);
                }
            }
            return $results;
        }
    }
    if (!function_exists('ocsr_unserialize_replace')) {
        function ocsr_unserialize_replace($from = '', $to = '', $data = '', $serialised = false)
        {
            try {
                if (false !== is_serialized($data)) {
                    $unserialized = unserialize($data);
                    $data = ocsr_unserialize_replace($from, $to, $unserialized, true);
                } elseif (is_array($data)) {
                    $_tmp = array();
                    foreach ($data as $key => $value) {
                        $_tmp[$key] = ocsr_unserialize_replace($from, $to, $value, false);
                    }
                    $data = $_tmp;
                    unset($_tmp);
                } else {
                    if (is_string($data)) {
                        $data = str_replace($from, $to, $data);
                    }
                }
                if ($serialised) {
                    return serialize($data);
                }
            } catch (Exception $error) {
            }
            return $data;
        }
    }
    if (isset($_POST['ocsr_settings_submit']) && !check_admin_referer('ocsr_submit', 'ocsr_nonce')) {
        if (isset($_POST['ocsr_oldurl']) && isset($_POST['ocsr_newurl'])) {
            if (function_exists('esc_attr')) {
                $ocsr_oldurl = trim(esc_url_raw($_POST['ocsr_oldurl']));
                $ocsr_newurl = trim(esc_url_raw($_POST['ocsr_newurl']));
            } else {
                $ocsr_oldurl = trim(esc_url_raw($_POST['ocsr_oldurl']));
                $ocsr_newurl = trim(esc_url_raw($_POST['ocsr_newurl']));
            }
        }
        echo esc_html_e('<div id="message" class="error fade"><p><strong>' . __('ERROR', 'ocsr-urls') . ' - ' . __('Please try again.', 'ocsr-urls') . '</strong></p></div>');
    } elseif (isset($_POST['ocsr_settings_submit']) && !isset($_POST['ocsr_update_links'])) {
        if (isset($_POST['ocsr_oldurl']) && isset($_POST['ocsr_newurl'])) {
            if (function_exists('esc_attr')) {
                $ocsr_oldurl = trim(esc_url_raw($_POST['ocsr_oldurl']));
                $ocsr_newurl = trim(esc_url_raw($_POST['ocsr_newurl']));
            } else {
                $ocsr_oldurl = trim(esc_url_raw($_POST['ocsr_oldurl']));
                $ocsr_newurl = trim(esc_url_raw($_POST['ocsr_newurl']));
            }
        }
        echo esc_html_e('<div id="message" class="error fade"><p><strong>' . __('ERROR', 'ocsr-urls') . ' - ' . __('Your URLs have not been updated.', 'ocsr-urls') . '</p></strong><p>' . __('Please select at least one checkbox ( see below of the page ).', 'ocsr-urls') . '</p></div>');
    } elseif (isset($_POST['ocsr_settings_submit'])) {
        $ocsr_update_links = array_map('sanitize_text_field',$_POST['ocsr_update_links']);
        if (isset($_POST['ocsr_oldurl']) && isset($_POST['ocsr_newurl'])) {
            if (function_exists('esc_attr')) {
                $ocsr_oldurl = trim(esc_url_raw($_POST['ocsr_oldurl']));
                $ocsr_newurl = trim(esc_url_raw($_POST['ocsr_newurl']));
            } else {
                $ocsr_oldurl = trim(esc_url_raw($_POST['ocsr_oldurl']));
                $ocsr_newurl = trim(esc_url_raw($_POST['ocsr_newurl']));
            }
        }
        if (($ocsr_oldurl && $ocsr_oldurl != 'http://www.oldurldemo.com' && trim($ocsr_oldurl) != '') && ($ocsr_newurl && $ocsr_newurl != 'http://www.newurldemo.com' && trim($ocsr_newurl) != '')) {
            $results = ocsr_update_urls($ocsr_update_links, $ocsr_oldurl, $ocsr_newurl);
            $empty = true;
            $emptystring = '<strong>' . __('Why do the results show 0 URLs updated?', 'ocsr-urls') . '</strong><br/>' . __('This happens if a URL is incorrect OR if it is not found in the content. Check your URLs and try again.', 'ocsr-urls') . '<br/><br/><strong>' . __('Want us to do it for you?', 'ocsr-urls') . '</strong><br/>' . __('Contact us at', 'ocsr-urls') . ' <a href="mailto:contact@itoneclick.com?subject=Move%20My%20WP%20Site">contact@itoneclick.com</a>. ' . __('We will backup your website and move it for $50 OR simply update your URLs for free.', 'ocsr-urls');
            $resultstring = '';
            foreach ($results as $result) {
                $empty = ($result[0] != 0 || $empty == false) ? false : true;
                $resultstring .= '<br/><strong>' . $result[0] . '</strong> ' . $result[1];
            }
            if ($empty) : ?>        
            <div id="message" class="error fade">
                    <table>
                        <tr>
                            <td>
                                <p><strong>
                                        <?php _e('ERROR: Something may have gone wrong.', 'ocsr-urls'); ?>
                                    </strong><br />
                                    <?php _e('Your URLs have not been updated.', 'ocsr-urls'); ?>
                                </p>
                            <?php
            else : ?>
                                <div id="message" class="updated fade">
                                    <table>
                                        <tr>
                                            <td>
                                                <p><strong>
                                                        <?php _e('Success! Your URLs have been updated.', 'ocsr-urls'); ?>
                                                    </strong></p>
                                            <?php
            endif; ?>
                                            <p><u>
                                                    <?php  wp_kses_post('Results'); ?>
                                                </u><?php echo wp_kses_post($resultstring); ?></p>

                                            <?php echo esc_html($empty) ? '<p>' . wp_kses_post($emptystring) . '</p>' : ''; ?>

                                            </td>
                                            <td width="60"></td>
                                            <td align="center">
                                                <?php if (!$empty) : ?>
                                                    <p>
                                                        <?php //You can now uninstall this plugin.<br/> ?>
                                                        <?php printf(__('If you found our plugin useful, %s please consider for any kind of help', 'ocsr-urls'), '<br/>'); ?>.</p>
                                                    <p><img src="<?php echo plugins_url('/images/logo.png', __FILE__) ?>" border="0" alt="Oneclick IT solutions -<?php _e('Best IT solutions providers', 'ocsr-urls'); ?>"></p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                        <?php
        } else {
            echo esc_html_e('<div id="message" class="error fade"><p><strong>' . __('ERROR', 'ocsr-urls') . ' - ' . __('Your URLs have not been updated.', 'ocsr-urls') . '</p></strong><p>' . __('Please enter values for both the old url and the new url.', 'ocsr-urls') . '</p></div>');
        }
    } ?>
                        <div class="wrap ocsr_top_area">
                            <h2>OCSR URLs ( Better search and replace by OneClick )</h2>
                            <form method="post" id="variations_form" action="tools.php?page=<?php echo basename(__FILE__); ?>">
                                <?php wp_nonce_field('ocsr_submit', 'ocsr_nonce'); ?>
                                <p><?php printf(__("After moving a website, %s lets you fix old URLs in content, excerpts, links, and custom fields.", 'ocsr-urls'), '<strong>OCSR URLs</strong>'); ?></p>

                                <p><strong class="red_color_notice">
                                        <?php _e('WE RECOMMEND THAT YOU MUST TAKE BACKUP OF YOUR WEBSITE/DATABASE INITIALLY.', 'ocsr-urls'); ?>
                                    </strong><br />
                                    <?php _e('If you are ticking up the options checkbox, then you have to chnage those URL Manually from database table ( options )', 'ocsr-urls'); ?>
                                </p>

                                <div style="display: flex;justify-content: space-between;">
                                    <div class="card" style="padding: 30px;box-shadow: 0 6px 20px 3px rgb(0 0 0 / 16%);border-radius: 7px;border: none;margin-bottom: 25px;width: 49%;max-width: initial;background: #f4f9ff;">
                                        <h3 style="margin-bottom:5px;color: #212121;font-size: 18px;font-weight: 600;">
                                            Step 1:
                                            Please add both URL in below mentioned fields. </h3>
                                        <table class="form-table">
                                            <tr valign="middle">
                                                <th scope="row" width="140" style="width:140px"><strong>
                                                        <?php _e('Old URL', 'ocsr-urls'); ?>
                                                    </strong><br />
                                                    <span class="description">
                                                        <?php _e('Old Site Address', 'ocsr-urls'); ?>
                                                    </span>
                                                </th>
                                                <td><input name="ocsr_oldurl" type="text" id="ocsr_oldurl" value="<?php echo (isset($ocsr_oldurl) && trim($ocsr_oldurl) != '') ? esc_html($ocsr_oldurl) : ''; ?>" style="width:300px;font-size:20px;" onfocus="if(this.value=='www.demooldurl.com') this.value='';" onblur="if(this.value=='') this.value='www.demooldurl.com';" />
                                                    <span class="oldurl_validate">Please Enter Old Url</span>
                                                </td>
                                            </tr>
                                            <tr valign="middle">
                                                <th scope="row" width="140" style="width:140px"><strong>
                                                        <?php _e('New URL', 'ocsr-urls'); ?>
                                                    </strong><br />
                                                    <span class="description">
                                                        <?php _e('New Site Address', 'ocsr-urls'); ?>
                                                    </span>
                                                </th>
                                                <td><input name="ocsr_newurl" type="text" id="ocsr_newurl" value="<?php echo (isset($ocsr_newurl) && trim($ocsr_newurl) != '') ? esc_html($ocsr_oldurl) : ''; ?>" style="width:300px;font-size:20px;" onfocus="if(this.value=='www.demonewurl.com') this.value='';" onblur="if(this.value=='') this.value='www.demonewurl.com';" />
                                                    <span class="newurl_validate">Please Enter New Url</span>
                                                </td>
                                            </tr>
                                        </table>
                                        <br />
                                    </div>


                                    <div class="card" style="padding: 30px;box-shadow: 0 6px 20px 3px rgb(0 0 0 / 16%);border-radius: 7px;border: none;margin-bottom: 25px;width: 49%;max-width: initial;background: #f4f9ff;">

                                        <h3 style="margin-bottom:5px;color: #212121;font-size: 18px;font-weight: 600;">
                                            Step 2:
                                            Where you would like to make changes? </h3>
                                        <table class="form-table">
                                            <tr>
                                                <td>
                                                    <p style="line-height:20px;">
                                                        <input name="ocsr_update_links[]" type="checkbox" id="ocsr_update_true" value="content" checked="checked" />
                                                        <label for="ocsr_update_true"><strong>
                                                                <?php _e('All the URLs related to any page content', 'ocsr-urls'); ?>
                                                            </strong> (
                                                            <?php _e('posts, pages, custom post types, revisions', 'ocsr-urls'); ?>
                                                            )</label>
                                                        <br />
                                                        <input name="ocsr_update_links[]" type="checkbox" id="ocsr_update_true0" value="options" />
                                                        <label for="ocsr_update_true0"><strong>
                                                                <?php _e('All the URLs related to options table', 'ocsr-urls'); ?>
                                                            </strong> (
                                                            <?php _e('Options related table data', 'ocsr-urls'); ?>
                                                            )</label>
                                                        <br />
                                                        <input name="ocsr_update_links[]" type="checkbox" id="ocsr_update_true1" value="excerpts" />
                                                        <label for="ocsr_update_true1"><strong>
                                                                <?php _e('All the URLs those exist in the excerpts', 'ocsr-urls'); ?>
                                                            </strong></label>
                                                        <br />
                                                        <input name="ocsr_update_links[]" type="checkbox" id="ocsr_update_true2" value="links" />
                                                        <label for="ocsr_update_true2"><strong>
                                                                <?php _e('All the URL those are related to the Links', 'ocsr-urls'); ?>
                                                            </strong></label>
                                                        <br />
                                                        <input name="ocsr_update_links[]" type="checkbox" id="ocsr_update_true3" value="attachments" />
                                                        <label for="ocsr_update_true3"><strong>
                                                                <?php _e('All the URLs those are in attachments', 'ocsr-urls'); ?>
                                                            </strong> (
                                                            <?php _e('Media,docs etc', 'ocsr-urls'); ?>
                                                            )</label>
                                                        <br />
                                                        <input name="ocsr_update_links[]" type="checkbox" id="ocsr_update_true4" value="custom" />
                                                        <label for="ocsr_update_true4"><strong>
                                                                <?php _e('Custom fields & metafields related URLs', 'ocsr-urls'); ?>
                                                            </strong></label>
                                                        <br />
                                                        <input name="ocsr_update_links[]" type="checkbox" id="ocsr_update_true5" value="guids" />
                                                        <label for="ocsr_update_true5"><strong>
                                                                <?php _e('ALL GUIDs', 'ocsr-urls'); ?>
                                                            </strong> <span class="description" style="color:#f00;">
                                                                <?php //_e('GUIDs for posts should only be changed on development sites.', 'ocsr-urls'); ?>
                                                            </span> <a href="http://codex.wordpress.org/Changing_The_Site_URL#Important_GUID_Note" target="_blank">
                                                                <?php _e('Learn More about GUID.', 'ocsr-urls'); ?>
                                                            </a></label>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="text-align: right;margin: 0;">
                                            <input class="button-primary ocsr_btn" name="ocsr_settings_submit" value="<?php _e('Search & Relace Now', 'ocsr-urls'); ?>" type="submit" style="background: #050ebb !important;padding: 5px 20px !important;width: auto;height: auto;font-weight: 500;font-size: 14px !important;border-radius: 6px;" />
                                        </p>

                                    </div>

                                </div>

                            </form>

                            <p>&nbsp;<br />
                                <strong>
                                    <?php _e('Need help?', 'ocsr-urls'); ?>
                                </strong> <?php printf(__("Get support at the %s plugin page%s.", 'ocsr-urls'), '<a href="http://www.oneclickitsolution.com/contact-us//" target="_blank">OCSR  URLs', '</a>'); ?>
                                <?php if (!isset($empty)) : ?>
                                    <br />
                                    <strong>
                                        <?php _e('Want us to do it for you?', 'ocsr-urls'); ?>
                                    </strong>
                                    <?php _e('Contact us at', 'ocsr-urls'); ?>
                                    <a href="mailto:contact@itoneclick.com?subject=Move%20My%20WP%20Site">contact@itoneclick.com</a>.
                                    <?php _e('We will backup your website and move it for $50.', 'ocsr-urls'); ?>
                                <?php endif; ?>
                            </p>

                            <div class="clear"></div>
                        </div>
                    <?php
}
add_action('admin_menu', 'ocsr_URL_add_management_page');
?>
