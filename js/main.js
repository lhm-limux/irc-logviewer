
jQuery(document).ready(function() {			
	jQuery(window).resize(function() {
		ircLogSearch.redrawWindow();
	});
					
	// Set layout correctly on page load
	ircLogSearch.redrawWindow();

	// Get list of IRC servers on page load
	ircLogSearch.populateIrcServerList();
});

var ircLogSearch = {};

ircLogSearch.populateIrcServerList = function() {

	jQuery.ajax({		
		url: "ajax/ListServers.php?timestamp=" + (new Date().getTime().toString()),
		type: "GET",
		dataType: "json",
		success: function(result) {

			$("#ircServer").html("");
			$("#ircChannel").html("");

			for (var i = 0; i < result.length; i++) { 
				var options = $('#ircServer').attr('options');
				options[options.length] = new Option(result[i], result[i]);			
			}	
		
			// Calling this method sucessfully always triggers the IRC Channel List to be re-populated too.
			ircLogSearch.populateIrcChannelList();	
		}
		
	});
}

ircLogSearch.populateIrcChannelList = function() {

	var server = $('#ircServer option:selected').val();

	jQuery.ajax({		
		url: "ajax/ListChannels.php?timestamp=" + (new Date().getTime().toString()) + "&server=" + encodeURIComponent(server),
		type: "GET",
		dataType: "json",
		success: function(result) {

			$("#ircChannel").html("");
			
			for (var i = 0; i < result.length; i++) {
					var options = $('#ircChannel').attr('options');
					options[options.length] = new Option(result[i], result[i]);			
			}
		}
		
	});
}
	
	
ircLogSearch.selectConversation = function(element, server, channel, startTime, endTime, keywords) {
	$(".conversation").removeClass("conversationSelected");
	$(element).addClass("conversationSelected");
	
	ircLogSearch.getConversation(server, channel, startTime, endTime, keywords);	
}


ircLogSearch.getConversation = function(server, channel, startTime, endTime, keywords) {

	jQuery('#ircLogSearchResultsLogViewWrapper').html('<div class="heading">Chat Log</div>');	
	
	$.ajax({		
		url: "ajax/GetConversation.php?timestamp=" + (new Date().getTime().toString()) + "&server=" + encodeURIComponent(server) + "&channel=" + encodeURIComponent(channel) + "&startTime=" + encodeURIComponent(startTime)+ "&endTime=" + encodeURIComponent(endTime)+"&keywords="+keywords,
		type: "GET",
		dataType: "json",
		success: function(json) {

			jQuery('#ircLogSearchResultsLogView').html('<div class="heading">Chat Log: ' + channel +'</div>'
                                                      +'<div id="ircLogSearchResultsLogViewWrapper"></div>');		
			ircLogSearch.redrawWindow();
		
			var rowClass = "oddRow";
			for (var i = 0; i < json['conversation'].length; i++) {
				
				if (json['conversation'][i].user) {
					jQuery('#ircLogSearchResultsLogViewWrapper').append(	'<div class="' + rowClass + '">'							
																			+'<div class="time">' + json['conversation'][i].time + '</div>'
																			+'<div class="user">&lt;' + json['conversation'][i].user + '&gt;</div>'
																			+'<span class="msg">' + json['conversation'][i].msg + '</span>'
																			+'</div>'
																		);
				} else {
					jQuery('#ircLogSearchResultsLogViewWrapper').append(	'<div class="' + rowClass + '">'							
																			+'<div class="time">' + json['conversation'][i].time + '</div>'
																			+'<span class="systemMsg">' + json['conversation'][i].msg + '</span>'
																			+'</div>'
																		);
				}
																	
				(rowClass == "oddRow") ? rowClass = "evenRow" : rowClass = "oddRow";									

			}		
		}
		
	});
	
	ircLogSearch.redrawWindow();
				
	return;	
}
	
ircLogSearch.search = function() {


	var server = document.getElementById('ircServer').value;
	var channel = document.getElementById('ircChannel').value;
	var keywords = document.getElementById('keywords').value;

	// Todo: Impliment href query string, so URL's of results can be copy/pasted
	//window.location.href = "#q=search&server="+encodeURIComponent(server) + "&channel=" + encodeURIComponent(channel) + "&keywords=" + encodeURIComponent(keywords);
	
	if (keywords == "")
		return;		
	
	// Reset results view
	jQuery('#searchResults').html('Searching...');	
	jQuery('#ircLogSearchResultsLogView').html('<div class="heading">Chat Log</div>');	
	ircLogSearch.redrawWindow();
					
	$.ajax({		
		url: "ajax/Search.php?timestamp=" + (new Date().getTime().toString()) + "&server=" + encodeURIComponent(server) + "&channel=" + encodeURIComponent(channel) + "&keywords=" + encodeURIComponent(keywords),
		type: "GET",
		dataType: "json",
		success: function(json) {
			
			// If we get a response with any matches, immedaitely start getting the first entry in the background
			if (json['searchResults'].length > 0)
				ircLogSearch.getConversation(server, channel, json['searchResults'][0].startTime, json['searchResults'][0].endTime, keywords);

			
			jQuery('#searchResults').html('');
			

			for (var i = 0; i < json['searchResults'].length; i++) {
								
				var conversationClass = "conversation";		
				if (i == 0)
					conversationClass = "conversation conversationSelected";				
			
				var usersHtml = '';
				for (user in json['searchResults'][i].users) {
					if (usersHtml != '')
						usersHtml += ', ';
						
					usersHtml += '<div class="ircConversationParticipant">' + user + '</div> (' + json['searchResults'][i].users[user] + ')';
					
				}

				var keywordsHtml = '';
				for (keyword in json['searchResults'][i].keywords) {
					if (keywordsHtml != '')
						keywordsHtml += ', ';
						
					keywordsHtml += '<div class="ircConversationKeyword">' + keyword + '</div> (' + json['searchResults'][i].keywords[keyword] + ')';
					
				}
				
				var duration = Math.floor(json['searchResults'][i].duration / 60);
				if (duration < 6) {
					if (json['searchResults'][i].startTime == json['searchResults'][i].endTime) {
						duration = "Mentioned once";
					} else {
						duration = "Less than 5 minutes";				
					}
				} else if (duration < 60) {
					duration += " minutes";
				} else {
					duration = Math.floor(duration / 60);
					if (duration == 1) {
						duration += " hour";
					} else {
						duration += " hours";
					}
				}
										
				jQuery('#searchResults').append(	'<div class="' + conversationClass + '" '
																    +'onclick="ircLogSearch.selectConversation(this, \''+server+'\', \''+channel+'\', \'' + json['searchResults'][i].startTime+'\', \'' + json['searchResults'][i].endTime+'\', \''+encodeURIComponent(keywords)+'\');">'

															+'<div><div class="ircConversationLabel">Start:</div>' + json['searchResults'][i].startTime + '</div>'
															+'<div><div class="ircConversationLabel">End:</div>' + json['searchResults'][i].endTime + '</div>'
															+'<div><div class="ircConversationLabel">Duration:</div>' + duration + '</div>'
															+'<div><div class="ircConversationLabel">Keywords:</div><div class="ircConversationValues">' + keywordsHtml + '</div></div>'
															+'<div><div class="ircConversationLabel">Users:</div><div class="ircConversationValues">' + usersHtml + '</div></div>'
															+'<div class="selectedArrow">&gt;</div>'
															+'</div>');	
			}
											
			ircLogSearch.redrawWindow();				
				
		}
	});	
	
	return false;
};

ircLogSearch.redrawWindow = function() {
	  var windowHeight = 0;
	  if( typeof( window.innerWidth ) == 'number' ) {
	    //Non-IE
	    windowHeight = window.innerHeight;
	  } else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
	    //IE 6+ in 'standards compliant mode'
	    windowHeight = document.documentElement.clientHeight;
	  } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
	    //IE 4 compatible
	    windowHeight = document.body.clientHeight;
	  }
	  
	//var newHeight = windowHeight - 331;
	if (windowHeight < 200)
		windowHeight = 400;
		
	if (document.getElementById('searchResults'))
		document.getElementById('searchResults').style.height = (windowHeight - 340) + "px";	
	if (document.getElementById('ircLogSearchResultsLogViewWrapper'))	
		document.getElementById('ircLogSearchResultsLogViewWrapper').style.height = (windowHeight - 70) + "px";	

}	
