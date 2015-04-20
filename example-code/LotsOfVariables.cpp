#include <iostream>

using namespace std;

void func() {
    int a;
    int b;
    double c;
    float d;
    bool e;

    a = 8;
    a = 15;
    c = 58.5;
    d = 1012.4;
    e = true;
}

int main(int argc, char **argv) {
    int x;
    double y;
    float f;
    bool b;
    char u;
    bool c;
    int z;
    double d;

    x = 2;
    y = 6.7;
    f = 10.1;
    b = true;
    u = 'c';
    c = false;
    z = 10;
    d = 897349873.3;
    
    func();

    while(z != 5) {
        u = u + 1;
        x = x + 1;
        z = z - 1;
        y = y * 4;
        d = d / 10;
    }
}
