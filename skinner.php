<?php
/* 
Plugin Name: Skinner
Version: 0.1.3
Plugin URI: http://windyroad.org/software/wordpress/skinner-plugin
Description: Provides the ability to apply different skins to a theme
Author: Windy Road
Author URI: http://windyroad.com

Copyright (C)2007 Windy Road

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
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.This work is licensed under a Creative Commons Attribution 2.5 Australia License http://creativecommons.org/licenses/by/2.5/au/
*/ 

$_BENICE[]='skinner;6770968883708243;5432787455';

require_once( 'skin_switcher.php' );

function get_skin_root() {
	return apply_filters('skin_root', get_template_directory() . '/skins' );
}
function get_skin_root_uri() {
	return apply_filters('skin_root_uri', get_template_directory_uri() . '/skins' );
}

function get_skin() {
	$skin = skinner_get_selected_skin();
	if (empty($skin)) {
		$skin = get_option('skin');
	}
	return apply_filters('skin', $skin);
}

function get_skin_directory() {
	$skin = get_skin();
	$skin_dir = get_skin_root() . "/$skin";
	return apply_filters('skin_directory', $skin_dir, $skin);
}
function get_skin_directory_uri() {
	$skin = get_skin();
	$skin_dir_uri = get_skin_root_uri() . "/$skin";
	return apply_filters('skin_directory_uri', $skin_dir_uri, $skin);
}


function get_skin_data( $skin_file ) {
	$skin_data = implode( '', file( $skin_file ) );
	$skin_data = str_replace ( '\r', '\n', $skin_data );
	preg_match( '|Skin Name:(.*)|i', $skin_data, $skin_name );
	preg_match( '|Skin URI:(.*)|i', $skin_data, $skin_uri );
	preg_match( '|Description:(.*)|i', $skin_data, $description );
	preg_match( '|Author:(.*)|i', $skin_data, $author_name );
	preg_match( '|Author URI:(.*)|i', $skin_data, $author_uri );
	if ( preg_match( '|Parent Skin:(.*)|i', $skin_data, $parent ) )
		$parent = trim( $parent[1] );
	else
		$parent = null;
		
	if ( preg_match( '|Version:(.*)|i', $skin_data, $version ) )
		$version = trim( $version[1] );
	else
		$version ='';
	if ( preg_match('|Status:(.*)|i', $skin_data, $status) )
		$status = trim($status[1]);
	else
		$status = 'publish';

	$description = wptexturize( trim( $description[1] ) );

	$name = $skin_name[1];
	$name = trim( $name );
	$skin = $name;

	if ( '' == $author_uri[1] ) {
		$author = trim( $author_name[1] );
	} else {
		$author = '<a href="' . trim( $author_uri[1] ) . '" title="' . __('Visit author homepage') . '">' . trim( $author_name[1] ) . '</a>';
	}

	return array( 'Name' => $name, 'Title' => $skin, 'Description' => $description, 
				  'Author' => $author, 'Version' => $version, 'Parent Skin' => $parent,
				  'Status' => $status );
}

function get_skin_default() {
	$td = get_theme_data( get_template_directory() . '/style.css' );
	$name        = 'Default';
	$title       = $td[ 'Title' ] . ' Default';
	$description = $td[ 'Title' ] .'\'s default style';
	$version     = null;
	$author      = $td['Author'];
	$stylesheet  = get_stylesheet();
	$screenshot = null;

	foreach ( array('png', 'gif', 'jpg', 'jpeg') as $ext ) {
		if (file_exists(get_template_directory() . "/screenshot.$ext")) {
			$screenshot = "screenshot.$ext";
			break;
		}
	}

	$stylesheet_files = array();
	$template_files = array();

	$skin_root = get_skin_root();
	$skin_loc = str_replace(ABSPATH, '', $skin_root);
	$stylesheet_dir = dirname($skin_loc);

	return array('Name' => $name,
				  'Title' => $title,
				  'Description' => $description,
				  'Author' => $author,
				  'Version' => $version,
				  'Stylesheet' => $stylesheet,
				  'Stylesheet Files' => $stylesheet_files,
				  'Template Files' => $template_files,
				  'Stylesheet Dir' => $stylesheet_dir, 
				  'Status' => 'publish', 
				  'Screenshot' => $screenshot,
				  'Parent Skin' => null);
}

