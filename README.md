# How To Use
I'm going to try to explain how (mainly) GDBComm works, and how to use its methods.

## GDB/MI
This application uses GDB to get its information about the program execution. Specifically, it uses GDB/MI, which makes GDB easier to interpret. GDB/MI can be a
ctivated by using the --interpreter=mi command line option.

Example
	gdb --interpreter=mi test

Here are some useful commands:

