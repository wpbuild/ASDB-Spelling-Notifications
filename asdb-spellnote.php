<?php
/*
Plugin Name: ASDB Spelling Notifications (Ctrl+Enter)
Plugin URI: http://wpbuild.ru
Description: The plugin allows site visitors to send reports to the webmaster / website owner about any spelling or grammatical errors which may be found by readers. Visitors should select text with a mouse, press Ctrl+Enter, enter comments and the webmaster will be notified about any such errors.
Version: 1.0.1
Author: Mikhail "kitassa" Tkacheff
Author URI: http://tkacheff.ru
License: MIT License
License URI: http://opensource.org/licenses/MIT
GitHub Plugin URI: https://github.com/wpbuild/ASDB-Spelling-Notifications
Text Domain: asdb-spellnote
Domain Path: /languages/

This plugin has been tested with Wordpress Version 4.5

Copyright 2016  Mikhail "kitassa" Tkacheff (@kitassa)
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE .
*/

if (!defined( 'ABSPATH' )) exit;

if (!function_exists('spellnote_load') && !function_exists('spellnote_load_textdomain')) {

	add_action( 'plugins_loaded',  'spellnote_load_textdomain' );
	add_action( 'plugins_loaded',  'spellnote_load', 20 );

	function spellnote_load_textdomain() {
		load_plugin_textdomain( 'asdb-spellnote', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	function spellnote_load() {

		class ASDB_SpellNote
		{
			private $save_flag = false;
			private $from_email = '';
			private $to_email = '';
			private $form_style = '';
			private $fields = array();

			public function __construct()
			{
				$this->fields = array(
						"form_title" 		=> __( "Spelling or Grammar Error", 'asdb-spellnote' ),
						"form_web"			=> __( "Webpage", 'asdb-spellnote' ),
						"form_description"	=> __( "Describe the error and offer a solution", 'asdb-spellnote' ),
						"form_width"		=> 	"500px",
						"form_send"			=> __( "Send to Author", 'asdb-spellnote' ),
						"form_cancel"		=> __( "Cancel", 'asdb-spellnote' ),
						"form_close"		=> __( "Close Window", 'asdb-spellnote' ),
						"plugin_name"		=> __( "ASDB Spelling Notifications (Ctrl+Enter)", 'asdb-spellnote' ),
						"form_message1" 	=> __( "Please select no more than 400 characters!", 'asdb-spellnote' ),
						"form_message2"		=> __( "Please select the spelling error!", 'asdb-spellnote' ),
						"form_message3"		=> __( "Thank you! Your message has been successfully sent, we highly appreciate your support!", 'asdb-spellnote' ),
						"form_message4"		=> __( "Error! Message not sent, Please try again!", 'asdb-spellnote' ),
						"email_subject"		=> sprintf(__( "Spelling Error on %s", 'asdb-spellnote' ), ucfirst($_SERVER["SERVER_NAME"])),
						"email_web"			=> __( "Webpage", 'asdb-spellnote' ),
						"email_error"			=> __( "Error", 'asdb-spellnote' ),
						"email_message"		=> __( "Message", 'asdb-spellnote' ),
						"email_user"		=> __( "User", 'asdb-spellnote' ),
						"email_ip"			=> __( "IP", 'asdb-spellnote' ),
						"email_ua"			=> __( "User Agent", 'asdb-spellnote' )
				);


				$this->default = $this->fields;

				if ( is_admin() ) {
					if (isset($_GET["page"]) && $_GET["page"] == "spell_note" && strpos($_SERVER["SCRIPT_NAME"], "options-general.php")) {
						if (isset($_POST["from_email"]) && isset($_POST["to_email"])) $this->save_settings();
						//add_action( 'admin_footer_text', array(&$this, 'admin_footer_text'), 25);
					}

						add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
						add_action( 'admin_head', array( &$this, 'html_header' ));

						} else {

					add_action("wp_head", array( &$this, 'html_header' ));
				}

				$this->get_settings();

				add_filter('plugin_action_links', array(&$this, 'plugin_action_links'), 10, 2 );
				add_action('parse_request', array(&$this, 'notification_window'));
				if  ($this->form_place == 'yes') {
					add_filter('the_content', array(&$this, 'spellnote_banner'));
				}
				return true;
			}

			public function spellnote_banner($content) {
    			if( is_single() ) {
					$content.= '<div class="spellnote">';
					$content.= __( 'Found error in text? Select it and press <span>Ctrl</span> + <span>Enter</span>', 'asdb-spellnote' );
					$content.= '</div>';
				}
				return $content;
			}

			public function plugin_action_links($links, $file) {

				static $this_plugin;

				if (false === isset($this_plugin) || true === empty($this_plugin)) {
					$this_plugin = plugin_basename(__FILE__);
				}

				if ($file == $this_plugin) {
					$settings_link = '<a href="'.admin_url('options-general.php?page=spell_note').'">'.__( 'Settings', 'asdb-spellnote' ).'</a>';
					array_unshift($links, $settings_link);
				}

				return $links;
			}

			public function admin_menu() {
				add_options_page(__(' ASDB Spelling Notifications', 'asdb-spellnote'), __('ASDB Spelling Notifications', 'asdb-spellnote'), 'add_users', 'spell_note', array(&$this, 'settings_page'));

				return true;
			}

			public function html_header() {
				echo '<script type="text/javascript" charset="UTF-8">splnote_path="'.trim(site_url(),' /').'";splnote_txt1="'.esc_html($this->fields["form_message1"]).'";splnote_txt2="'.esc_html($this->fields["form_message2"]).'"</script>';
				echo '<script src="'.plugins_url('/js/asdb-spellnote.js', __FILE__).'" type="text/javascript"></script>';
				echo '<link rel="stylesheet" type="text/css" href="'.plugins_url('/spellnote.css', __FILE__).'" media="all" />';
				if ($this->fields["form_width"] != "500px") echo "<style>#splwin #splwindow {width:".esc_html($this->fields["form_width"])."}</style>";

				return true;
			}

			private function save_settings()
			{
				$args = array_keys($this->fields);
				$args[] = "from_email";
				$args[] = "to_email";
				$args[] = "form_style";
				$args[] = "form_place";

				foreach ($args as $k)
					if (isset($_POST[$k]))
					{
						if (isset($_POST["restore_default"])) $v = (isset($this->default[$k])) ? $this->default[$k] : "";
						else $v = $_POST[$k];

						if (is_string($v)) $v = trim(stripslashes($v));
						update_option('asdb-spellnote'.$k, $v);
						$this->saveopt = true;
					}

					return true;
			}

			private function get_settings()
			{
				$args = array_keys($this->fields);
				$args[] = "from_email";
				$args[] = "to_email";
				$args[] = "form_style";
				$args[] = "form_place";

				foreach ($args as $k)
				{
					$v = get_option('asdb-spellnote'.$k);
					if (is_string($v)) $v = trim(stripslashes($v));

					if (isset($this->fields[$k]))
					{
						if (mb_strlen(trim($v)) < 3) $v = $this->default[$k];
						$this->fields[$k] = $v;
					}
					else $this->$k = $v;
				}

				$this->fields["form_width"] = str_replace("px", "", $this->fields["form_width"]);
				if (!is_numeric($this->fields["form_width"]) || $this->fields["form_width"] < 350 || $this->fields["form_width"] > 1000) $this->fields["form_width"] = 500;
				$this->fields["form_width"] .= "px";

				$admin_email = get_option('admin_email');

				if (!$this->from_email || !is_email($this->from_email)) {
					$this->from_email = 'wordpress@'.$_SERVER["SERVER_NAME"];
					if (!is_email($this->from_email)) $this->from_email = $admin_email;
				}

				if (!$this->to_email || !is_email($this->to_email)) $this->to_email = $admin_email;
				if (!in_array($this->form_style, array("img", "txt"))) $this->form_style = "img";
				if (!in_array($this->form_place, array("yes", "no"))) $this->form_place = "yes";

				return true;
			}


			public function settings_page()
			{
				$this->get_settings();





				$assa = "<div style='margin:30px 20px'>";
				$assa .= "<form accept-charset='utf-8' action='".admin_url('options-general.php?page=spell_note')."' method='post'>";

				$assa .= "<h1>".__('Settings: ASDB Spelling Notifications', 'asdb-spellnote')."</h1>";


				if ($this->saveopt)
				$assa .= "<br><div class='updated'><p>".(isset($_POST["restore_default"])?__('Settings has been restored <strong>successfully</strong>', 'asdb-spellnote'):__('Settings has been saved <strong>successfully</strong>', 'asdb-spellnote'))."</p></div><br>";

				$assa .= "<table class='widefat' cellspacing='20' style='padding:10px 25px'>";

				$assa .= "<tr valign='top'>";
				$assa .= "<th colspan='2'>";
				$assa .= __('<b>Ready to use:</b> &#160; Select any text on webpage and press CTRL+ENTER on your keyboard', 'asdb-spellnote') . "<br><br>";
				$assa .= "<a target='_blank' href='http://wpbuld.ru/spelling-notifications/'>".__( 'Plugin Homepage', 'asdb-spellnote' )."</a> &#160;&amp;&#160; <a target='_blank' href='http://wpbuld.ru/spelling-notifications/#screenshot'>".__( 'screenshots', 'asdb-spellnote' )." &#187;</a><br>";
				$assa .= "<a target='_blank' href='https://github.com/wpbuild/ASDB-Spelling-Notifications'>".__( 'Plugin on Github - 100% Free Open Source', 'asdb-spellnote' )." &#187;</a><br><br>";

				$assa .= __('The plugin allows site visitors to send reports to the webmaster / website owner about any spelling or grammatical errors which may be found by readers.', 'asdb-spellnote').'<br>';
				$assa .= __('Visitors should select text with a mouse, press Ctrl+Enter, enter comments and the webmaster will be notified about any such errors.', 'asdb-spellnote').'<br><br>';
				$assa .= "<br><br>";
				$assa .= "</th>";
				$assa .= "</tr>";

				$assa .= "<tr valign='top'>";
				$assa .= "<th scope='row' width='150'><label for='from_email'><b>".__( 'Email From:', 'asdb-spellnote' )."</b></label></th>";
				$assa .= "<td><input type='text' size='50' value='".esc_html($this->from_email)."' name='from_email' id='from_email'>";
				$assa .= "<p class='description'>".__('Please enter email address', 'asdb-spellnote' )."</p>";
				$assa .= "</tr>";

				$assa .= "<tr valign='top'>";
				$assa .= "<th scope='row' width='150'><label for='to_email'><b>".__( 'Email To:', 'asdb-spellnote' )."</b></label></th>";
				$assa .= "<td><input type='text' size='50' value='".esc_html($this->to_email)."' name='to_email' id='to_email'>";
				$assa .= "<p class='description'>".__('Please enter your email address for spelling error notifications', 'asdb-spellnote' )."</p>";
				$assa .= "</tr>";

				$assa .= "<tr valign='top'>";
				$assa .= "<th scope='row' width='150'><label for='form_style'><b>".__( 'Place:', 'asdb-spellnote' )."</b></label></th>";
				$assa .= "<td><input type='radio' name='form_place' value='yes'".$this->check_checked($this->form_place, "yes").">".__('Yes', 'asdb-spellnote' )." &#160; &#160; &#160; <input type='radio' name='form_place' value='no'".$this->check_checked($this->form_place, "no").">".__('No', 'asdb-spellnote' );
				$assa .= "<p class='description'>".__('Automatically place (Ctrl+Enter) after content on single page', 'asdb-spellnote' )."</p>";
				$assa .= "</tr>";

				$assa .= "<tr valign='top'>";
				$assa .= "<th scope='row' width='150'><label for='form_style'><b>".__( 'Style:', 'asdb-spellnote' )."</b></label></th>";
				$assa .= "<td><input type='radio' name='form_style' value='img'".$this->check_checked($this->form_style, "img").">".__('Banner', 'asdb-spellnote' )." &#160; &#160; &#160; <input type='radio' name='form_style' value='txt'".$this->check_checked($this->form_style, "txt").">".__('Text', 'asdb-spellnote' );
				$assa .= "<p class='description'>".__('', 'asdb-spellnote' )."</p>";
				$assa .= "</tr>";

//				if  ($this->form_style == 'banner') {
				$banner_img = '<a href="http://wpbuld.ru/spelling-notifications/" target="_blank"><img title="'.esc_html($this->fields["plugin_name"]).'" alt="'.esc_html($this->fields["plugin_name"]).'" src="'.plugins_url('/images/asdbspellnote.png', __FILE__).'" border="0" width="95" height="95"></a>';
				$assa .= "<tr id='banner_img' valign='top' height='200'>";
				$assa .= "<th scope='row' width='150'><label><b>".__( 'Banner:', 'asdb-spellnote')."</b></label></th>";
				$assa .= "<td><textarea rows='6' class='large-text' style='font-size:12px' readonly='readonly'>".esc_html($banner_img)."</textarea>";
				$assa .= "<p class='description'>".sprintf(__("Copy and paste the banner code to the bottom of your webpages. <a target='_blank' href='%s'>More info</a>", 'asdb-spellnote' ), "https://wordpress.org/support/topic/how-do-i-add-a-banner-code-to-the-bottom-of-my-page")."</p>";
				$assa .= "</td><td> &#160; ".$banner_img."</td>";
				$assa .= "</tr>";
//				} else {
				$banner_text = '<a href="http://wpbuld.ru/spelling-notifications/" target="_blank"><img title="'.esc_html($this->fields["plugin_name"]).'" alt="'.esc_html($this->fields["plugin_name"]).'" src="'.plugins_url('/images/spell-note.png', __FILE__).'" border="0" width="305" height="34"></a>';
				$assa .= "<tr id='banner_txt' valign='top' height='200'>";
				$assa .= "<th scope='row' width='150'><label><b>".__( 'Text:', 'asdb-spellnote')."</b></label></th>";
				$assa .= "<td> &#160; ".$banner_text."</td>";
				$assa .= "</tr>";
//				}

				$assa .= "<tr valign='top'>";
				$assa .= "<th colspan='2'><br><br><h3 class='title'>".__('Text Localization and customization (optional)', 'asdb-spellnote' )."</h3></th>";
				$assa .= "</tr>";

				foreach ($this->fields as $k => $v)
				{
					$assa .= "<tr valign='top'>";
					$assa .= "<th scope='row' width='150'><label for='".$k."'><b>".ucwords(str_replace("_", " ", __( $k, 'asdb-spellnote' )))."</b></label></th>";
					$assa .= "<td><input type='text' class='widefat' value='".esc_html($v)."' name='".$k."' id='".$k."'>";
					$assa .= "<p class='description'>".__('Default:', 'asdb-spellnote' ).' '.$this->default[$k]."</p>";
					$assa .= "</tr>";
				}

				$assa .= "<tr valign='top'>";
				$assa .= "<th colspan='2'><br>";
				$assa .= "<input type='submit' class='button button-primary' name='submit' value='".__('Save Settings', 'asdb-spellnote')."'> &#160; &#160; &#160; ";
				$assa .= "<input type='submit' class='button button-default' name='restore_default' value='".__('Restore Default', 'asdb-spellnote')."'>";
				$assa .= "<br><br></th>";
				$assa .= "</tr>";
				$assa .= "</table>";
				$assa .= "</form>";
				$assa .= "</div>";

				echo $assa;

				return true;
			}


			public function notification_window( &$wp )
			{
				global $wp, $current_user;

				if (in_array(strtolower($this->right($_SERVER["REQUEST_URI"], "/", false)), array("?spell_note.php", "index.php?spell_note.php")))
				{
					ob_clean();

					if(isset($_POST['submit']) && $_POST['submit'])
					{
						$df 	 = get_option( 'date_format' );
						$tf 	 = get_option( 'time_format' );
						$dt		 = date( "{$df} {$tf}", current_time( 'timestamp' ) );
						$title 	 = $this->fields["email_subject"] . ', ' . $dt;
						$url 	 = esc_html(mb_substr(trim($_POST['url']), 0, 2000));
						$spl 	 = mb_substr(trim(stripslashes($_POST['spl'])), 0, 2000);
						$message = esc_html(mb_substr(trim(esc_html(stripslashes($_POST['message']))), 0, 20000));
						$agent	 = esc_html(trim($_SERVER['HTTP_USER_AGENT']));
						$user    = (!$current_user->ID) ? __('Guest', 'asdb-spellnote') : "<a style='color:#007cb9' href='".admin_url("user-edit.php?user_id=".$current_user->ID)."'>user".$current_user->ID."</a>, ".$current_user->user_login.", ".$current_user->user_firstname." ".$current_user->user_lastname.", ".$current_user->user_email;


						$body = '<html>
								<head>
								<title>'.esc_html($this->fields["form_title"]).'</title>
								</head>
								<body style="font-size: 13px; margin: 5px; color: #333333; line-height: 25px; font-family: Verdana, Arial, Helvetica">
								'.$dt.'<br><br>
								<strong>'.esc_html($this->fields["email_web"]).':</strong> &#160;<a style="color:#007cb9" href='.$url.'>'.$url.'</a>
								<br><br><br>
								<strong>'.esc_html($this->fields["email_error"]).':</strong><br>---------<br>'.str_replace("<strong>", "<strong style='color:red'>", $spl).(!mb_strpos($spl, '</strong>')?'</strong>':'').'
								<br><br><br>
								<strong>'.esc_html($this->fields["email_message"]).':</strong><br>---------<br>'.$message.'
								<br><br><br><br>
								<strong>'.esc_html($this->fields["email_user"]).':</strong> '.str_replace(',  ,', ', ', $user).'
								<br>
								<strong>'.esc_html($this->fields["email_ip"]).':</strong> <a style="color:#007cb9" href="http://myip.ms/info/whois'.(filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)?'6':'').'/'.esc_html($_SERVER['REMOTE_ADDR']).'">'.esc_html($_SERVER['REMOTE_ADDR']).'</a>
								<br>
								<strong>'.esc_html($this->fields["email_ua"]).':</strong> '.esc_html($_SERVER['HTTP_USER_AGENT']).'
								</body>
								</html>
							';

						$from = "From: =?utf-8?B?".base64_encode($this->from_email)."?= <".$this->from_email.">\n";
						$from .= "X-Sender: <".$this->from_email.">\n";
						$from .= "Content-Type: text/html; charset=utf-8\n";

						$result = mail($this->to_email, $title, $body, $from);
					}

					$assa = '
					<!DOCTYPE HTML>
					<html>
					<head>
						<meta charset="utf-8">
						<title>'.esc_html($this->fields["plugin_name"]).'</title>
						<link href="'.plugins_url('/spellnote.css', __FILE__).'" rel="stylesheet">
						<script>var p=top;function loaddata(){null!=p&&(document.forms.splwin.url.value=p.splloc);null!=p&&(document.forms.splwin.spl.value=p.spl);if("undefined"==typeof p.spl || "undefined"==typeof p.splloc) {document.getElementById("submit").disabled = true;document.getElementById("cancel").disabled = true;}}function hide(){var a=p.document.getElementById("splwin");a.parentNode.removeChild(a)};window.onkeydown=function(event){if(event.keyCode===27){hide()}};window.onload = window.onresize = function() { var SpellH = document.getElementById("spellnote"); SpellH.style.marginTop = (470 - Math.ceil(SpellH.offsetHeight))/2 + "px";}</script>
					</head>
					<body onload=loaddata()>
						<div id="spellnote" class="container">
							<p><b>'.esc_html($this->fields["form_title"]).'</b></p>
							<div class="close"><a href="javascript:void(0)" onclick="hide()" title="'.esc_html($this->fields["form_close"]).'" class="btn btn-default btn-xs"><span class="icon-remove">X</span></a></div>';

							if(isset($_POST['submit']) && $_POST['submit'])
							{
								$assa .= '<br><br><br>';

								if($result)
								{
									$assa .= '<div class="alert-panel" role="alert"><span class="glyphicon glyphicon-ok"></span>
												&#160;'.esc_html($this->fields["form_message3"]).'
											</div>';
								}
								else
								{
									$assa .= '<br>
											<div class="alert-panel" role="alert"><span class="icon-remove-sign">X</span>
												&#160;'.esc_html($this->fields["form_message4"]).'
											</div>';
								}

								$assa .= '<br><div style="text-align:center; margin-left:-20px"><a href="http://wpbuild.ru/spelling-notifications" target="_blank" title="">'.esc_html($this->fields["plugin_name"]).' &#187;</a>
											<br><br><a target="_blank" href="http://wpbuild.ru/spelling-notifications"><img alt="'.esc_html($this->fields["plugin_name"]).'" title="'.esc_html($this->fields["plugin_name"]).'" src="'.plugins_url('/images/wpbuild.ru.png', __FILE__).'"></a></div>
										 <br><br><br><div style="text-align:center"><input class="button danger" onclick="hide()" type="button" value="'.esc_html($this->fields["form_close"]).'" id="cancel" name="cancel"></div>';
							}
							else
							{
								$assa .= '<form method="post" action="'.site_url('/index.php?spell_note.php').'" name="splwin">


										<label>'.esc_html($this->fields["form_web"]).':</label>
										<input class="form-control" id="url" type="text" name="url" size="35" readonly="readonly">
										<input class="form-control" type="hidden" id="spl" name="spl">
										<div id="m" class="alert-panel" style="margin-bottom:7px;"><script>document.write(p.spl);</script></div>
										<label>'.esc_html($this->fields["form_description"]).':</label>
										<textarea class="form-control" style="margin-bottom:11px;" id="message" rows="6" name="message" required="required" autofocus="autofocus"></textarea>

										<input class="button success" type="submit" value="'.esc_html($this->fields["form_send"]).'" id="submit" name="submit"> &#160;
										<input class="button danger" onclick="hide()" type="button" value="'.esc_html($this->fields["form_cancel"]).'" id="cancel" name="cancel">
										<div style="position:absolute;right:30px;bottom:5px;width:70px"><a target="_blank" href="http://wpbuild.ru/spelling-notifications/"><img alt="'.esc_html($this->fields["plugin_name"]).'" title="'.esc_html($this->fields["plugin_name"]).'" src="'.plugins_url('/images/wpbuild.ru.png', __FILE__).'"></a></div>
										</form>';
							}

					$assa .= '
							</div>
						</body>
					</html>';

					echo $assa;

					ob_flush();

					die;
				}

				return true;
			}

			public function admin_footer_text() {
				return sprintf( __( "If you like <b>ASDB Spelling Notifications</b> please leave us a %s rating on %s.", 'asdb-spellnote' ), "<a href='https://wordpress.org/support/view/plugin-reviews/asdb-spelling-notifications?filter=5#postform' target='_blank'>&#9733;&#9733;&#9733;&#9733;&#9733;</a>", "<a href='https://wordpress.org/support/view/plugin-reviews/asdb-spelling-notifications?filter=5#postform' target='_blank'>WordPress.org</a>");
			}

			public function right($str, $findme, $firstpos = true)
			{
				$pos = ($firstpos)? mb_stripos($str, $findme) : mb_strripos($str, $findme);

				if ($pos === false) return $str;
				else return mb_substr($str, $pos + mb_strlen($findme));
			}



			private function check_checked($val1, $val2) {
				$assa = (strval($val1) == strval($val2)) ? ' checked="checked"' : '';
				return $assa;
			}

			private function check_selected($val1, $val2) {
				$assa = (strval($val1) == strval($val2)) ? ' selected="selected"' : '';
				return $assa;
			}


		} // end class

		new ASDB_SpellNote;

	} // end spellnote_load()

}
