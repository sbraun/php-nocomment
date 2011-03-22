<?php

include '../nocomment.php';

$NoComment = new NoComment();
echo "NoComment ".$NoComment->version."\n";

// Add Comment to all undocumented functions
$src = $NoComment->addFctComment('data/test_2_src.php');

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