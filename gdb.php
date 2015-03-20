<?php
	require_once("includes/GDBComm.php");
	$start_time = microtime(true);
	$gdbcomm = new GDBComm("test.cpp");
//	$gdbcomm->debug();
        $gdbcomm->compile("#include<iostream>\n\nusing namespace std;\n\nint main(int argc, char **argv) {\nint x;\nx=4;\n}");
	/* Start GDB with the program running in debug mode */
	$gdbcomm->start();
	$gdbcomm->get_locals();
	$gdbcomm->set_watchpoint("x");
//	printf("Take a step\n");
        $gdbcomm->take_step();
  //      printf("Finished taking step\n");
	//$gdbcomm->take_step();
	$gdbcomm->finish();
//        $gdbcomm->print_array();
        $gdbcomm->return_json();
        $gdbcomm->close();
	$end_time = microtime(true);
	$total_time = $end_time - $start_time;
//	print "Total time = $total_time";
?>
