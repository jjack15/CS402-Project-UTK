<?php

class StackFrame {
	
	private $name;
	private $local_vars;
        private $encoded_locals;
        private $ordered_varnames;
        private $frame_id;
        private $func_name;
        private $is_highlighted;
	private $is_parent;
        private $is_zombie;
        private $parent_frame_id_list;
        private $unique_hash;
        private $all_locals;

        function __construct($frame_name) {
            $this->name = $frame_name;
	    $this->local_vars = array();
	    $this->encoded_locals = array();
            $this->parent_frame_id_list = array();
            $this->all_locals = array();
            $this->is_highlighted = true;
            $this->ordered_varnames = array();
            $this->is_parent = false;
            $this->is_zombie = false;
        }

	function get_name() {
	    return $this->name;
	}
        
        function get_local_vars() {
            return $this->local_vars;
        }

        function get_encodeded_locals() {
            return $this->encodeded_locals;
        }

        function get_frame_id() {
            return $this->frame_id;
        }

        function get_func_name() {
            return $this->name;
        }

        function get_is_highlighted() {
            return $this->is_highlighted;
        }

        function get_is_parent() {
            return $this->is_parent;
        }

        function get_all_locals() {
            return $this->all_locals;
        }

	function set_local_vars($in_locals) {
	    $this->local_vars = $in_locals;
	}
        
        function set_ordered_varnames($in_ordered_varnames) {
            $this->ordered_varnames = $in_ordered_varnames;
        }

        function set_encoded_locals($in_encoded_locals) {
            $this->encoded_locals = $in_encoded_locals;
        }

        function set_frame_id($in_frame_id) {
            $this->frame_id = $in_frame_id;
            $this->unique_hash = strval($in_frame_id);
        }

        function set_func_name($in_func_name) {
            $this->name = $in_func_name;
        }
        
        function set_is_highlighted($in_is_highlighted) {
            $this->is_highlighted = $in_is_highlighted;
        }

        function set_is_parent($in_is_parent) {
            $this->is_parent = $in_is_parent;
        }
        
        function set_all_locals($in_all_locals) {
            $this->all_locals = $in_all_locals;
        }

	function add_local($value) {
	    array_push($this->local_vars, $value);
	}

        function get_ordered_varnames() {
            return $this->ordered_varnames;
        }

        function get_encoded_locals() {
            return $this->encoded_locals;
        }

        function return_array() {
            $array = array();
            $array["encoded_locals"] = $this->encoded_locals;
            $array["frame_id"] = $this->frame_id;
            $array["func_name"] = $this->name;
            $array["is_highlighted"] = $this->is_highlighted;
            $array["is_parent"] = $this->is_parent;
            $array["is_zombie"] = $this->is_zombie;
            $array["ordered_varnames"] = $this->ordered_varnames;
            $array["parent_frame_id_list"] = $this->parent_frame_id_list;
            $array["unique_hash"] = $this->unique_hash;
            return $array;
        }
}

?>
