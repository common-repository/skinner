<?php
add_action('admin_menu', 'add_skins_admin'); 
add_action('admin_menu', 'add_skins_editor'); 
add_action('init', 'skinner_process_action' );

function skinner_set_skin_status( $skin, $theme, $status ) {
	$curr = get_option( 'skin_statuses' );
	if( $curr == null )
		$curr = array();
	if( !isset( $curr[ $theme ] ) ) {
		$curr[ $theme ] = array();
	}
	$curr[ $theme ][ $skin ] = $status;
	update_option('skin_statuses', $curr );
}

function skinner_process_action() {
	if( isset( $_POST[ 'submit'] )
		&& $_POST[ 'submit'] == 'Disable'
		&& isset( $_POST[ 'skin' ] )
		&& isset( $_POST[ 'theme' ] ) ) {
		skinner_set_skin_status( $_POST[ 'skin' ], $_POST[ 'theme' ], false );
	}
	if( isset( $_POST[ 'submit'] )
		&& $_POST[ 'submit'] == 'Enable'
		&& isset( $_POST[ 'skin' ] )
		&& isset( $_POST[ 'theme' ] ) ) {
		skinner_set_skin_status( $_POST[ 'skin' ], $_POST[ 'theme' ], true );
	}
	if( isset( $_REQUEST[ 'createskin' ] ) ) {
		skinner_create_new_skin();
	}
}

function skinner_create_new_skin() {
	$name = null;
	if( isset( $_POST[ 'skin' ] ) ) {
		$title = wp_specialchars(stripcslashes(trim($_POST[ 'skin' ])));
		$search = array(' ', '\\', '/', ':', ';', '#');
		$name = str_replace($search, '_', $title);
		$skins = get_skins();
		$names = array_keys($skins);
		$lnames = array();
		foreach( $names as $sname ) {
			$lnames[] = strtolower($sname);
		}
		$lname = strtolower($name);
		if( array_search(strtolower($lname), $lnames) ) {
			wp_redirect( 'themes.php?page=skinner-admin.php&createskin&error=nameconflict&skin=' . Title );
			exit;
		}
		if( !is_dir(get_skin_root()) ) {
			if( !mkdir(get_skin_root()) ) {
				wp_redirect( 'themes.php?page=skinner-admin.php&error=skinrootdir&skin=' . name );
				exit;
			}
		}
		$dir = get_skin_root() . '/' . $name;
		if( !is_dir($dir) ) {
			if( !mkdir($dir) ) {
				wp_redirect( 'themes.php?page=skinner-admin.php&error=skindir&skin=' . name );
				exit;
			}
		}
		$handle = @fopen( $dir . '/style.css', 'a+');
		if( $handle ) {
			$file_data = <<< SKINNEREOD
/*
Skin Name: $title
Skin URI: 
Description: 
Version: 
Author: 
Author URI: 
*/
SKINNEREOD;
			fwrite( $handle, $file_data );
			fclose( $handle );
			skinner_set_skin_status( $name, get_current_theme(), false);
			wp_redirect( 'themes.php?page=skinner-admin.php&action=created&skin=' . $name . '&title=' . $title );
			exit;
		}
		else {
			wp_redirect( 'themes.php?page=skinner-admin.php&error=style&skin=' . $name );
			exit;
		}
	}
}


