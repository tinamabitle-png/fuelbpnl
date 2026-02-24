package com.example.mobile_app

import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.content.ServiceConnection
import android.os.IBinder
import android.os.RemoteException
import com.xcheng.printerservice.IPrinterCallback
import com.xcheng.printerservice.IPrinterService

object Pro545PrinterBridge {
    private var service: IPrinterService? = null
    private var isBound = false

    private val callback = object : IPrinterCallback.Stub() {
        override fun onException(code: Int, msg: String?) {}
        override fun onLength(current: Long, total: Long) {}
        override fun onComplete() {}
    }

    private val connection = object : ServiceConnection {
        override fun onServiceConnected(name: ComponentName?, binder: IBinder?) {
            service = IPrinterService.Stub.asInterface(binder)
        }

        override fun onServiceDisconnected(name: ComponentName?) {
            service = null
            isBound = false
        }
    }

    private fun bind(context: Context): Boolean {
        if (service != null) return true
        val intent = Intent().apply {
            `package` = "com.xcheng.printerservice"
            action = "com.xcheng.printerservice.IPrinterService"
        }
        isBound = context.applicationContext.bindService(
            intent,
            connection,
            Context.BIND_AUTO_CREATE
        )
        return isBound
    }

    fun isAvailable(context: Context): Boolean {
        return bind(context)
    }

    fun printReceipt(context: Context, payload: Map<String, Any?>): Result<Unit> {
        if (!bind(context)) {
            return Result.failure(IllegalStateException("PrinterService bind failed"))
        }
        val svc = service ?: return Result.failure(IllegalStateException("PrinterService unavailable"))

        val station = ((payload["station"] as? Map<*, *>)?.get("name")
            ?: payload["station_name"]
            ?: "Station").toString()
        val driver = ((payload["driver"] as? Map<*, *>)?.get("name")
            ?: "Unknown").toString()
        val voucherId = (payload["voucher_id"] ?: "-").toString()
        val voucherCode = (payload["voucher_code"] ?: payload["code"] ?: "-").toString()
        val amount = (payload["amount"] ?: "0").toString()
        val status = (payload["status"] ?: "unknown").toString().uppercase()
        val txStatus = (payload["transaction_status"] ?: "unknown").toString().uppercase()
        val whenAt = (payload["redeemed_at"] ?: payload["issued_at"] ?: "").toString()
        val qr = (payload["qr_code"] ?: voucherCode).toString()

        return try {
            svc.printerInit(callback)
            svc.setPrintEncode("utf-8", callback)
            svc.setAlignment(1, callback)
            svc.printText("BWISER POS RECEIPT\n", callback)
            svc.setAlignment(0, callback)
            svc.printText("Station: $station\n", callback)
            svc.printText("Driver: $driver\n", callback)
            svc.printText("Voucher ID: $voucherId\n", callback)
            svc.printText("Voucher Code: $voucherCode\n", callback)
            svc.printText("Amount: R $amount\n", callback)
            svc.printText("Voucher Status: $status\n", callback)
            svc.printText("Transaction: $txStatus\n", callback)
            if (whenAt.isNotBlank()) {
                svc.printText("When: $whenAt\n", callback)
            }
            svc.printWrapPaper(1, callback)
            svc.setAlignment(1, callback)
            svc.printQRCode(qr, 1, 4, callback)
            svc.printWrapPaper(3, callback)
            Result.success(Unit)
        } catch (e: RemoteException) {
            Result.failure(e)
        } catch (e: Throwable) {
            Result.failure(e)
        }
    }
}

