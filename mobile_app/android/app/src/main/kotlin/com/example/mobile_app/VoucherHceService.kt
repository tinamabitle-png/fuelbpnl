package za.bwiser.driverapp

import android.nfc.cardemulation.HostApduService
import android.os.Bundle
import java.nio.charset.StandardCharsets

class VoucherHceService : HostApduService() {
    companion object {
        private val SELECT_OK_SW = byteArrayOf(0x90.toByte(), 0x00.toByte())
        private val UNKNOWN_CMD_SW = byteArrayOf(0x6F.toByte(), 0x00.toByte())
        private val APP_AID = "F222222222"
        private const val MAX_PAYLOAD_BYTES = 220

        @Volatile
        private var tapToken: String = ""

        fun setTapToken(value: String) {
            tapToken = value.trim()
        }

        fun clearTapToken() {
            tapToken = ""
        }
    }

    override fun processCommandApdu(commandApdu: ByteArray?, extras: Bundle?): ByteArray {
        if (commandApdu == null || commandApdu.isEmpty()) {
            return UNKNOWN_CMD_SW
        }

        if (isSelectAidApdu(commandApdu)) {
            val tokenBytes = tapToken.toByteArray(StandardCharsets.UTF_8)
            if (tokenBytes.isEmpty()) {
                return SELECT_OK_SW
            }
            val payload = if (tokenBytes.size > MAX_PAYLOAD_BYTES) {
                tokenBytes.copyOf(MAX_PAYLOAD_BYTES)
            } else {
                tokenBytes
            }
            return payload + SELECT_OK_SW
        }

        return UNKNOWN_CMD_SW
    }

    override fun onDeactivated(reason: Int) {
        // no-op
    }

    private fun isSelectAidApdu(apdu: ByteArray): Boolean {
        val hex = apdu.joinToString("") { "%02X".format(it) }
        val aid = APP_AID
        return hex.startsWith("00A40400") && hex.contains(aid)
    }
}
