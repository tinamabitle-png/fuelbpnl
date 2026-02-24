package com.xcheng.printerservice;

interface IPrinterCallback {
    void onException(int code, String msg);
    void onLength(long current, long total);
    void onComplete();
}

