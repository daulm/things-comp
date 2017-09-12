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
$con = mysqli_connect($db_host, $db_username, $db_pw, 'things');
if (!$con){
	die('DB connection failed: '.mysqli_error($con));
}
	

//if host, set up new game and change game state of lobby
if(isset($_SESSION['Host'])){
	//check if the game was already created
	$sql = "SELECT * FROM Lobby WHERE GameState='answer' AND LobbyID=".$_SESSION['Lobby_ID'];
	if(!$result = mysqli_query($con, $sql)){
		echo('Unable to check if we already created game');
	}
	if(mysqli_num_rows($result) == 0){
		//pull players who didn't check in recently out of the lobby/game
		$sql = "UPDATE players SET LobbyID=NULL WHERE LastCheck <= (NOW() - INTERVAL 30 SECOND) AND LobbyID=".$_SESSION['Lobby_ID'];
		if(!mysqli_query($con, $sql)){
			echo('Unable to remove idle players');
		}
		
		//create new game 
		$sql = "INSERT INTO games g (g.LobbyID, g.ReaderID) VALUES (";
		$sql .= $_SESSION['Lobby_ID'].", (SELECT IF(ISNULL(MIN(p.PlayerID)),";
		$sql .= " (SELECT MIN(f.PlayerID) FROM players f WHERE f.LobbyID=".$_SESSION['Lobby_ID']."), MIN(p.PlayerID))";
		$sql .= "FROM players p, lobby l, games g1 WHERE g1.GameID = l.GameID AND l.LobbyID=".$_SESSION['Lobby_ID'];
		$sql .= "AND p.LobbyID =".$_SESSION['Lobby_ID']." AND p.PlayerID > g1.ReaderID))";
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
		
	}
}

//set the gameid and readerid to the session
$sql = "SELECT l.GameID, g.ReaderID FROM lobby l, games g";
$sql .= " WHERE g.GameID - l.GameID l.LobbyID=".$_SESSION['Lobby_ID'];
if(!$result = mysqli_query($con, $sql)){
	echo('Cant find code for this lobby');
}	
while($row = mysqli_fetch_row($result)){
	$_SESSION['Game_ID'] = $row[0];
	$_SESSION['Reader_ID'] = $row[1];
}

if($_SESSION['Reader_ID'] == $_SESSION['Player_ID']){
	
	echo '<div class="container alert alert-info"><strong>Note </strong>You were selected to read the answers out loud.</div>';
}
	
mysqli_close($con);
?>
  
<div class="container">
	
    	<div class="input-group">
      		<textarea class="form-control custom-control" rows="3" placeholder="Enter your answer" name="answertxt" id="answertxt"></textarea>
        	<span class="input-group-addon btn btn-primary" type="button" onclick="submitAnswer(1)">Submit</span>
    	</div>
</div>	

<span id="msglist" data-gamestate="answer"></span>

<div id="footer" class="container text-center"><button type="button" class="btn btn-warning" onclick="if(confirm('You want to Quit?')){mainMenu(1)}">Quit to Main</button></div>
	
</body>
</html>
