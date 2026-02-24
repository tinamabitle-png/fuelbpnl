package com.example.mobile_app

import android.nfc.NfcAdapter
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.embedding.android.FlutterActivity
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    private val nfcChannel = "bwiser/nfc_hce"
    private val printerChannel = "bwiser/pro545_printer"

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)

        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, nfcChannel)
            .setMethodCallHandler { call, result ->
                when (call.method) {
                    "isAvailable" -> {
                        val adapter = NfcAdapter.getDefaultAdapter(this)
                        result.success(adapter != null && adapter.isEnabled)
                    }
                    "setTapToken" -> {
                        val token = call.argument<String>("token") ?: ""
                        if (token.isBlank()) {
                            result.error("invalid_token", "Token is required.", null)
                            return@setMethodCallHandler
                        }
                        VoucherHceService.setTapToken(token)
                        result.success(true)
                    }
                    "clearTapToken" -> {
                        VoucherHceService.clearTapToken()
                        result.success(true)
                    }
                    else -> result.notImplemented()
                }
            }

        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, printerChannel)
            .setMethodCallHandler { call, result ->
                when (call.method) {
                    "isAvailable" -> {
                        result.success(Pro545PrinterBridge.isAvailable(this))
                    }
                    "printReceipt" -> {
                        val payload = call.argument<Map<String, Any?>>("payload")
                        if (payload == null) {
                            result.error("invalid_payload", "Missing receipt payload.", null)
                            return@setMethodCallHandler
                        }
                        val printResult = Pro545PrinterBridge.printReceipt(this, payload)
                        if (printResult.isSuccess) {
                            result.success(true)
                        } else {
                            result.error(
                                "print_failed",
                                printResult.exceptionOrNull()?.message ?: "PrinterService print failed.",
                                null
                            )
                        }
                    }
                    else -> result.notImplemented()
                }
            }
    }
}
