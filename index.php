<!DOCTYPE html>
<?php
session_start();
?>
<html>
<head>
<title>Balderdash</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="css/style.css">
<script src="js/jquery.min.js"></script>
<script type="text/javascript">
var refresh_lobby = true;
var refresh_review = false;
var refresh_results = false;
var mytimer;
// The rate in milliseconds at which the lobby refreshes
var refresh_speed = 1000;
var $dasherid = 0;
var $hideansid = 0;
var $voterun = false;

$(document).ready(function(){
	initialise();
});

function initialise(){
$(".settings").focusin(function (){
	//stop refreshing the lobby if a user clicks on the settings
	refresh_lobby=false;
	
});	
	
$(".settings").focusout(function (){
	//resume refreshing the lobby if the host clicks away from the settings
	if ($("#msglist").data("gamestate") == "lobby"){
		refresh_lobby=true;
	}
});
	
$('.clicky').click(function() {
	//This function should only be used by the host when changing who is set as the dasher
	var $newdasher = $(this).data("playerid");
	updateDasher($newdasher);
});

}
	
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
			case "vote":
				launchVote(0);
				break;
			case "results":
				launchVote(0);
				break;
			case "complete":
				returnLobby();
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
		initialise();
		if ($("#msglist").data("gamestate") == "answer"){
			launchGame();
		} else {
			refresh_results = false;
			refresh_review = false;
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
	initialise();
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
	initialise();
	refresh_lobby = true;
	setTimeout(showLobby, refresh_speed);
}

function lobbySettings(){
	// change the game settings in the lobby
	var $anstime = $('#anstime').val();
	var $votetime = $('#votetime').val();
	if (!$.isNumeric($anstime) || !$.isNumeric($votetime)){
		alert("Please enter integers");
		return 0;
	}
	$.post("lobby.php?mode=settings", {anstime: $anstime, votetime: $votetime}, function(result){
		$('#bd_content').html(result);
	});
	refresh_lobby = true;
	//setTimeout(showLobby, refresh_speed);
}
	
function updateClue(){
	// let's the dasher update the current clue
	var $clue = $('#cluetxt').val();
	$.post("lobby.php?mode=clue", {clue: $clue}, function(result){
		$('#bd_content').html(result);
	});
	refresh_lobby = true;
	//setTimeout(showLobby, refresh_speed);	
}
	
function updateDasher($dasherid){
	// let's the host switch who the dasher is
	$.post("lobby.php?mode=dasher", {dasherid: $dasherid}, function(result){
		$('#bd_content').html(result);
	});
	refresh_lobby = true;
	//setTimeout(showLobby, refresh_speed);	
}

function updateDasherScore(){
	// let's the host switch who the dasher is
	var $newscore = $('#player_score').val();
	$.post("lobby.php?mode=dasherscore", {dasherscore: $newscore}, function(result){
		$('#bd_content').html(result);
	});
	refresh_lobby = true;
	//setTimeout(showLobby, refresh_speed);	
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
	refresh_review = false;
	refresh_lobby = true;
	$voterun = false;
	initialise();
	setTimeout(showLobby, refresh_speed);
}
	
function timeUp(){
	// this function is called if the timer reaches 0, it checks where it is and has the user send in any completed data
	switch($("#msglist").data("gamestate")){
		case "answer":
			//if it is an answer submission submit whatever they have completed
			submitAnswer(false);
			break;
		case "vote":
			//if it is voting submit nothing and move on to the results
			submitVote(0);
			break;
		case "complete":
			//if voting just completed submit nothing and move on to the results
			submitVote(0);
			break;
		default:
			//if it is anything else, do nothin
			
	}					      
}

function startTimer(duration, display) {
	var timer = duration, minutes, seconds;
	if(mytimer){
		clearInterval(mytimer);
	}
	mytimer = setInterval(function(){
		minutes = parseInt(timer / 60, 10);
		seconds = parseInt(timer % 60, 10);
		minutes = minutes < 10 ? "0" + minutes : minutes;
		seconds = seconds < 10 ? "0" + seconds : seconds;
		display.text(minutes + ":" + seconds);
		if(--timer < 0){
			clearInterval(mytimer);
			timeUp();
		}
	}, 1000);
}

