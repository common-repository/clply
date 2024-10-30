<?php
/*
Plugin Name: curate.us
Plugin URI: http://curate.us/plugins/wordpress
Description: PLEASE USE http://wordpress.org/extend/plugins/curateus/ INSTEAD. This plugin integrates curate.us into your blog. Allow your readers to easily create clips and quotes from your posts.
Version: 1.1
Author: Kate McKinley <kate@freerangecontent.com>
Author URI: http://curate.us
License: Apache License, Version 2.0
*/
?>
<?php
/*  Copyright 2010 Free Range Content, LLC
 *  Kate McKinley<kate@freerangecontent.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */
?>
<?php
if (!class_exists("CLP_WordpressPlugin")) {
    class CLP_WordpressPlugin {
        protected $adminOptionsName = 'clp_admin_options'; 
        protected $clpOptions;
        protected $clpOptionsDefault = array(
                'initialized' => 'false',
                'accept_link' => 'false',
                'clp_server' => 'curate.us',
                'appkey' => '',
                'show_in_title' => 'true',
                'show_in_content' => 'true',
                'button_url' => '',
            );
        protected $buttons = array(
            /* Solid */
            'http://curate.us/buttons/8.png' => 'background-color:white;',
            'http://curate.us/buttons/9.png' => 'background-color:white;',
            'http://curate.us/buttons/1.png' => 'background-color:white;',
            'http://curate.us/buttons/2.png' => 'background-color:white;',
            'http://curate.us/buttons/3.png' => 'background-color:white;',
            'http://curate.us/buttons/0.png' => 'background-color:white;',
            /* Dark text, transparent bg */
            'http://curate.us/buttons/4.png' => 'background-color:white;',
            'http://curate.us/buttons/6.png' => 'background-color:white;',
            /* Light text, transparent bg */
            'http://curate.us/buttons/5.png' => 'background-color:grey;',
            'http://curate.us/buttons/7.png' => 'background-color:grey;',
        );
        function CLP_WordpressPlugin() {
        }

        function resetAdminOptions() {
            update_option($this->adminOptionsName, $this->clpOptionsDefault);
        }

        function getAdminOptions() {
            $clpAdminOptions = $this->clpOptionsDefault;
            $clpOptions = get_option($this->adminOptionsName);
            if(!empty($clpOptions)) {
                foreach ($clpOptions as $key => $option) {
                    if ( $key == 'clp_server' || $key == 'button_url' ) {
                        $option = preg_replace('/clp\.ly/', 'curate.us', $option);
                    }
                    $clpAdminOptions[$key] = $option;
                }
            }
            update_option($this->adminOptionsName, $clpAdminOptions);
            return $clpAdminOptions;
        }

        function check_initialized() {
            $this->clpOptions = $this->getAdminOptions();
            return ($this->clpOptions['accept_link'] == 'true' && $this->clpOptions['initialized'] == 'true');
        }

        function init() {
            $this->getAdminOptions();
        }

        function clpHeader() {
            $this->check_initialized();
            $clplyJs = 'http://'.$this->clpOptions['clp_server'].'/clipthis.js';
            echo "<!--\n";
            foreach ($this->clpOptions as $key => $option) {
                echo "   ".$key." => ".$option."\n";
            }
            echo $clplyJs."\n";
            echo "-->\n";
            wp_enqueue_script('clplyJs', $clplyJs);
        }

        function clipButton($atts=null, $content=null, $code="") {
            if ($this->check_initialized()) {
                return "<a class='ClipThisButton' href='http://".apply_filters('esc_html', $this->clpOptions['clp_server'])."/simple/clipthis/".apply_filters('esc_html', $this->clpOptions['appkey'])."?url=".urlencode(get_permalink())."'><img title='Clip this story' alt='Clip this story' src='".apply_filters('esc_html', $this->clpOptions['button_url'])."'/></a>";
            } else {
                return '';
            }
        }

        function addToContent($content='') {
            if($this->check_initialized() && $this->clpOptions['show_in_content'] == 'true') {
                $content .= "<p>".$this->clipButton()."</p>";
            }
            return $content;
        }

        function addToTitle($title='') {
            if($this->check_initialized() && in_the_loop() && $this->clpOptions['show_in_title'] == 'true') {
                $title .= "&nbsp;" . $this->clipButton();
            }
            return $title;
        }

        // utility function to update a variable from the post
        function update_var_from_post(&$opts, $name, $novalue='', $value=null) {
            if(isset($_POST[$name])) {
                if($value == null) {
                    $opts[$name] = $_POST[$name];
                } else {
                    $opts[$name] = $value;
                }
            } else {
                $opts[$name] = $novalue;
            }
        }

        // prints the admin page for our plugin
        function printAdminPage() {
            $this->check_initialized();
            if (isset($_POST['update_clplyPluginSettings'])) {
                check_admin_referer('clply_admin_form_' . $clp_plugin);
                $this->update_var_from_post($this->clpOptions, 'appkey', '', preg_replace('/ /', '', $_POST['appkey']));
                $this->update_var_from_post($this->clpOptions, 'clp_server', 'curate.us', preg_replace('/[^[:alnum]-]/', '', $_POST['clp_server']));
                $this->update_var_from_post($this->clpOptions, 'show_in_title', 'false', 'true');
                $this->update_var_from_post($this->clpOptions, 'show_in_content', 'false', 'true');
                $this->update_var_from_post($this->clpOptions, 'accept_link', 'false', 'true');
                $prevButton = $this->clpOptions['button_url'];
                $this->update_var_from_post($this->clpOptions, 'button_url');
                $haveButton = false;
                foreach ($this->buttons as $button => $style) {
                    if($button == $this->clpOptions['button_url']) {
                        $haveButton = true;
                        break;
                    }
                }
                if (!$haveButton) {
                    $this->clpOptions['button_url'] = $prevButton;
                }
                if($this->clpOptions['appkey'] != '') {
                    $this->clpOptions['initialized'] = 'true';
                } else {
                    $this->clpOptions['initialized'] = 'false';
                }
                update_option($this->adminOptionsName, $this->clpOptions);
?>
<div class="updated"><p><strong><?php _e("Settings Updated.", "curate.us Plugin"); ?></strong></p></div>
<?php       
                $this->check_initialized();
            } ?>
<div class=wrap>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
                if ( function_exists('wp_nonce_field') )
                    wp_nonce_field('clply_admin_form_' . $clp_plugin);
?>
<input type=hidden name='clp_server' value="<?php _e(apply_filters('format_to_edit', $this->clpOptions['clp_server'])) ?>" />
<h2>curate.us Plugin</h2>
<h3>Allow Links Offsite</h3>
<p>
In order to function, the curate.us plugin needs to load javascript and insert links to <a href="http://<?php _e($this->clpOptions['clp_server']); ?>">curate.us</a>.<br>
<label for=accept_link>Allow?</label>&nbsp;<input type=checkbox name=accept_link value=true <?php if($this->clpOptions['accept_link'] == 'true') _e(' checked'); ?> />
</p>
<h3>API key (get from <a href="http://<?php _e(apply_filters('format_to_edit', $this->clpOptions['clp_server'])); ?>/my-clply#mysites">curate.us</a>)</h3>
<p>
<input type=text size=32 name='appkey' value="<?php _e(apply_filters('format_to_edit',$this->clpOptions['appkey']), 'clplyPlugin'); ?>" />
</p>
<h3>Display &quot;Clip This&quot; Buttons</h3>
<p>
<label for='show_in_title'>on post titles</label>
<input type=checkbox name='show_in_title' value='true' <?php if($this->clpOptions['show_in_title'] == 'true') _e("checked"); ?> />
</p>
<p>
<label for='show_in_content'>on post content</label>
<input type=checkbox name='show_in_content' value='true' <?php if($this->clpOptions['show_in_content'] == 'true') _e("checked"); ?> />
</p>
<h3>Choose graphical button</h3>
<p>
<?php
                foreach ($this->buttons as $button => $style) {
                    $selected = "";
                    if($this->clpOptions['button_url']==$button) {
                        $selected = "checked";
                    }
                    _e("<table><tr><td style=".$style."><input type=radio name='button_url' value='".apply_filters('format_to_edit', $button)."' ".$selected." /><img src='".apply_filters('format_to_edit', $button)."' /></td></tr></table>");
                }
?>
</p>
<div class="Submit">
<input type="submit" name="update_clplyPluginSettings" value="<?php _e('Update Settings', 'curate.us Plugin'); ?>" />
</div>
</div>
<?php
        } // end function printAdminPage()
    }

}

