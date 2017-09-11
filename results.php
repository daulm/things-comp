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
$donevote = FALSE;
$action_type = $_GET['mode'];
switch ($action_type){
	case "submit":
		// Add the answer
		$sql = "INSERT IGNORE INTO answers (GameID, PlayerID, AnswerText, OrderVal) VALUES (";
		$sql .= $_SESSION['Game_ID'].", ".$_SESSION['Player_ID'].", '".mysql_real_escape_string($_POST['ans'])."', RAND())";
		if(!mysqli_query($con, $sql)){
			echo('Unable to submit the answer');
		}
	case "endvote":
		$donevote = TRUE;
		break;
	default:
}

if(isset($_SESSION['Reader'])){
	//check if the time is up
	$sql = "SELECT l.VoteTime*60 - TIME_TO_SEC(TIMEDIFF(NOW(), g.LaunchVoteTime))";
	$sql .= " FROM lobby l, games g WHERE l.GameID = g.GameID AND l.LobbyID =".$_SESSION['Lobby_ID'];
	if(!$result = mysqli_query($con, $sql)){
		echo('Cant find voting timeout');
	}
	$row = mysqli_fetch_row($result);
	if($row[0] < -10){
		$donevote = TRUE;
	}
	// check if all players have submitted votes
	$sql = "SELECT p.PlayerID FROM players p, lobby l";
	$sql .= " WHERE p.LobbyID = l.LobbyID AND l.LobbyID=".$_SESSION['Lobby_ID'];
	$sql .= " AND NOT EXISTS (SELECT v.VoteID FROM votes v WHERE v.PlayerID = p.PlayerID";
	$sql .= " AND v.GameID = l.GameID)";
	if(!$result = mysqli_query($con, $sql)){
		echo('Cant check whether voting is done.');
	}		
	if(mysqli_num_rows($result) == 0){
		$donevote = TRUE;	
	}
	if($donevote){
		// make sure we haven't already updated scores
		$sql = "SELECT * FROM Lobby WHERE GameState='complete' AND LobbyID=".$_SESSION['Lobby_ID'];
		if(!$result = mysqli_query($con, $sql)){
			echo('Unable to check if we already created game');
		}
		if(mysqli_num_rows($result) == 0){
			//update the game state
			$sql = "UPDATE lobby SET GameState='complete' WHERE LobbyID=".$_SESSION['Lobby_ID'];
			if(!mysqli_query($con, $sql)){
				echo('Unable to sync Game to Lobby');
			}
		}
		
	} else {
		//show a waiting message
		?>
		<div class="container alert alert-danger"> Please wait for players to complete voting</div>
		<div id="footer" class="container text-center"><button type="button" class="btn btn-danger" onclick="endVoting()">Close voting</button></div>
		<?php
	}
} else {
	//check the game state
	$sql = "SELECT * FROM Lobby WHERE GameState='complete' AND LobbyID=".$_SESSION['Lobby_ID'];
	if(!$result = mysqli_query($con, $sql)){
		echo('Unable to check if we already created game');
	}
	if(mysqli_num_rows($result) == 0){
		//People are still voting
		echo '<div class="container alert alert-danger"> Please wait for players to complete voting</div>';
		
	} else {
		$donevote = TRUE;
	}
}
	
if($donevote){

	//pull all answers and their votes
	$sql = "SELECT a.AnswerID, a.AnswerText, p.PlayerName, p.PlayerID, p.Score, vp.PlayerName, vp.PlayerID, g.DasherID";
	$sql .= " FROM games g, players p, answers a LEFT JOIN votes v JOIN players vp ON vp.PlayerID = v.PlayerID ON a.AnswerID = v.AnswerID";
	$sql .= " WHERE a.PlayerID = p.PlayerID AND a.GameID = g.GameID AND a.GameID=".$_SESSION['Game_ID'];
	$sql .= " ORDER BY ISNULL(vp.PlayerName), a.AnswerID"; 
	if(!$result = mysqli_query($con, $sql)){
		echo('Cant find list of answers/votes.');
	}
	$previd = 0;
	echo '<div><div>';  //these divs won't contain anything, but each new row <div> must close out last row
	while($row = mysqli_fetch_row($result)){
		// 0-ansid 1-anstxt 2-name 3-playerid 4-score 5-votername 6-voterid
		if($previd == $row[0]){
			// we already created the table row, just add the new voter
			echo '<div class="row"><span class="label label-'.$rstyle.'">'.$row[5].'</span></div>';
		} else {
			$rstyle = "active";
			if(!is_null($row[5])){
				//this answer got votes	
				$rstyle = "info";
			}
			if($row[3] == $row[7]){
				//this is the correct answer
				$rstyle = "success";
			}			
			echo '</div></div><div class="row">';
			echo '	<div class="col-xs-3 text-center alert alert-'.$rstyle.'">'.$row[2].'<span class="badge">'.$row[4].'</span></div>';
			echo '	<div class="col-xs-6 alert alert-'.$rstyle.'">'.$row[1].'</div>';
			echo '	<div class="col-xs-3"><div class="row"><span class="label label-'.$rstyle.'">'.$row[5].'</span></div>';
			$previd = $row[0];
		}

	}
	echo '</div></div>';
}


mysqli_close($con);
?>

<div id="footer" class="container text-center"><button type="button" class="btn btn-info" onclick="returnLobby()">Return to the Lobby</button></div>
<div id="footer" class="container text-center"><button type="button" class="btn btn-warning" onclick="if(confirm('You want to Quit?')){mainMenu(1)}">Quit to Main</button></div>
</body>
</html>