function add_skins_editor() {
	if( $_GET[ 'page' ] == 'editskinner-admin.php' ) {
	
		$skin = null;
		if(!isset( $_REQUEST[ 'skin' ] ) ) {
			$skin = get_current_skin();
		}
		else {
			$skin = stripslashes($_REQUEST[ 'skin' ]);
		}

		$skins = get_skins();
			
		$allowed_files = array_merge($skins[$skin]['Stylesheet Files'], $skins[$skin]['Template Files']);

		$file = null;
		if(!isset( $_REQUEST[ 'skinfile' ] )) {
			$file = $allowed_files[0];
		}
		else {
			$file = $_REQUEST[ 'skinfile' ];
		}

		$file = validate_file_to_edit($file, $allowed_files);
		$real_file = get_real_file_to_edit($file);

		$file_show = basename( $file );
		
		$skinaction = null;
		if(isset( $_REQUEST[ 'skinaction' ] )) {
			$skinaction = $_REQUEST[ 'skinaction' ];
		}

		if ( !current_user_can('edit_themes') )
			wp_die('<p>'.__('You do not have sufficient permissions to edit skins for this blog.').'</p>');
		
		switch($skinaction) {
			case 'update':

				check_admin_referer('edit-skin_' . $file . $skin);

				$newcontent = stripslashes($_POST['newcontent']);
				$skin = urlencode($skin);
				if (is_writeable($real_file)) {
					$f = fopen($real_file, 'w+');
					fwrite($f, $newcontent);
					fclose($f);
					$location = "themes.php?page=editskinner-admin.php&skinfile=$file&skin=$skin&a=te";
				} else {
					$location = "themes.php?page=editskinner-admin.php&skinfile=$file&skin=$skin";
				}

				$location = wp_kses_no_null($location);
				$strip = array('%0d', '%0a');
				$location = str_replace($strip, '', $location);
				header("Location: $location");
				exit();

				break;
			case 'switch':
				$skin = urlencode($skin);
				$location = "themes.php?page=editskinner-admin.php&skinfile=$file&skin=$skin";
				$location = wp_kses_no_null($location);
				$strip = array('%0d', '%0a');
				$location = str_replace($strip, '', $location);
				header("Location: $location");
				exit();
			
				break;
				
			default:

		}
	}
	$skins = array_filter(get_skins(), 'real_skin');
	if( count($skins) > 0 ) {
		add_theme_page('Edit Skins', 'Skin Editor', 'edit_themes', 'edit' . basename(__FILE__), 'skin_editor');
	}
}

// attribute_escape() was only introduced in wp 2.1 
if( function_exists('attribute_escape')) {
	function skinner_attribute_escape($text) {
		return attribute_escape($text);
	}
}
else {
	function skinner_attribute_escape($text) {
		$safe_text = wp_specialchars($text, true);
		return apply_filters('attribute_escape', $safe_text, $text);
	}
}

function real_skin( $skin ) {
	$count =  count($skin['Stylesheet Files'])
			 + count($skin['Template Files']);
	return $count > 0;
}

function skin_editor() {
	$skin = null;
	if(!isset( $_REQUEST[ 'skin' ] ) ) {
		$skin = get_current_skin();
	} else {
		$skin = stripslashes($_REQUEST[ 'skin' ]);
	}

	$skins = array_filter(get_skins(), 'real_skin');
	if( count($skins) > 0 ) {
		$allowed_files = array_merge($skins[$skin]['Stylesheet Files'], $skins[$skin]['Template Files']);
	
		$file = null;
		if(!isset( $_REQUEST[ 'skinfile' ] )) {
			$file = $allowed_files[0];
		}
		else {
			$file = $_REQUEST[ 'skinfile' ];
		}
	
		$file = validate_file_to_edit($file, $allowed_files);
		$real_file = get_real_file_to_edit($file);
	
		$file_show = basename( $file );
		
		require_once('admin-header.php');
	
		update_recently_edited($file);
	
		if (!is_file($real_file))
			$error = 1;
	
		if (!$error && filesize($real_file) > 0) {
			$f = fopen($real_file, 'r');
			$content = fread($f, filesize($real_file));
			$content = htmlspecialchars($content);
		}
	
		if( isset($_GET['a']) ) {
			?><div id="message" class="updated fade"><?php
				?><p><?php _e('File edited successfully.') ?></p><?php
			?></div><?php
		}
	 	?><div class="wrap"><?php
			?><form name="theme" action="themes.php?page=editskinner-admin.php" method="post"><?php
				?><input type="hidden" name="skinaction" value="switch" /><?php
					 _e('Select skin to edit:');
					?><select name="skin" id="skin"><?php
						foreach ($skins as $a_skin) {
							$skin_name = $a_skin['Name'];
							if ($skin_name == $skin) $selected = " selected='selected'";
							else $selected = '';
							$skin_name = skinner_attribute_escape($skin_name);
							echo "\n\t<option value=\"$skin_name\" $selected>$skin_name</option>";
						}
					?></select>
			<input type="submit" name="Submit" value="<?php _e('Select &raquo;') ?>" class="button" />
			</form>
			</div>
	
			<div class="wrap"> 
	<?php
		if ( is_writeable($real_file) ) {
			echo '<h2>' . sprintf(__('Editing <code>%s</code>'), $file_show) . '</h2>';
		} else {
			echo '<h2>' . sprintf(__('Browsing <code>%s</code>'), $file_show) . '</h2>';
		}
	?>
				<div id="templateside">
					<h3><?php printf(__("<strong>'%s'</strong> skin files"), $skin) ?></h3>
	
	<?php
		if ($allowed_files) :
	?>
					<ul>
		<?php foreach($allowed_files as $allowed_file) : ?>
						<li><a href="themes.php?page=editskinner-admin.php&amp;skinfile=<?php echo "$allowed_file"; ?>&amp;skin=<?php echo urlencode($skin) ?>"><?php echo get_file_description($allowed_file); ?></a></li>
		<?php endforeach; ?>
					</ul>
	<?php
		endif; ?>
				</div>
	<?php
		if (!$error) {
	?>
				<form name="template" id="template" action="themes.php?page=editskinner-admin.php" method="post">
		<?php wp_nonce_field('edit-skin_' . $file . $skin) ?>
					<div><textarea cols="70" rows="25" name="newcontent" id="newcontent" tabindex="1"><?php echo $content ?></textarea>
						<input type="hidden" name="skinaction" value="update" />
						<input type="hidden" name="skinfile" value="<?php echo $file ?>" />
						<input type="hidden" name="skin" value="<?php echo $skin ?>" />
					</div>
		<?php if ( is_writeable($real_file) ) : ?>
					<p class="submit">
		<?php
				echo "<input type='submit' name='submit' value='	" . __('Update File &raquo;') . "' tabindex='2' />";
		?>
					</p>
		<?php else : ?>
					<p><em><?php _e('If this file were writable you could edit it.'); ?></em></p>
		<?php endif; ?>
				</form>
		<?php
		}
		else {
			echo '<div class="error"><p>' . __('Oops, no such file exists! Double check the name and try again, merci.') . '</p></div>';
		}
	} else {
		// no skins
		?><div class="error"><?php
				?><p><?php _e('Oops, this theme has no skins.') ?></p><?php
			?></div><?php
	}
	?>
		
	<div class="clear"> &nbsp; </div>
	</div>
	
	<?php
	skinner_admin_footer();
}

