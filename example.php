<?php

include("./class/tracking.php");

$tracking = new ups_tracking();

if(isset($_POST["tracking_number"])){

	// here you have to put your INFOS
	$arr_data = array(
		"%%ACCESS%%"	=> "AccessLicenseNumber",
		"%%USER%%"		=> "UserId",
		"%%PW%%"		=> "Password",
		"%%TRACKING%%"	=> $_POST["tracking_number"],
		"%%POST%%"		=> "PostalCode",
		"%%COUNTRY%%"	=> "CountryCode"
	);

	echo "<pre>";

//	print_r($tracking->get_raw_tracking_data($arr_data));
//	print_r($tracking->get_tracking_array($arr_data));
//	print_r($tracking->get_tracking_xml($arr_data));
	print_r($tracking->get_pre_formatted_tracking_array($arr_data,"Your Company"));

	echo "</pre>";

}
?>

<!doctype html>
<html>
<head>
	<title>UPS Tracking by Sven Delueg</title>
	<meta charset="UTF-8" />
</head>
<body>
<form action="example.php" method="post">
	<input name="tracking_number" type="text" />
	<input type="submit" value="Send" />

</form>
</body>
</html>