<?php
$directory = "uploaded/";
$to_upload = $directory . basename($_FILES["fileToUpload"]["name"]);
$uploadPass = 1;
$textFileType = pathinfo($to_upload,PATHINFO_EXTENSION);

//allow only txt
if($textFileType != "txt") {
	echo "Only TXT files are allowed.";
	$uploadPass = 0;
}

// does the file exist already
if (file_exists($to_upload)) {
	echo "File already exists.";
	$uploadPass = 0;
}

//check file size, currently caped at 500k
if ($_FILES["$to_upload"]["size"] > 500000) {
	echo "File is to large.";
	$uploadPass = 0;
}

//confirm upload
if($uploadPass = 0) {
	echo "File was not uploaded.";
} else {
	if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $to_upload)) {
		echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been successfully uploaded.";
	} else {
		echo "File failed to upload.";
	}
}
?>