function skinner_admin_footer()
{
?>
<p style="text-align: center;">
<a style="text-decoration: none;" href="http://windyroad.org/software/wordpress/skinner-plugin">Skinner Plugin</a><br />
by<br />
<a style="text-decoration: none;" href="http://windyroad.org">
<img src="http://windyroad.org/static/logos/windyroad-105x15.png" style="border: none;" alt="Windy Road" />
</a>
</p>
<?php
}

function add_skins_admin() {
    global $wp_skin, $wp_broken_skins;

	if ( isset($_GET['action']) ) {
		if ('activateskin' == $_GET['action']) {
			if ( isset($_GET['skin']) ) {
				update_option('skin', $_GET['skin']);
				$curr_theme = current_theme_info();
				$theme = $curr_theme->title;
				skinner_set_skin_status( $_GET['skin'], $theme, true );
				skinner_clear_skin_cookie();
			}
			
			do_action('switch_skin', get_current_skin());

			wp_redirect('themes.php?page=skinner-admin.php&activated=true');
			exit;
		}
	}
    add_theme_page('Manage Skins', 'Skins', 'edit_themes', basename(__FILE__), 'skin_admin');
}


function skinner_new_skin() {
	$name = null;
	if( isset( $_POST[ 'skin' ] ) ) {
		$title = wp_specialchars(stripcslashes(trim($_POST[ 'skin' ])));
		$search = array(' ', '\\', '/', ':', ';', '#');
		$name = str_replace($search, '_', $title);
		$skins = get_skins();
		$names = array_keys($skins);
		$lnames = array();
		foreach( $names as $sname ) {
			$lnames[] = strtolower($sname);
		}
		$lname = strtolower($name);
		if( array_search(strtolower($lname), $lnames) ) {
			?><div id="message1" class="error"><p><?php echo __('Please choose another name. An existing skin conflicts with \'') . $title . __('\'.');  ?></p></div><?php			
			skinner_display_new_skin_form( $title );
			return false;	
		}
		if( !is_dir(get_skin_root()) ) {
			if( !mkdir(get_skin_root()) ) {
				?><div id="message2" class="error"><p><?php echo __('A \'skins\' directory could be created in \'' . get_theme_root() . '\'.  Please create this directory manually.');  ?></p></div><?php						
				skinner_display_new_skin_form( $title );
				return false;	
			}
		}
		$dir = get_skin_root() . '/' . $name;
		if( !is_dir($dir) ) {
			if( !mkdir($dir) ) {
				?><div id="message3" class="error"><p><?php echo __('A \'skins/' . $name . '\' directory could be created in \'' . get_theme_root() . '\'.  Please create this directory manually.');  ?></p></div><?php						
				skinner_display_new_skin_form( $title );
				return false;	
			}
		}
		$handle = @fopen( $dir . '/style.css', 'a+');
		if( $handle ) {
			$file_data = <<< SKINNEREOD
/*
Skin Name: $title
Skin URI: 
Description: 
Version: 
Author: 
Author URI: 
*/
SKINNEREOD;
			fwrite( $handle, $file_data );
			fclose( $handle );
			$skin_root = get_skin_root();
			$skin_loc = str_replace(ABSPATH, '', $skin_root);
			$location = "themes.php?page=editskinner-admin.php&skinfile=".$skin_loc . '/' . $name . "/style.css&skin=$title";
			?><div id="message5" class="updated fade"><p><?php echo __('The skin \'' . $title . '\' has been created. ') . '<a href="' . $location . '">Edit &raquo;</a>';  ?></p></div><?php
			skinner_set_skin_status( $name, get_current_theme(), false);
			return true;
		}
		else {
			?><div id="message4" class="error fade"><p><?php echo __('Could not create \'style.css\' file. Please create this file manually.');  ?></p></div><?php						
		}
	}
	skinner_display_new_skin_form( '' );
	return false;
}

