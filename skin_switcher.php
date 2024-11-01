<?php

/*
Adapted from Ryan Boren's theme switcher.
http://boren.nu/
*/

function skinner_clear_skin_cookie() {
	$theme = get_stylesheet();
	setcookie("wpskin-" . $theme . COOKIEHASH, false, time(), COOKIEPATH );
	// clear the selected theme as well,
	// otherwise the amin screen will be confused
	setcookie("wptheme" . COOKIEHASH,
							stripslashes($theme),
							time(),
							COOKIEPATH
							);
	
}
function skinner_set_skin_cookie() {
	$expire = time() + 30000000;
	$theme = get_stylesheet();
	if (!empty($_GET["wpskin"])) {
		setcookie("wpskin-" . $theme . COOKIEHASH,
							stripslashes($_GET["wpskin"]),
							$expire,
							COOKIEPATH
							);
		$url = remove_query_arg('wpskin', $_SERVER[ 'REQUEST_URI'] );
		wp_redirect( $url );
		exit;
	}
}

function skinner_get_selected_skin() {
	$theme = get_stylesheet();
	if (isset($_COOKIE["wpskin-" . $theme . COOKIEHASH])) {
		$skin = $_COOKIE["wpskin-" . $theme . COOKIEHASH];
	}	else {
		return null;
	}
	$skins = get_skins();
	if( isset( $skins[ $skin ] ) ) {
		return $skins[ $skin ]['Stylesheet'];
	}
	return null;
}


function skinner_skin_switcher($style = "text") {
	$skins = get_skins();
	$skins = array_filter($skins, 'skin_enabled' );
	$default_skin = get_current_skin();

	if (count($skins) > 1) {
		$skin_names = array_keys($skins);
		natcasesort($skin_names);

		$ts = '<ul id="skinswitcher">'."\n";		

		if ($style == 'dropdown') {
			$ts .= '<li>'."\n"
				. '	<select name="skinswitcher" onchange="location.href=\''.get_settings('home').'/index.php?wpskin=\' + this.options[this.selectedIndex].value;">'."\n"	;

			foreach ($skin_names as $skin_name) {
				// Skip unpublished skins.
				if (isset($skins[$skin_name]['Status']) && $skins[$skin_name]['Status'] != 'publish')
					continue;
				$theme = get_stylesheet();	
				if ((!empty($_COOKIE["wpskin-" . $theme . COOKIEHASH]) && $_COOKIE["wpskin-" . $theme . COOKIEHASH] == $skin_name)
						|| (empty($_COOKIE["wpskin-" . $theme . COOKIEHASH]) && ($skin_name == $default_skin))) {
					$ts .= '		<option value="'.$skin_name.'" selected="selected">'
						. htmlspecialchars($skin_name)
						. '</option>'."\n"
						;
				}	else {
					$ts .= '		<option value="'.$skin_name.'">'
						. htmlspecialchars($skin_name)
						. '</option>'."\n"
						;
				}				
			}
			$ts .= '	</select>'."\n"
				. '</li>'."\n"
				;
		}	else {
			foreach ($skin_names as $skin_name) {
				// Skip unpublished skins.
				if (isset($skins[$skin_name]['Status']) && $skins[$skin_name]['Status'] != 'publish')
					continue;

				$display = htmlspecialchars($skin_name);
				$theme = get_stylesheet();
				if ((!empty($_COOKIE["wpskin-" . $theme . COOKIEHASH]) && $_COOKIE["wpskin-" . $theme . COOKIEHASH] == $skin_name)
						|| (empty($_COOKIE["wpskin-" . $theme . COOKIEHASH]) && ($skin_name == $default_skin))) {
					$ts .= '	<li>'.$display.'</li>'."\n";
				}	else {
					$ts .= '	<li><a href="'
						.get_settings('home').'/'. 'index.php'
						.'?wpskin='.urlencode($skin_name).'">'
						.$display.'</a></li>'."\n";
				}
			}
		}
		$ts .= '</ul>';
	}

	echo $ts;
}

add_action('init', 'skinner_set_skin_cookie');

function skinner_widget_skin_switcher($args) {
	extract($args);
	echo $before_widget;
	echo $before_title;
	echo "Skins";
	echo $after_title;
	skinner_skin_switcher();
	echo $after_widget;
}

function skinner_register_widget() {
	if ( function_exists('register_sidebar_widget') ) {
		register_sidebar_widget('Skin Switcher', 'skinner_widget_skin_switcher');
	}
}	

add_action('widgets_init', 'skinner_register_widget');

