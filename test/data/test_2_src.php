<?php

define('TEST_CST', 1);

function simpleToDoc() {
	echo 'pec';
}


/**
* FCT
*/
function noNeed() {
	echo 'poc';
}


/**
*
*/
class Test {
	
	/**
	*
	*/
	function noNeed() {
		
	}
	
	function fctWithParam($a, &$g, $b='',$c =NULL, $d=TEST_CST, $e = 1, $f= false) {
		return false;
	}
	
	/**
	* Docu
	*/
	static function noNeedStatic() {
		
	}
	
	static function fctStatic() {
	
	}
	
	private function fctPrivate() {
	
	}
	
	public static function fctPublicStatic() {
		
	}
	
	function &ref() {
		$a = 1;
		return $a;
	}

}


abstract class Abstr {
	
	abstract function fctAbstract();
	
}

?>