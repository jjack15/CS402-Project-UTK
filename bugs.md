# Known Bugs
In addition to features not yet supported, there are some features that are only partially supported because of bugs.

## Setting variable equal to function return value
Example code:

    int test () {
    	int y;
    	y = 8;
    	return y;    }
    
    int main (int argc, char **argv) {
    	int x;
    	x = 100;
    	x = test();
    	return 0;    }
    
Running this code will result in a server error because when returning from the test function, it doesn't have the correct current list of variables in main.

### How to Fix
Detect when you are looking for a variable that isn't in the current scope. (Check func="(name)" and make sure it equals the current function)

## cout deletes watchpoints
Example code:

    #include <iostream>
    
    using namespace std;
    
    int main(int argc, char **argv) {
    	int x;
    	x = 8;
    	cout << "Hello World!" << endl;
    	x = 10;
    	cout << "Another line!" << endl;
    	x = 100;
    }

### How to Fix
The cause of this is the new "set can-use-ha-watchpoints 0" line. Watchpoints are deleted when gdb gets lines from other files (like when running the << operator).

## Sub Blocks
Example Code:

    int x;
    x = 8;
    if (x == 8) {
    	int y;
    	y = 8;    }

y will not be displayed on the frontend.

### How to Fix
We will have to check for updates to declared variables. Currently we only check for these at the beginning of a new function. We may have to check for changes after every step in the same function.

# Other Issues

## Strings / Standard Out / Vectors
These will have to use functionality found in other files. These lines are unimportant to us, but seem to have to be processed. This creates a lot of extra processing that is not needed, and it slows down the program considerably.