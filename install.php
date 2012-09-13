<?php ob_start(); ?>
<!DOCTYPE html>
<html>
<!--
 - install.php (c) Shish et all. 2007-2012
 -
 - Initialise the database, check that folder
 - permissions are set properly.
 -
 - This file should be independant of the database
 - and other such things that aren't ready yet
-->
	<head>
		<title>Shimmie Installation</title>
		<link rel="shortcut icon" href="favicon.ico" />
		<link rel='stylesheet' href='lib/shimmie.css' type='text/css'>
		<script src="lib/jquery-1.7.1.min.js"></script>
	</head>
	<body>
<?php if(false) { ?>
		<div id="installer">
			<h1>Install Error</h1>
			<p>Shimmie needs to be run via a web server with PHP support -- you
			appear to be either opening the file from your hard disk, or your
			web server is mis-configured.</p>
			<p>If you've installed a web server on your desktop PC, you probably
			want to visit <a href="http://localhost/">the local web server</a>.<br/><br/>
			</p>
		</div>
		<div style="display: none;">
			<PLAINTEXT>
<?php }
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

/*
 * Compute the path to the folder containing "install.php" and 
 * store it as the 'Shimmie Root' folder for later on.
 *
 * Example:
 *	__SHIMMIE_ROOT__ = '/var/www/shimmie2/'
 *
 */
define('__SHIMMIE_ROOT__', trim(remove_trailing_slash(dirname(__FILE__))) . '/');

// Pull in necessary files
require_once __SHIMMIE_ROOT__."core/util.inc.php";
require_once __SHIMMIE_ROOT__."core/exceptions.class.php";
require_once __SHIMMIE_ROOT__."core/database.class.php";

if(is_readable("data/config/shimmie.conf.php")) die("Already installed");

do_install();

// utilities {{{

/**
  * Strips off any kind of slash at the end so as to normalise the path.
  * @param string $path    Path to normalise.
  * @return string         Path without trailing slash.
  */
function remove_trailing_slash($path) {
	if ((substr($path, -1) === '/') || (substr($path, -1) === '\\')) {
		return substr($path, 0, -1);
	} else {
		return $path;
	}
}

function check_gd_version() {
	$gdversion = 0;

	if (function_exists('gd_info')){
		$gd_info = gd_info();
		if (substr_count($gd_info['GD Version'], '2.')) {
			$gdversion = 2;
		} else if (substr_count($gd_info['GD Version'], '1.')) {
			$gdversion = 1;
		}
	}

	return $gdversion;
}

function check_im_version() {
	if(!ini_get('safe_mode')) {
		$convert_check = exec("convert");
	}
	return (empty($convert_check) ? 0 : 1);
}

