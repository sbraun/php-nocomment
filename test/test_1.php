<?php

include '../nocomment.php';

$NoComment = new NoComment();
echo "NoComment ".$NoComment->version."\n";

// Update or create page-level DocBlock
$comments = array(
				  'category'=>'My Category',				  
				  'package'=>'My Package',				  
				  'author'=>'Me',
				  'version'=>'1.0',				  
				  'copyright'=>"Copyright (c) Me."
				  );
$src = $NoComment->addFileComment('data/test_1_src.php', $comments);

if ($src === false)  {
	echo "ERROR\n";
} elseif ($src === NULL) {
	echo "NO MODIFICATION\n";
} else {
	echo "RESULT :\n";
	echo $src;
}

echo "\n";

?>