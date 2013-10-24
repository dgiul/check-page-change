<?php
	
	date_default_timezone_set('America/Los_Angeles');
	
	// Log file
	$logFile = "log.txt";
	$timestamp = strftime('%Y-%m-%d %H:%M:%S', time());
	$toLog = '';

	$urlToCheck = 'url_to_check';
	$successMessage = 'Looks like it\'s in stock!';
	
	if ( !file_exists($logFile)){
		touch ($logFile);
		$handle = fopen ($logFile, 'r+');
		$toLog = '';
	} else {
		include $logFile;
		$handle = fopen ($logFile, 'a');
	}
	
	// The phone numbers you want send texts too. Sending relies on your carrier supporting being able to send you texts
	// from an email so your mileage may vary. In the US, AT&T lets you send an email to phonenumber@txt.att.net
	$phones = array(
		'##########@txt.att.net'
	);
	
	// Check to see if the user is trying to test sending messages.  This is just to make sure the email to text stuff is working
	// To use, just type something like this into your browser: http://www.your_site.com/folder/index.php?mode=test
	if ($_GET['mode'] == 'test') {

		for ($i = 0; $i < count($phones); $i++) {
			mail($phones[$i], 'Test message','Please ignore');
			$toLog .= "\n".$timestamp." - TEST SENT TO ".$phones[$i];
		};

	} else {

		// Check the URL (the use of @ below will suppress warning messages)
		
		/**
		* CHECK SITE
		*/
		$html = @file_get_contents($urlToCheck);

		// Before getting too far, make sure file_get_contents retrieved content
		if (strpos($http_response_header[0], "200")) { 
		
			// Make sure the document isn't empty before continuing
			if (!is_null($html)) {
			
				// Look for a specific string in the page content and count the occurrences
				// For example, this might be a string saying something like "sorry, this item is out of stock"
				preg_match_all("/This is the text to look for/", $html, $matches);
				
				// Count how many times the string appears in the page content
				$foundCount = count($matches[0]);
				
				// Check the count, and if it's 0 then the sting is not there -- send the notification
				if ($foundCount == 0) {
						for ($i = 0; $i < count($phones); $i++) {
							mail($phones[$i], $successMessage, $urlToCheck);
							$toLog .= "\n".$timestamp." - COUNT IS ".$foundCount." - NOTIFICATION SENT TO ".$phones[$i];
						};
						
						// Write a new log file in case it helps to troubleshoot false positives
						touch ($timestamp.'.log');
						$handleInStock = fopen ($timestamp.'.log', 'r+');
						fwrite ($handleInStock, $html);
						fclose ($handleInStock);
					
				} else {
					$toLog .= "\n".$timestamp." - COUNT IS ".$foundCount." - NO NOTIFICATIONS SENT";
				}
			} else {
				// We got http status 200 back but the document is empty so don't send notices
				$toLog .= "\n".$timestamp." - COUNT IS NULL ( DOCUMENT IS EMPTY) - NO NOTIFICATIONS SENT";
			}
			
		} else {

			// file_get_contents failed, maybe the server is down?
			$toLog .= "\n".$timestamp." - ERROR CHECKING SITE, SEE ".$timestamp.".log"." FOR MORE INFO";
			
			// Write a new log file in case it helps to troubleshoot
			touch ($timestamp.'.log');
			$handleError = fopen ($timestamp.'.log', 'r+'); 
			fwrite ($handleError, $html); 
			fclose ($handleError); 

		}

	}
	
	fwrite ($handle, $toLog);
	fclose ($handle);
	
?>