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

$code = "";
$action_type = $_GET['mode'];
switch ($action_type){
	case "create":
		//randomly generate a code
		$alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		while(true){
			$code = "";
			$code = substr($alphabet, rand(0,25), 1);
			$code .= substr($alphabet, rand(0,25), 1);
			$code .= substr($alphabet, rand(0,25), 1);
			$code .= substr($alphabet, rand(0,25), 1);
			
			$sql = "SELECT * FROM lobby WHERE Code='".$code."'";
			if(!$result = mysqli_query($con, $sql)){
				echo('Unable to verify the code is unique');
			}
			if(mysqli_num_rows($result) == 0){
				break;
			}
		}
		// Create the lobby
		$sql = "INSERT INTO lobby (Code, HostID)";
		$sql .= " VALUES ('".$code."', ".$_SESSION['Player_ID'].")";
		if(!mysqli_query($con, $sql)){
			echo('Unable to create the lobby');
		}
		$sql = "SELECT MAX(LobbyID) FROM lobby";
		if(!$result = mysqli_query($con, $sql)){
			echo('Unable to find new lobby');
		}
		while($row = mysqli_fetch_row($result)){
			$_SESSION['Lobby_ID'] = $row[0];
		}
		
		//update the current lobby the player is in and their name
		$sq_pname = mysqli_real_escape_string($con, $_POST['pname']);
		$sql = "UPDATE players SET LobbyID=".$_SESSION['Lobby_ID'].", PlayerName='".$sq_pname."'";
		$sql .= " WHERE PlayerID=".$_SESSION['Player_ID'];
		if(!mysqli_query($con, $sql)){
			echo('Unable to add player ID to the lobby');
		}		
		
		$_SESSION['Host'] = true;
		break;
	case "join":
		// check that the code is correct and the lobby was created in the past 24 hours
		$sq_code = mysqli_real_escape_string($con, $_POST['code']);
		$sql = "SELECT MAX(LobbyID) FROM lobby WHERE Code=UPPER('".$sq_code."') AND CreationTime > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
		if(!$result = mysqli_query($con, $sql)){
			echo('Cant find a Lobby with the given code.');
		}		
		if(mysqli_num_rows($result) == 0){
			echo ('<frame onload="alert(\'Cant find a Lobby with the given code\')">wrong code</frame>');
			exit('<br><frame onload="mainMenu()">Return to Main</frame>');
			break;
		}		
		while($row = mysqli_fetch_row($result)){
			$_SESSION['Lobby_ID'] = $row[0];
		}
		$code = mysql_real_escape_string($_POST['code']);
		$pname = mysql_real_escape_string($_POST['pname']);
		//If a player already exists with the chosen name, add [the number of current players] to the end of their name
		$sql = "SELECT p.PlayerName, (SELECT COUNT(*) FROM players WHERE LobbyID =".$_SESSION['Lobby_ID'].") as num";
		$sql .= " FROM players p, lobby l";
		$sql .= " WHERE p.LobbyID = l.LobbyID AND l.LobbyID=".$_SESSION['Lobby_ID'];
		$sql .= " AND p.LastCheck > NOW() - INTERVAL 15 SECOND";
		$sql .= " AND p.PlayerName ='".$pname."'";
		if(!$result = mysqli_query($con, $sql)){
			echo('Cant find players in this game');
		}
		if(mysqli_num_rows($result) > 0){
			//There was a player who already has that name
			while($row = mysqli_fetch_row($result)){
				$pname .= $row[1];	
			}
		}
		
		// Add player to the lobby and update their name
		$sql = "UPDATE players SET LobbyID=".$_SESSION['Lobby_ID'].", PlayerName='".$pname."'";
		$sql .= " WHERE PlayerID=".$_SESSION['Player_ID'];
		if(!mysqli_query($con, $sql)){
			echo('Unable to add player ID to the lobby');
		}		
				
		break;
	case "update":
		//update last time player checked in
		$sql = "UPDATE players SET LastCheck=NOW()";
		$sql .= " WHERE PlayerID=".$_SESSION['Player_ID'];
		if(!mysqli_query($con, $sql)){
			echo('Unable to check in player');
		}
		break;
	case "return":
		if(isset($_SESSION['Host'])){
			$sql = "UPDATE lobby SET GameID=0 WHERE LobbyID=".$_SESSION['Lobby_ID'];
			if(!mysqli_query($con, $sql)){
				echo('Unable to return to the lobby');
			}
		}
	default:			
}

//query to pull room code, game state, clue, dasher score, and time limits
$sql = "SELECT l.Code, l.GameState FROM lobby l, players p";
$sql .= " WHERE p.LobbyID = l.LobbyID AND p.PlayerID=".$_SESSION['Player_ID'];
if(!$result = mysqli_query($con, $sql)){
	echo('Cant find code for this lobby');
	exit('<button type="button" class="btn btn-warning" onclick="mainMenu()">Return to Main</button>');
}	
while($row = mysqli_fetch_row($result)){
	$code = $row[0];
	$gamestate = $row[1];
}

//query to pull player list
$sql = "SELECT p.PlayerName, p.PlayerID, l.HostID";
$sql .= " FROM lobby l, players p";
$sql .= " WHERE p.LobbyID = l.LobbyID AND l.LobbyID=".$_SESSION['Lobby_ID'];
$sql .= " AND p.LastCheck > NOW() - INTERVAL 30 SECOND ORDER BY p.PlayerID";
if(!$playerlist = mysqli_query($con, $sql)){
	echo('Cant find players in this game');
}


?>
<span id="msglist" data-gamestate="<?php echo $gamestate ?>"></span>
<div id="titleback">
	<div class="text-center" id="title">The Game of Things</div>
</div>
<div class="container-fluid">
	<h2 class="text-center">Room Code:<b> <?php echo $code ?></b></h2>
</div>
<div id="players" class="container-fluid">
<?php

while($row = mysqli_fetch_row($playerlist)){
	//[0]-playername [1]-PlayerID [2]-HostID 
	if($row[1] == $row[2]){
		// this player should be identified as the host
		$note = "<i>(H)</i>";
	}else {
		$note = "";
	}
	echo '<div class="col-xs-6 col-sm-4 col-md-3 text-center" data-playerid="'.$row[1].'">'.$row[0].$note.'</div>';
}
echo '</div><br>';

if(isset($_SESSION['Host'])){
	//show form for settings and button for kick off
	?>
	<div class="container-fluid text-center well well-sm row settings" id="settings">
			<button type="submit" class="btn btn-info" onclick="launchGame()">Start Round</span></button>
	</div>
	<?php
} 


mysqli_close($con);
?>

<div id="footer" class="container text-center"><button type="button" class="btn btn-warning" onclick="if(confirm('You want to Quit?')){mainMenu(1)}">Quit to Main</button></div>


</body>
</html>
