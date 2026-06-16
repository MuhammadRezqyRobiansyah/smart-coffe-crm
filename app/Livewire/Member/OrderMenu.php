<?php

namespace App\Livewire\Member;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\CrmNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrderMenu extends Component
{
    public $cart = []; // Array of ['nama' => '', 'harga' => 0, 'kategori' => '', 'manis' => 3, 'qty' => 1]
    
    // Receipt state
    public $showReceiptModal = false;
    public $lastTxData = [];
    public $successMessage = '';

    // Menu Catalog (shared with cashier)
    public $menuCatalog = [
        ['nama' => 'Espresso', 'harga' => 18000, 'kategori' => 'Coffee', 'manis' => 1, 'img' => '☕'],
        ['nama' => 'Americano', 'harga' => 20000, 'kategori' => 'Coffee', 'manis' => 1, 'img' => '☕'],
        ['nama' => 'Caffe Latte', 'harga' => 25000, 'kategori' => 'Coffee', 'manis' => 2, 'img' => '☕'],
        ['nama' => 'Cappuccino', 'harga' => 25000, 'kategori' => 'Coffee', 'manis' => 2, 'img' => '☕'],
        ['nama' => 'Flat White', 'harga' => 24000, 'kategori' => 'Coffee', 'manis' => 1, 'img' => '☕'],
        ['nama' => 'Kopi Susu Aren', 'harga' => 22000, 'kategori' => 'Non-Coffee', 'manis' => 4, 'img' => '🧋'],
        ['nama' => 'Matcha Latte', 'harga' => 28000, 'kategori' => 'Non-Coffee', 'manis' => 4, 'img' => '🍵'],
        ['nama' => 'Red Velvet Latte', 'harga' => 28000, 'kategori' => 'Non-Coffee', 'manis' => 4, 'img' => '🥤'],
        ['nama' => 'Chocolate Signature', 'harga' => 26000, 'kategori' => 'Non-Coffee', 'manis' => 4, 'img' => '🍫'],
        ['nama' => 'Caramel Macchiato', 'harga' => 29000, 'kategori' => 'Non-Coffee', 'manis' => 5, 'img' => '🥤'],
        ['nama' => 'Iced Lychee Tea', 'harga' => 22000, 'kategori' => 'Non-Coffee', 'manis' => 5, 'img' => '🧃'],
        ['nama' => 'Croissant Butter', 'harga' => 30000, 'kategori' => 'Food', 'manis' => 2, 'img' => '🥐'],
        ['nama' => 'Chocolate Fudge Cake', 'harga' => 35000, 'kategori' => 'Food', 'manis' => 4, 'img' => '🍰'],
        ['nama' => 'Cheesecake Premium', 'harga' => 40000, 'kategori' => 'Food', 'manis' => 3, 'img' => '🧀'],
        ['nama' => 'Almond Croissant', 'harga' => 32000, 'kategori' => 'Food', 'manis' => 3, 'img' => '🥐'],
        ['nama' => 'Affogato', 'harga' => 30000, 'kategori' => 'Food', 'manis' => 4, 'img' => '🍨'],
    ];

    public function addToCart($menuIndex)
    {
        $menuItem = $this->menuCatalog[$menuIndex];
        
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
        
        $this->successMessage = '';
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

    public function checkout()
    {
        if (empty($this->cart)) {
            return;
        }

        $user = Auth::user();
        if (!$user) {
            return;
        }

        $totalBayar = 0;
        $jumlahItem = 0;

        foreach ($this->cart as $item) {
            $totalBayar += $item['harga'] * $item['qty'];
            $jumlahItem += $item['qty'];
        }

        // Apply discount based on current Tier
        $tierDiscountPercent = 0;
        if ($user->tier_status === 'Silver') {
            $tierDiscountPercent = 5;
        } elseif ($user->tier_status === 'Gold') {
            $tierDiscountPercent = 10;
        }

        $discountAmount = 0.0;
        $originalTotal = $totalBayar;
        if ($tierDiscountPercent > 0) {
            $discountAmount = ($originalTotal * $tierDiscountPercent) / 100;
            $totalBayar = $originalTotal - $discountAmount;
        }

        // Calculate points
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
        $user->total_poin += $pointsEarned;
        $user->total_pengeluaran += $totalBayar;
        $user->save();

        // Recalculate loyalty tier
        $loyaltyResult = $user->recalculateLoyalty();

        // Add Notification
        $waMessage = "Halo {$user->name}, pesanan Anda sebesar Rp " . number_format($totalBayar, 0, ',', '.') . " berhasil diproses. Anda mendapatkan +{$pointsEarned} poin! Tier saat ini: {$user->tier_status}.";
        if ($loyaltyResult['tier_changed']) {
            $waMessage .= " Selamat! Status tier Anda naik dari {$loyaltyResult['old_tier']} ke {$loyaltyResult['new_tier']}!";
        }

        CrmNotification::create([
            'user_id' => $user->id,
            'type' => 'WhatsApp',
            'message' => $waMessage,
        ]);

        CrmNotification::create([
            'user_id' => $user->id,
            'type' => 'Email',
            'message' => "Detail Pesanan Smart Coffee CRM:\nTotal Bayar: Rp " . number_format($totalBayar, 0, ',', '.') . "\nTambahan Poin: {$pointsEarned} poin\nStatus Tier: {$user->tier_status}\nPesanan sedang diproses!",
        ]);

        // Save last transaction details for receipt modal
        $this->lastTxData = [
            'id' => $transaction->id,
            'items' => $this->cart,
            'subtotal' => $originalTotal,
            'discount_percent' => $tierDiscountPercent,
            'discount_amount' => $discountAmount,
            'final_total' => $totalBayar,
            'points_earned' => $pointsEarned,
            'total_points' => $user->total_poin,
            'date' => now()->format('d M Y H:i:s'),
            'tier_changed' => $loyaltyResult['tier_changed'],
            'new_tier' => $loyaltyResult['new_tier']
        ];
        
        $this->showReceiptModal = true;
        $this->successMessage = 'Pesanan berhasil dibuat!';
        $this->cart = [];
    }

    public function closeReceipt()
    {
        $this->showReceiptModal = false;
        $this->successMessage = '';
    }

    public function render()
    {
        $user = Auth::user();
        
        $cartTotal = 0;
        foreach ($this->cart as $item) {
            $cartTotal += $item['harga'] * $item['qty'];
        }

        $tierDiscountPercent = 0;
        if ($user && $user->tier_status === 'Silver') {
            $tierDiscountPercent = 5;
        } elseif ($user && $user->tier_status === 'Gold') {
            $tierDiscountPercent = 10;
        }
        
        $discountAmount = 0;
        $finalTotal = $cartTotal;
        if ($tierDiscountPercent > 0) {
            $discountAmount = ($cartTotal * $tierDiscountPercent) / 100;
            $finalTotal = $cartTotal - $discountAmount;
        }

        return view('livewire.member.order-menu', [
            'cartTotal' => $cartTotal,
            'discountPercent' => $tierDiscountPercent,
            'discountAmount' => $discountAmount,
            'finalTotal' => $finalTotal,
        ])->layout('layouts.app');
    }
}