function get_skins() {
	global $wp_skins, $wp_broken_skins;

	if( isset( $wp_skins ) )
		return $wp_skins;
		
	$skins = array();
	$wp_broken_skins = array();
	$skin_root = get_skin_root();
	$skin_loc = str_replace(ABSPATH, '', $skin_root);
	$skins['Default'] = get_skin_default();
	$skins_dir = @ dir($skin_root);
	if ( !$skins_dir ) {
		return $skins;
	}
	while ( ($skin_dir = $skins_dir->read()) !== false ) {
		if ( is_dir($skin_root . '/' . $skin_dir) && is_readable($skin_root . '/' . $skin_dir) ) {
			if ( $skin_dir{0} == '.' || $skin_dir == '..' || $skin_dir == 'CVS' )
				continue;
			$stylish_dir = @ dir($skin_root . '/' . $skin_dir);
			$found_stylesheet = false;
			while ( ($skin_file = $stylish_dir->read()) !== false ) {
				if ( $skin_file == 'style.css' || $skin_file == 'style.css.php' ) {
					$skin_files[] = $skin_dir . '/' . $skin_file;
					$found_stylesheet = true;
					break;
				}
			}
			if ( !$found_stylesheet ) { 
				$wp_broken_skins[$skin_dir] = array('Name' => $skin_dir, 'Title' => $skin_dir, 'Description' => __("Stylesheet is missing: $skin_root/$skin_dir/style.css"));
			}
		}
	}

	if ( !$skins_dir || !$skin_files )
		return $skins;

	sort($skin_files);
	
	foreach ( (array) $skin_files as $skin_file ) {
		if ( !is_readable("$skin_root/$skin_file") ) {
			$wp_broken_skins[$skin_file] = array('Name' => $skin_file, 'Title' => $skin_file, 'Description' => __("File not readable: $skin_root/$skin_file"));
			continue;
		}

		$skin_data = get_skin_data("$skin_root/$skin_file");
		$name        = $skin_data['Name'];
		$title       = $skin_data['Title'];
		$description = wptexturize($skin_data['Description']);
		$version     = $skin_data['Version'];
		$author      = $skin_data['Author'];
		$stylesheet  = dirname($skin_file);
		$screenshot = null;
		
		foreach ( array('png', 'gif', 'jpg', 'jpeg') as $ext ) {
			if (file_exists("$skin_root/$stylesheet/screenshot.$ext")) {
				$screenshot = "screenshot.$ext";
				break;
			}
		}
		if ( empty($name) ) {
			$name = dirname($skin_file);
			$title = $name;
		}

		$stylesheet_files = array();
		$template_files = array();
		$stylesheet_dir = @ dir("$skin_root/$stylesheet");
		if ( $stylesheet_dir ) {
			while ( ($file = $stylesheet_dir->read()) !== false ) {
				if ( !preg_match('|^\.+$|', $file) ) {
					if( preg_match('|\.css(\.php)?$|', $file) ) {
						$stylesheet_files[] = "$skin_loc/$stylesheet/$file";
					}
					else if( preg_match('|\.php$|', $file) ) {
						$template_files[] = "$skin_loc/$stylesheet/$file";
					}
				}
			}
		}

		$stylesheet_dir = dirname($stylesheet_files[0]);
 
		if ( empty($stylesheet_dir) )
			$stylesheet_dir = '/';
		// Check for skin name collision.  This occurs if a skin is copied to
		// a new skin directory and the skin header is not updated.  Whichever
		// skin is first keeps the name.  Subsequent skins get a suffix applied.
		// The Default and Shiny and Citrus skins always trump their pretenders.
		if ( isset($skins[$name]) ) {
			if ( ('Default' == $name || 'Citrus' == $name || 'Shiny' == $name ) &&
				 ('default' == $stylesheet || 'citrus' == $stylesheet || 'shiny' == $stylesheet ) ) {
				// If another skin has claimed to be one of our default skins, move
				// them aside.
				$suffix = $skins[$name]['Stylesheet'];
				$new_name = "$name/$suffix";
				$skins[$new_name] = $skins[$name];
				$skins[$new_name]['Name'] = $new_name;
			} else {
				$name = "$name/$stylesheet";
			}
		}
		$skins[$name] = array('Name' => $name,
							  'Title' => $title,
							  'Description' => $description,
							  'Author' => $author,
							  'Version' => $version,
							  'Stylesheet' => $stylesheet,
							  'Stylesheet Files' => $stylesheet_files,
							  'Template Files' => $template_files,
							  'Stylesheet Dir' => $stylesheet_dir, 
							  'Status' => $skin_data['Status'], 
							  'Screenshot' => $screenshot,
							  'Parent Skin' => $skin_data['Parent Skin']);
	}

	if( !array_key_exists( 'Default', $skins ) ) { 
		$default = get_skin_default();
		$skins['Default'] = $default;
	}

	// Chek skin dependencies.
	$skin_names = array_keys($skins);

	foreach ( (array) $skin_names as $skin_name ) {
		$parent = $skins[$skin_name]['Parent Skin'];
		if ( $parent ) {
			if( !isset( $skins[ $parent ] ) ) {
				$wp_broken_skins[$skin_file] = array('Name' => $skins[$skin_name]['Name'], 'Title' => $skins[$skin_name]['Title'], 'Description' => __("Parent skin not found: $parent"));
				unset( $skins[$skin_name] );
			}
		}
	}

	// check screenshots
	foreach ( (array) $skin_names as $skin_name ) {
		if( $skins[ $skin_name ][ 'Screenshot' ] == null ) {
			for( $parent = $skins[ $skin_name ]['Parent Skin'];
				 $parent != null && isset( $skins[ $parent ] );
				 $parent = $skins[ $parent ][ 'Parent Skin' ] ) {
				if( $skins[ $parent ][ 'Screenshot'] != null ) {
					$skins[ $skin_name ][ 'Screenshot' ] = '../' . $skins[ $parent ][ 'Stylesheet'] . '/' . $skins[ $parent ][ 'Screenshot'];
					break;
				}
			}
			if( $skins[ $skin_name ][ 'Screenshot' ] == null ) {
				foreach ( array('png', 'gif', 'jpg', 'jpeg') as $ext ) {
					if (file_exists(get_template_directory() . "/screenshot.$ext")) {
						$skins[ $skin_name ][ 'Screenshot' ] = "../../screenshot.$ext";
						break;
					}
				}
			}
		}
	}

	$wp_skins = $skins;
	return $skins;
}

