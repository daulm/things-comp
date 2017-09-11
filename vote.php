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
	
if(isset($_SESSION['Dasher'])){
	// change game state 
	$sql = "UPDATE lobby SET GameState='vote'";
	$sql .= " WHERE LobbyID=".mysql_real_escape_string($_SESSION['Lobby_ID']);
	$sql .= " AND GameState = 'answer' AND DasherID=".$_SESSION['Player_ID'];
	if(!mysqli_query($con, $sql)){
		echo('Unable update the gamestate');
	}
	$sql = "UPDATE games SET LaunchVoteTime = NOW()";
	$sql .= " WHERE GameID = (SELECT l.GameID FROM lobby l";
	$sql .= " WHERE l.LobbyID =".$_SESSION['Lobby_ID'].")";
	if(!mysqli_query($con, $sql)){
		echo('Unable update the voting begin time');
	}
	echo '<div class="alert alert-info">';
	echo '<strong>No need to vote</strong> Just click the button below and wait for the voting to finish.</div>';
} else {
	//look to see if they have already voted
	$sql = "SELECT v.VoteID FROM votes v, lobby l, answers a";
	$sql .= " WHERE v.PlayerID=".mysql_real_escape_string($_SESSION['Player_ID']);
	$sql .= " AND a.GameID = l.GameID AND v.AnswerID = a.AnswerID AND l.LobbyID =".$_SESSION['Lobby_ID'];
	if(!$result = mysqli_query($con, $sql)){
		echo('Unable check if I voted');
	}
	if(mysqli_num_rows($result) == 0){
		//Look up all the available options 
		$sql = "SELECT a.AnswerText, a.AnswerID FROM answers a, lobby l, players p";
		$sql .= " WHERE l.GameID = a.GameID AND a.BindAnswerID = 0 AND l.LobbyID=".$_SESSION['Lobby_ID'];
		$sql .= " AND p.PlayerID = a.PlayerID AND a.PlayerID !=".$_SESSION['Player_ID']." ORDER BY p.OrderVal";
		if(!$result = mysqli_query($con, $sql)){
			echo('Unable find voting options');
		}
		while($row = mysqli_fetch_row($result)){
			//Show the Answer Text and voting button
			?>
			<div class="container">
    				<div class="input-group panel panel-success">
				<span class="input-group-addon btn btn-primary" type="button" onclick="submitVote(<?php echo $row[1] ?>)" style="display: none;">Vote</span>
      		 		<div class="panel-body"><?php echo $row[0] ?></div>
        			<span class="input-group-addon btn btn-success" type="button" onclick="preVote(this)">Select</span>
    				</div>
     			</div>
			<?php
		}
	} else {
		// We found a vote from this player for this game, no more voting
		echo '<div class="alert alert-info">';
		echo '<strong>Looks like you already voted</strong> Just click the button below and wait for the voting to finish.</div>';
	}
}
$sql = "SELECT VoteTime FROM lobby WHERE LobbyID=".$_SESSION['Lobby_ID'];
if(!$result = mysqli_query($con, $sql)){
	echo('Unable to find time limit');
}
$row = mysqli_fetch_row($result);
$timeleft = $row[0]*60;

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

mysqli_close($con);
?>
<br>
<div id="footer" class="container text-center">
	<div class="col-xs-6">Time Left: <span id="countdown" data-timeleft="<?php echo htmlspecialchars($timeleft) ?>"></span></div>
	<div class="col-xs-6"><button type="button" class="btn btn-info" onclick="submitVote(0)">Skip voting and wait for results</button></div>
	
</div>
<div id="footer" class="container text-center"><button type="button" class="btn btn-warning" onclick="if(confirm('You want to Quit?')){mainMenu(1)}">Quit to Main</button></div>

</body>
</html>
