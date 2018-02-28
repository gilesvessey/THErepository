<!DOCTYPE HTML>
<html>
<head>
<Style>
</style>
</head

<body>

<?php
//define var and set empty value
$fileTypeErr = $issnAreaErr = $numResultErr = $ShowOptionErr = "";
$fileType = $issnArea = $numResult = $showOption = "";

?>
<h2>Editor's Search Form</h2>
<form method="post" action=<?php echo htmlspecialchars($_SERVER["PHP_SELF"])?>
	<div id="container" style="width:100%">
	<div id="left" style="position:absolute; float:left; width:50%;">
	Upload a(n)
	<input type="radio" name="fileType" value="issn"><?php if (isset($fileType) && $fileType=="issn") echo "checked";?>ISSN
	<input type="radio" name="fileType" value="lccn"><?php if (isset($fileType) && $fileType=="lccn") echo "checked";?>LCCN file:
	<br><br>
	<form action="upload.php" method="post" enctype="multipart/form-data">
	<input type="file" name="fileToUpload" id="fileToUpload">
	</form>
	<br><br>
	<hr style="float:left; width:265px"></hr>
	<br><br>
	...or Paste Your ISSN Query Below:
	<textArea name="issnArea" rows="10" cols="30"></textarea>
	<?php echo $issnArea; ?>
	<br><br>
	</div>
	<div id="middle" style="position:absolute; float:left; width:100%; left:200px; bottom:0; margin-bottom:0px;">
	<input type="submit" name="Download" value="Download">
	-OR-
	<input type="submit" name="Display" value="Display">	
	</div>
	<div id="right" style="position:absolute; float:left; left:295px; width:50%;">
	# of Results Per Page: <input type="text" name="numResult">
	<span class="error"><?php echo $numResultErr;?></span>
	<br><br><br>
	<hr style="float:left; width:305px"></hr>
	<br><br>	
	<br><br>
	Show Result:
	<select name="showOption">
	<option value="o">My Organization</option>
	<option value="c">My Contribution</option>
	</select>
	</div>
</div>
</form>

</body>
</html>
