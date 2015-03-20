<?php
	require_once("Stack.php");
	require_once("StackFrame.php");

	$stack = new Stack();
	$frame = new StackFrame("main");
	//$stack->push(5);
	/*$current = $stack->top();
	echo gettype($current);*/
	echo gettype($frame);
	$stack->push($frame);
	$frame_from_stack = $stack->top();
	echo gettype($frame_from_stack);
	echo $frame_from_stack->get_name();
?>