function launchGame(){
	// kick off the game, either because of the game state, or because the host chose to launch the game
	refresh_lobby = false;
	var myreq = $.get("answer.php", function(result){
		$("#bd_content").html(result);
	});

	myreq.done(function(){
		var timeleft = $("#countdown").data("timeleft");
		startTimer(timeleft, $("#countdown"));
	});
}
	
function launchVote(num){
	// kick off the vote menu
	refresh_review = false;
	var myreq = $.get("vote.php", function(result){
		$("#bd_content").html(result);
	});
	
	myreq.done(function(){
		var timeleft = $("#countdown").data("timeleft");
		startTimer(timeleft, $("#countdown"));
	});
	if (num>0){
		$voterun = true;
	}
}
	
function showReview(){
	/* this function waits a few seconds and then refreshes the data in the review screen and then calls itself again
	it looks for a data attribute in the html to see if it is time to kick off voting. */
	var myreq = $.get("review.php?mode=update", function(result){
		if (refresh_review){		
			$("#bd_content").html(result);
		}
	});
		
	myreq.done(function(){
			
		if ($("#msglist").data("gamestate") == "vote"){
			if(!$voterun){
				launchVote(0);
			}
		} else {
			setTimeout(showReview, refresh_speed);
		}
	});
		
}
	
function hide(ansid){
	// this function marks an answer to be hidden, it switches around which buttons are visible on each row
	// and it changes the highlighting of the selected row
	refresh_review = false;
	$hideansid = ansid;
	$(".mybind").show();
	$(".myhide").hide();
	$("#"+ansid+" .mybind").hide();
	$("#"+ansid+" .myhide").hide();
	$("#"+ansid+" .myundo").show();
        $("#"+ansid).addClass("info");	
}
	
function undoHide(){
	// undo the change made when the dasher picked an answer to hide
	refresh_review = true;
	$hideansid = 0;
	$(".myhide").show();
	$(".myundo,.mybind").hide();
	$(".info").removeClass("info");
}
	
function unhide(ansid){
	//This function removes the hidden state of the selected answer
	var posting = $.post("review.php?mode=unbind", {ansid: ansid}, function(result){
		
	});
	refresh_review = true;
	showReview();
}
	
function bind(bindansid){
	//this function hides one answer behind another because they are too similar	
	// it is only called after hide()
	var posting = $.post("review.php?mode=update", {hideansid: $hideansid, bindansid: bindansid}, function(result){
		
	});
	refresh_review = true;
	setTimeout(showReview, refresh_speed);
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
	var posting = $.post("review.php?mode=submit", {ans: ans}, function(result){
		$("#bd_content").html(result);
	});
	clearInterval(mytimer);
	refresh_review = true;
	setTimeout(showReview, refresh_speed);
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
	
function preVote(elem){
	
	$(".btn-primary").hide();
    $(".btn-success").show();
    $(elem).prev().prev().show();
    $(elem).hide();
    $(".panel").removeClass("panel-primary");
    $(".panel").addClass("panel-success");
    $(elem).closest("div").removeClass("panel-success");
    $(elem).closest("div").addClass("panel-primary");	
}
	
function submitVote(ansid){
	// this function submits the vote
	if(ansid == 0){
		var posting = $.get("results.php?mode=skip", function(result){
			$("#bd_content").html(result);
		});
	} else {
		var posting = $.post("results.php?mode=submit", {ansid: ansid}, function(result){
			$("#bd_content").html(result);
		});
	}
	clearInterval(mytimer);
	refresh_results = true;
	setTimeout(showResults, refresh_speed);
}	

</script>
</head>

<body onload="mainMenu(0)">

<div id="bd_content">Loading Things Companion...</div>

</body>
