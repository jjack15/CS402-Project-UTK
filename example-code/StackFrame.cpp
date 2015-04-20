#include <iostream>

using namespace std;

void testing() {
    int a;
    a = 4;
}

void test() {
    int z;
    z = 8;
    z = 10;
    testing();
}

int main(int argc, char **argv) {
    int x;
    int y;
    double b;

    b = 8.4;

    x = 8;
    x = 10;
    x = 100;
    y = 8;
    test();
}
