<?php

class StackFrame {
	
	private $name;
	private $local_vars;
        private $ordered_locals;
        private $frame_id;
        private $func_name;
        private $is_highlighted;
	private $is_parent;
        private $is_zombie;
        private $parent_frame_id_list;

        function __construct($frame_name) {
		$this->name = $frame_name;
		$this->local_vars = array();
	        $this->ordered_locals = array();
        }

	function get_name() {
	    return $this->name;
	}
        
        function get_local_vars() {
            return $this->local_vars;
        }

        function get_ordered_locals() {
            return $this->ordered_locals;
        }

        function get_frame_id() {
            return $this->frame_id;
        }

        function get_func_name() {
            return $this->func_name;
        }

        function get_is_highlighted() {
            return $this->is_highlighted;
        }

        function get_is_parent() {
            return $this->is_parent;
        }

	function set_locals_vars($in_locals) {
	    $this->local_vars = $in_locals;

	}

        function set_ordered_locals($in_ordered_locals) {
            $this->ordered_locals = $in_ordered_locals;
        }

        function set_frame_id($in_frame_id) {
            $this->frame_id = $in_frame_id;
        }

        function set_func_name($in_func_name) {
            $this->func_name = $in_func_name;
        }
        
        function set_is_highlighted($in_is_highlighted) {
            $this->is_highlighted = $in_is_highlighted;
        }

        function set_is_parent($in_is_parent) {
            $this->is_parent = $in_is_parent;
        }
        
	function add_local($value) {
		array_push($this->local_vars, $value);
	}
}

?>
