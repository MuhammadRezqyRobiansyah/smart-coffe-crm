<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    
    // Modal Edit State
    public $showEditModal = false;
    public $editUserId;
    public $editName;
    public $editEmail;
    public $editPhone;
    public $editTier;
    public $editPoints;
    public $editSpending;

    // Modal Create State
    public $showCreateModal = false;
    public $newName = '';
    public $newEmail = '';
    public $newPhone = '';

    // Modal Delete State
    public $showDeleteModal = false;
    public $deleteUserId;
    public $deleteUserName;

    public $successMessage = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openEditModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->editUserId = $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editPhone = $user->no_hp;
        $this->editTier = $user->tier_status;
        $this->editPoints = $user->total_poin;
        $this->editSpending = $user->total_pengeluaran;
        
        $this->successMessage = '';
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
    }

    public function saveEdit()
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editEmail' => 'required|email|unique:users,email,' . $this->editUserId,
            'editPhone' => 'required|string',
            'editTier' => 'required|in:Bronze,Silver,Gold',
            'editPoints' => 'required|integer|min:0',
            'editSpending' => 'required|numeric|min:0',
        ]);

        $user = User::findOrFail($this->editUserId);
        $user->update([
            'name' => $this->editName,
            'email' => $this->editEmail,
            'no_hp' => $this->editPhone,
            'tier_status' => $this->editTier,
            'total_poin' => $this->editPoints,
            'total_pengeluaran' => $this->editSpending,
        ]);

        $this->showEditModal = false;
        $this->successMessage = 'Data member berhasil diperbarui!';
    }

    public function openCreateModal()
    {
        $this->newName = '';
        $this->newEmail = '';
        $this->newPhone = '';
        $this->successMessage = '';
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
    }

    public function createMember()
    {
        $this->validate([
            'newName' => 'required|string|max:255',
            'newEmail' => 'required|email|unique:users,email',
            'newPhone' => 'required|string',
        ]);

        User::create([
            'name' => $this->newName,
            'email' => $this->newEmail,
            'no_hp' => $this->newPhone,
            'password' => Hash::make('password'),
            'role' => 'member',
            'tier_status' => 'Bronze',
            'total_poin' => 0,
            'total_pengeluaran' => 0.00
        ]);

        $this->showCreateModal = false;
        $this->successMessage = 'Member baru berhasil ditambahkan!';
    }

    public function confirmDelete($userId, $userName)
    {
        $this->deleteUserId = $userId;
        $this->deleteUserName = $userName;
        $this->successMessage = '';
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
    }

    public function deleteMember()
    {
        $user = User::findOrFail($this->deleteUserId);

        // Guard: prevent deleting admin users or self
        if ($user->role === 'admin' || $user->id === auth()->id()) {
            $this->showDeleteModal = false;
            $this->successMessage = '';
            $this->addError('delete', 'Tidak dapat menghapus akun admin atau akun Anda sendiri.');
            return;
        }

        $user->delete();

        $this->showDeleteModal = false;
        $this->successMessage = 'Member berhasil dihapus!';
    }

    public function render()
    {
        $members = User::where('role', 'member')
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('no_hp', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.user-management', [
            'members' => $members
        ])->layout('layouts.app');
    }
}
