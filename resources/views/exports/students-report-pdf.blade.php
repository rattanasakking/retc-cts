<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 90px 32px 60px 32px;
        }

        body {
            font-family: 'thaisans', 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1b2430;
        }

        header {
            position: fixed;
            top: -70px;
            left: 0;
            right: 0;
            text-align: center;
        }

        header h1 {
            font-size: 16px;
            margin: 0 0 4px;
        }

        header p {
            font-size: 10px;
            color: #666;
            margin: 0;
        }

        footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #888;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d7dce3;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #eef1f4;
            font-weight: bold;
        }

        td.num {
            text-align: right;
            white-space: nowrap;
        }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 9px;
        }

        .badge-employed { background: #dbe9f7; color: #1f4f80; }
        .badge-unemployed { background: #f7dede; color: #7a2a2c; }
        .badge-other { background: #eaeaea; color: #555; }
    </style>
</head>
<body>
    <header>
        <h1>รายงานภาวะการมีงานทำ</h1>
        <p>{{ $filterSummary }}</p>
        <p>ออกรายงานเมื่อ {{ $generatedAt }}</p>
    </header>

    <footer>
        หน้า <span class="page"></span> จาก <span class="topage"></span> — RETC Smart Career Tracking System
    </footer>

    <table>
        <thead>
            <tr>
                <th>รหัสนักศึกษา</th>
                <th>ชื่อ-สกุล</th>
                <th>แผนกวิชา</th>
                <th>ระดับ</th>
                <th>สถานะนักศึกษา</th>
                <th>ภาวะการมีงานทำ</th>
                <th>สถานที่ทำงาน/กิจการ</th>
                <th class="num">เงินเดือน</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $student)
                @php $career = $student->careerStatuses->first(); @endphp
                <tr>
                    <td>{{ $student->student_code }}</td>
                    <td>{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</td>
                    <td>{{ $student->program ?: '—' }}</td>
                    <td>{{ $student->degree_level ?: '—' }}</td>
                    <td>{{ match($student->status) {
                        'studying' => 'กำลังศึกษา',
                        'graduated' => 'จบการศึกษา',
                        'dropped_out' => 'ออกกลางคัน',
                        default => $student->status,
                    } }}</td>
                    <td>
                        @if ($career)
                            <span @class([
                                'badge',
                                'badge-employed' => in_array($career->status->value, ['employed', 'entrepreneur']),
                                'badge-unemployed' => $career->status->value === 'unemployed',
                                'badge-other' => ! in_array($career->status->value, ['employed', 'entrepreneur', 'unemployed']),
                            ])>{{ $career->status->label() }}</span>
                        @else
                            <span class="badge badge-other">ยังไม่ตอบแบบสำรวจ</span>
                        @endif
                    </td>
                    <td>{{ $career?->company_name ?: '—' }}</td>
                    <td class="num">{{ $career?->monthly_salary ? number_format($career->monthly_salary, 0) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
