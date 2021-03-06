# How To Use
I'm going to try to explain how (mainly) GDBComm works, and how to use its methods.

## What needs to be implemented
* Support for multiple stack frames
* Support for more complex variables (vectors, classes)
* Support for stdin, arguments
* Support for uploading separate files
* Run application using safeexec

## GDB/MI
This application uses GDB to get its information about the program execution. Specifically, it uses GDB/MI, which makes GDB easier to interpret. GDB/MI can be a
ctivated by using the --interpreter=mi command line option.

Example

	gdb --interpreter=mi test

Here are some useful commands:

Start running program

	-exec-run

Insert breakpoint at main

	-break-insert main

Step to next line

	-exec-step

You can view the full documentation [here](https://sourceware.org/gdb/onlinedocs/gdb/GDB_002fMI.html#GDB_002fMI).

## GDBComm
GDBComm is the main class that compiles the user's program, executes gdb, and gets the output back to the frontend.

### Constructor
GDBComm takes the user's .cpp file name as its only argument (so far). For now, in gdb.php it is hardcoded as "test.cpp" in the future, this will be a time-based filename that will be unique for every time code is generated. Once the constructor is called, it assigns the file to the source file property, it makes the executable file name from that (just take off .cpp) and sets up its pipes. I will explain this use later in the `start` section.

### Compile()
The `compile` method will call use a call to `exec` to execute g++ on the desired program with the -g option to enable its use in GDB.

### Start()
The `start` method will actually call gdb and begin the debugging process, as the name implies. Of note is the call to `proc_open`. `proc_open` is similar to `exec` in that it executes a process, but is provides us with more control over the program. Like `exec`, you supply the command to execute, in our case we call `gdb` with the user's program and the GDB/MI option. We also supply it with an array for pipes. This array will be filled with stdin, stdout, and stderr pipes that we can use to communicate with the process.

	$this->process = proc_open("gdb --interpreter=mi $this->exec_file", $this->descriptor, $this->pipes);
	
`$this->descriptor` is an array that describes the pipes that will be supplied to `$this->pipes`. In this case, `$this->pipes[0]` provides stdout for the gdb process, `$this->pipes[1]` provides stdin, and `$this->pipes[2]` provides stderr.

This means that if you want to send a command to gdb you will write to `$this->pipes[1]`, and if you want to read output from that command you can read from `$this->pipes[0]`. For example, when we first start gdb, it will output a bunch of lines that we don't need (try running GDB with the test program to see). This means that we need a loop to read each line until the end. How do we know when the output is finished? GDB/MI helps us out with this by starting the last line with special characters such as "done" or "stopped".

In this case, the output will stop with ~"done." then a newline symbol. In `start`, we have a loop that calls `fgets` on the stdout pipe to get lines of output, check if they start with ~"done", and keep going if not. If it does start with the done characters, we still need to call `fgets` again because GDB/MI will spit out another line that reads "(gdb)" (again, try this). Once we process that line, we are now ready to input a line.