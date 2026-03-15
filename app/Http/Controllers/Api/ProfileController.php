<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Get user profile
     */
    public function getProfile(Request $request)
    {
        $user = $request->user()->load([
            'wallet', 
            'creditLimit', 
            'devices',
            'leases' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(5);
            },
            'vouchers' => function ($query) {
                $query->where('status', 'issued')
                      ->where('expires_at', '>', now())
                      ->limit(5);
            }
        ]);

        // Calculate statistics
        $statistics = [
            'total_vouchers' => $user->vouchers()->count(),
            'active_vouchers' => $user->vouchers()
                ->where('status', 'issued')
                ->where('expires_at', '>', now())
                ->count(),
            'total_leases' => $user->leases()->count(),
            'active_leases' => $user->leases()->where('status', 'active')->count(),
            'total_repayments' => $user->repayments()->where('status', 'paid')->count(),
            'total_spent' => $user->vouchers()->sum('amount'),
            'days_active' => $user->created_at->diffInDays(now()),
            'savings_from_bnpl' => $this->calculateTotalSavings($user),
        ];

        // Get recent activity
        $recentActivity = $this->getRecentActivity($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->only([
                    'id', 'name', 'email', 'phone', 'credit_score', 
                    'status', 'created_at', 'last_login_at'
                ]),
                'wallet' => $user->wallet,
                'credit_limit' => $user->creditLimit,
                'devices' => $user->devices,
                'statistics' => $statistics,
                'recent_activity' => $recentActivity,
                'preferences' => $this->getUserPreferences($user),
                'notifications' => $this->getNotificationSettings($user),
            ]
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|max:2048',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date|before:-18 years',
            'gender' => 'nullable|in:male,female,other',
            'id_number' => 'nullable|string|max:20',
            'occupation' => 'nullable|string|max:100',
            'monthly_income' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'name', 'email', 'address', 'city', 'country',
            'date_of_birth', 'gender', 'id_number', 'occupation',
            'monthly_income'
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }
            
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        // Log the activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('profile_updated');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->fresh()->only(['name', 'email', 'avatar', 'phone']),
                'avatar_url' => $user->avatar ? Storage::url($user->avatar) : null,
            ]
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 401);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Log the activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('password_changed');

        // Invalidate other sessions (optional)
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'payment_reminders' => 'boolean',
            'credit_limit_alerts' => 'boolean',
            'promotional_offers' => 'boolean',
            'voucher_expiry_alerts' => 'boolean',
            'low_balance_alerts' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // In production, save to database
        // For now, simulate saving
        $settings = $request->all();
        $settings['updated_at'] = now();

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties($settings)
            ->log('notification_settings_updated');

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated',
            'data' => [
                'settings' => $settings,
            ]
        ]);
    }

    /**
     * Update preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'language' => 'nullable|in:en,sw',
            'currency' => 'nullable|in:KES,USD,EUR',
            'theme' => 'nullable|in:light,dark,system',
            'default_payment_method' => 'nullable|in:wallet,mpesa,bank_transfer',
            'default_fuel_type' => 'nullable|in:petrol,diesel,super',
            'auto_payment_enabled' => 'boolean',
            'biometric_login' => 'boolean',
            'data_saver' => 'boolean',
            'location_sharing' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // In production, save to database
        $preferences = $request->all();
        $preferences['updated_at'] = now();

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties($preferences)
            ->log('preferences_updated');

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated',
            'data' => [
                'preferences' => $preferences,
            ]
        ]);
    }

    /**
     * Get linked devices
     */
    public function devices(Request $request)
    {
        $user = $request->user();
        
        $devices = $user->devices()
            ->orderBy('last_login_at', 'desc')
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->id,
                    'device_name' => $device->device_name,
                    'device_type' => $device->device_type,
                    'last_login_at' => $device->last_login_at,
                    'last_login_ip' => $device->ip_address,
                    'location' => $this->getLocationFromIP($device->ip_address),
                    'is_current' => $device->last_login_at->diffInMinutes(now()) < 5,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'devices' => $devices,
                'current_device' => $devices->firstWhere('is_current', true),
                'total_devices' => $devices->count(),
            ]
        ]);
    }

    /**
     * Revoke device access
     */
    public function revokeDevice(Request $request, $deviceId)
    {
        $user = $request->user();
        
        $device = $user->devices()->find($deviceId);

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }

        // Delete device
        $device->delete();

        // Log the activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'device_id' => $deviceId,
                'device_name' => $device->device_name,
            ])
            ->log('device_revoked');

        return response()->json([
            'success' => true,
            'message' => 'Device access revoked',
        ]);
    }

    /**
     * Get account activity log
     */
    public function activityLog(Request $request)
    {
        $user = $request->user();
        $limit = $request->query('limit', 50);
        $page = $request->query('page', 1);
        $type = $request->query('type');

        $query = $user->auditLogs()
                     ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('action', 'like', "%{$type}%");
        }

        $activities = $query->paginate($limit, ['*'], 'page', $page);

        // Format activities for display
        $formattedActivities = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'action' => $activity->action,
                'description' => $activity->description,
                'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                'time_ago' => $activity->created_at->diffForHumans(),
                'ip_address' => $activity->ip_address,
                'location' => $this->getLocationFromIP($activity->ip_address),
                'changes' => $activity->old_values || $activity->new_values ? [
                    'old' => $activity->old_values,
                    'new' => $activity->new_values,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'activities' => $formattedActivities,
                'summary' => [
                    'total_activities' => $activities->total(),
                    'this_month' => $user->auditLogs()
                        ->whereMonth('created_at', now()->month)
                        ->count(),
                    'last_login' => $user->last_login_at,
                ],
            ]
        ]);
    }

    /**
     * Request account deletion
     */
    public function requestDeletion(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password'
            ], 401);
        }

        // Check if user has active leases
        $activeLeases = $user->leases()->where('status', 'active')->count();
        if ($activeLeases > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account with active leases. Please repay all loans first.',
                'active_leases' => $activeLeases,
            ], 422);
        }

        // Check if user has outstanding balance
        if ($user->wallet->outstanding_balance > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account with outstanding balance.',
                'outstanding_balance' => $user->wallet->outstanding_balance,
            ], 422);
        }

        // In production, create deletion request with 30-day grace period
        // For now, simulate request
        
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'reason' => $request->reason,
                'requested_at' => now(),
            ])
            ->log('account_deletion_requested');

        return response()->json([
            'success' => true,
            'message' => 'Account deletion request submitted',
            'data' => [
                'deletion_date' => now()->addDays(30)->format('Y-m-d'),
                'notice' => 'Your account will be permanently deleted in 30 days. You can cancel this request anytime before then.',
                'required_actions' => [
                    'Withdraw any remaining wallet balance',
                    'Cancel any active vouchers',
                    'Export your data if needed',
                ],
                'contact_support' => 'support@fuelcredit.com',
            ]
        ]);
    }

    /**
     * Export user data
     */
    public function exportData(Request $request)
    {
        $user = $request->user();

        // In production, generate and send data export
        // For now, return summary
        
        $dataSummary = [
            'profile' => $user->only(['name', 'email', 'phone', 'created_at']),
            'wallet' => $user->wallet,
            'credit_limit' => $user->creditLimit,
            'statistics' => [
                'total_vouchers' => $user->vouchers()->count(),
                'total_leases' => $user->leases()->count(),
                'total_repayments' => $user->repayments()->count(),
                'total_spent' => $user->vouchers()->sum('amount'),
            ],
            'last_updated' => now()->format('Y-m-d H:i:s'),
        ];

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('data_export_requested');

        return response()->json([
            'success' => true,
            'message' => 'Data export request received',
            'data' => [
                'summary' => $dataSummary,
                'estimated_time' => '24-48 hours',
                'delivery_method' => 'Email',
                'formats' => ['PDF', 'CSV', 'JSON'],
                'note' => 'Your data export will be sent to your registered email address.',
            ]
        ]);
    }

    /**
     * Get support information
     */
    public function support(Request $request)
    {
        $user = $request->user();

        $supportInfo = [
            'contact' => [
                'email' => 'support@fuelcredit.com',
                'phone' => '+254 700 123 456',
                'whatsapp' => '+254 700 123 456',
                'telegram' => '@fuelcredit_support',
            ],
            'hours' => [
                'weekdays' => '8:00 AM - 8:00 PM',
                'weekends' => '9:00 AM - 6:00 PM',
                'emergency' => '24/7 via app',
            ],
            'common_issues' => [
                [
                    'issue' => 'Voucher not working',
                    'solution' => 'Check expiry time and ensure station accepts vouchers',
                ],
                [
                    'issue' => 'Payment failed',
                    'solution' => 'Verify payment method and sufficient balance',
                ],
                [
                    'issue' => 'Credit limit not increased',
                    'solution' => 'Improve repayment history and wait 90 days',
                ],
            ],
            'user_specific' => [
                'account_manager' => 'Not assigned',
                'priority_support' => $user->credit_score > 700,
                'dedicated_channel' => $user->vouchers()->count() > 50,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $supportInfo,
        ]);
    }

    /**
     * Calculate total savings from BNPL
     */
    private function calculateTotalSavings($user)
    {
        $leases = $user->leases()->where('status', 'completed')->get();
        $totalSavings = 0;
        
        foreach ($leases as $lease) {
            // Simplified calculation: assume immediate payment would have been from savings
            // and BNPL allowed money to earn interest elsewhere
            $savings = $lease->principal_amount * 0.08 * ($lease->term_days / 365);
            $totalSavings += max(0, $savings - $lease->interest_amount);
        }
        
        return $totalSavings;
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity($user)
    {
        $activities = [];
        
        // Recent vouchers
        $recentVouchers = $user->vouchers()
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($voucher) {
                return [
                    'type' => 'voucher',
                    'action' => 'requested',
                    'amount' => $voucher->amount,
                    'fuel_type' => $voucher->fuel_type,
                    'time' => $voucher->created_at->diffForHumans(),
                ];
            });
        
        $activities = array_merge($activities, $recentVouchers->toArray());
        
        // Recent repayments
        $recentRepayments = $user->repayments()
            ->where('status', 'paid')
            ->orderBy('paid_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($repayment) {
                return [
                    'type' => 'repayment',
                    'action' => 'made',
                    'amount' => $repayment->amount,
                    'time' => $repayment->paid_at->diffForHumans(),
                ];
            });
        
        $activities = array_merge($activities, $recentRepayments->toArray());
        
        // Sort by time
        usort($activities, function ($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($activities, 0, 5);
    }

    /**
     * Get user preferences
     */
    private function getUserPreferences($user)
    {
        // In production, fetch from database
        return [
            'language' => 'en',
            'currency' => 'ZAR',
            'theme' => 'light',
            'default_payment_method' => 'mpesa',
            'default_fuel_type' => 'petrol',
            'auto_payment_enabled' => false,
            'biometric_login' => true,
            'data_saver' => false,
            'location_sharing' => true,
        ];
    }

    /**
     * Get notification settings
     */
    private function getNotificationSettings($user)
    {
        // In production, fetch from database
        return [
            'email_notifications' => true,
            'sms_notifications' => true,
            'push_notifications' => true,
            'payment_reminders' => true,
            'credit_limit_alerts' => true,
            'promotional_offers' => false,
            'voucher_expiry_alerts' => true,
            'low_balance_alerts' => true,
        ];
    }

    /**
     * Get location from IP (simulated)
     */
    private function getLocationFromIP($ip)
    {
        // In production, use IP geolocation service
        // For now, return simulated data
        $locations = ['Johannesburg', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret'];
        return $ip === '127.0.0.1' ? 'Localhost' : $locations[rand(0, 4)];
    }
}