function skinner_display_new_skin_form() {
	?><div class="wrap"><?php
		?><h2><?php _e('New Skin'); ?></h2><?php
		?><form style="text-align: center" action="themes.php?page=skinner-admin.php" method="post"><?php
			?><label>Skin Name: <input type="text" name="skin" value="<?php echo stripslashes(wp_specialchars($_REQUEST[ 'skin' ])); ?>" /></label><?php
			?><p class="submit"><input type="submit" name="createskin" value="Create Skin &raquo;" /></p><?php
	  	?></form><?php
	?><div class="clear"> &nbsp; </div><?php
	?></div><?php
	skinner_admin_footer();	
}

function skinner_display_msgs() {
	if ( ! validate_current_skin() ) {
		?><div id="message1" class="updated fade"><p><?php _e('The active skin is broken.  Reverting to the default skin.'); ?></p></div><?php
	}
	elseif ( isset($_GET['activated']) ) {
		?><div id="message2" class="updated fade"><p><?php printf(__('New skin activated. <a href="%s">View site &raquo;</a>'), get_bloginfo('home') . '/'); ?></p></div><?php
	}
	if( isset( $_GET['error'])) {
		if( $_GET[ 'error'] == 'style') {
			$theme = current_theme_info();
			$style = $theme->stylesheet_dir . '/skins/' . $_GET[ 'skin' ] . '/style.css'; 
			?><div id="message4" class="error"><p><?php echo sprintf(__('Could not create \'%s\'. Please create this file manually.'), $style );  ?></p></div><?php						
			
		}
	}
	if( isset( $_GET[ 'action'] ) ) {
		$skin_root = get_skin_root();
		$skin_loc = str_replace(ABSPATH, '', $skin_root);
		$location = "themes.php?page=editskinner-admin.php&skinfile=".$skin_loc . '/' . $_GET[ 'skin' ] . "/style.css&skin=" . $_GET[ 'title'] ;
		?><div id="message5" class="updated fade"><p><?php echo __('The skin \'' . $_GET[ 'title' ] . '\' has been created. ') . '<a href="' . $location . '">Edit &raquo;</a>';  ?></p></div><?php
	}	
}

