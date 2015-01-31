<?php

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

    function __construct($src_file) {
        $this->source_file = $src_file;
        $this->exec_file = substr($src_file, 0, strlen($src_file)-4);
        $this->pipes = array();
        $this->descriptor = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "error-output.txt", "a")
        );
    }

    /* Compile the source file into program that GDB can debug */
    function compile() {
		$descriptor_array = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "error-output.txt", "a")
        );
        $result = exec("g++ -O0 -g " . $this->source_file . " -o " . $this->exec_file, $output, $rv);
		//$process = proc_open("g++ -O0 -g " . $this->source_file . " -o " . $this->exec_file, $output, $rv);
		//print "Result is $rv\n";
		//print_r($output);
    }

    /* Start the GDB instance and clear all the stdout */
    function start() {
        $this->process = proc_open("gdb --interpreter=mi $this->exec_file", $this->descriptor, $this->pipes);
    	while ($f = fgets($this->pipes[1])) {
			//print "$f\n";
			if ($f == "~\"done.\\n\"\n") {
				//print "Yoooo";
				$f = fgets($this->pipes[1]);
				break;
			}
		}

		/* Set the breakpoint at main */
		$fout = fwrite($this->pipes[0], "-break-insert main\r\n");
		//print "fout = $fout\n";
		fgets($this->pipes[1]);
		fgets($this->pipes[1]);
	
		/* Run GDB */
		$fout = fwrite($this->pipes[0], "-exec-run\r\n");

		while ($f = fgets($this->pipes[1])) {
			//print $f;
			if (substr($f, 0, 8) == "*stopped") {
				break;
			}
		}
		$f = fgets($this->pipes[1]);
		//print $f;

		/* At this point, gdb has started, loaded the program, and is now executing, but stopped at a breakpoint in main */
	}
	
	/* Get local variables on the stack, and record them into the class */
	function get_locals() {
		$fout = fwrite($this->pipes[0], "-stack-list-locals --skip-unavailable 1\r\n");
		//print "fout for locals: $fout\n";
		$f = fgets($this->pipes[1]);
		print "\n$f\n";
		$result = preg_match_all('/name="([A-Za-z0-9_]*)",value="([A-Za-z0-9_]*)/', $f, $matches);
		//print "Any matches? $result\n";
		print_r($matches);
		$f = fgets($this->pipes[1]);
		print $f;
	}
	
	/* Take a *step* through the code. After calling this function, you must read the output and see if any variables were changed or if the frame has changed */
	function take_step() {
		print "\nIn take step\n";
		$fout = fwrite($this->pipes[0], "-exec-step\r\n");
		print "fout: $fout\n";
		while($f = fgets($this->pipes[1])) {
			print "\n$f";
			if (substr($f, 0, 8) == "*stopped") {
				print "Starts with stopped!\n";
				$return = preg_match('/file="([A-Za-z0-9._]*)"/', $f, $matches);
				if (isset($matches[1])) {
					print "\nRETURN: $return $matches[1]\n";
					print_r($matches);
					if ($matches[1] == $this->source_file) {
						preg_match('/line="([0-9]*)"/', $f, $line_match);
						print_r($line_match);
						$this->current_line = $line_match[1];
						break;	
					}
				}	
			}
			else {
				print "Standard output: $f";
			}
			//fgets($this->pipes[1]);
		}
		//print "\n$f";
		fgets($this->pipes[1]);
	}
	
	/* Get the current line that the is being debugged */
	function get_current_line() {
		return $this->current_line;	
	}

	function close() {
		fclose($this->pipes[1]);
		proc_close($this->process);
		unlink("test");
	}
}
?>
