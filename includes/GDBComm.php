<?php
require_once("LocalVar.php");
require_once("Stack.php");
require_once("StackFrame.php");
require_once("TraceStep.php");
/* This class is used for all communication to the GDB
   process running, including compilation of the target
   program, starting GDB, and quitting GDB */

class GDBComm
{
    private $source_file = NULL;
    private $exec_file = NULL;
    private $pipes;
    private $descriptor;
    private $process;
    private $stopped = "*stopped";
    private $current_line;
    private $local_vars;
    private $stack;
    private $json_array;
    private $trace_array;
    private $ordered_locals;
    private $debug;
    private $frame_count;
    private $output_folder = "output";
    private $trace_count;

    function __construct($src_file) {
        $this->source_file = $src_file;
        $this->exec_file = substr($src_file, 0, strlen($src_file)-4);
        $this->pipes = array();
        $this->descriptor = array(
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
                2 => array("pipe", "error-output.txt", "a")
                );
        $this->stack = new Stack();
        $this->json_array = array();
        $this->ordered_locals = array();
        $this->trace_array = array();
        $this->debug = false;
        $this->frame_count = 0;
        $this->trace_count = 0;
    }

    function debug() {
        $this->debug = true;
    }

    /* Compile the source file into program that GDB can debug */
    function compile($input_code = null) {
        if ($input_code != null) {
            $this->json_array["code"] = $input_code;
            if ($this->debug) print "INPUT DOESN'T EQUAL NULL";
            $current_time = floatval(time());
            if ($this->debug) print "current_time = ".$current_time."\n";
            //mkdir(strval(current_time), 0700);
            if (!file_exists($this->output_folder)) mkdir(strval($this->output_folder));
            mkdir($this->output_folder."/".strval($current_time));
            //$this->source_file = strval($current_time)."/main.cpp";
            $this->source_file = $this->output_folder."/".strval($current_time)."/main.cpp";
            $this->exec_file = substr($this->source_file, 0, strlen($this->source_file)-4);
            if ($this->debug) echo "SOURCE FILE: ".$this->source_file."\n";
            $main_file = fopen($this->source_file, "w");
            fwrite($main_file, $input_code);
        }

        $descriptor_array = array(
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
                2 => array("pipe", "w")
                );
        //$result = exec("g++ -O0 -g " . $this->source_file . " -o " . $this->exec_file, $output, $rv);
        $process = proc_open("g++ -O0 -g " . $this->source_file . " -o " . $this->exec_file, $descriptor_array, $pipes);
        //print "Result is $rv\n";
        while ($f = fgets($pipes[2])) {
       //     print "G++: " . $f; 
        }
        $info = proc_get_status($process);
        if ($info["running"] == FALSE) {
            if ($this->debug) print "Exit code: \n";
            if ($info["exitcode"]) {
                if ($this->debug) print "There was an error!\n";
            }
            return 0;
        }
        //$this->trace_list = array();
        //$this->json_array["trace"] = $this->trace_list;  
        return 1;
    }

