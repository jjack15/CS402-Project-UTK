<?php
	/* 
    * gdb.php
    * Entry code to the backend. Shows the general outline of trace
    * information without lower level code. JSON array is returned
    * here.
    */
  	require_once("includes/GDBComm.php");
  	
    /* Header for json */
    header("Content-type: text/plain; charset=utf-8"); 
    $compile_result;
    $stdin = "";
    
    /*
     * Check if the script is being run from the web application.
     * If run by web application, the $_REQUEST variable will be
     * set with information including the code and standard in.
     */
    if ($_REQUEST != null) {
        $data = $_REQUEST['data'];
        $data = json_decode($data, true);
        $usercode = $data['user_script'];
        $stdin = $data['stdin'];
    }

    /* If you want to record the time of the execution */
    $start_time = microtime(true);
    $gdbcomm = new GDBComm("test.cpp");
    
    /*
     * Determine if the application was used. If so, take code
     * inpu there and compile from that (this creates a file in
     * the output folder). Otherwise, look in local directory for
     * "test.cpp" and compile that.
     */
    if (isset($data)) {
        $compile_result = $gdbcomm->compile($usercode);
    }
    else {
        $file_text = file_get_contents("test.cpp");
        $compile_result = $gdbcomm->compile($file_text); 
    }

	/* Check for compile errors */
    if (!$compile_result) {
        echo $gdbcomm->get_error();
        return 0;
    }

    /* 
     * Create the GDB process, do initial commands to start
     * GDB and then run get_locals() to put local variables
     * in variable defed in includes/GDBComm.php.
     */
    $gdbcomm->start($stdin);
    $gdbcomm->get_locals();
    
    /* 
    * While loop keeps taking a step until take_step()
    * indicates that it is at the end of a program
    */
    while ($gdbcomm->take_step());
    $gdbcomm->finish();
    echo $gdbcomm->return_json();
    $gdbcomm->close();
    $end_time = microtime(true);
    $total_time = $end_time - $start_time;
    return 0;
?>
