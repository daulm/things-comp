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

$action_type = $_GET['mode'];
switch ($action_type){
	case "submit":
		// Add the answer
		$sql = "INSERT IGNORE INTO answers (GameID, PlayerID, AnswerText) VALUES (";
		$sql .= $_SESSION['Game_ID'].", ".$_SESSION['Player_ID'].", '".mysql_real_escape_string($_POST['ans'])."')";
		if(!mysqli_query($con, $sql)){
			echo('Unable to submit the answer');
		}
		break;
	case "bind":
		/*
		$sql = "UPDATE answers SET BindAnswerID=0";
		$sql .= " WHERE AnswerID=".mysql_real_escape_string($_POST['ansid']);
		if(!mysqli_query($con, $sql)){
			echo('Unable to sync Game to Lobby');
		}*/
		break;
	case "unbind":
		$sql = "UPDATE answers SET BindAnswerID=0";
		$sql .= " WHERE AnswerID=".mysql_real_escape_string($_POST['ansid']);
		if(!mysqli_query($con, $sql)){
			echo('Unable to sync Game to Lobby');
		}
		break;
	case "update":
		if(isset($_SESSION['Dasher'])){
			if(isset($_POST['hideansid'])){
				//the dasher wants to hide a similar answer behind another
				$sql = "UPDATE answers SET BindAnswerID=".mysql_real_escape_string($_POST['bindansid']);
				$sql .= " WHERE AnswerID=".mysql_real_escape_string($_POST['hideansid']);
				if(!mysqli_query($con, $sql)){
					echo('Unable to bind answers');
				}	
			}
			
			//pull all the players in this game, and show their answers if they have been submitted
			$sql = "SELECT p.PlayerID, p.PlayerName, a.AnswerID, a.AnswerText, a.BindAnswerID";
			$sql .= " FROM players p INNER JOIN lobby l ON l.lobbyID = p.LobbyID";
			$sql .= " LEFT JOIN answers a ON p.PlayerID = a.PlayerID AND l.GameID = a.GameID";
			$sql .= " WHERE l.GameID =".$_SESSION['Game_ID']." ORDER BY p.OrderVal";
			if(!$result = mysqli_query($con, $sql)){
				echo('Cant find code for this lobby');
			}
			echo '<span id="msglist" data-gamestate="review"></span>';
			echo '<div class="container">';
			echo '<table class="table table-striped table-bordered table-condensed"><tbody>';
			while($row = mysqli_fetch_row($result)){
				if(is_null($row[2])){
					//this player hasn't submitted their answer yet
					?>
					<tr>
						<td style="width: 150px"><b><?php echo htmlspecialchars($row[1]) ?></b></td>
        					<td>- Waiting for Answer -</td>
        					<td style="width: 150px"></td>
      					</tr>
					<?php
				} else {
					//An answer has been submitted
					if($row[4] == 0){
						//This answer is active and will be visible to players
						?>
						<tr id="<?php echo htmlspecialchars($row[2]) ?>">
							<td style="width: 150px"><b><?php echo htmlspecialchars($row[1]) ?></b></td>
        						<td><?php echo htmlspecialchars($row[3]) ?></td>
        						<td style="width: 150px">
							<button type="button" class="btn btn-danger myhide" onclick="hide(<?php echo htmlspecialchars($row[2]) ?>)">Hide from Voting</button>
							<button type="button" class="btn btn-danger myundo" onclick="undoHide()" style="display: none;">Undo</button>
							<button type="button" class="btn btn-info mybind" onclick="bind(<?php echo htmlspecialchars($row[2]) ?>)" style="display: none;">Bind to this</button>
							</td>
      						</tr>
						<?php							
					} else { 
						//This answer will be hidden from voting
						?>
						<tr class="danger">
							<td style="width: 150px"><b><?php echo htmlspecialchars($row[1]) ?></b></td>
        						<td><?php echo htmlspecialchars($row[3]) ?></td>
        						<td style="width: 150px">
							<button type="button" class="btn btn-info" onclick="unhide(<?php echo htmlspecialchars($row[2]) ?>)">Unhide</button>
							</td>
      						</tr>
						<?php						
					}
				}
			}
			echo '</tbody></table></div>';
			echo '<div id="footer" class="text-center"><button type="button" class="btn btn-info" onclick="launchVote(); submitVote(0)">Begin Voting</button></div>';
		} else {
			//look up the game state
			$gamestate = "";
			$sql = "SELECT l.GameState FROM lobby l, players p";
			$sql .= " WHERE p.LobbyID = l.LobbyID AND p.PlayerID=".$_SESSION['Player_ID'];
			if(!$result = mysqli_query($con, $sql)){
				echo('Cant find code for this lobby');
			}
			while($row = mysqli_fetch_row($result)){
				$gamestate = $row[0];
			}
			echo '<span id="msglist" data-gamestate="'.$gamestate.'"></span>';
			echo '<div class="alert alert-info">';
			echo '<strong>Hang On!</strong> The Dasher is still reviewing submissions.</div>';
		}
		break;
		
	default:
}



mysqli_close($con);
?>

</body>
</html>
