<?php
/*
Plugin Name: GlobalQuran
Plugin URI: http://docs.globalquran.com/Wordpress
Description: Easily add Quran on your website. This plugin let's you easily add 100+ Quran text translation and audio on your site. demo http://GlobalQuran.com
Version: 1.0
Author: Basit
Author URI: http://ibasit.me
License: Simple Public License (Simple-2.0)
*/

// First task will be commenting all code to understand what it does
// Second task will be to replace references to globalquran.com with alquran.cloud ones
// Third task extended testing prior to releasing


/* This code adds two filters to let plugin retrieve quran tags from the source content and uses function to output them
It uses a preg_match to instruct plugin to substitute shortcode with html taken from source and returns it */

add_filter('the_content', 'findQuranTag');
function findQuranTag ($text)
{
	if(preg_match("/{GlobalQuran}/", $text))
	{
		$html = renderQuran();
		$text = str_ireplace('{GlobalQuran}', $html, $text);
	}

	return $text;
}

/* Runs when plugin is activated */
register_activation_hook(__FILE__,'GlobalQuran_install');

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'GlobalQuran_remove' );

function GlobalQuran_install() {
	/* Creates new database field */
	add_option("gq_key", '', '', 'no');
	add_option("gq_css_url", 'http://GlobalQuran.com/images/themes/default/css/global.min.css', '', 'no');
	add_option("gq_css_print_url", 'http://GlobalQuran.com/images/themes/default/css/print.css', '', 'no');
}

function GlobalQuran_remove() {
	/* Deletes the database field */
	delete_option('gq_key');
	delete_option('gq_css_url');
	delete_option('gq_css_print_url');
}

/* This lines of code call another file and execute it.
This file is used to create plugin menu in admin dashboard with settings */

function GlobalQuran_admin () {
	include 'gq_admin.php';
}

function GlobalQuran_admin_actions() {
	add_options_page("GlobalQuran Settings", "GlobalQuran", 1, "GlobalQuran", "GlobalQuran_admin");
}
add_action('admin_menu', 'GlobalQuran_admin_actions');

/*
 * GlobalQuran Rendering code begins
 */
function renderQuran ()
{
	// html url to the application - THSI SHOULD BE REPLACED WITH NEW API REFERENCES TO ALQURAN.CLOUD?
	$api_url = 'http://GlobalQuran.com/';
	$api_key = get_option('gq_key');
	
// The code below comes in action in cae api call dies and relies on curl method to render the output //
	################## DO NOT EDIT BELOW THIS ###################################
	if (!$api_url)
	{
		die('missing vaules, please fill the configuration values and try again!');
	}

	$_REQUEST['apiKey'] = $api_key;

	$urlstring = NULL;
	build_string($_REQUEST, $urlstring);
	$ch = curl_init($api_url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $urlstring);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = curl_exec($ch);
	curl_close($ch);

	$html = str_ireplace('<!DOCTYPE html>', '', strip_only($data, 'head', true));
	$html = str_ireplace('<html lang="en" dir="lrt">', '', $html);
	$html = str_ireplace('<body class="ltr  ">', '', $html);
	$html = str_ireplace('<body class="ltr ">', '', $html);
	$html = str_ireplace('</body>', '', $html);
	$html = str_ireplace('</html>', '', $html);

	$head = '<link rel="shortcut icon" href="http://GlobalQuran.com/favicon.ico" />
			<link rel="stylesheet" href="'.get_option('gq_css_url').'" media="screen,print" />
			<link rel="stylesheet" href="'.get_option('gq_css_print_url').'" media="print" />';

	return $head.$html;
}
function build_string ($array, &$urlstring)
{
	foreach ($array as $key => $value)
	{
		if (is_array($value))
		{
			foreach ($value as $key2 => $value2)
			{
				$urlstring .= $key . '[' . $key2 . ']=' . $value2 . '&';
			}
		}
		else
			$urlstring .= "$key=$value&";
	}
}

function strip_only ($str, $tags, $stripContent = false)
{
    $content = '';
    if(!is_array($tags)) {
        $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
        if(end($tags) == '') array_pop($tags);
    }
    foreach($tags as $tag) {
        if ($stripContent)
             $content = '(.+</'.$tag.'[^>]*>|)';
         $str = preg_replace('#</?'.$tag.'[^>]*>'.$content.'#is', '', $str);
    }
    return $str;
}
?>
