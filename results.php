<!DOCTYPE html>
<html>
<head>

</head>
<body>
<?php
include 'db_config.php';
session_name('things');
session_start();
if (!isset($_SESSION['Player_ID'])){
	die('Session lost, please reload the app.');
}
$con = mysqli_connect($db_host, $db_username, $db_pw, 'things');
if (!$con){
	die('DB connection failed: '.mysqli_error($con));
}
$doneans = FALSE;
$action_type = $_GET['mode'];
switch ($action_type){
	case "submit":
		// Add the answer
		$sql = "INSERT IGNORE INTO answers (GameID, PlayerID, AnswerText, OrderVal) VALUES (";
		$sql .= $_SESSION['Game_ID'].", ".$_SESSION['Player_ID'].", '".mysql_real_escape_string($_POST['ans'])."', RAND())";
		if(!mysqli_query($con, $sql)){
			echo('Unable to submit the answer');
		}
	case "endans":		
		$doneans = TRUE;
		//We could have a query here that pulls players from the lobby who haven't submitted answers
		// otherwise answers and players might pop in after the rest of the answers were read
		
		break;
	case "showall":
		$sql = "UPDATE lobby SET GameState='complete' WHERE LobbyID=".$_SESSION['Lobby_ID'];
		if(!mysqli_query($con, $sql)){
			echo('Unable to mark game as complete');
		}
	default:
}
	
//check the game state
$sql = "SELECT GameState FROM Lobby WHERE LobbyID=".$_SESSION['Lobby_ID'];
if(!$result = mysqli_query($con, $sql)){
	echo('Unable to check the game state');
}
$row = mysqli_fetch_row($result);
$gamestate = $row[0];
echo '<span id="msglist" data-gamestate="'.$gamestate.'"></span>';
	
if($gamestate != "complete"){
	//People are still voting/reviewing
	echo '<div class="container alert alert-danger"> Please wait for players to complete their answers</div>';
	
	// check if all players have submitted votes
	$sql = "SELECT p.PlayerID, p.PlayerName FROM players p, lobby l";
	$sql .= " WHERE p.LobbyID = l.LobbyID AND l.LobbyID=".$_SESSION['Lobby_ID'];
	$sql .= " AND NOT EXISTS (SELECT a.AnswerID FROM answers a WHERE a.PlayerID = p.PlayerID";
	$sql .= " AND a.GameID = l.GameID) ORDER BY p.PlayerID";
	if(!$slowplayers = mysqli_query($con, $sql)){
		echo('Cant check whether voting is done.');
	}		
	if(mysqli_num_rows($slowplayers) == 0){
		$doneans = TRUE;	
	} else {
		// display the players yet to vote
		while($row = mysqli_fetch_row($slowplayers)){
			echo '<div class="container text-center alert alert-info">'.$row[1].'</div>';
			
		}
		if($_SESSION['Reader_ID'] == $_SESSION['Player_ID']){
			//show the button to end the answer window
			echo '<div class="container text-center"><button type="button" class="btn btn-danger" onclick="endAnswers()">End time for submitting answers</button></div>';
		}
	}
	if($_SESSION['Reader_ID'] == $_SESSION['Player_ID'] && $doneans){
		//view list of answers to be read out
		$sql = "SELECT AnswerText FROM answers WHERE GameID=".$_SESSION['Game_ID']." ORDER BY OrderVal";
		if(!$result = mysqli_query($con, $sql)){
			echo('Cant find answers.');
		}
		while($row = mysqli_fetch_row($result)){
			echo '<div class="container well well-sm">'.$row[0].'</div>';
		}
		echo '<div class="container text-center"><button type="button" class="btn btn-info" onclick="showAll()">Done reading, show all players the options</button></div>';
	}
	

} else {
	//show the game board
	echo '<div class="container-fluid"><div class="col-xs-4">';
	//pull the players
	$sql = "SELECT PlayerName FROM players WHERE LobbyID=".$_SESSION['Lobby_ID']." ORDER BY PlayerID";
	if(!$result = mysqli_query($con, $sql)){
		echo('Cant find the players.');
	}
	while($row = mysqli_fetch_row($result)){
		echo '<div class="row"><button type="button" class="btn btn-success btn-block" style="white-space: normal;" onclick="$(this).toggleClass(\'btn-success\')">'.$row[0].'</button></div>';
	}
	echo '</div><div class="col-xs-8">';
	//pull the answers
	$sql = "SELECT AnswerText FROM Answers WHERE GameID=".$_SESSION['Game_ID']." ORDER BY OrderVal";
	if(!$result = mysqli_query($con, $sql)){
		echo('Cant find the answers.');
	}
	while($row = mysqli_fetch_row($result)){
		echo '<div class="row"><button type="button" class="btn btn-success btn-block" style="white-space: normal;" onclick="$(this).toggleClass(\'btn-success\')">'.$row[0].'</button></div>';
	}
	echo '</div></div>';	
	
}
	

mysqli_close($con);
?>
<br>
<div id="footer" class="container text-center"><button type="button" class="btn btn-info" onclick="returnLobby()">Return to the Lobby</button></div>
<div id="footer" class="container text-center"><button type="button" class="btn btn-warning" onclick="if(confirm('You want to Quit?')){mainMenu(1)}">Quit to Main</button></div>
</body>
</html>
