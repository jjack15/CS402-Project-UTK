<?php
    class TraceStep {
        private $stack;
        private $event;
        private $func_name;
        private $globals;
        private $heap;
        private $line;
        private $ordered_globals;
        private $stdout;

        function __construct() {
            $this->stdout = "";
            $this->globals = array();
            $this->heap = array();
            $this->ordered_globals = array();
            $this->stack = array();
        }

        function set_stack($in_stack) {
            $this->stack = $in_stack;
        }

        function set_event($in_event) {
            $this->event = $in_event;
        }

        function set_func_name($in_func_name) {
            $this->func_name = $in_func_name;
        }

        function set_globals($in_globals) {
            $this->globals = $in_globals;
        }

        function set_stdout($in_stdout) {
            $this->stdout = $in_stdout;
        }

        function set_heap($in_heap) {
            $this->heap = $in_heap;
        }

        function set_line($in_line) {
            $this->line = $in_line;
        }

        function set_ordered_globals($in_ordered_globals) {
            $this->ordered_globals = $in_ordered_globals;
        }
        
        function get_func_name() {
            return $this->func_name;
        }

        function get_line() {
            return $this->line;
        }
        function get_as_array() {
            $out_array = array();
            $out_array["event"] = $this->event;
            $out_array["func_name"] = $this->func_name;
            $out_array["func_name"] = $this->globals;
            $out_array["heap"] = $this->heap;
            $out_array["line"] = $this->line;
            $out_array["ordered_globals"] = $this->ordered_globals;
            $out_array["stack_to_render"] = $this->stack;
            $out_array["stdout"] = stdout;
        }

        function return_array() {
            $array = array();
            $array["event"] = $this->event;
            $array["func_name"] = $this->func_name;
            $array["globals"] = $this->globals;
            $array["heap"] = $this->heap;
            $array["line"] = $this->line;
            $array["ordered_globals"] = $this->ordered_globals;
            $array["stack_to_render"] = $this->stack;
            $array["stdout"] = $this->stdout;
            return $array;
        }
    }
?>
