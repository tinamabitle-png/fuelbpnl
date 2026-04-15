package za.bwiser.driverapp

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.nfc.NfcAdapter
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.embedding.android.FlutterActivity
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    private val nfcChannel = "bwiser/nfc_hce"
    private val printerChannel = "bwiser/pro545_printer"
    private val ussdChannel = "bwiser/ussd_call"
    private val callPhonePermissionRequestCode = 9004
    private var pendingCallPermissionResult: MethodChannel.Result? = null

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

        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, ussdChannel)
            .setMethodCallHandler { call, result ->
                when (call.method) {
                    "isSupported" -> {
                        result.success(
                            packageManager.hasSystemFeature(PackageManager.FEATURE_TELEPHONY)
                        )
                    }
                    "hasCallPermission" -> {
                        result.success(hasCallPermission())
                    }
                    "requestCallPermission" -> {
                        if (hasCallPermission()) {
                            result.success(true)
                            return@setMethodCallHandler
                        }
                        if (pendingCallPermissionResult != null) {
                            result.error(
                                "permission_in_progress",
                                "A phone permission request is already in progress.",
                                null
                            )
                            return@setMethodCallHandler
                        }
                        pendingCallPermissionResult = result
                        ActivityCompat.requestPermissions(
                            this,
                            arrayOf(Manifest.permission.CALL_PHONE),
                            callPhonePermissionRequestCode
                        )
                    }
                    "launchUssdCall" -> {
                        val code = call.argument<String>("code") ?: ""
                        val sanitized = sanitizeUssdCode(code)
                        if (sanitized.isBlank()) {
                            result.error("invalid_code", "A valid USSD code is required.", null)
                            return@setMethodCallHandler
                        }
                        if (!hasCallPermission()) {
                            result.error(
                                "permission_denied",
                                "Phone call permission is required to launch the USSD flow.",
                                null
                            )
                            return@setMethodCallHandler
                        }

                        try {
                            val intent = Intent(Intent.ACTION_CALL).apply {
                                data = Uri.parse("tel:${Uri.encode(sanitized)}")
                            }
                            if (intent.resolveActivity(packageManager) == null) {
                                result.error("unsupported", "No dialer is available on this device.", null)
                                return@setMethodCallHandler
                            }
                            startActivity(intent)
                            result.success(true)
                        } catch (error: SecurityException) {
                            result.error(
                                "permission_denied",
                                error.message ?: "Phone call permission is required.",
                                null
                            )
                        } catch (error: Throwable) {
                            result.error(
                                "launch_failed",
                                error.message ?: "Could not start the USSD call.",
                                null
                            )
                        }
                    }
                    else -> result.notImplemented()
                }
            }
    }

    override fun onRequestPermissionsResult(
        requestCode: Int,
        permissions: Array<out String>,
        grantResults: IntArray
    ) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)

        if (requestCode != callPhonePermissionRequestCode) {
            return
        }

        val granted = grantResults.isNotEmpty() &&
            grantResults[0] == PackageManager.PERMISSION_GRANTED
        pendingCallPermissionResult?.success(granted)
        pendingCallPermissionResult = null
    }

    private fun hasCallPermission(): Boolean {
        return ContextCompat.checkSelfPermission(
            this,
            Manifest.permission.CALL_PHONE
        ) == PackageManager.PERMISSION_GRANTED
    }

    private fun sanitizeUssdCode(code: String): String {
        val compact = code.replace("\\s+".toRegex(), "")
        if (compact.isBlank() || compact.length > 64) {
            return ""
        }

        return if (compact.matches(Regex("^[0-9*#]+$"))) compact else ""
    }
}
