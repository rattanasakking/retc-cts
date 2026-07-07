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

    public string $college_name = '';

    public ?string $currentLogoPath = null;

    public $logo = null;

    public function mount(): void
    {
        $setting = SystemSetting::current();

        $this->system_name = $setting->system_name;
        $this->college_name = (string) $setting->college_name;
        $this->currentLogoPath = $setting->logo_path;
    }

    protected function rules(): array
    {
        return [
            'system_name' => ['required', 'string', 'max:255'],
            'college_name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
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
            'college_name' => $this->college_name,
            'logo_path' => $this->currentLogoPath,
        ]);

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
        $this->currentLogoPath = null;

        session()->flash('success', 'ลบโลโก้เรียบร้อยแล้ว');
    }

    public function render()
    {
        return view('livewire.settings.system-information');
    }
}
