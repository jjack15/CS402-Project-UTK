<?php
	error_reporting(E_ERROR);
        require_once("includes/GDBComm.php");
        header("Content-type: text/plain; charset=utf-8"); 
        if ($_REQUEST != null) {
            $data = $_REQUEST['data'];
            $data = json_decode($data, true);
            $usercode = $data['user_script'];
	}

        $compile_result;

        $start_time = microtime(true);
	$gdbcomm = new GDBComm("test.cpp");
//	$gdbcomm->debug();
        if (isset($data)) {
            $compile_result = $gdbcomm->compile($usercode);
        }
        else {
            //$compile_result = $gdbcomm->compile("#include<iostream>\n\nusing namespace std;\n\nint main(int argc, char **argv) {\nint x;\nx=4;\nx = 8;\n}");
            /*while (!feof($fp)) {
                echo "hit\n";
                $file_text = strval(fgets($fp));
            }*/
            $compile_result = $gdbcomm->compile($file_text); 
        }

        if (!$compile_result) {
            //return 0;
            return $gdbcomm->get_error();
        }

        /* Start GDB with the program running in debug mode */
	$gdbcomm->start();
        $gdbcomm->get_locals();
        while ($gdbcomm->take_step());
        $gdbcomm->finish();
        echo $gdbcomm->return_json();
        return 0;
        $gdbcomm->close();
	$end_time = microtime(true);
	$total_time = $end_time - $start_time;
?>
