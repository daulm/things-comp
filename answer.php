<!DOCTYPE html>
<html>
<head>

</head>
<body>
<?php
include 'db_config.php';
session_start();
if (!isset($_SESSION['Player_ID'])){
	die('Session lost, please reload the app.');
}
$con = mysqli_connect($db_host, $db_username, $db_pw, 'balderdash');
if (!$con){
	die('DB connection failed: '.mysqli_error($con));
}
	
//Look up the clue and time limit
$sql = "SELECT Clue, AnswerTime, DasherID FROM lobby";
$sql .= " WHERE LobbyID=".$_SESSION['Lobby_ID'];
if(!$result = mysqli_query($con, $sql)){
	echo('Cant find code for this lobby');
}	
while($row = mysqli_fetch_row($result)){
	$clue = $row[0];
	$timeleft = $row[1]*60;
	$dasherid = $row[2];
}

//if host, set up new game and change game state of lobby
if(isset($_SESSION['Host'])){
	//check if the game was already created
	$sql = "SELECT * FROM Lobby WHERE GameState='answer' AND LobbyID=".$_SESSION['Lobby_ID'];
	if(!$result = mysqli_query($con, $sql)){
		echo('Unable to check if we already created game');
	}
	if(mysqli_num_rows($result) == 0){
		//create new game 
		$sql = "INSERT INTO games (LobbyID, Clue, DasherID) VALUES (";
		$sql .= $_SESSION['Lobby_ID'].", '".mysql_real_escape_string($clue)."', ".$dasherid.")";
		if(!mysqli_query($con, $sql)){
			echo('Unable to create Game');
		}
		$sql = "SELECT MAX(GameID) FROM games";
		if(!$result = mysqli_query($con, $sql)){
			echo('Unable to find newest Game');
		}
		$row = mysqli_fetch_row($result);
		$_SESSION['Game_ID'] = $row[0];
		
		$sql = "UPDATE lobby SET GameID=".$_SESSION['Game_ID'].", GameState='answer' WHERE LobbyID=".$_SESSION['Lobby_ID'];
		if(!mysqli_query($con, $sql)){
			echo('Unable to sync Game to Lobby');
		}
		
		//pull players who didn't check in recently out of the lobby/game
		$sql = "UPDATE players SET LobbyID=NULL WHERE LastCheck <= (NOW() - INTERVAL 30 SECOND) AND LobbyID=".$_SESSION['Lobby_ID'];
		if(!mysqli_query($con, $sql)){
			echo('Unable to remove idle players');
		}
		
		//set a random order for player names/answers/scores to appear for this game
		$sql = "UPDATE players SET OrderVal=RAND() WHERE LobbyID=".$_SESSION['Lobby_ID'];
		if(!mysqli_query($con, $sql)){
			echo('Unable to randomize player order');
		}
	}
}

//set the gameid to the session
$sql = "SELECT MAX(GameID) FROM lobby";
$sql .= " WHERE LobbyID=".$_SESSION['Lobby_ID'];
if(!$result = mysqli_query($con, $sql)){
	echo('Cant find code for this lobby');
}	
while($row = mysqli_fetch_row($result)){
	$_SESSION['Game_ID'] = $row[0];
}
	
mysqli_close($con);
?>
  
<div class="container">
	<div class="row well well-sm">
		<div class="col-xs-8"><?php echo htmlspecialchars($clue) ?></div>
	  	<div class="col-xs-4">Time Left: <span id="countdown" data-timeleft="<?php echo htmlspecialchars($timeleft) ?>"></span></div>
  	</div>
	
    	<div class="input-group">
      		<textarea class="form-control custom-control" rows="3" placeholder="Enter your answer" name="answertxt" id="answertxt"></textarea>
        	<span class="input-group-addon btn btn-primary" type="button" onclick="submitAnswer(1)">Submit</span>
    	</div>
</div>	
<span id="msglist" data-gamestate="answer"></span>

<div id="footer" class="container text-center"><button type="button" class="btn btn-warning" onclick="if(confirm('You want to Quit?')){mainMenu(1)}">Quit to Main</button></div>
	
</body>
</html>
