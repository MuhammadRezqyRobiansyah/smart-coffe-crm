<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\CrmNotification;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;

class Cashier extends Component
{
    // Search & Selection
    public $search = '';
    public $selectedUserId = null;
    
    // New User Form
    public $newMemberName = '';
    public $newMemberEmail = '';
    public $newMemberPhone = '';
    public $showCreateModal = false;

    // Cart details
    public $cart = []; // Array of ['nama' => '', 'harga' => 0, 'kategori' => '', 'manis' => 3, 'qty' => 1]
    
    // Coupon Code Wallet
    public $couponCode = '';
    public $couponApplied = false;
    public $couponError = '';
    public $couponDiscountPercent = 0;

    // Simulated Receipt Modal State
    public $showReceiptModal = false;
    public $lastTxData = [];

    // Success State
    public $successMessage = '';
    public $pointsEarned = 0;
    public $tierChanged = false;
    public $newTier = '';

    // Points Redemption
    public $usePoints = false;
    public $pointsRedeemed = 0;
    public $pointsDiscountAmount = 0;

    // Menu Catalog
    public $menuCatalog = [
        ['nama' => 'Espresso', 'harga' => 18000, 'kategori' => 'Coffee', 'manis' => 1],
        ['nama' => 'Americano', 'harga' => 20000, 'kategori' => 'Coffee', 'manis' => 1],
        ['nama' => 'Caffe Latte', 'harga' => 25000, 'kategori' => 'Coffee', 'manis' => 2],
        ['nama' => 'Cappuccino', 'harga' => 25000, 'kategori' => 'Coffee', 'manis' => 2],
        ['nama' => 'Flat White', 'harga' => 24000, 'kategori' => 'Coffee', 'manis' => 1],
        ['nama' => 'Kopi Susu Aren', 'harga' => 22000, 'kategori' => 'Non-Coffee', 'manis' => 4],
        ['nama' => 'Matcha Latte', 'harga' => 28000, 'kategori' => 'Non-Coffee', 'manis' => 4],
        ['nama' => 'Red Velvet Latte', 'harga' => 28000, 'kategori' => 'Non-Coffee', 'manis' => 4],
        ['nama' => 'Chocolate Signature', 'harga' => 26000, 'kategori' => 'Non-Coffee', 'manis' => 4],
        ['nama' => 'Caramel Macchiato', 'harga' => 29000, 'kategori' => 'Non-Coffee', 'manis' => 5],
        ['nama' => 'Iced Lychee Tea', 'harga' => 22000, 'kategori' => 'Non-Coffee', 'manis' => 5],
        ['nama' => 'Croissant Butter', 'harga' => 30000, 'kategori' => 'Food', 'manis' => 2],
        ['nama' => 'Chocolate Fudge Cake', 'harga' => 35000, 'kategori' => 'Food', 'manis' => 4],
        ['nama' => 'Cheesecake Premium', 'harga' => 40000, 'kategori' => 'Food', 'manis' => 3],
        ['nama' => 'Almond Croissant', 'harga' => 32000, 'kategori' => 'Food', 'manis' => 3],
        ['nama' => 'Affogato', 'harga' => 30000, 'kategori' => 'Food', 'manis' => 4],
    ];

    public function selectUser($id)
    {
        $this->selectedUserId = $id;
        $this->successMessage = '';
    }

    public function deselectUser()
    {
        $this->selectedUserId = null;
        $this->successMessage = '';
        $this->couponCode = '';
        $this->couponApplied = false;
        $this->couponDiscountPercent = 0;
        $this->couponError = '';
        $this->usePoints = false;
        $this->pointsRedeemed = 0;
        $this->pointsDiscountAmount = 0;
    }

    public function applyCoupon()
    {
        $this->couponError = '';
        $this->couponApplied = false;
        $this->couponDiscountPercent = 0;

        $code = strtoupper(trim($this->couponCode));
        if (empty($code)) {
            return;
        }

        switch ($code) {
            case 'MISSYOU20':
            case 'SWEET20':
                $this->couponDiscountPercent = 20;
                $this->couponApplied = true;
                break;
            case 'STRONG15':
            case 'PREMIUM15':
                $this->couponDiscountPercent = 15;
                $this->couponApplied = true;
                break;
            case 'COFFEEWELCOME':
                $this->couponDiscountPercent = 10;
                $this->couponApplied = true;
                break;
            default:
                $this->couponError = 'Kupon tidak valid!';
                $this->couponDiscountPercent = 0;
                break;
        }
    }

    public function addToCart($menuIndex)
    {
        $menuItem = $this->menuCatalog[$menuIndex];
        
        // Check if item already exists in cart
        foreach ($this->cart as $key => $cartItem) {
            if ($cartItem['nama'] === $menuItem['nama']) {
                $this->cart[$key]['qty']++;
                return;
            }
        }

        $this->cart[] = [
            'nama' => $menuItem['nama'],
            'harga' => $menuItem['harga'],
            'kategori' => $menuItem['kategori'],
            'manis' => $menuItem['manis'],
            'qty' => 1
        ];
    }

