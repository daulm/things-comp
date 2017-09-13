<!DOCTYPE html>
<?php
include 'db_config.php';

// First check and see if the player was in a Lobby, then remove them from the Lobby
session_name('things');
session_start();
// establish connection to DB
$con = mysqli_connect($db_host, $db_username, $db_pw, 'things');
if (!$con){
	die('DB connection failed: '.mysqli_error($con));
}

$gamestate = "default";

if($_GET['mode'] == 1){
	unset($_SESSION['Host']);
	unset($_SESSION['Game_ID']);
	unset($_SESSION['Dasher']);


	if (isset($_SESSION['Lobby_ID']) AND isset($_SESSION['Player_ID'])) {
		// query to purge player from Lobby
		$sql = "UPDATE players SET LobbyID=NULL WHERE PlayerID=".$_SESSION['Player_ID'];
		if(!mysqli_query($con, $sql)){
			echo('Unable to clear player from Lobby.');
		}
		unset($_SESSION['Lobby_ID']);
	}
} 

if (isset($_SESSION['Lobby_ID'])){
	// check the gamestate in the lobby
	$sql = "SELECT GameState FROM lobby WHERE LobbyID=".$_SESSION['Lobby_ID'];
	if(!$result = mysqli_query($con, $sql)){
		echo('Unable to find old lobby');
	}
	while($row = mysqli_fetch_row($result)){
		$gamestate = $row[0];
	}
}
	


$player_name = "";

if (isset($_SESSION['Player_ID'])) {
	//query to pull player name
	$sql = "SELECT PlayerName FROM players WHERE PlayerID=".$_SESSION['Player_ID'];
	if(!$result = mysqli_query($con, $sql)){
		echo('Unable to find player name');
	}
	while($row = mysqli_fetch_row($result)){
		$player_name = $row[0];
	}
} else {
	$sql = "INSERT INTO players (PlayerName, LobbyID) VALUES ('Newbie', NULL)";
	if(!mysqli_query($con, $sql)){
		echo('Unable to add new players');
	}
	$sql = "SELECT MAX(PlayerID) FROM players";
	if(!$result = mysqli_query($con, $sql)){
		echo('Unable to find new userID');
	}
	while($row = mysqli_fetch_row($result)){
		$_SESSION['Player_ID'] = $row[0];
	}
}
mysqli_close($con);
?>
<html>
<head>

</head>

<body>

<div id="titleback">
	<div class="text-center" id="title">Game of Things</div>
	<span id="msglist" data-gamestate="<?php echo $gamestate ?>"></span>
</div>

<div class="container" style="padding-top: 10px">
<div class="input-group">
	<span class="input-group-addon">Name</span>
	<input type="text" name="playername" id="playername" class="form-control" maxlength="20" size="12" value="<?php echo htmlspecialchars($player_name) ?>">
</div>
<div class="input-group">
	<span class="input-group-addon">Room Code</span>
	<input type="text" name="code" id="code" class="form-control" maxlength="4" size="4">
</div>
<div class="btn-group btn-group-justified">
	<div class="btn-group">
	<button id="join" class="btn btn-primary" onclick="enterLobby()">Join Game</button>
	</div>
	<div class="btn-group">
	<button id="host" class="btn btn-primary" onclick="hostGame()">Host Game</button>
	</div>
</div>
</div>

</body>
</html>
