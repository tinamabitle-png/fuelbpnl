package com.xcheng.printerservice;

// Minimal callback contract used by the Pro545PrinterBridge.
interface IPrinterCallback {
  void onException(int code, String msg);
  void onLength(long current, long total);
  void onComplete();
}

