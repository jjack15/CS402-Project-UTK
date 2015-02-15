<?php
	require_once("includes/GDBComm.php");
	$start_time = microtime(true);
	$gdbcomm = new GDBComm("test.cpp");
	$gdbcomm->compile();
	/* Start GDB with the program running in debug mode */
	$gdbcomm->start();
	$gdbcomm->get_locals();
	$gdbcomm->set_watchpoint("x");
	$gdbcomm->take_step();
	$gdbcomm->take_step();
	$gdbcomm->close();
	$end_time = microtime(true);
	$total_time = $end_time - $start_time;
	print "Total time = $total_time";
?>