<?php

// Database access configuration
$config["dbuser"] = "ora_cwl";			// change "cwl" to your own CWL
$config["dbpassword"] = "a12345678";	// change to 'a' + your student number
$config["dbserver"] = "dbhost.students.cs.ubc.ca:1522/stu";
$db_conn = NULL;
$success = true;
$show_debug_alert_messages = False;

?>

<html>

<head>
	<title>CPSC 304 Event Management Project</title>
</head>

<body>
	<h2>Reset All Tables</h2>
	<p>If this is the first time you're running this page, you MUST use reset.</p>

	<form method="POST" action="main.php">
		<input type="hidden" id="resetTablesRequest" name="resetTablesRequest">
		<p><input type="submit" value="Reset" name="reset"></p>
	</form>

	<hr />

	<h2>Delete Event</h2>
	<form method="POST" action="main.php">
		<input type="hidden" id="deleteEventRequest" name="deleteEventRequest">
		Event ID: <input type="text" name="eventID"> <br /><br />

		<input type="submit" value="Delete" name="deleteSubmit"></p>
	</form>

	<hr />

	<h2>Display All Events</h2>
	<form method="GET" action="main.php">
		<input type="hidden" id="displayTuplesRequest" name="displayTuplesRequest">
		<input type="submit" name="displayEvents"></p>
	</form>


	<?php

	function debugAlertMessage($message)
	{
		global $show_debug_alert_messages;

		if ($show_debug_alert_messages) {
			echo "<script type='text/javascript'>alert('" . $message . "');</script>";
		}
	}

	function executePlainSQL($cmdstr)
	{
		global $db_conn, $success;

		$statement = oci_parse($db_conn, $cmdstr);

		if (!$statement) {
			echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
			$e = OCI_Error($db_conn);
			echo htmlentities($e['message']);
			$success = False;
		}

		$r = oci_execute($statement, OCI_DEFAULT);
		if (!$r) {
			echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
			$e = oci_error($statement);
			echo htmlentities($e['message']);
			$success = False;
		}

		return $statement;
	}

	function executeBoundSQL($cmdstr, $list)
	{
		global $db_conn, $success;
		$statement = oci_parse($db_conn, $cmdstr);

		if (!$statement) {
			echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
			$e = OCI_Error($db_conn);
			echo htmlentities($e['message']);
			$success = False;
		}

		foreach ($list as $tuple) {
			foreach ($tuple as $bind => $val) {
				oci_bind_by_name($statement, $bind, $val);
				unset($val);
			}
			
			$r = oci_execute($statement, OCI_DEFAULT);
			if (!$r) {
				echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
				$e = OCI_Error($statement);
				echo htmlentities($e['message']);
				echo "<br>";
				$success = False;
			}
		}
	}

	function printEventsResult($result)
	{
		echo "<table>";
		echo "<tr><th>Event ID</th><th>Venue ID</th><th>Date Time</th></tr>";

		while ($row = OCI_Fetch_Array($result, OCI_ASSOC)) {
			echo "<tr><td>" . $row["EVENTID"] . "</td><td>" . $row["VENUEID"] . "</td><td>" . $row["DATETIME"] . "</td></tr>";
		}

		echo "</table>";
	}

	function connectToDB()
	{
		global $db_conn;
		global $config;

		$db_conn = oci_connect($config["dbuser"], $config["dbpassword"], $config["dbserver"]);

		if ($db_conn) {
			debugAlertMessage("Database is Connected");
			return true;
		} else {
			debugAlertMessage("Cannot connect to Database");
			$e = OCI_Error();
			echo htmlentities($e['message']);
			return false;
		}
	}

	function disconnectFromDB()
	{
		global $db_conn;

		debugAlertMessage("Disconnect from Database");
		oci_close($db_conn);
	}


	function handleResetRequest()
	{	
		global $db_conn;
		$sqlFilePath = './event_management.sql';
		$sqlContent = file_get_contents($sqlFilePath);
		$sqlStatements = explode(';', $sqlContent);
		$sqlStatements = array_filter($sqlStatements, 'trim');
		
		foreach ($sqlStatements as $cmdstr) {
			executePlainSQL($cmdstr);
		}
		echo "<br> All tables reset. <br>";
		
		oci_commit($db_conn);
	}

	function handleDeleteEventRequest()
	{
		global $db_conn;

		$eventID = $_POST['eventID'];

		executePlainSQL("DELETE FROM event WHERE eventID = $eventID");
		oci_commit($db_conn);
	}

	function handleDisplayEventsRequest()
	{
		global $db_conn;
		$result = executePlainSQL("SELECT * FROM event");
		printEventsResult($result);
	}

	function handlePOSTRequest()
	{
		if (connectToDB()) {
			if (array_key_exists('resetTablesRequest', $_POST)) {
				handleResetRequest();
			} else if (array_key_exists('updateQueryRequest', $_POST)) {
				handleUpdateRequest();
			} else if (array_key_exists('insertQueryRequest', $_POST)) {
				handleInsertRequest();
			} else if (array_key_exists('deleteEventRequest', $_POST)) {
				handleDeleteEventRequest();
			}

			disconnectFromDB();
		}
	}

	function handleGETRequest()
	{
		if (connectToDB()) {
			if (array_key_exists('countTuples', $_GET)) {
				handleCountRequest();
			} else if (array_key_exists('displayEvents', $_GET)) {
				handleDisplayEventsRequest();
			}

			disconnectFromDB();
		}
	}

	if (isset($_POST['reset']) || isset($_POST['updateSubmit']) || isset($_POST['insertSubmit']) || isset($_POST['deleteSubmit'])) {
		handlePOSTRequest();
	} else if (isset($_GET['countTupleRequest']) || isset($_GET['displayTuplesRequest'])) {
		handleGETRequest();
	}

	?>
</body>

</html>