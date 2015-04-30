<?php
require_once("LocalVar.php");
require_once("Stack.php");
require_once("StackFrame.php");
require_once("TraceStep.php");

/* 
* GDBComm.php
* This class is used for all communication to the GDB
* process running, including compilation of the target
* program, starting GDB, and quitting GDB 
*/

class GDBComm
{
    private $source_file = NULL; 	// The relative file path of source file
    private $exec_file = NULL; 		// The relative file path of the executable
    private $pipes; 				// Pipes array used to communicate with GDB
    private $descriptor;			// Assign pipes using this descriptor array (see constructor)
    private $process; 				// Holds the process resource of the GDB process
    private $current_line; 			// Holds the current line of execution
    private $local_vars; 			// An array containing LocalVar structures of current local variables
    private $stack; 				// A Stack structure containing StackFrames
    private $json_array; 			// An array consisting of all information to be returned to front end
    private $trace_array; 			// Array containing TraceElements
    private $ordered_locals; 		// An array containing local variables in the order they appear
    private $debug; 				// A flag telling if currently in debug mode
    private $frame_count; 			// Global frame counter that must be increased on each new frame
    private $output_folder; 		// The folder where all compiled programs go to
    private $trace_count;			// Current trace number
    private $error_array;			// Array containing error strings
    private $current_depth;			// The current depth of stacks
    private $stdout;				// String holding all standard out that is continually updated
    private $string_watch = 0;		// Check for implementing strings (not working)

    /* 
     * Constructor takes the name of the source file and allocates arrays
     * and other data structures needed during the execution.
     */
    function __construct($src_file) {
        $this->source_file = $src_file;
        $this->exec_file = substr($src_file, 0, strlen($src_file)-4);
        $this->pipes = array();
        $this->stack = new Stack();
        $this->json_array = array();
        $this->ordered_locals = array();
        $this->trace_array = array();
        $this->debug = false;
        $this->frame_count = 0;
        $this->trace_count = 0;
        $this->error_array = array();
        $this->output_folder = "output";
        $this->descriptor = array(
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
                2 => array("pipe", "w")
        );
    }

    /* 
     * Keep reading standard output until reached (gdb) line. (gdb) usually marks
     * the end of standard out.
     */
    function stop_at_gdb() {
        while ($f = fgets($this->pipes[1])) {
            //echo $f;
            $f = str_replace(array("\r", "\n", " "), '', $f);
            if ($f === "(gdb)") {
                return;
            }
        }
    }
    
    /* Set the source file */
    function set_source($in_source) {
        $this->source_file = $in_source;
    }

    /* Set debug flag */
    function debug() {
        $this->debug = true;
    }

    /* Compile the source file into program that GDB can debug */
    function compile($input_code = null) {
        if ($input_code != null) {
            $this->json_array["code"] = $input_code;
            $current_time = floatval(time());
            if (!file_exists($this->output_folder)) mkdir(strval($this->output_folder));
            mkdir($this->output_folder."/".strval($current_time));
            $this->source_file = $this->output_folder."/".strval($current_time)."/main.cpp";
            $this->exec_file = substr($this->source_file, 0, strlen($this->source_file)-4);
            $main_file = fopen($this->source_file, "w");
            fwrite($main_file, $input_code);
        }

        $descriptor_array = array(
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
                2 => array("pipe", "w")
        );
        $process = proc_open("./g++ -O0 -g " . $this->source_file . " -o " . $this->exec_file, $descriptor_array, $pipes);
        $stderr_array = array();
        while ($f = fgets($pipes[2])) {
            array_push($stderr_array, $f);
            $preg_source = preg_quote($this->source_file, '/');
            preg_match("/($preg_source):[0-9]*:[0-9]*: error/", $f, $matches);
            if (sizeof($matches) > 0) {
                array_push($this->error_array, $f);
            }
            preg_match("/($preg_source):[0-9]*:[0-9]*: warning/", $f, $matches);
            if (sizeof($matches) > 0) {
            }
        }
        $info = proc_get_status($process);
        if ($info["running"] == FALSE) {
            if ($info["exitcode"]) {
                return 0;
            }
        }
        return 1;
    }