function skin_admin() {
	if( isset( $_REQUEST[ 'createskin' ] ) ) {
		skinner_display_new_skin_form();
		return;		
	}
	skinner_display_msgs();

	$skins = get_skins();
	$ct = current_skin_info();
	$theme = current_theme_info();
?>

<div class="wrap">
<h2><?php _e('Current Skin'); ?></h2>
<div id="currenttheme">
<?php if ( $ct->screenshot ) : ?>
<img src="<?php echo get_option('siteurl') . '/' . $ct->stylesheet_dir . '/' . $ct->screenshot; ?>" alt="<?php _e('Current skin preview'); ?>" />
<?php endif; ?>
<h3><?php printf(__('%1$s %2$s by %3$s'), $ct->title, $ct->version, $ct->author) ; ?></h3>
<p><?php echo $ct->description; ?></p>
<?php if ($ct->parent_skin) { ?>
	<p><?php printf(__('The stylesheet files are located in <code>%2$s</code>.<br/><strong>%3$s</strong> uses files from <strong>%4$s</strong>.  Changes made to %4$s files will affect both skins.'), $ct->title, $ct->stylesheet_dir, $ct->title, $ct->parent_skin); ?></p>
<?php } else { ?>
	<p><?php printf(__('All of this skin&#8217;s files are located in <code>%2$s</code>.'), $ct->title, $ct->stylesheet_dir); ?></p>
<?php } ?>
</div>
<?php
$skins = array_filter($skins, 'skin_inactive' );

$enabled_skins = array_filter($skins, 'skin_enabled' );
$disabled_skins = array_filter($skins, 'skin_disabled' );
?>
<?php if ( !empty($enabled_skins) ) { ?>
<h2><?php _e('Available Skins'); ?></h2>

<?php
$style = '';

$skin_names = array_keys($enabled_skins);
natcasesort($skin_names);

foreach ($skin_names as $skin_name) {
	if ( $skin_name == $ct->name )
		continue;
	$stylesheet = $skins[$skin_name]['Stylesheet'];
	$title = $skins[$skin_name]['Title'];
	$version = $skins[$skin_name]['Version'];
	$description = $skins[$skin_name]['Description'];
	$author = $skins[$skin_name]['Author'];
	$screenshot = $skins[$skin_name]['Screenshot'];
	$stylesheet_dir = $skins[$skin_name]['Stylesheet Dir'];
	$activate_link = "themes.php?page=skinner-admin.php&amp;action=activateskin&amp;skin=$stylesheet";
?>
<div class="available-theme">
<h3><a href="<?php echo $activate_link; ?>"><?php echo "$title $version"; ?></a></h3>
<a href="<?php echo $activate_link; ?>" class="screenshot">
<?php if ( $screenshot ) : ?>
<img src="<?php echo get_option('siteurl') . '/' . $stylesheet_dir . '/' . $screenshot; ?>" alt="" />
<?php endif; ?>
</a><?php
if( !empty( $description) ) { ?><p><?php echo $description; ?></p><?php }
?><form action="themes.php?page=skinner-admin.php" method="post">
  	<input type="hidden" name="skin" value="<?php echo $stylesheet; ?>" />
  	<input type="hidden" name="theme" value="<?php echo get_current_theme(); ?>" />
	<p class="submit"><input type="submit" name="submit" value="Disable" /></p>
  </form>
</div>
<?php } // end foreach skin_names ?>

<?php } ?>

<?php if ( !empty($disabled_skins) ) { ?>
<h2><?php _e('Disabled Skins'); ?></h2>

<?php
$style = '';

$skin_names = array_keys($disabled_skins);
natcasesort($skin_names);

foreach ($skin_names as $skin_name) {
	if ( $skin_name == $ct->name )
		continue;
	$stylesheet = $skins[$skin_name]['Stylesheet'];
	$title = $skins[$skin_name]['Title'];
	$version = $skins[$skin_name]['Version'];
	$description = $skins[$skin_name]['Description'];
	$author = $skins[$skin_name]['Author'];
	$screenshot = $skins[$skin_name]['Screenshot'];
	$stylesheet_dir = $skins[$skin_name]['Stylesheet Dir'];
	$activate_link = "themes.php?page=skinner-admin.php&amp;action=activateskin&amp;skin=$stylesheet";
?>
<div class="available-theme">
<h3><a href="<?php echo $activate_link; ?>"><?php echo "$title $version"; ?></a></h3>
<a href="<?php echo $activate_link; ?>" class="screenshot">
<?php if ( $screenshot ) : ?>
<img src="<?php echo get_option('siteurl') . '/' . $stylesheet_dir . '/' . $screenshot; ?>" alt="" />
<?php endif; ?>
</a><?php
if( !empty( $description) ) { ?><p><?php echo $description; ?></p><?php }
?><form action="themes.php?page=skinner-admin.php" method="post">
  	<input type="hidden" name="skin" value="<?php echo $stylesheet; ?>" />
  	<input type="hidden" name="theme" value="<?php echo get_current_theme(); ?>" />
	<p class="submit"><input type="submit" name="submit" value="Enable" /></p>
  </form>
</div>
<?php } // end foreach skin_names ?>

<?php } ?>

<?php
// List broken skins, if any.
$broken_skins = get_broken_skins();
if ( count($broken_skins) ) {
?>

<h2><?php _e('Broken skins'); ?></h2>
<p><?php _e('The following skins are installed but incomplete.  skins must have a stylesheet called style.css or style.css.php.'); ?></p>

<table width="100%" cellpadding="3" cellspacing="3">
	<tr>
		<th><?php _e('Name'); ?></th>
		<th><?php _e('Description'); ?></th>
	</tr>
<?php
	$skin = '';

	$skin_names = array_keys($broken_skins);
	natcasesort($skin_names);

	foreach ($skin_names as $skin_name) {
		$title = $broken_skins[$skin_name]['Title'];
		$description = $broken_skins[$skin_name]['Description'];

		$skin = ('class="alternate"' == $skin) ? '' : 'class="alternate"';
		echo "
		<tr $skin>
			 <td>$title</td>
			 <td>$description</td>
		</tr>";
	}
?>
</table>
<?php
}
?>

<?php 
	$themes = get_themes();
	$curr_theme = current_theme_info();
	$archive = get_theme_skin_directory( $curr_theme );
	$parent = $curr_theme->title . ' ' . $curr_theme->version;
?>
<h2><?php _e('Create Your Own Skin'); ?></h2>
<p>You can make your own skin for <?php echo($parent); ?>. Creating your own skin is easy and uses the themes default look and feel as a starting point.</p>
  <form action="themes.php?page=skinner-admin.php" method="post">
	<p class="submit"><input type="submit" name="createskin" value="Create Skin &raquo;" /></p>
  </form>
<?php	if( $archive ) { ?>
<h2><?php _e('Get More Skins'); ?></h2>
<p>You can find additional skins for <?php echo($parent); ?> in the <a href="<?php echo(trim($archive)); ?>"><?php echo($parent); ?> skin directory</a>. To install a skin you generally just need to upload the skin folder into your <code><?php echo $curr_theme->stylesheet_dir; ?>/skins</code> directory. Once a skin is uploaded, you should see it on this page.</p>

<?php
	}
?>
</div>
		<div class="clear"> &nbsp; </div>
<?php
	skinner_admin_footer();
}