if(class_exists("CLP_WordpressPlugin")) {
    $clp_plugin = new CLP_WordpressPlugin();
}


//Initialize the admin panel
if (!function_exists("clply_adminPanel")) {
    function clply_adminPanel() {
        global $clp_plugin;
        if (!isset($clp_plugin)) {
            return;
        }
        if (function_exists('add_options_page')) {
            add_options_page('curate.us Plugin', 'curate.us Plugin', 9, basename(__FILE__), array(&$clp_plugin, 'printAdminPage'));
        }
    }   
}

// Short code for putting a clip this button anywhere on the page
if (!function_exists("clipButton")) {
    function clipButton($atts=null, $content=null, $code="") {
        global $clp_plugin;
        if (!isset($clp_plugin)) {
            return '';
        }
        return $clp_plugin->clipButton($atts, $content, $code);
    }   
}

// setup actions & filters
if(isset($clp_plugin)) {
    // actions
    add_action('clply/clply.php',  array(&$clp_plugin, 'init'));
    add_action('wp_head', array(&$clp_plugin, 'clpHeader'),1);
    add_action('admin_menu', 'clply_adminPanel');
    
    // filters
    add_filter('the_content' ,array(&$clp_plugin, 'addToContent'));
    add_filter('the_title' ,array(&$clp_plugin, 'addToTitle'));

    // add a shortcode for post authors to control placement
    add_shortcode('clp_button', 'clipButton');
}
?>