    public function updateSweetness($cartIndex, $val)
    {
        if (isset($this->cart[$cartIndex])) {
            $this->cart[$cartIndex]['manis'] = (int) $val;
        }
    }

    public function removeFromCart($cartIndex)
    {
        unset($this->cart[$cartIndex]);
        $this->cart = array_values($this->cart);
    }

    public function incrementQty($cartIndex)
    {
        if (isset($this->cart[$cartIndex])) {
            $this->cart[$cartIndex]['qty']++;
        }
    }

    public function decrementQty($cartIndex)
    {
        if (isset($this->cart[$cartIndex])) {
            $this->cart[$cartIndex]['qty']--;
            if ($this->cart[$cartIndex]['qty'] <= 0) {
                $this->removeFromCart($cartIndex);
            }
        }
    }

    public function createMember()
    {
        $this->validate([
            'newMemberName' => 'required|string|max:255',
            'newMemberEmail' => 'required|email|unique:users,email',
            'newMemberPhone' => 'required|string',
        ]);

        $user = User::create([
            'name' => $this->newMemberName,
            'email' => $this->newMemberEmail,
            'no_hp' => $this->newMemberPhone,
            'password' => Hash::make('password'), // default password
            'role' => 'member',
            'tier_status' => 'Bronze',
            'total_poin' => 0,
            'total_pengeluaran' => 0.00
        ]);

        // Send welcome notifications
        CrmNotification::create([
            'user_id' => $user->id,
            'type' => 'WhatsApp',
            'message' => "Halo {$user->name}, selamat! Anda terdaftar sebagai member di Smart Coffee CRM. Kumpulkan transaksi untuk naik ke Silver dan nikmati diskon 5%!",
        ]);

        CrmNotification::create([
            'user_id' => $user->id,
            'type' => 'Email',
            'message' => "Selamat bergabung di Smart Coffee CRM! Dapatkan update promo personal terbaik Anda di dashboard.",
        ]);

        $this->selectedUserId = $user->id;
        $this->newMemberName = '';
        $this->newMemberEmail = '';
        $this->newMemberPhone = '';
        $this->showCreateModal = false;

        $this->successMessage = 'Member baru berhasil didaftarkan dan terpilih!';
    }