    /* Start the GDB instance and clear all the stdout */
    function start($program_input) {
		$this->process = proc_open("./gdb --interpreter=mi $this->exec_file", $this->descriptor, $this->pipes);
        $about_proc = proc_get_status($this->process);
		while ($f = fgets($this->pipes[1])) {
            if ($f == "~\"done.\\n\"\n") {
                $this->stop_at_gdb();
				break;
	    	}
        }
        
        /* Set the breakpoint at main */
        $fout = fwrite($this->pipes[0], "set can-use-hw-watchpoints 0\r\n");
        $this->stop_at_gdb();
        $fout = fwrite($this->pipes[0], "-break-insert main\r\n");
        $this->stop_at_gdb();

        /* Build run command string. Pipe standard out to a text file */
        $run_command_str = "interpreter-exec console \"r";
        $stdout_filename = "testing.txt";
        $run_command_str = $run_command_str." > ".$stdout_filename;

        // If program input not empty, write input to file and update run command string
        
        if (!empty($program_input)) {
            $stdin_filename = "input.txt";
            $stdin_file = fopen($stdin_filename, "w");
            fwrite($stdin_file, $program_input);
            fclose($stdin_file);
            $run_command_str = $run_command_str." < ".$stdin_filename;
        } // end if
        else {
            $run_command_str = $run_command_str." < input2.txt";
        }

        $run_command_str = $run_command_str."\"\r\n";

        $fout = fwrite($this->pipes[0], $run_command_str);
        $trace_step = new TraceStep(); 
        $trace_step->set_event("call");

        /* Keep getting output until it has stopped */
        while ($f = fgets($this->pipes[1])) {
            if (substr($f, 0, 8) == "*stopped") {
                preg_match('/line="([0-9]*)"/', $f, $matches);   
                $trace_step->set_line(intval($matches[1]));
                preg_match('/func="([A-Za-z0-9_]*)"/', $f, $matches);
                $trace_step->set_func_name($matches[1]);
                break;
            }
        }
        $this->local_vars = $this->get_locals();
        $ordered_locals = array(); 

        // Set watchpoints
        foreach ($this->local_vars as $local_var) {
            $this->set_watchpoint($local_var->get_name());
        }
        
        $stack_frame = new StackFrame($trace_step->get_func_name().":".$trace_step->get_line());
        $stack_frame->set_ordered_varnames($ordered_locals);
        $stack_frame->set_frame_id($this->frame_count++);
        $this->stack->push($stack_frame); 
        $stack_as_array = $this->stack->return_array();
        $trace_step->set_stack($stack_as_array);
        array_push($this->trace_array, $trace_step->return_array());

        /* At this point, gdb has started, loaded the program, and is now executing, but stopped at a breakpoint in main */
        $this->trace_count++;
        $this->current_depth = 1;
    }

    /* Returns an array containing ALL (even unitialized values) local variables for the current frame */
    function get_locals() {
        $local_vars = array();
        $fout = fwrite($this->pipes[0], "-stack-list-locals 1\r\n");
        $name_value = fgets($this->pipes[1]);
        $result = preg_match_all('/name="([A-Za-z0-9_]*)",value="([A-Za-z0-9_.]*)/', $name_value, $matches);
        $fout = fgets($this->pipes[1]);
        $var_names = $matches[1];
        $var_values = $matches[2];
        $i = 0;
        foreach ($var_names as $var_name) {
            // FOR NOW LOCAL VARS ARE HARD CODED AS PRIMITIVES
            $new_local = new LocalVar($var_name);
            fwrite($this->pipes[0], "whatis ".$var_name."\r\n");
            while ($f = fgets($this->pipes[1])) {
                    $pos = strpos($f, '~"type');
                    if($pos === 0) {
                        $matches = array();
                        preg_match('/~"type = ([\\A-Za-z:_1-9]*)/', $f, $matches);
                        if ($matches[1] == "std::__1::string") {
                            $matches[1] = "string";
                        } else {
                            $new_local->set_value($var_values[$i]);
                        }
                        $new_local->set_type($matches[1]);
                    }
                if (strpos($f, '^done') === 0) {
                    break;
                }
            }
            $local_vars[$var_name] = $new_local;
            $i++;
        }
        $f = fgets($this->pipes[1]);
        return $local_vars;
    }

    function set_watchpoint($variable) {
        $fout = fwrite($this->pipes[0], "-break-watch $variable\r\n");
        $f1 = fgets($this->pipes[1]);
        $f2 = fgets($this->pipes[1]);
        if ((substr($f1, 0, 5) == "^done") && (substr($f2, 0, 5) == "(gdb)")) {
        }
    }

