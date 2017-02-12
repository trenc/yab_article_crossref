<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'yab_article_crossref';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version']     = '0.1';
$plugin['author']      = 'Tommy Schmucker';
$plugin['author_uri']  = 'http://www.yablo.de/';
$plugin['description'] = 'Easy cross-reference an article in custom fields.';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '3';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

/** Uncomment me, if you need a textpack
$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
abc_sample_string => Sample String
abc_one_more => One more
#@language de-de
abc_sample_string => Beispieltext
abc_one_more => Noch einer
EOT;
**/
// End of textpack

if (!defined('txpinterface'))
{
	@include_once('zem_tpl.php');
}

# --- BEGIN PLUGIN CODE ---
/**
 * yab_article_crossref
 *
 * A Textpattern CMS plugin.
 * Easy cross-reference an article in custom fields.
 *
 * @author Tommy Schmucker
 * @link   http://www.yablo.de/
 * @link   http://tommyschmucker.de/
 * @date   201702-12
 *
 * This plugin is released under the GNU General Public License Version 2 and above
 * Version 2: http://www.gnu.org/licenses/gpl-2.0.html
 * Version 3: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Configuration
 * Do your configuration here
 *
 * @return array
 */
function yab_article_crossref_config()
{
	$config = array(
		'custom_field_nr' => '', // custom_field number containing the cross reference article ID
		'sections'        => '', // sections from which we build the dropdown, comma separated
		'categories'      => '' // categories from which we build the dropdown, comma separated
	);
	
	return $config;
}

if (txpinterface == 'admin')
{
	register_callback(
		'yab_article_crossref',
		'article_ui',
		'custom_fields'
	);
}

/**
 * Echo the plugin JavaScript on article write tab.
 * Adminside Textpattern callback function
 * Hooked in the custom_fields on article_ui
 *
 * @param  string $event Textpattern admin event
 * @param  string $step  Textpattern admin step
 * @param  string $data  Textpattern data from callback step hook
 * @param  array  $rs    Textpattern data array from callback event hook
 * @return string        Echos the JavaScript
 */
function yab_article_crossref($event, $step, $data, $rs)
{
	$config = yab_article_crossref_config();

	if (gps('event') !== 'article' or !$config['custom_field_nr'])
	{
		return;
	}

	$cf_nr    = $config['custom_field_nr'];
	$dropdown = yab_cf_cr_dropdown($config, $rs);

	$js = <<<EOF
<script>
$(function() {
	$('#custom-$cf_nr').replaceWith('$dropdown');
});
</script>
EOF;

	return $data.$js;
}

/**
 * Build dropdown depending on configuration
 *
 * @param  array  $config Configuration array
 * @param  array  $rs     Textpattern data array from callback event hook
 * @return string         Dropdown select HTML element
 */
function yab_cf_cr_dropdown($config, $rs)
{
	$category     = join("','", doSlash(do_list($config['categories'])));
	$categories   = array();
	$categories[] = "Category1 IN ('$category')";
	$categories[] = "Category2 IN ('$category')";
	$categories   = join(" OR ", $categories);
	$category     = (!$category or !$categories)  ? '' : " AND ($categories)";
	$sects        = $config['sections'];
	$sections     = (!$sects) ? '' : " AND Section IN ('".join("','", doSlash(do_list($sects)))."')";
	$cf_nr        = $config['custom_field_nr'];
	$old          = $rs['custom_'.$cf_nr];
	$out_begin    = '<select name="custom_'.$cf_nr.'" id="custom-'.$cf_nr.'">';
	$out_end      = '</select>';
	$options      = '<option></option>';
	$where        = "1 = 1".$sections.$category;
	$rows         = safe_rows('ID, Title', 'textpattern', $where);

	foreach($rows as $row)
	{
		$selected = ($row['ID'] === $old) ? ' selected="selected"': '';

		$options .= '<option value="'.$row['ID'].'"'.$selected.'>'.$row['Title'].'</option>';
	}

	return $out_begin.$options.$out_end;
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN CSS ---

# --- END PLUGIN CSS ---
-->
<!--
# --- BEGIN PLUGIN HELP ---
h1. yab_article_crossref

Easy cross-reference an article in custom fields.

*Version:* 0.1

h2. Table of contents

# "Plugin requirements":#help-section02
# "Configuration":#help-config03
# "Changelog":#help-section10
# "License":#help-section11
# "Author contact":#help-section12

h2(#help-section02). Plugin requirements

yab_article_crossref's  minimum requirements:

* Textpattern 4.x

h2(#help-config03). Configuration

Open the plugin code. the first function contains the configuration values:

bc. 'custom_field_nr' => ', // custom_field number containing the cross reference article ID
'sections'        => ', // sections from which we build the dropdown, comma separated
'categories'      => ' // categories from which we build the dropdown, comma separated

h2(#help-section10). Changelog

* v0.1: 2017-02-12
** initial release

h2(#help-section11). Licence

This plugin is released under the GNU General Public License Version 2 and above
* Version 2: "http://www.gnu.org/licenses/gpl-2.0.html":http://www.gnu.org/licenses/gpl-2.0.html
* Version 3: "http://www.gnu.org/licenses/gpl-3.0.html":http://www.gnu.org/licenses/gpl-3.0.html

h2(#help-section12). Author contact

* "Plugin on author's site":http://www.yablo.de/article/500/yab_article_crossref-cross-reference-articles-in-custom-fields
* "Plugin on GitHub":https://github.com/trenc/yab_article_crossref
* "Plugin on textpattern forum":http://forum.textpattern.com/viewtopic.php?pid=303985
* "Plugin on textpattern.org":http://textpattern.org/plugins/1321/yab_article_crossref
# --- END PLUGIN HELP ---
-->
<?php
}
?>
