<div class="mb-8">
    <h4 class="text-md font-semibold text-gray-900 mb-4">Identity & Compliance Documents</h4>
    <p class="text-sm text-gray-500 mb-4">Upload any identity or banking documents you have available. All uploads are optional.</p>

    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-xl border border-dashed border-blue-300 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">ID Document</p>
                <input type="file" name="id_document" accept=".pdf,.jpg,.jpeg,.png"
                       class="mt-3 block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                @error('id_document')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-xl border border-dashed border-blue-300 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Driver License</p>
                <input type="file" name="driver_license_document" accept=".pdf,.jpg,.jpeg,.png"
                       class="mt-3 block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                @error('driver_license_document')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-700">3-Month Bank Statement (Optional)</p>
                <input type="file" name="bank_statement_document" accept=".pdf,.jpg,.jpeg,.png"
                       class="mt-3 block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
                @error('bank_statement_document')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <p class="mt-3 text-xs text-gray-500">Allowed: PDF/JPG/PNG. Maximum 5MB per file.</p>
    </div>
</div>

<div class="mb-8">
    <h4 class="text-md font-semibold text-gray-900 mb-4">Payment Details (Optional)</h4>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Payment Method</label>
            <select name="payment_method_preference"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Select payment method</option>
                <option value="bank_transfer" @selected(old('payment_method_preference') === 'bank_transfer')>Bank Transfer</option>
                <option value="card" @selected(old('payment_method_preference') === 'card')>Card</option>
                <option value="mobile_money" @selected(old('payment_method_preference') === 'mobile_money')>Mobile Money</option>
            </select>
            @error('payment_method_preference')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Account Name</label>
            <input type="text" name="payment_account_name"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   value="{{ old('payment_account_name') }}" placeholder="Optional">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
            <input type="text" name="payment_account_number"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   value="{{ old('payment_account_number') }}" placeholder="Optional">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
            <input type="text" name="payment_bank_name"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   value="{{ old('payment_bank_name') }}" placeholder="Optional">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Branch Code</label>
            <input type="text" name="payment_branch_code"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   value="{{ old('payment_branch_code') }}" placeholder="Optional">
        </div>
    </div>
</div>
