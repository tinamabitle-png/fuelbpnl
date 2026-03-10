package com.xcheng.printerservice;

import com.xcheng.printerservice.IPrinterCallback;

// Minimal service contract used by the Pro545PrinterBridge.
interface IPrinterService {
  void printerInit(IPrinterCallback cb);
  void setPrintEncode(String encode, IPrinterCallback cb);
  void setAlignment(int alignment, IPrinterCallback cb);
  void printText(String text, IPrinterCallback cb);
  void printWrapPaper(int lines, IPrinterCallback cb);
  void printQRCode(String text, int qrType, int qrSize, IPrinterCallback cb);
}