function eok($name, $value) {
	echo "<br>$name ... ";
	if($value) {
		echo "<font color='green'>ok</font>\n";
	}
	else {
		echo "<font color='red'>failed</font>\n";
	}
}
// }}}
function do_install() { // {{{
	if(file_exists("data/config/auto_install.conf.php")) {
		require_once "data/config/auto_install.conf.php";
		install_process();
	}
	else if(@$_POST["database_type"] == "sqlite" && isset($_POST["database_name"])) {
		define('DATABASE_DSN', "sqlite:{$_POST["database_name"]}");
		install_process();
	}
	else if(isset($_POST['database_type']) && isset($_POST['database_host']) && isset($_POST['database_user']) && isset($_POST['database_name'])) {
		define('DATABASE_DSN', "{$_POST['database_type']}:user={$_POST['database_user']};password={$_POST['database_password']};host={$_POST['database_host']};dbname={$_POST['database_name']}");
		install_process();
	}
	else {
		ask_questions();
	}
} // }}}
function ask_questions() { // {{{
	$warnings = array();
	$errors = array();

	if(check_gd_version() == 0 && check_im_version() == 0) {
		$errors[] = "
			No thumbnailers cound be found - install the imagemagick
			tools (or the PHP-GD library, of imagemagick is unavailable).
		";
	}
	else if(check_im_version() == 0) {
		$warnings[] = "
			The 'convert' command (from the imagemagick package)
			could not be found - PHP-GD can be used instead, but
			the size of thumbnails will be limited.
		";
	}

	$drivers = PDO::getAvailableDrivers();
	if(
		!in_array("mysql", $drivers) &&
		!in_array("pgsql", $drivers) &&
		!in_array("sqlite", $drivers)
	) {
		$errors[] = "
			No database connection library could be found; shimmie needs
			PDO with either Postgres, MySQL, or SQLite drivers
		";
	}

	$db_m = in_array("mysql", $drivers)  ? '<option value="mysql">MySQL</option>' : "";
	$db_p = in_array("pgsql", $drivers)  ? '<option value="pgsql">PostgreSQL</option>' : "";
	$db_s = in_array("sqlite", $drivers) ? '<option value="sqlite">SQLite</option>' : "";

	$warn_msg = $warnings ? "<h3>Warnings</h3>".implode("\n<br>", $warnings) : "";
	$err_msg = $errors ? "<h3>Errors</h3>".implode("\n<br>", $errors) : "";

	print <<<EOD
		<div id="installer">
			<h1>Shimmie Installer</h1>

			$warn_msg
			$err_msg

			<h3>Database Install</h3>
			<form action="install.php" method="POST">
				<center>
					<table class='form'>
						<tr>
							<th>Type:</th>
							<td><select name="database_type" id="database_type" onchange="update_qs();">
								$db_m
								$db_p
								$db_s
							</select></td>
						</tr>
						<tr class="dbconf mysql pgsql">
							<th>Host:</th>
							<td><input type="text" name="database_host" size="40" value="localhost"></td>
						</tr>
						<tr class="dbconf mysql pgsql">
							<th>Username:</th>
							<td><input type="text" name="database_user" size="40"></td>
						</tr>
						<tr class="dbconf mysql pgsql">
							<th>Password:</th>
							<td><input type="password" name="database_password" size="40"></td>
						</tr>
						<tr class="dbconf mysql pgsql sqlite">
							<th>DB&nbsp;Name:</th>
							<td><input type="text" name="database_name" size="40" value="shimmie"></td>
						</tr>
						<tr><td colspan="2"><input type="submit" value="Go!"></td></tr>
					</table>
				</center>
				<script>
				$(function() {
					update_qs();
				});
				function update_qs() {
					$(".dbconf").hide();
					var seldb = $("#database_type").val();
					$("."+seldb).show();
				}
				</script>
			</form>

			<h3>Help</h3>
					
			<p class="dbconf mysql pgsql">
				Please make sure the database you have chosen exists and is empty.<br>
				The username provided must have access to create tables within the database.
			</p>
			<p class="dbconf sqlite">
				For SQLite the database name will be a filename on disk, relative to
				where shimmie was installed.
			</p>
			
		</div>
EOD;
} // }}}
function install_process() { // {{{
	build_dirs();
	create_tables();
	insert_defaults();
	write_config();
	
	header("Location: index.php");
} // }}}
function create_tables() { // {{{
	try {
		$db = new Database();
		
		if ( $db->count_tables() > 0 ) {
			echo "
				<p>Warning: The Database schema is not empty!</p>
				<p>Please ensure that the database you are installing Shimmie with is empty before continuing.</p>
				<p>Once you have emptied the database of any tables, please hit 'refresh' to continue.</p>";
			exit;
		}
		
		$db->create_table("aliases", "
			oldtag VARCHAR(128) NOT NULL PRIMARY KEY,
			newtag VARCHAR(128) NOT NULL,
			INDEX(newtag)
		");
		$db->create_table("config", "
			name VARCHAR(128) NOT NULL PRIMARY KEY,
			value TEXT
		");
		$db->create_table("users", "
			id SCORE_AIPK,
			name VARCHAR(32) UNIQUE NOT NULL,
			pass CHAR(32),
			joindate SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			class VARCHAR(32) NOT NULL DEFAULT 'user',
			email VARCHAR(128),
			INDEX(name)
		");
		$db->create_table("images", "
			id SCORE_AIPK,
			owner_id INTEGER NOT NULL,
			owner_ip SCORE_INET NOT NULL,
			filename VARCHAR(64) NOT NULL,
			filesize INTEGER NOT NULL,
			hash CHAR(32) UNIQUE NOT NULL,
			ext CHAR(4) NOT NULL,
			source VARCHAR(255),
			width INTEGER NOT NULL,
			height INTEGER NOT NULL,
			posted SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			locked SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
			INDEX(owner_id),
			INDEX(width),
			INDEX(height),
			INDEX(hash),
			FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
		");
		$db->create_table("tags", "
			id SCORE_AIPK,
			tag VARCHAR(64) UNIQUE NOT NULL,
			count INTEGER NOT NULL DEFAULT 0,
			INDEX(tag)
		");
		$db->create_table("image_tags", "
			image_id INTEGER NOT NULL,
			tag_id INTEGER NOT NULL,
			INDEX(image_id),
			INDEX(tag_id),
			UNIQUE(image_id, tag_id),
			FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
			FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
		");
		$db->execute("INSERT INTO config(name, value) VALUES('db_version', 11)");
		$db->commit();
	}
	catch(PDOException $e) {
		// FIXME: Make the error message user friendly
		exit($e->getMessage());
	}
} // }}}
function insert_defaults() { // {{{
	try {
		$db = new Database();
	
		$db->execute("INSERT INTO users(name, pass, joindate, class) VALUES(:name, :pass, now(), :class)", Array("name" => 'Anonymous', "pass" => null, "class" => 'anonymous'));
		$db->execute("INSERT INTO config(name, value) VALUES(:name, :value)", Array("name" => 'anon_id', "value" => $db->get_last_insert_id('users_id_seq')));

		if(check_im_version() > 0) {
			$db->execute("INSERT INTO config(name, value) VALUES(:name, :value)", Array("name" => 'thumb_engine', "value" => 'convert'));
		}
		$db->commit();
	}
	catch(PDOException $e) {
		// FIXME: Make the error message user friendly
		exit($e->getMessage());
	}
} // }}}
function build_dirs() { // {{{
	// *try* and make default dirs. Ignore any errors --
	// if something is amiss, we'll tell the user later
	if(!file_exists("images")) @mkdir("images");
	if(!file_exists("thumbs")) @mkdir("thumbs");
	if(!file_exists("data")  ) @mkdir("data");
	if(!is_writable("images")) @chmod("images", 0755);
	if(!is_writable("thumbs")) @chmod("thumbs", 0755);
	if(!is_writable("data")  ) @chmod("data", 0755);

	if(
		!file_exists("images") || !file_exists("thumbs") || !file_exists("data") ||
		!is_writable("images") || !is_writable("thumbs") || !is_writable("data")
	) {
		print "
			<p>Shimmie needs three folders in it's directory, 'images', 'thumbs', and 'data',
			and they need to be writable by the PHP user.</p>
			<p>If you see this error, if probably means the folders are owned by you, and they need to be
			writable by the web server.</p>
			<p>PHP reports that it is currently running as user: ".$_ENV["USER"]." (". $_SERVER["USER"] .")</p>
			<p>Once you have created these folders and / or changed the ownership of the shimmie folder, hit 'refresh' to continue.</p>
		";
		exit;
	}
} // }}}
function write_config() { // {{{
	$file_content = '<' . '?php' . "\n" .
			"define('DATABASE_DSN', '".DATABASE_DSN."');\n" .
			'?' . '>';
	
	if(!file_exists("data/config")) {
		mkdir("data/config", 0755, true);
	}
	
	if(!file_put_contents("data/config/shimmie.conf.php", $file_content)) {
		$h_file_content = htmlentities($file_content);
		print <<<EOD
		    The web server isn't allowed to write to the config file; please copy
		    the text below, save it as 'data/config/shimmie.conf.php', and upload it into the shimmie
		    folder manually. Make sure that when you save it, there is no whitespace
		    before the "&lt;?php" or after the "?&gt;"

		    <p><textarea cols="80" rows="2">$file_content</textarea>
						
		    <p>One done, <a href='index.php'>Continue</a>
EOD;
		exit;
	}
} // }}}
?>
	</body>
</html>
