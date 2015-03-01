<?php

class StackFrame {
	
	private $name;
	private $locals;

	function __construct($frame_name) {
		$this->name = $frame_name;
		$this->locals = array();
	}

	function get_name() {
		return $this->name;
	}

	function set_locals($in_locals) {
		$this->locals = $in_locals;

	}

	function add_local($value) {
		array_push($this->locals, $value);
	}

}

?>