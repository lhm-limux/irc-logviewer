<?php
	// Version string in URLs is used to prevent unwanted caching of JS and CSS files following upgrades.
	// As a side effect, also causes browsers to more aggressively cache as long as it remains unchanged.
	$version = "0.24b";

	if (!file_exists(dirname(__FILE__) . '/lib/config.ini'))
		die("<b>Error:</b> ./lib/config.ini not found! Please copy <i>config.ini-dist</i> to <i>config.ini</i> and configure appropriately.");
	if (!isset($searchKeywords))
		$searchKeywords = "";
?>
<!DOCTYPE html>
<html>
	<head>
		<title>FireBreath IRC Log Viewer</title>
		<link rel="stylesheet" href="/javascript/jquery-ui/css/smoothness/jquery-ui.min.css?v=<?php echo $version ?>" />
		<link rel="stylesheet" href="css/main.css?v=<?php echo $version ?>" />
		<script type="text/javascript" src="/javascript/jquery/jquery.min.js?v=<?php echo $version ?>"></script>
		<script type="text/javascript" src="/javascript/jquery-ui/jquery-ui.min.js?v=<?php echo $version ?>"></script>
		<script type="text/javascript" src="/javascript/jquery-ui/ui/jquery.ui.datepicker.min.js?v=<?php echo $version ?>"></script>
		<script type="text/javascript" src="/javascript/jquery-ui/ui/jquery.effects.core.min.js?v=<?php echo $version ?>"></script>
		<script type="text/javascript" src="/javascript/jquery-ui/ui/jquery.ui.widget.min.js?v=<?php echo $version ?>"></script>
		<script type="text/javascript" src="/javascript/jquery-ui/ui/jquery.ui.accordion.min.js?v=<?php echo $version ?>"></script>
		<script src="js/main.js?v=<?php echo $version ?>"></script>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	</head>
	<body>

		<!-- Header -->
		<div id="header">
			<h1>FireBreath IRC Log Viewer</h1>
			<div class="version">Version <?php echo $version ?></div>
		</div>

		<form id="ircLogSearchForm" name="ircLogSearchForm" method="get" onsubmit="ircLogSearch.search(); return false;">
		<!-- Search Results -->
			<div id="leftNavigation">
				<!-- Select Server / Channel -->
				<div id="selectChannel">
					<label for="ircServer">Server:</label>
					<select id="ircServer" name="server" onchange="ircLogSearch.populateIrcChannelList();"></select>
					<label for="ircChannel">Channel:</label>
					<select id="ircChannel" name="channel"></select>
				</div>

				<!-- Search Input -->
				<div id="search">
					<div id="searchBox">
						<input type="text" id="keywords" name="keywords" value="<?php echo $searchKeywords ?>" />
						<button type="button" name="search" onclick="ircLogSearch.search(); return false;" value="search">Search</button>
					</div>
				</div>

				<div id="leftNavigationAccordion">
					<h3><a href="#">Browse Logs</a></h3>
					<div>
						<div id="datePicker"></div>
					</div>
					<h3><a href="#">Search Results</a></h3>
					<div>
						<div id="searchResults">
							<p>No results to display</p>
						</div>
					</div>
				</div>

			</div>

			<div id="ircLogSearchResultsLogView"></div>
		</form>

		<!-- Footer -->
		<div id="footer">
			<a href="http://validator.w3.org/check?uri=referer">W3C Validator</a>
		</div>
	</body>
</html>
