package com.xcheng.printerservice;

import com.xcheng.printerservice.IPrinterCallback;

interface IPrinterService {
    void printerInit(in IPrinterCallback callback);
    int updatePrinterState(in IPrinterCallback callback);
    boolean printerPaper(in IPrinterCallback callback);
    void sendRAWData(in byte[] data, in IPrinterCallback callback);
    void setAlignment(int align, in IPrinterCallback callback);
    void printText(String text, in IPrinterCallback callback);
    void printQRCode(String text, int align, int size, in IPrinterCallback callback);
    void printWrapPaper(int n, in IPrinterCallback callback);
    void setPrintEncode(String encode, in IPrinterCallback callback);
}

