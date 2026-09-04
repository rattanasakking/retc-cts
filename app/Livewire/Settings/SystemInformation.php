<?php

namespace App\Livewire\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('ตั้งค่า: ข้อมูลระบบ')]
class SystemInformation extends Component
{
    use WithFileUploads;

    public string $system_name = '';

    public string $short_name = '';

    public string $college_name = '';

    public string $primary_color = '';

    public string $contact_email = '';

    public string $contact_phone = '';

    public ?string $currentLogoPath = null;

    public $logo = null;

    /** สีสำเร็จรูปให้เลือก เผื่อไม่มีรหัสสีประจำสถาบันอยู่ในมือ */
    public const COLOR_PRESETS = [
        '#00e5ff' => 'ฟ้านีออน (ค่าเริ่มต้น)',
        '#32d74b' => 'เขียวมะนาว',
        '#64d2ff' => 'ฟ้าอ่อน',
        '#ffd60a' => 'เหลือง',
        '#ff9f0a' => 'ส้ม',
        '#ff453a' => 'แดง',
        '#bf5af2' => 'ม่วง',
        '#ff375f' => 'ชมพูบานเย็น',
    ];

    public function mount(): void
    {
        $setting = SystemSetting::current();

        $this->system_name = $setting->system_name;
        $this->short_name = (string) $setting->short_name;
        $this->college_name = (string) $setting->college_name;
        $this->primary_color = (string) $setting->primary_color;
        $this->contact_email = (string) $setting->contact_email;
        $this->contact_phone = (string) $setting->contact_phone;
        $this->currentLogoPath = $setting->logo_path;
    }

    protected function rules(): array
    {
        return [
            'system_name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:60'],
            'college_name' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function messages(): array
    {
        return [
            'system_name.required' => 'กรุณากรอกชื่อระบบ',
            'short_name.max' => 'ชื่อย่อควรสั้นกว่านี้ (ไม่เกิน :max ตัวอักษร)',
            'primary_color.regex' => 'รหัสสีต้องอยู่ในรูปแบบ #RRGGBB เช่น #2563a8',
            'contact_email.email' => 'อีเมลติดต่อไม่ถูกต้อง',
        ];
    }

    public function useColor(string $hex): void
    {
        $this->primary_color = $hex;
    }

    public function save(): void
    {
        $this->validate();

        $setting = SystemSetting::current();

        if ($this->logo) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $this->currentLogoPath = $this->logo->store('logos', 'public');
        }

        $setting->update([
            'system_name' => $this->system_name,
            'short_name' => $this->short_name ?: null,
            'college_name' => $this->college_name ?: null,
            'logo_path' => $this->currentLogoPath,
            'primary_color' => $this->primary_color ?: null,
            'contact_email' => $this->contact_email ?: null,
            'contact_phone' => $this->contact_phone ?: null,
        ]);

        // ทุกเลย์เอาต์อ่านค่าจาก cached() — ต้องล้างไม่งั้นหน้าที่ render ต่อ
        // จากคำขอนี้ยังเห็นชื่อ/สีเดิม
        SystemSetting::forgetCached();

        $this->reset('logo');

        session()->flash('success', 'บันทึกข้อมูลระบบเรียบร้อยแล้ว');
    }

    public function getCurrentLogoUrlProperty(): ?string
    {
        return $this->currentLogoPath ? Storage::disk('public')->url($this->currentLogoPath) : null;
    }

    public function removeLogo(): void
    {
        $setting = SystemSetting::current();

        if ($setting->logo_path) {
            Storage::disk('public')->delete($setting->logo_path);
        }

        $setting->update(['logo_path' => null]);
        SystemSetting::forgetCached();
        $this->currentLogoPath = null;

        session()->flash('success', 'ลบโลโก้เรียบร้อยแล้ว');
    }

    public function render()
    {
        return view('livewire.settings.system-information', [
            'colorPresets' => self::COLOR_PRESETS,
        ]);
    }
}
