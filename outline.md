# Outline

## State: Compilation
This step deals with compiling the user's program from a .cpp file.

1. Execute "g++ -g -0O test_12345.cpp -o test_12345"
2. Capture output of this execution
	* If error, save error, output to front end, and exit.
3. Get globals from the executable file using nm
4. Save the globals using nm