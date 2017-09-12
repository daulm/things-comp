<!DOCTYPE html>
<?php
session_start();
?>
<html>
<head>
<title>Game of Things</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="css/style.css">
<script src="js/jquery.min.js"></script>
<script type="text/javascript">
var refresh_lobby = true;
var refresh_results = false;
// The rate in milliseconds at which the lobby refreshes
var refresh_speed = 1000;
	
function mainMenu(mode){
	// this function loads the main menu of the game
	refresh_lobby = false;
	var myreq = $.get("main_menu.php?mode="+mode, function(result){
		$("#bd_content").html(result);
	});
	myreq.done(function(){
		switch($("#msglist").data("gamestate")){
			case "lobby":
				returnLobby();
				break;
			case "answer":
				launchGame();
				break;
			case "complete":
				showResults();
				break;
			default:
		
		}
		
	});
}

function showLobby(){
	/* this function waits a few seconds and then refreshes the data in the lobby and then calls itself again
	it looks for a data attribute in the html to see if it is time to launch the game. */
	
	var myreq = $.get("lobby.php?mode=update", function(result){
		if (refresh_lobby){
			$("#bd_content").html(result);
		}
	});
	
	myreq.done(function(){
		if ($("#msglist").data("gamestate") == "answer"){
			launchGame();
		} else {
			refresh_results = false;
			setTimeout(showLobby, refresh_speed);
		}
	});
	
	
}

function hostGame(){
	// this function initiates a new game and returns the lobby with a 4 letter password
	var name = $("#playername").val();
	if (name.length > 20 || name.length < 1){
		alert("Name must be 1-20 characters");
		return 0;
	}
	var posting = $.post("lobby.php?mode=create", {pname: name}, function(result){
		$("#bd_content").html(result);
	});
	refresh_lobby = true;
	setTimeout(showLobby, refresh_speed);
}

function enterLobby(){
	// this function joins a lobby that already exists
	var name = $("#playername").val();
	var code = $("#code").val();
	if (name.length > 20 || name.length < 1){
		alert("Name must be 1-20 characters");
		return 0;
	}
	if (code.length != 4){
		alert("Code must be 4 characters");
		return 0;
	}
	var posting = $.post("lobby.php?mode=join", {pname: name, code: code}, function(result){
		$("#bd_content").html(result);
	});
	refresh_lobby = true;
	setTimeout(showLobby, refresh_speed);
}
	
function stopRefresh(){
	//stop refreshing if the host is trying to change the settings
	refresh_lobby=false;	
}


function returnLobby(){
	$.get("lobby.php?mode=return", function(result){
		$("#bd_content").html(result);
	});
	refresh_results = false;
	refresh_lobby = true;
	setTimeout(showLobby, refresh_speed);
}
	

function launchGame(){
	// kick off the game, either because of the game state, or because the host chose to launch the game
	refresh_lobby = false;
	var myreq = $.get("answer.php", function(result){
		$("#bd_content").html(result);
	});
}

	
	
function submitAnswer(check){
	// this function submits the answer
	var ans = $("#answertxt").val();
	if(check){
		if (ans.length > 2000 || ans.length < 1){
			alert("Answer must be 1-2000 characters");
			return 0;
		}
	}
	var posting = $.post("results.php?mode=submit", {ans: ans}, function(result){
		$("#bd_content").html(result);
	});
	refresh_results = true;
	setTimeout(showResults, refresh_speed);
}
	
function showResults(){
	/* this function waits a few seconds and then refreshes the data in the review screen and then calls itself again
	it looks for a data attribute in the html to see if it is time to launch the game. */
	
	
	var myreq = $.get("results.php?mode=update", function(result){
		if (refresh_results){
			$("#bd_content").html(result);
		}
	});
		
	myreq.done(function(){
			
		if ($("#msglist").data("gamestate") == "complete"){
			refresh_results = false;
		} else {
			setTimeout(showResults, refresh_speed);
		}
	});
		
}

function showAll(){
	var myreq = $.get("results.php?mode=showall", function(result){
		if (refresh_results){
			$("#bd_content").html(result);
		}
	});
}

function endAnswers(){
	var myreq = $.get("results.php?mode=endans", function(result){
		if (refresh_results){
			$("#bd_content").html(result);
		}
	});
}	

</script>
</head>

<body onload="mainMenu(0)">

<div id="bd_content">Loading Things Companion...</div>

</body>