    /* Start the GDB instance and clear all the stdout */
    function start() {
        $error = fopen("error.txt", "w");
        fwrite($error, $this->exec_file);
        $this->process = proc_open("./gdb --interpreter=mi $this->exec_file", $this->descriptor, $this->pipes);
        if (is_resource($this->process)) fwrite($error, "YOOO");
        $about_proc = proc_get_status($this->process);
        if ($about_proc["running"]) {
            fwrite($error, "It's running");
        }
        while ($f = fgets($this->pipes[1])) {
            //print "$f\n";
            fwrite($error, "STUFF");
            if ($f == "~\"done.\\n\"\n") {
                $f = fgets($this->pipes[1]);
                break;
            }
        }
        fwrite($error, "\n$f\n");

        /* Set the breakpoint at main */
        $fout = fwrite($this->pipes[0], "-break-insert main\r\n");
        //print "fout = $fout\n";
        fgets($this->pipes[1]);
        fgets($this->pipes[1]);

        /* Run GDB */
        $fout = fwrite($this->pipes[0], "-exec-run\r\n");
        $trace_step = new TraceStep(); 
        $trace_step->set_event("call");

        $fp = fopen("matches.txt", "w");
        fwrite($fp, "YOOO");
        /* Keep getting output until it has stopped */
        while ($f = fgets($this->pipes[1])) {
            //print $f;
            fwrite($fp, "Yo");
            if (substr($f, 0, 8) == "*stopped") {
                preg_match('/line="([0-9]*)"/', $f, $matches);   
                fwrite($fp, "LINE");
                $trace_step->set_line(intval($matches[1]));
                preg_match('/func="([A-Za-z0-9_]*)"/', $f, $matches);
                $trace_step->set_func_name($matches[1]);
                if ($this->debug) print "\n$f\n";
                $f = fgets($this->pipes[1]);
                break;
            }
        }
        fwrite($fp, "AFTER");
        //$trace_step->set_line(7);
        //$trace_step->set_func_name("hello");
        $this->local_vars = $this->get_locals();
        $ordered_locals = array(); 
        if ($this->debug) print "\nPRINTING LOCALS\n";
        if ($this->debug) print_r($locals);
        foreach ($this->local_vars as $local_var) {
            $this->set_watchpoint($local_var->get_name());
        }
        
        $stack_frame = new StackFrame($trace_step->get_func_name().":".$trace_step->get_line());
        $stack_frame->set_ordered_varnames($ordered_locals);
        $stack_frame->set_frame_id($this->frame_count++);
        $this->stack->push($stack_frame); 
        $stack_as_array = $this->stack->return_array();
        if ($this->debug) print_r($stack_as_array);
        //frame_count = frame_count + 1;
        $trace_step->set_stack($stack_as_array);
        if ($this->debug) print "\nORDERED LOCALS\n";
        if ($this->debug) print_r($ordered_locals);
        if ($this->debug) print "CURRENT LINE: ".$this->current_line."\n";
        array_push($this->trace_array, $trace_step->return_array());
        //print "\n\nTRACE ARRAY\n\n";
        //print_r($trace_step->return_array());
        //print_r($this->trace_array); 
        //print $f;

        /* At this point, gdb has started, loaded the program, and is now executing, but stopped at a breakpoint in main */
        $this->trace_count++;
    }

    /* Get local variables on the stack, and record them into the class */
    function get_locals() {
        $fp = fopen("yo.txt", "w");
        fwrite($fp, "YOOO");
        if ($this->debug) print "In get_locals()\n"; 
        $local_vars = array();
        $fout = fwrite($this->pipes[0], "-stack-list-locals --skip-unavailable 1\r\n");
        //print "fout for locals: $fout\n";
        $f = fgets($this->pipes[1]);
        fwrite($fp, $f);
        if ($this->debug) print "\n$f\n";
        $result = preg_match_all('/name="([A-Za-z0-9_]*)",value="([A-Za-z0-9_]*)/', $f, $matches);
        //print "Any matches? $result\n";
        if ($this->debug) print_r($matches);
        $var_names = $matches[1];
        $var_values = $matches[2];
        $i = 0;
        foreach ($var_names as $var_name) {
            // FOR NOW LOCAL VARS ARE HARD CODED AS PRIMITIVES
            $new_local = new LocalVar($var_name);
            //array_push($this->local_vars, $new_local);
            $new_local->set_value($var_values[$i]);
            $local_vars[$var_name] = $new_local;
            if ($this->debug) print "i = $i\n";
            $i++;
        }
        /*$i = 0;
        foreach ($var_values as $var_value) {
            $new_local = $this->local_vars[$i];
            $new_local->set_value($var_value);
            $i++;
        }*/
        //print "Matches: $vars[0]\n";
        if ($this->debug) print_r($local_vars);
        $f = fgets($this->pipes[1]);
        if ($this->debug) print $f;
        return $local_vars;
    }

    function set_watchpoint($variable) {
        //print "\n In set watchpoint \n";
        $fout = fwrite($this->pipes[0], "-break-watch $variable\r\n");
        //print "After writing $fout\n";
        $f1 = fgets($this->pipes[1]);
        //print "After the first fgets()\n";
        $f2 = fgets($this->pipes[1]);
        if ((substr($f1, 0, 5) == "^done") && (substr($f2, 0, 5) == "(gdb)")) {
            //print "\nWe did it!\n";
        }
    }

