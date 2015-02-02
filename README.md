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

### Compile()
The compile method will call use a call to exec() to execute g++ on the desired program with the -g option to enable its use in GDB.