    /* Take a *step* through the code. After calling this function, you must read the output and see if any variables were changed or if the frame has changed */
    function take_step() {
        $return_val = TRUE;
        $trace_step = new TraceStep();
        $fout = fwrite($this->pipes[0], "-exec-step\r\n");
        $f = null;
        while($f = fgets($this->pipes[1])) {
            /* Detect when the execution of step has stopped */
           // echo $f;
            $f = str_replace(array("\r", "\n"), '', $f);
            $f = str_replace(" ", '', $f);
            if (substr($f, 0, 8) == "*stopped") {
                $reason_return = preg_match('/reason="([A-Za-z0-9\\-._]*)"/', $f, $matches);
                if ($matches[1] == "watchpoint-trigger") {
                    preg_match('/wpt={number="[0-9]*",exp="([A-Za-z0-9_]*)"}/', $f, $watchpoint_match);
                    $the_type = $this->local_vars[$watchpoint_match[1]]->get_type();
                    //echo "\n\nTHE TYPE: $the_type \n\n";
                    if ($this->local_vars[$watchpoint_match[1]]->get_type() == "std::string") {
                        $this->string_watch = $watchpoint_match[1];
                        $this->stop_at_gdb();
                        //echo "\n\nAfter stop at gdb\n\n";
                        return 1;
                    }
                    preg_match('/line="([0-9]*)"/', $f, $line_match);
                    if (isset($line_match[1])) $trace_step->set_line($line_match[1]);  
                    if ($this->local_vars[$watchpoint_match[1]]->get_type() == "char") {
                        //echo $f;
                        preg_match('/old="[0-9]*\'[\\\0-9A-Za-z]*\'",new="[0-9]*\'([A-Za-z]*)\'"/', $f, $new_value);
                        //print_r($new_value);
                        $this->local_vars[$watchpoint_match[1]]->set_value($new_value[1]);
                    }
                    else {
                        preg_match('/value={old="[-0-9A-Za-z.]*",new="([0-9A-Za-z.]*)"/', $f, $new_value);
                        $this->local_vars[$watchpoint_match[1]]->set_value($new_value[1]);
                    }
                    $this->local_vars[$watchpoint_match[1]]->set_initialized();
                    //preg_match('/func="([a-zA-Z0-9:]*)
                    break;
                }
                else if ($matches[1] == "end-stepping-range") {
                    //echo "\n\nWe're in end stepping range\n\n";
                    if ($this->string_watch != 0) {
                        //echo "\n\nSTRING CHANGE\n\n";
                        continue;
                    }
                    $return = preg_match('/file="([A-Za-z0-9._\/]*)"/', $f, $matches);
                    if (isset($matches[1])) {
                        if ($matches[1] == $this->source_file) {
                            preg_match('/line="([0-9]*)"/', $f, $line_match);
                            $trace_step->set_line($line_match[1]);
                            break;  
                        }
                    }
                    else {
                        while ($f = fgets($this->pipes[1])) {
                            $this->stop_at_gdb();
                            return 1;
                        }
                        return 1;
                    }
                }
                else if ($matches[1] == "watchpoint-scope") {
                    $return = preg_match('/file="([A-Za-z0-9._\/]*)"/', $f, $matches);
                    if (isset($matches[1])) {
                        if ($matches[1] == $this->source_file) {
                            preg_match('/line="([0-9]*)"/', $f, $line_match);
                            $trace_step->set_line($line_match[1]);
                            break;
                        } else {
                            while ($f = fgets($this->pipes[1])) {
                                $this->stop_at_gdb();
                                return 1;
                            }
 
                        }
                        //$this->stop_at_gdb();
                    } else {
                        return 1;
                    }
                        
                }
                else if ($matches[1] == "exited-normally") {
                    $return_val = FALSE;
                    break;
                }
                else if ($matches[1] == "watchpoint-scope") {
                    $this->stop_at_gdb();
                    return 1;
                }

                $return = preg_match('/file="([A-Za-z0-9._\/]*)"/', $f, $matches);
                if (isset($matches[1])) {
                    if ($matches[1] == $this->source_file) {
                        preg_match('/line="([0-9]*)"/', $f, $line_match);
                        $trace_step->set_line($line_match[1]);
                        break;	
                    }
                }	                
            } else {
            }
        }

        while ($new_line = fgets($this->pipes[1])) {
            $new_line = str_replace(array("\n", "\r"), '', $new_line);
            $new_line = str_replace(" ", '', $new_line);
            if ($new_line == "(gdb)") break;
        }
        fwrite($this->pipes[0], "-stack-info-depth\r\n");
        $depth_line = fgets($this->pipes[1]);
        preg_match ("/\^done,depth=\"([0-9]*)\"/", $depth_line, $depth_matches);
        preg_match('/func="([A-Za-z0-9._\/]*)"/', $f, $matches);
        $stack_depth;
        if (isset($depth_matches[1])) {
            $stack_depth = intval($depth_matches[1]);
            if ($stack_depth > $this->current_depth) {
                $this->stack->top()->set_is_highlighted(false);
                $stack_frame = new StackFrame($matches[1].":".$trace_step->get_line());
                $stack_frame->set_frame_id($this->frame_count++);
                $this->stack->top()->set_local_vars($this->local_vars);
                fgets($this->pipes[1]);
                $this->local_vars = $this->get_locals(); 
                $trace_step->set_func_name($matches[1]);
                // Set watchpoint
                foreach ($this->local_vars as $local_var) {
                    $this->set_watchpoint($local_var->get_name());
                }
                $ordered_varnames = array();
                $stack_frame->set_ordered_varnames($ordered_varnames);
                $this->stack->push($stack_frame);
                $trace_step->set_stack($this->stack->return_array());
                array_push($this->trace_array, $trace_step->return_array());
                $this->current_depth = $stack_depth;
            }
            else {
                if ($stack_depth < $this->current_depth) {
                    $this->stack->pop();
                    $this->stack->top()->set_is_highlighted(true);
                    $this->stack->top()->set_frame_id($this->frame_count++);
                    $this->stack->top()->set_func_name($matches[1].":".$trace_step->get_line());
                    $this->current_depth = $stack_depth; 
                    fgets($this->pipes[1]);
                    $this->local_vars = $this->stack->top()->get_local_vars(); 
                    $trace_step->set_stack($this->stack->return_array());
                    array_push($this->trace_array, $trace_step->return_array());
                    $this->trace_count++;
                    return $return_val;
                }
                /*} else if ($stack_depth == $this->current_depth) {
                    
                }*/
                $trace_step->set_func_name($matches[1]);
                $stack_frame = $this->stack->top();
                $stack_frame->set_func_name($matches[1].":".$trace_step->get_line());
                $stack_frame->set_frame_id($this->frame_count++);
                $test_array = $this->return_encoded_locals();
                $stack_frame->set_encoded_locals($test_array);
                $stack_frame->set_ordered_varnames($this->return_ordered_varnames());
                $this->stack->set_new_top($stack_frame);
                $trace_step->set_stack($this->stack->return_array());
                $this->stdout = file_get_contents("testing.txt");
                $trace_step->set_stdout($this->stdout);
                array_push($this->trace_array, $trace_step->return_array());
            }
        }
        $this->trace_count++;
        return $return_val;
   }	

