<?php

class Stack {
	
	private $stack;

	function __construct() {
		$this->stack = array();
    }

    /* Add a stack frame to the stack */
    function push($element) {
    	array_unshift($this->stack, $element);
    }

    /* Remote element from the top of the stack */
    function pop() {
    	return array_shift($this->stack);
    }

    /* Return the stack frame on top of the stack */
	function top() {
		return current($this->stack);
	}    

}

?>