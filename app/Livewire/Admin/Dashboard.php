<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Transaction;
use App\Models\CrmNotification;
use Livewire\Component;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $successMsg = '';
    public $churnCount = 0;

    // Broadcast Campaign State
    public $campaignText = '';
    public $campaignSuccessMsg = '';

    public function broadcastCampaign()
    {
        $this->validate([
            'campaignText' => 'required|string|min:5|max:500',
        ]);

        $members = User::where('role', 'member')->get();
        $sentCount = 0;

        foreach ($members as $member) {
            // Create WhatsApp notification
            CrmNotification::create([
                'user_id' => $member->id,
                'type' => 'WhatsApp',
                'message' => "📢 PROMO CAFE: " . $this->campaignText,
            ]);

            // Create Email notification
            CrmNotification::create([
                'user_id' => $member->id,
                'type' => 'Email',
                'message' => "Halo {$member->name},\n\n" . $this->campaignText . "\n\nKunjungi kami dan nikmati kopinya!",
            ]);

            $sentCount++;
        }

        $this->campaignText = '';
        $this->campaignSuccessMsg = "📢 Kampanye berhasil dibroadcast ke {$sentCount} member (WhatsApp & Email)!";
    }

    /**
     * Run Churn Prevention Scan directly in Livewire
     */
    public function scanChurnPrevention()
    {
        $members = User::where('role', 'member')->get();
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $sentCount = 0;

        foreach ($members as $member) {
            $lastTx = $member->transactions()->latest()->first();
            
            // Member is inactive if their last transaction is older than 30 days
            // Or if they registered more than 30 days ago and have no transactions at all
            $isInactive = false;
            if ($lastTx) {
                if ($lastTx->created_at->lt($thirtyDaysAgo)) {
                    $isInactive = true;
                }
            } else {
                if ($member->created_at->lt($thirtyDaysAgo)) {
                    $isInactive = true;
                }
            }

            if ($isInactive) {
                // Check if we sent a churn prevention message in the last 7 days
                $alreadySent = CrmNotification::where('user_id', $member->id)
                    ->where('message', 'like', '%kami rindu Anda%')
                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                    ->exists();

                if (!$alreadySent) {
                    // Create simulated WA notification
                    CrmNotification::create([
                        'user_id' => $member->id,
                        'type' => 'WhatsApp',
                        'message' => "Halo {$member->name}, kami rindu Anda! Ini kupon diskon 20% khusus untukmu [VOUCHER: MISSYOU20], yuk kunjungi coffee shop kami lagi!",
                    ]);

                    // Create simulated Email notification
                    CrmNotification::create([
                        'user_id' => $member->id,
                        'type' => 'Email',
                        'message' => "Kami Rindu Anda, {$member->name}! Dapatkan diskon 20% untuk semua menu dengan voucher MISSYOU20.",
                    ]);

                    $sentCount++;
                }
            }
        }

        $this->successMsg = "Scan Churn Prevention selesai. Berhasil mengirimkan WhatsApp & Email penawaran khusus ke {$sentCount} pelanggan pasif.";
    }

    public function render()
    {
        // 1. Operational CRM Metrics
        $totalSales = Transaction::sum('total_bayar');
        $totalMembers = User::where('role', 'member')->count();
        $avgSpending = Transaction::avg('total_bayar') ?: 0;
        $totalTransactions = Transaction::count();

        // 2. Tier status counts
        $goldCount = User::where('role', 'member')->where('tier_status', 'Gold')->count();
        $silverCount = User::where('role', 'member')->where('tier_status', 'Silver')->count();
        $bronzeCount = User::where('role', 'member')->where('tier_status', 'Bronze')->count();

        // 3. Find Churn Customers (no transaction in > 30 days)
        $members = User::where('role', 'member')->get();
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $churnMembersList = [];

        foreach ($members as $member) {
            $lastTx = $member->transactions()->latest()->first();
            $isInactive = false;
            
            if ($lastTx) {
                if ($lastTx->created_at->lt($thirtyDaysAgo)) {
                    $isInactive = true;
                    $lastDate = $lastTx->created_at;
                }
            } else {
                if ($member->created_at->lt($thirtyDaysAgo)) {
                    $isInactive = true;
                    $lastDate = null;
                }
            }

            if ($isInactive) {
                $churnMembersList[] = [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'no_hp' => $member->no_hp,
                    'last_visited' => $lastTx ? $lastTx->created_at->diffForHumans() : 'Belum pernah bertransaksi',
                    'days_inactive' => $lastTx ? $lastTx->created_at->diffInDays(Carbon::now()) : $member->created_at->diffInDays(Carbon::now()),
                ];
            }
        }

        // Sort by longest inactive
        usort($churnMembersList, function($a, $b) {
            return $b['days_inactive'] <=> $a['days_inactive'];
        });

        $this->churnCount = count($churnMembersList);

        // 4. Gather Scatter Plot Cluster data
        $scatterPoints = [];
        foreach ($members as $m) {
            $features = $m->getKnnFeatures();
            $scatterPoints[] = [
                'x' => (float) $features[0], // Sweetness average (1-5)
                'y' => (float) $features[1], // Coffee ratio (0-1)
                'name' => $m->name,
                'label' => $m->behavior_label ?? 'Belum Terklasifikasi',
                'spending' => (float) $features[2], // Avg Spending
            ];
        }

        // 5. Recent Transactions & Notifications
        $recentTransactions = Transaction::with('user')->latest()->limit(5)->get();
        $recentNotifications = CrmNotification::with('user')->latest()->limit(8)->get();

        return view('livewire.admin.dashboard', [
            'totalSales' => $totalSales,
            'totalMembers' => $totalMembers,
            'avgSpending' => $avgSpending,
            'totalTransactions' => $totalTransactions,
            'goldCount' => $goldCount,
            'silverCount' => $silverCount,
            'bronzeCount' => $bronzeCount,
            'churnMembers' => $churnMembersList,
            'scatterPointsJson' => json_encode($scatterPoints),
            'recentTransactions' => $recentTransactions,
            'recentNotifications' => $recentNotifications,
        ])->layout('layouts.app');
    }
}
