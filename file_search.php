<!DOCTYPE html>
<html lang="en">
<head>
	<title>File Search</title> 
	<meta charset="utf-8">
	<style type="text/css">
		::selection { background-color: #E13300; color: white; }
		::-moz-selection { background-color: #E13300; color: white; }

		body {
			background-color: #fff;
			margin: 40px;
			font: 13px/20px normal Helvetica, Arial, sans-serif;
			color: #4F5155;
		}

		a {
			color: #003399;
			background-color: transparent;
			font-weight: normal;
		}

		h1, h2 {
			color: #444;
			background-color: transparent;
			border-bottom: 1px solid #D0D0D0;
			font-size: 17px;
			font-weight: normal;
			margin: 0 0 14px 0;
			padding: 10px 15px 6px 15px;
		}

		h2 {
			font-size: 14px;
			font-weight: bold;
		}

		code {
			font-family: Consolas, Monaco, Courier New, Courier, monospace;
			font-size: 12px;
			background-color: #f9f9f9;
			border: 1px solid #D0D0D0;
			color: #002166;
			display: block;
			margin: 14px 0 14px 0;
			padding: 12px 10px 12px 10px;
		}

		#body {
			margin: 0 15px 0 15px;
		}

		p.footer {
			text-align: right;
			font-size: 11px;
			border-top: 1px solid #D0D0D0;
			line-height: 32px;
			padding: 0 10px 0 10px;
			margin: 20px 0 0 0;
		}

		#container {
			margin: 10px;
			border: 1px solid #D0D0D0;
			box-shadow: 0 0 8px #D0D0D0;
		}
	</style>
</head>
<body>
	<div id="container">
		<h1>File Search</h1>
		<div id="body">
			<p>
				<form method="get" action="<?= basename($_SERVER['PHP_SELF']) ?>">
					Find text: <input type="text" name="search_text" value="">
					<input type="submit" value="Find it">
				</form>
			</p>
		</div>
	</div>

	<?php
	function scan_directory_recursively($directory, $filter=FALSE, $search_text) {
		global $exclude;
		$directory_tree = array();
		// if the path has a slash at the end we remove it here
		if (substr($directory,-1) == '/') {
			$directory = substr($directory,0,-1);
		}

		// if the path is not valid or is not a directory ...
		if (!file_exists($directory) || !is_dir($directory)) {
			// ... we return false and exit the function
			return FALSE;
		// ... else if the path is readable
		} elseif (is_readable($directory)) {
			// we open the directory
			$directory_list = opendir($directory);

			// and scan through the items inside
			while (FALSE !== ($file = readdir($directory_list))) {
				// if the filepointer is not the current directory
				// or the parent directory
				if($file != '.' && $file != '..' && !in_array($file, $exclude)) {
					// we build the new path to scan
					$path = $directory.'/'.$file;

					// if the path is readable
					if(is_readable($path)) {
						// we split the new path by directories
						$subdirectories = explode('/',$path);

						// if the new path is a directory
						if(is_dir($path)) {
							// add the directory details to the file list
							scan_directory_recursively($path, $filter, $search_text);
						} elseif(is_file($path)) { // if the new path is a file
							// get the file extension by taking everything after the last dot
							$end_subdirectories = explode('.', end($subdirectories));
							$extension = end($end_subdirectories);

							// if there is no filter set or the filter is set and matches
							if($filter === FALSE || $filter == $extension) {
								// add the file details to the file list
								search_this_file($path, $search_text);
							}
						}
					}
				}
			}

			// close the directory
			closedir($directory_list); 

			// return file list
			return $directory_tree;
		} else { // if the path is not readable ...
			// ... we return false
			return FALSE;	
		}
	}

	function search_this_file($file, $search_text) {
		$show_file = '';
		if (file_exists($file)) {
			$show_file .= '<div id="container">';
			$show_file .= '<h2>' . $file . '</h2>';
			$show_file .= '<div id="body">';

			// put file into an array to be scanned
			$lines = file($file);
			$found_line = 'false';
			$found = 'false';

			// loop through the array, show line and line number
			$cnt_lines = 0;
			$cnt_found = 0;
			foreach ($lines as $line_num => $line) {
				$cnt_lines++;
				if (stristr(strtoupper($line), $search_text)) {
					$found_line= 'true';
					$found = 'true';
					$cnt_found++;
					$show_file .= "<code>Line #<strong>{$line_num}</strong> : " ;

					//prevent db pwd from being displayed, for sake of security
					$show_file .= (substr_count($line,"'DB_SERVER_PASSWORD'")) ? '***HIDDEN***' : htmlspecialchars($line);
					$show_file .= "</code>";
				} else {
					if ($cnt_lines >= 5) {
						// $show_file .= ' .';
						$cnt_lines=0;
					}
				}
			}
		}

		$show_file .= '</p></div></div>' . "\n";
		if ($found == 'true') print $show_file;
	}

	$exclude = array('_shrub', '_old', 'images');
	if (isset($_GET['search_text']) && $_GET['search_text'] && strlen(trim($_GET['search_text'])) > 0) {
		$search_text = stripslashes($_GET['search_text']);
		scan_directory_recursively('.', 'php', $search_text);
		scan_directory_recursively('.', 'css', $search_text);
		scan_directory_recursively('.', 'html', $search_text);
	}
	?>
</body>
</html>