<?php

namespace App\Livewire\Settings;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('ตั้งค่า: จัดการผู้ใช้งาน')]
class Users extends Component
{
    // Create/edit form
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'teacher';

    public string $line_user_id = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Change-password modal
    public ?int $passwordUserId = null;

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    // Delete confirmation
    public ?int $confirmingDeleteId = null;

    public ?string $actionError = null;

    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'line_user_id' => ['nullable', 'string', 'max:255'],
        ];

        if (! $this->editingId) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'email.unique' => 'อีเมลนี้มีผู้ใช้งานในระบบแล้ว',
            'password.confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->line_user_id = (string) $user->line_user_id;
        $this->password = '';
        $this->password_confirmation = '';

        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->actionError = null;

        // Guard: an admin may not demote themselves away from admin if they
        // are the last remaining admin — that would lock everyone out of Settings.
        if ($this->editingId === auth()->id() && $this->role !== UserRole::Admin->value) {
            $adminCount = User::where('role', UserRole::Admin->value)->count();
            $currentUser = User::find($this->editingId);

            if ($currentUser?->role === UserRole::Admin && $adminCount <= 1) {
                $this->actionError = 'ไม่สามารถเปลี่ยนสิทธิ์ตัวเองได้ เนื่องจากเป็นผู้ดูแลระบบคนสุดท้าย';

                return;
            }
        }

        $validated = $this->validate();

        if ($this->editingId) {
            User::findOrFail($this->editingId)->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'line_user_id' => $validated['line_user_id'] ?: null,
            ]);
            session()->flash('success', 'บันทึกการแก้ไขผู้ใช้งานเรียบร้อยแล้ว');
        } else {
            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'line_user_id' => $validated['line_user_id'] ?: null,
                'password' => Hash::make($validated['password']),
            ]);
            session()->flash('success', 'เพิ่มผู้ใช้งานเรียบร้อยแล้ว');
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function openPasswordModal(int $id): void
    {
        $this->passwordUserId = $id;
        $this->newPassword = '';
        $this->newPassword_confirmation = '';
        $this->resetErrorBag(['newPassword', 'newPassword_confirmation']);
    }

    public function closePasswordModal(): void
    {
        $this->passwordUserId = null;
        $this->newPassword = '';
        $this->newPassword_confirmation = '';
    }

    public function updatePassword(): void
    {
        $this->validate([
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'newPassword.confirmed' => 'การยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        User::findOrFail($this->passwordUserId)->update([
            'password' => Hash::make($this->newPassword),
        ]);

        session()->flash('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
        $this->closePasswordModal();
    }

    public function confirmDelete(int $id): void
    {
        $this->actionError = null;

        if ($id === auth()->id()) {
            $this->actionError = 'ไม่สามารถลบบัญชีของตัวเองได้';

            return;
        }

        $target = User::find($id);

        if ($target?->role === UserRole::Admin && User::where('role', UserRole::Admin->value)->count() <= 1) {
            $this->actionError = 'ไม่สามารถลบผู้ดูแลระบบคนสุดท้ายได้';

            return;
        }

        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        if ($this->confirmingDeleteId) {
            User::findOrFail($this->confirmingDeleteId)->delete();
            $this->confirmingDeleteId = null;
            session()->flash('success', 'ลบผู้ใช้งานเรียบร้อยแล้ว');
        }
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'line_user_id', 'password', 'password_confirmation']);
        $this->role = 'teacher';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.settings.users', [
            'users' => User::orderBy('name')->get(),
            'roles' => UserRole::cases(),
        ]);
    }
}
