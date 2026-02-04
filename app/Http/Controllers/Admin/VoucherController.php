<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FuelVoucher;
use App\Models\FuelStation;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = FuelVoucher::with(['user', 'fuelStation', 'lease']);
        
        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('station_id')) {
            $query->where('fuel_station_id', $request->station_id);
        }
        
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $vouchers = $query->latest()->paginate(20);
        $stations = FuelStation::all();
        
        return view('admin.vouchers.index', compact('vouchers', 'stations'));
    }

    public function pending()
    {
        $vouchers = FuelVoucher::with(['user', 'fuelStation'])
            ->where('status', 'issued')
            ->where('expires_at', '>', now())
            ->latest()
            ->paginate(20);
            
        return view('admin.vouchers.pending', compact('vouchers'));
    }

    public function show(FuelVoucher $voucher)
    {
        $voucher->load(['user', 'fuelStation', 'lease.repayments', 'settlement']);
        
        return view('admin.vouchers.show', compact('voucher'));
    }

    public function approve(FuelVoucher $voucher)
    {
        if ($voucher->status !== 'issued') {
            return back()->with('error', 'Voucher cannot be approved.');
        }
        
        $voucher->update(['status' => 'approved']);
        
        // Send notification to user
        // Notification::send($voucher->user, new VoucherApproved($voucher));
        
        return back()->with('success', 'Voucher approved successfully.');
    }

    public function reject(FuelVoucher $voucher, Request $request)
    {
        $request->validate(['reason' => 'required|string']);
        
        $voucher->update([
            'status' => 'cancelled',
            'transaction_reference' => $request->reason,
        ]);
        
        // Refund if BNPL
        if ($voucher->lease_id) {
            $voucher->user->wallet->decrement('outstanding_balance', $voucher->amount);
        }
        
        return back()->with('success', 'Voucher rejected successfully.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,expire',
            'vouchers' => 'required|array',
            'vouchers.*' => 'exists:fuel_vouchers,id',
        ]);
        
        $vouchers = FuelVoucher::whereIn('id', $request->vouchers)->get();
        
        foreach ($vouchers as $voucher) {
            if ($request->action === 'approve' && $voucher->status === 'issued') {
                $voucher->update(['status' => 'approved']);
            } elseif ($request->action === 'reject') {
                $voucher->update(['status' => 'cancelled']);
            } elseif ($request->action === 'expire') {
                $voucher->update(['status' => 'expired']);
            }
        }
        
        return back()->with('success', count($vouchers) . ' vouchers updated successfully.');
    }

    public function export(Request $request)
    {
        $query = FuelVoucher::with(['user', 'fuelStation']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $vouchers = $query->get();
        
        $csv = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
        $csv->insertOne(['ID', 'Code', 'User', 'Station', 'Amount', 'Status', 'Issued At', 'Expires At']);
        
        foreach ($vouchers as $voucher) {
            $csv->insertOne([
                $voucher->id,
                $voucher->code,
                $voucher->user->name,
                $voucher->fuelStation->name,
                $voucher->amount,
                $voucher->status,
                $voucher->issued_at->format('Y-m-d H:i:s'),
                $voucher->expires_at->format('Y-m-d H:i:s'),
            ]);
        }
        
        $csv->output('vouchers_' . date('Y-m-d') . '.csv');
    }
}