function get_theme_skin_directory( $curr_theme )
{
	$theme_file = get_stylesheet_directory() . '/style.css';
	$theme_data = implode( '', file( $theme_file ) );
	$theme_data = str_replace ( '\r', '\n', $theme_data );
	if( preg_match( '|Skins URI:(.*)|i', $theme_data, $skin_dir ) )
		return $skin_dir[1];
	else
		return null;
}

function skin_inactive( $skin ) {
	return get_current_skin() != $skin[ 'Name' ];
}

function skin_disabled( $skin ) {
	return !skin_enabled( $skin );
}

function get_broken_skins() {
        global $wp_broken_skins;

        get_skins();
        return $wp_broken_skins;
}

function current_skin_info() {
        $skins = get_skins();
        $current_skin = get_current_skin();
        $cs->name = $current_skin;
        $cs->title = $skins[$current_skin]['Title'];
        $cs->version = $skins[$current_skin]['Version'];
        $cs->parent_skin = $skins[$current_skin]['Parent Skin'];
        $cs->stylesheet_dir = $skins[$current_skin]['Stylesheet Dir'];
        $cs->stylesheet = $skins[$current_skin]['Stylesheet'];
        $cs->screenshot = $skins[$current_skin]['Screenshot'];
        $cs->description = $skins[$current_skin]['Description'];
        $cs->author = $skins[$current_skin]['Author'];
        $cs->stylesheet_files = $skins[$current_skin]['Stylesheet Files'];
        return $cs;
}


function validate_current_skin() {
	// Don't validate during an install/upgrade.
	if ( defined('WP_INSTALLING') )
		return true;
	if( get_current_skin() == 'Default' )
		return true;
	if ( get_skin() != 'default' && 
		 !file_exists(get_skin_directory() . '/style.css') && 
		 !file_exists(get_skin_directory() . '/style.css.php') ) {
		update_option('skin', 'default');
		do_action('switch_skin', 'Default');
		return false;
	}

	return true;
}

?>
