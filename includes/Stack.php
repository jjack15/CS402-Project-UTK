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
        //$top = current($this->stack);
        return $this->stack[0];
        //return current($this->stack);
    }    

    function set_new_top($new_top) {
        $this->stack[0] = $new_top;
    }

    function size() {
        return sizeof($this->stack);
    }

    function return_array() {
        $array = array();
        foreach ($this->stack as $stack_frame) {
            array_push($array, $stack_frame->return_array());
        }
        return $array;
    }
}

?>