    /* Take a *step* through the code. After calling this function, you must read the output and see if any variables were changed or if the frame has changed */
    function take_step() {
        $trace_step = new TraceStep();
        //print "\nIn take step\n";
        $fout = fwrite($this->pipes[0], "-exec-step\r\n");
        //print "fout: $fout\n";
        //set_watchpoint("x");
        $f = null;
        while($f = fgets($this->pipes[1])) {
           // print "\n$f";
            /* Detect when the execution of step has stopped */
            if (substr($f, 0, 8) == "*stopped") {
                $reason_return = preg_match('/reason="([A-Za-z0-9\\-._]*)"/', $f, $matches);
                //print "REASON: \n";
               // print_r($matches);
                if ($matches[1] == "watchpoint-trigger") {
                    //print "YO THE WATCHPOINT WAS TRIGGERED!\n";
                    //echo "$f\n";
                    preg_match('/line="([0-9]*)"/', $f, $line_match);
                    $trace_step->set_line($line_match[1]);

                    preg_match('/wpt={number="[0-9]*",exp="([A-Za-z0-9_]*)"}/', $f, $watchpoint_match);
             //       print_r($watchpoint_match);
                    preg_match('/value={old="[0-9A-Za-z]*",new="([0-9A-Za-z]*)"/', $f, $new_value);
                    
               //     print_r($new_value);
                    $this->local_vars[$watchpoint_match[1]]->set_value($new_value[1]);
                    $this->local_vars[$watchpoint_match[1]]->set_initialized();
                 //   echo "\n\nNEW VALUE: ".$this->local_vars[$watchpoint_match[1]]->get_value()."\n\n";
                    break;
                }
                $return = preg_match('/file="([A-Za-z0-9._\/]*)"/', $f, $matches);
               // echo "\nAFTER RETURN!\n";
               // echo "$f\n";
               // print_r($matches);
                if (isset($matches[1])) {
                 //   print "\nRETURN: $return $matches[1]\n";
                    //print_r($matches);
                 //   echo "\n\n$this->source_file\n\n";
                    if ($matches[1] == $this->source_file) {
                        //if ($this->debug) echo "YO IT MATCHES\n";
                    //    echo "YO IT MATCHES\n";
                        preg_match('/line="([0-9]*)"/', $f, $line_match);
                        //print_r($line_match);
                        $trace_step->set_line($line_match[1]);
                        echo "TRACE STEP LINE: ".$trace_step->get_line()."\n";
                   //     echo "We're breaking!\n";
                        break;	
                    }
                }	
            }
            else {
                //print "Standard output: $f";
            }
            //fgets($this->pipes[1]);
        }
        //print "\n$f";
       // echo "\nIs this gonna work?";
        fgets($this->pipes[1]);
        preg_match('/func="([A-Za-z0-9._\/]*)"/', $f, $matches);
        //print_r($matches);
        $trace_step->set_func_name($matches[1]);
        $stack_frame = $this->stack->top();
        $stack_frame->set_func_name($matches[1]);
        $test_array = $this->return_encoded_locals();
        $stack_frame->set_encoded_locals($test_array);
        $stack_frame->set_ordered_varnames($this->return_ordered_varnames());
        $this->stack->set_new_top($stack_frame);
        $trace_step->set_stack($this->stack->return_array());
        array_push($this->trace_array, $trace_step->return_array());
        $this->trace_count++;
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
            //echo $local_var->get_name()." : ".$local_var->get_value()."\n";
        }
    }

    function print_array() {
        print(json_encode($this->json_array));
    }
   
    function return_encoded_locals() {
        $array = array();
        foreach ($this->local_vars as $local_var) {
            //print "\nENCODED LOCALS\n";
            if ($local_var->is_initialized()) {
              //  print "\nRUH ROH\n";
                $array[$local_var->get_name()] = $local_var->get_value();
            }
        }
        //print_r($array);
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

    function finish() {
        if ($this->debug) print "\nIn finish\n";
        $this->json_array["trace"] = $this->trace_array;
        //print_r($this->json_array);
    }
    
    function return_json() {
        //return json_encode($this->json_array);
        $final_file = fopen("final2.txt", "w");
        fwrite($final_file, json_encode($this->json_array));
        return json_encode($this->json_array);
    }

    function close() {
        fclose($this->pipes[1]);
        proc_close($this->process);
//        unlink("test");
    }
}
?>
