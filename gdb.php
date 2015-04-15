<?php
//	error_reporting(E_ERROR);
        require_once("includes/GDBComm.php");
        header("Content-type: text/plain; charset=utf-8"); 

        $stdin = "";
        if ($_REQUEST != null) {
            $data = $_REQUEST['data'];
            $data = json_decode($data, true);
            $usercode = $data['user_script'];
            $stdin = $data['stdin'];
	}

        $compile_result;

        $start_time = microtime(true);
	$gdbcomm = new GDBComm("test.cpp");
//	$gdbcomm->debug();
        if (isset($data)) {
            $compile_result = $gdbcomm->compile($usercode);
        }
        else {
            $file_text = file_get_contents("test.cpp");
            $compile_result = $gdbcomm->compile($file_text); 
//            $gdbcomm->set_source("test.cpp");
        }

        if (!$compile_result) {
            //return 0;
//            echo "WE IN HERE\n";
            echo $gdbcomm->get_error();
            return 0;
        }

        /* Start GDB with the program running in debug mode */
	$gdbcomm->start($stdin);
        $gdbcomm->get_locals();
        while ($gdbcomm->take_step());
        $gdbcomm->finish();
        echo $gdbcomm->return_json();
        return 0;
        $gdbcomm->close();
	$end_time = microtime(true);
	$total_time = $end_time - $start_time;
?>
