<?PHP

	require('FH_Loader.php');
	
	$fh = new FH_Loader('USERNAME','PASSWORD');
	
	$profile = $fh->loadPersonal();
	$name = $profile["fname"]." ".$profile["lname"];
	$matrikel = $profile["matrik"];
	
	$groups = $fh->loadRegisteredGroupsJson();
	$mails = $fh->loadMailsJson();
	$cal = $fh->loadCal();
	$calDataURI = "data:text/calendar;base64,".base64_encode($cal);
	
?>

<html>
	<head>
		<script>
			var groups = <?PHP
				echo $groups;
			?>;
			
			var mails = <?PHP
				echo $mails;
			?>;
			
			function ld(id){
				return document.getElementById(id);
			}
			
			window.onload=function(){
				var gs = ld("groupSelect");
				var ms = ld("mailSelect");
				var mb = ld("mailBody");
				
				for(i in groups){
					var opt = document.createElement("option");
					opt.value=groups[i].url;
					opt.innerHTML=groups[i].name;
					gs.appendChild(opt);
				}
				
				for(i in mails){
					var opt = document.createElement("option");
					opt.value=mails[i].msg.replace(/\n\n/g, "").replace(/\t/g,"");
					opt.innerHTML=mails[i].subject;
					ms.appendChild(opt);
				}
				ms.addEventListener('change', function(e){
					mb.value=ms.value;
				});
			}
		</script>
	</head>
	<body>
		<h1>Welcome, <?PHP echo $name; ?></h1>
		<h2><i>Mtr-Nr.: <?PHP echo $matrikel; ?></i></h2>
		<h3>Registered Courses:</h3>
		<select size="6" id="groupSelect">
		</select>
		<br>
		<h3>Received Mail: </h3>
		<select size="10" id="mailSelect">
		</select>
		<br/>
		<textarea style="width:800px;height:400px;" id="mailBody"></textarea>
		<br/><br/><br/>
		<a href="<?PHP echo $calDataURI; ?>">Download Stundenplan</a>
	</body>
</html>