    function update_local($var_name, $var_value) {
        $this->local_vars[$var_name] = $var_value;
    }

    /* Get the current line that the is being debugged */
    function get_current_line() {
        return $this->current_line;	
    }
    
    function print_locals() {
        foreach ($this->local_vars as $local_var) {
            echo $local_var->get_name()." : ".$local_var->get_value()."\n";
        }
    }

    function get_error() {
        $trace_step = new TraceStep();
        $trace_step->set_event("uncaught_exception");
        $exception_msg;
        $i = 0;
        foreach ($this->error_array as $error) {
            if ($i == 0) {
                preg_match("/^[A-Za-z0-9\/\.]*:([0-9]*):([0-9]*): error/", $error, $matches); 
                $trace_step->set_line($matches[1]);
                $trace_step->set_offset($matches[2]);
            }
            $exception_msg = $exception_msg.$error;
            $i++;
        }
        $trace_step->set_exception_msg($exception_msg);
        array_push($this->trace_array, $trace_step->return_array()); 
        $this->finish();
        echo $this->return_json();
    }

    function print_array() {
        print(json_encode($this->json_array));
    }
   
    function return_encoded_locals() {
        $array = array();
        foreach ($this->local_vars as $local_var) {
            if ($local_var->is_initialized()) {
                $array[$local_var->get_name()] = $local_var->get_value();
            }
        }
        return $array;
    }

    function return_ordered_varnames() {
        $array = array();
        foreach ($this->local_vars as $local_var) {
            if ($local_var->is_initialized()) {
                array_push($array, $local_var->get_name());
            }
        }
        return $array;
    }

    /* 
     * Add the trace array to the json_array
     */
    function finish() {
        $this->json_array["trace"] = $this->trace_array;
    }
    
    /* 
     * Encodes the array into a JSON string and returns it.
     */
    function return_json() {
        return json_encode($this->json_array);
    }

    function close() {
        fclose($this->pipes[1]);
        proc_close($this->process);
        // Eventually add delete file
    }
}
?>
