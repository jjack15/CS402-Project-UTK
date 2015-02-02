# How To Use
I'm going to try to explain how (mainly) GDBComm works, and how to use its methods.

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
GDBComm takes the user's .cpp file name as its only argument (so far). For now, in gdb.php it is hardcoded as "test.cpp" in the future, this will be a time-based filename that will be unique for every time code is generated. Once the constructor is called, it assigns the file to the source file property, it makes the executable file name from that (just take off .cpp) and sets up its pipes. I will explain this use later in the start() section.

### Compile()
The compile method will call use a call to exec() to execute g++ on the desired program with the -g option to enable its use in GDB.

### Start()
The start method will actually call gdb and begin the debugging process, as the name implies. Of note is the call to proc_open.