    public function submitTransaction()
    {
        if (empty($this->cart)) {
            return;
        }

        if (!$this->selectedUserId) {
            $this->addError('transaction', 'Silakan pilih member terlebih dahulu.');
            return;
        }

        $user = User::find($this->selectedUserId);
        if (!$user) {
            return;
        }

        $totalBayar = 0;
        $jumlahItem = 0;

        foreach ($this->cart as $item) {
            $totalBayar += $item['harga'] * $item['qty'];
            $jumlahItem += $item['qty'];
        }

        // Apply discount based on current Tier + Coupon:
        // Silver: 5% discount, Gold: 10% discount, Bronze: 0%
        $tierDiscountPercent = 0;
        if ($user->tier_status === 'Silver') {
            $tierDiscountPercent = 5;
        } elseif ($user->tier_status === 'Gold') {
            $tierDiscountPercent = 10;
        }

        $couponPercent = $this->couponApplied ? $this->couponDiscountPercent : 0;
        $totalDiscountPercent = $tierDiscountPercent + $couponPercent;

        $discountAmount = 0.0;
        $originalTotal = $totalBayar;
        if ($totalDiscountPercent > 0) {
            $discountAmount = ($originalTotal * $totalDiscountPercent) / 100;
            $totalBayar = $originalTotal - $discountAmount;
        }

        // Apply Points Discount
        $pointsRedeemed = 0;
        $pointsDiscountAmount = 0.0;
        if ($this->usePoints && $user->total_poin >= 1000) {
            $maxPointsRedeemableByPoin = floor($user->total_poin / 1000) * 1000;
            $maxPointsRedeemableByTotal = floor($totalBayar / 10000) * 1000;
            $pointsRedeemed = (int) min($maxPointsRedeemableByPoin, $maxPointsRedeemableByTotal);
            $pointsDiscountAmount = ($pointsRedeemed / 1000) * 10000;
            $totalBayar = $totalBayar - $pointsDiscountAmount;
        }

        // Calculate points based on the current tier:
        // Every 10,000 kelipatan gets 100 points
        // Gold: multiplier 1.5x, Silver: multiplier 1.2x, Bronze: multiplier 1.0x
        $basePoints = floor($totalBayar / 10000) * 100;
        $multiplier = 1.0;
        if ($user->tier_status === 'Gold') {
            $multiplier = 1.5;
        } elseif ($user->tier_status === 'Silver') {
            $multiplier = 1.2;
        }
        $pointsEarned = (int) ($basePoints * $multiplier);

        // Record Transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'total_bayar' => $totalBayar,
            'jumlah_item' => $jumlahItem,
        ]);

        // Record Details
        foreach ($this->cart as $item) {
            for ($k = 0; $k < $item['qty']; $k++) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'nama_menu' => $item['nama'],
                    'kategori_menu' => $item['kategori'],
                    'rasa_manis' => $item['manis'],
                    'harga' => $item['harga'],
                ]);
            }
        }

        // Update customer points and total spending
        $user->total_poin -= $pointsRedeemed; // deduct first
        $user->total_poin += $pointsEarned;   // then add points from new transaction
        $user->save();

        // Recalculate loyalty tier
        $loyaltyResult = $user->recalculateLoyalty();

        // Add Notification triggers
        $waMessage = "Halo {$user->name}, transaksi Rp " . number_format($totalBayar, 0, ',', '.') . " berhasil dibayar. Anda mendapatkan +{$pointsEarned} poin! Tier saat ini: {$user->tier_status}.";
        if ($loyaltyResult['tier_changed']) {
            $waMessage .= " Selamat! Status tier Anda naik dari {$loyaltyResult['old_tier']} ke {$loyaltyResult['new_tier']}!";
            $this->tierChanged = true;
            $this->newTier = $loyaltyResult['new_tier'];
        } else {
            $this->tierChanged = false;
        }

        CrmNotification::create([
            'user_id' => $user->id,
            'type' => 'WhatsApp',
            'message' => $waMessage,
        ]);

        CrmNotification::create([
            'user_id' => $user->id,
            'type' => 'Email',
            'message' => "Detail Pembelian Smart Coffee CRM:\nTotal Bayar: Rp " . number_format($totalBayar, 0, ',', '.') . "\nTambahan Poin: {$pointsEarned} poin\nStatus Tier: {$user->tier_status}\nTerima kasih atas kunjungan Anda!",
        ]);

        // Save last transaction details for receipt modal
        $this->lastTxData = [
            'id' => $transaction->id,
            'customer_name' => $user->name,
            'tier' => $user->tier_status,
            'items' => $this->cart,
            'subtotal' => $originalTotal,
            'discount_percent' => $totalDiscountPercent,
            'discount_amount' => $discountAmount,
            'points_redeemed' => $pointsRedeemed,
            'points_discount_amount' => $pointsDiscountAmount,
            'final_total' => $totalBayar,
            'points_earned' => $pointsEarned,
            'total_points' => $user->total_poin,
            'date' => now()->format('d M Y H:i:s'),
        ];
        $this->showReceiptModal = true;

        $this->pointsEarned = $pointsEarned;
        $this->successMessage = 'Transaksi berhasil dicatat!';
        $this->cart = [];
        
        // Reset Coupon
        $this->couponCode = '';
        $this->couponApplied = false;
        $this->couponDiscountPercent = 0;
        $this->usePoints = false;
        $this->pointsRedeemed = 0;
        $this->pointsDiscountAmount = 0;
    }

    public function render()
    {
        // Fetch members matching search filter
        $members = User::where('role', 'member')
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('no_hp', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->limit(8)
            ->get();

        $selectedUser = $this->selectedUserId ? User::find($this->selectedUserId) : null;

        // Calculate Cart Totals
        $cartTotal = 0;
        foreach ($this->cart as $item) {
            $cartTotal += $item['harga'] * $item['qty'];
        }

        // Calculate discounted totals if member selected and/or coupon applied
        $tierDiscountPercent = 0;
        if ($selectedUser) {
            if ($selectedUser->tier_status === 'Silver') {
                $tierDiscountPercent = 5;
            } elseif ($selectedUser->tier_status === 'Gold') {
                $tierDiscountPercent = 10;
            }
        }
        
        $couponPercent = $this->couponApplied ? $this->couponDiscountPercent : 0;
        $totalDiscountPercent = $tierDiscountPercent + $couponPercent;
        
        $discountAmount = 0;
        $finalTotal = $cartTotal;
        if ($totalDiscountPercent > 0) {
            $discountAmount = ($cartTotal * $totalDiscountPercent) / 100;
            $finalTotal = $cartTotal - $discountAmount;
        }

        // Calculate Points Discount for live preview
        $pointsRedeemed = 0;
        $pointsDiscountAmount = 0.0;
        if ($selectedUser && $this->usePoints && $selectedUser->total_poin >= 1000) {
            $maxPointsRedeemableByPoin = floor($selectedUser->total_poin / 1000) * 1000;
            $maxPointsRedeemableByTotal = floor($finalTotal / 10000) * 1000;
            $pointsRedeemed = (int) min($maxPointsRedeemableByPoin, $maxPointsRedeemableByTotal);
            $pointsDiscountAmount = ($pointsRedeemed / 1000) * 10000;
            $finalTotal = $finalTotal - $pointsDiscountAmount;
        }
        $this->pointsRedeemed = $pointsRedeemed;
        $this->pointsDiscountAmount = $pointsDiscountAmount;

        return view('livewire.admin.cashier', [
            'members' => $members,
            'selectedUser' => $selectedUser,
            'cartTotal' => $cartTotal,
            'discountPercent' => $totalDiscountPercent,
            'discountAmount' => $discountAmount,
            'pointsRedeemed' => $pointsRedeemed,
            'pointsDiscountAmount' => $pointsDiscountAmount,
            'finalTotal' => $finalTotal,
        ])->layout('layouts.app');
    }
}
