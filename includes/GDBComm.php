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
        return exec("g++ -O0 -g " . $this->source_file . " -o " . $this->exec_file);
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
		print "fout = $fout\n";
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
		print $f;

		/* At this point, gdb has started, loaded the program, and is now executing, but stopped at a breakpoint in main */
	}
	
	/* Get local variables on the stack, and record them into the class */
	function get_locals() {
		$fout = fwrite($this->pipes[0], "-stack-list-locals --skip-unavailable 1\r\n");
		print "fout for locals: $fout\n";
		$f = fgets($this->pipes[1]);
		$result = preg_match_all('/name="([A-Za-z0-9_]*)"/', $f, $matches);
		print "Any matches? $result\n";
		print_r($matches);
		print $f;
	}
		
	function close() {
		fclose($this->pipes[1]);
		proc_close($this->process);
		unlink("test");
	}
}
?>