function get_current_skin() {
	$skins = get_skins();
	if( !$skins )
		return;
	$current_skin = get_skin();
	foreach( $skins as $skin ) {
		if( $skin[ 'Stylesheet'] == $current_skin ) {
			return $skin[ 'Name'];
		}
	}
	return 'Default';
}

function get_skinstyles( $skin ) {
	$skins = get_skins();
	if( !$skins || !isset( $skins[ $skin ] ) )
		return false;
	$cs = $skins[ $skin ];
	$styles = array();
	if( $cs[ 'Parent Skin' ] != null ) {
		$styles = get_skinstyles( $cs[ 'Parent Skin' ] );
	}
	// get the none ie styles first
	foreach( $cs['Stylesheet Files'] as $file ) {
		$url = get_option('siteurl') . '/' . $file;
		if( !preg_match('|\-ie\.css\.php?$|', $file) ) {
			$styles[] = $url; 
		}
	}
	// then apply the ie6 overrides
	foreach( $cs['Stylesheet Files'] as $file ) {
		$url = get_option('siteurl') . '/' . $file;
		if( preg_match('|\-ie\.css\.php?$|', $file) ) {
			$url = add_query_arg( 'skin', $cs['Stylesheet'], $url );
			$styles[] = $url; 
		}
	}
	return apply_filters('skinstyles', $styles, $cs[ 'Stylesheet' ] );
}

function get_blogskinstyles() {
	$styles = get_skinstyles( get_current_skin() );
	return $styles;
}

function blogskinstyles() {
	$urls = get_blogskinstyles();
	if( !$urls )
		return;
	foreach( $urls as $url ) {
		if( preg_match('|\-ie\.css\.php?|', $url ) ) {
?>
<!--[if lte IE 6]>
<link rel="stylesheet" href="<?php echo htmlspecialchars($url) ?>" type="text/css" media="screen" />
<![endif]-->
<?php			
		}
		else {
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($url) ?>" type="text/css" />
<?php
		}
	}
}

function skin_enabled( $skin ) {
	$curr = get_option( 'skin_statuses' );
	if( $curr == null ) {
		return true;
	}
	$theme = get_current_theme();
	if( !isset( $curr[ $theme ] ) ) {
		return true;
	}
	if( !isset( $curr[ $theme ][ $skin['Stylesheet'] ] ) ) {
		return true;
	}
	return $curr[ $theme ][ $skin['Stylesheet'] ];
}

function skinner_include_functions( $skin ) {
	$skins = get_skins();
	if( $skins[ $skin ][ 'Parent Skin'] != null ) {
		skinner_include_functions( $skins[ $skin ][ 'Parent Skin'] );
	}
	// Check for a functions.php in the selected skin directory
	if( get_template_directory() != ABSPATH . $skins[ $skin ]['Stylesheet Dir'] ) {
		$skin_functions = ABSPATH . $skins[ $skin ]['Stylesheet Dir'] . "/functions.php";
		if( file_exists( $skin_functions ) ) {
			include( $skin_functions );
		}
	}
}

function skinner_init() {
	$skins = get_skins();
	if( $skins ) {
		skinner_include_functions( get_current_skin() );
	}
}

skinner_init();




//add_action('init', 'skinner_init' );

if( is_admin() ) {
	require_once( dirname(__FILE__).'/skinner-admin.php' );
}
else {
	add_action('wp_head', 'blogskinstyles');
}

function skinner_add_styles( $styles ) {
	$urls = get_blogskinstyles();
	if( !$urls )
		return $styles;
	foreach( $urls as $url ) {
		if( !preg_match('|\-ie\.css\.php?|', $url ) ) {
			$styles[] = $url;
		}
	}
	return $styles;
}

function skinner_add_styles_ie( $styles ) {
	$urls = get_blogskinstyles();
	
	if( !$urls )
		return $styles;
	foreach( $urls as $url ) {
		if( preg_match('|\-ie\.css\.php?|', $url ) ) {
			$styles[] = $url;
		}
	}
	return $styles;
}

add_action( 'real_wysiwyg_style_sheets', 'skinner_add_styles' );
add_action( 'real_wysiwyg_style_sheets_ie', 'skinner_add_styles_ie' );

?>