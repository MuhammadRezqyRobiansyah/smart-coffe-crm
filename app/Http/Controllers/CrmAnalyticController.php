<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CrmNotification;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CrmAnalyticController extends Controller
{
    /**
     * Get JSON data for Chart.js scatter plot
     */
    public function getClusterData()
    {
        $members = User::where('role', 'member')->get();

        $data = $members->map(function ($member) {
            $features = $member->getKnnFeatures();
            return [
                'id' => $member->id,
                'name' => $member->name,
                'avg_sweetness' => $features[0],
                'coffee_ratio' => $features[1],
                'avg_spending' => $features[2],
                'label' => $member->behavior_label ?? 'Belum Terklasifikasi',
                'tier' => $member->tier_status,
            ];
        });

        return response()->json($data);
    }

    /**
     * Run Churn Prevention Scan
     * Detects members who haven't made a transaction in over 30 days
     * and generates simulated notification logs.
     */
    public function runChurnPrevention()
    {
        $members = User::where('role', 'member')->get();
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $sentCount = 0;

        foreach ($members as $member) {
            $lastTx = $member->transactions()->latest()->first();
            
            // Check if user has no transactions or last transaction is older than 30 days
            // For seeding purposes, let's treat no transaction or > 30 days as inactive
            if ($lastTx && $lastTx->created_at->lt($thirtyDaysAgo)) {
                // Check if we already sent a churn prevention message in the last 7 days to avoid duplicates
                $alreadySent = CrmNotification::where('user_id', $member->id)
                    ->where('message', 'like', '%kami rindu Anda%')
                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                    ->exists();

                if (!$alreadySent) {
                    CrmNotification::create([
                        'user_id' => $member->id,
                        'type' => 'WhatsApp',
                        'message' => "Halo {$member->name}, kami rindu Anda! Ini kupon diskon 20% khusus untukmu [VOUCHER: MISSYOU20], yuk kunjungi coffee shop kami lagi!",
                    ]);

                    CrmNotification::create([
                        'user_id' => $member->id,
                        'type' => 'Email',
                        'message' => "Kami Rindu Anda, {$member->name}! Dapatkan diskon 20% untuk semua menu dengan voucher MISSYOU20.",
                    ]);

                    $sentCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Scan selesai. Berhasil mendeteksi pelanggan tidak aktif dan mengirimkan {$sentCount} set notifikasi WhatsApp & Email.",
            'sent_count' => $sentCount
        ]);
    }
}
