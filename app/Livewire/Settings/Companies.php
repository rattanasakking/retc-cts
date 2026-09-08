<?php

namespace App\Livewire\Settings;

use App\Models\Company;
use App\Support\AuditLogger;
use App\Support\CompanyImporter;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('ตั้งค่า: ฐานข้อมูลบริษัท')]
class Companies extends Component
{
    use WithFileUploads, WithPagination;

    public $file = null;

    /** ลิงก์ไฟล์ CSV จาก opendata.dbd.go.th */
    public string $url = '';

    /** เก็บเฉพาะจังหวัดนี้ — ทั้งประเทศมีเป็นล้านแถว เกินความจำเป็นของวิทยาลัย */
    public string $onlyProvince = '';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'file' => ['nullable', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200'],
            'url' => ['nullable', 'url', 'max:2048'],
            'onlyProvince' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function messages(): array
    {
        return [
            'file.mimes' => 'รองรับไฟล์ CSV, XLSX และ XLS',
            'file.max' => 'ไฟล์ใหญ่เกิน 50MB — ลองแบ่งไฟล์หรือกรองเฉพาะจังหวัดก่อนอัปโหลด',
            'url.url' => 'ลิงก์ไม่ถูกต้อง',
        ];
    }

    public function importFile(): void
    {
        $this->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200']]);

        $this->runImport($this->file->getRealPath(), $this->file->getClientOriginalName());
        $this->reset('file');
    }

    /**
     * ดึงไฟล์จากลิงก์ opendata.dbd.go.th มาเก็บลงไฟล์ชั่วคราวก่อนแล้วค่อยอ่าน —
     * ไฟล์ชุดข้อมูลเปิดมีขนาดหลายสิบ MB ถ้าโหลดเข้าหน่วยความจำทั้งก้อนจะเกิน
     * memory_limit ของโฮสต์ทั่วไป
     */
    public function importUrl(): void
    {
        $this->validate(['url' => ['required', 'url', 'max:2048']]);

        // openapi.dbd.go.th ไม่ใช่ไฟล์ชุดข้อมูล แต่เป็น API ค้นทีละบริษัทด้วย
        // เลขทะเบียน 13 หลัก และต้องมี API key — วางลิงก์นี้มาแล้วจะได้ HTML
        // หรือ 403 กลับไปเงียบ ๆ จึงบอกให้ชัดตั้งแต่ตรงนี้
        if (str_contains($this->url, 'openapi.dbd.go.th') || str_contains($this->url, '{')) {
            $this->addError('url', 'ลิงก์นี้เป็น API ค้นรายบริษัท (ต้องใส่เลขทะเบียน 13 หลักและมี API key) ไม่ใช่ไฟล์ชุดข้อมูล — ให้ใช้ลิงก์ไฟล์ CSV/XLSX จากชุด "นิติบุคคลจดทะเบียนตั้งใหม่" บน opendata.dbd.go.th แทน');

            return;
        }

        $temp = tempnam(sys_get_temp_dir(), 'dbd');

        try {
            $response = Http::timeout(180)->sink($temp)->get($this->url);

            if (! $response->successful()) {
                $this->addError('url', 'ดาวน์โหลดไม่สำเร็จ (HTTP '.$response->status().')');

                return;
            }

            $this->runImport($temp, $this->url);
        } catch (Throwable $e) {
            $this->addError('url', 'ดาวน์โหลดไม่สำเร็จ: '.$e->getMessage());
        } finally {
            if (is_file($temp)) {
                unlink($temp);
            }
        }
    }

    /** เก็บชื่อสถานประกอบการที่เคยมีคนกรอกไว้แล้วเข้าฐานข้อมูลนี้ */
    public function importFromExisting(): void
    {
        $added = (new CompanyImporter)->importFromExistingCareerStatuses();

        session()->flash('success', "เพิ่มชื่อจากข้อมูลที่มีอยู่แล้ว {$added} รายการ");
    }

    private function runImport(string $path, string $label): void
    {
        try {
            $result = (new CompanyImporter)->import($path, $this->onlyProvince ?: null);
        } catch (Throwable $e) {
            $this->addError('file', $e->getMessage());

            return;
        }

        AuditLogger::log(
            action: 'import_csv',
            module: 'ฐานข้อมูลบริษัท',
            description: "นำเข้าข้อมูลนิติบุคคลจาก {$label} (เพิ่ม {$result['imported']}, อัปเดต {$result['updated']} รายการ)",
        );

        session()->flash('success', "นำเข้าเรียบร้อย — เพิ่มใหม่ {$result['imported']} รายการ, อัปเดต {$result['updated']} รายการ, ข้าม {$result['skipped']} แถว");
    }

    public function render()
    {
        return view('livewire.settings.companies', [
            'companies' => Company::query()
                ->when($this->search, fn ($query) => $query->matching($this->search))
                ->when(! $this->search, fn ($query) => $query->orderByDesc('id'))
                ->paginate(15),
            'total' => Company::count(),
            'fromDbd' => Company::where('source', 'dbd')->count(),
            'lastUpdated' => Company::max('updated_at'),
            'provinces' => Company::query()
                ->whereNotNull('province')
                ->distinct()
                ->orderBy('province')
                ->pluck('province'),
        ]);
    }
}
