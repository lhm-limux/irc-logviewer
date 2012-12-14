<?php

include_once(dirname(__FILE__).'/PathValidator.class.php');

// Load config file
if (!array_key_exists('irc-logviewer-config', $GLOBALS))
	$GLOBALS['irc-logviewer-config'] = parse_ini_file("config.ini", true);

// Set timezone to whatever the time zone of the server currently is (assumes
// the logs are timestamped using system time, which is overwhelming likely).
// TODO: Make configurable option in config.ini
// FIXME: This does not work repliaibly on PHP for Windows (because on
// Windows the zone value returned is not always in a compatible format).
@date_default_timezone_set(date("T"));

class GetConversation {

	public $conversation = array();

	public function __construct($server, $channel, $startTime, $endTime, $keywords) {

		// Replace leading/trailing spaces and all multiple spaces with single spaces.
		$keywords = preg_replace("/( )+/", " ", $keywords);
		$keywords = preg_replace("/ $/", "", $keywords);
		$keywords = preg_replace("/^ /", "", $keywords);

		$startTimeStamp = strtotime($startTime);
		$endTimeStamp = strtotime($endTime);
		$date = date('Y-m-d', strtotime($startTime));

		$baseLogDir = $GLOBALS['irc-logviewer-config']['irc-logviewer']['irc_log_dir'];

		// This will throw an exception if the $server or $channel names are not valid
		PathValidator::validateChannelLogDir($baseLogDir, $server, $channel);

		// Loop through each file in the log diretory and look for matches using searchInFile()
		// If a match is found a SearchResult object will be pushed into $this->searchResults
		$logDir = $baseLogDir."/".addslashes($server)."/".addslashes($channel);

		$pathToFile = "";
		$dirHandle = opendir($logDir);
		$i = 0;
		while(($filename = readdir($dirHandle)) !== false) {
			if(substr($filename, 0, 1) != ".") { // Don't include hidden directories (i.e. beginning with a ".")
				if (is_file($logDir."/".$filename)) { // Only open files

					// Get the day from the filename (this is why filenames must have the date
					// in them, in the e.g. "mylog_YYYY-MM-DD.log" or "mylogYYYYMMDD.txt", etc..
					$dateFromFilename = preg_replace('/^(.*)(\d{4})(.*?)(\d{2})(.*?)(\d{2}?)(.*?)$/', "$2-$4-$6", $filename);
					$dateRangeStart = strtotime($dateFromFilename." 00:00:00");
					$dateRangeEnd = strtotime($dateFromFilename." 23:59:59");

					if ($startTimeStamp >= $dateRangeStart && $startTimeStamp <= $dateRangeEnd) {
						$pathToFile = $logDir."/".$filename;
						break;
					}

				}
			}
			$i++;
		}
		closedir($dirHandle);

		// TODO: Make these config options
		$startTimeStamp = $startTimeStamp - (60 * 5); // Show leading 5 minutes
		$endTimeStamp = $endTimeStamp + (60 * 60); // Show trailing 60 minutes

		// Min / Max lines (so that even if a channel is really quiet or really busy, responses are useful)
		$minLines = 25; // Good when people ask questions during the night that are not answered for hours
		$maxLines = 250; // TODO: Create UI option so users can load more of a convo if they really want

		$lineCount = 0;
		$matchingLineCount = 0;
		$fileHandle = fopen($pathToFile, "r") or die("Unable to open IRC log file for reading.");
		while(!feof($fileHandle)) {
			$line = fgets($fileHandle);

			$lineCount++;

			// Get timestamp (based on filename + time on line where match was found)
			@list($time, $username, $msg) = explode(' ', $line, 3);
			$username = preg_replace("/[^A-z0-9:_()\\|-]/", "", $username);
			$time = preg_replace("/[^0-9:]/", "", $time);
			$timestamp = strtotime($date." ".$time);

			$logLine = null;
			if ($time && $username) {
				$msg = preg_replace("/\n$/", "", $msg);
				$msg = htmlentities($msg);
				$msg = $this->highliteKeywords($msg, $keywords);

				if ($timestamp >= $startTimeStamp) {
					$logLine = array('line' => $lineCount, 'time' => $time, 'user' => $username, 'msg' => $msg);
					$matchingLineCount++;
				}

			} else {
				// Handle system messages & emotes (i.e. lines without a username)
				@list($time, $msg) = explode(' ', $line, 2);
				$time = preg_replace("/[^0-9:]/", "", $time);
				$timestamp = strtotime($date." ".$time);

				$msg = preg_replace("/\n$/", "", $msg);
				$msg = htmlentities($msg);
				$msg = $this->highliteKeywords($msg, $keywords);

				if ($timestamp >= $startTimeStamp) {
					$logLine = array('line' => $lineCount, 'time' => $time, 'msg' => $msg);
					$matchingLineCount++;
				}
			}

			if ($logLine !== null)
				array_push($this->conversation,$logLine);

			// Only attempt to exit if we have got at least number of lines in $minLines
			if ($matchingLineCount >= $minLines)
				if ($timestamp > $endTimeStamp)
					break;

			// Exit early if we have hit $maxLines
			if ($matchingLineCount >= $maxLines)
				break;

		}
		fclose($fileHandle);

		return $this->conversation;
	}
}

?>
