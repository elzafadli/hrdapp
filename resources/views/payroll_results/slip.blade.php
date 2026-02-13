<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji Karyawan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            background: #f0f0f0;
            padding: 20px;
        }

        .slip-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            border-bottom: 3px double #999;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
        }

        .header p {
            font-size: 11px;
            color: #666;
        }

        .employee-info {
            margin-bottom: 20px;
        }

        .employee-info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .employee-info-table td {
            padding: 12px 15px;
            border: none;
            font-size: 12px;
        }

        .employee-info-table tr:nth-child(odd) {
            background: #f8f9fa;
        }

        .employee-info-table tr:nth-child(even) {
            background: white;
        }

        .employee-info-label {
            font-weight: bold;
            color: #333;
            min-width: 120px;
        }

        .employee-info-value {
            font-weight: 600;
            color: #555;
        }

        .employee-info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .employee-info-row.left {
            justify-content: flex-start;
        }

        .employee-info-row.right {
            justify-content: flex-end;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            border: none;
            padding: 8px 12px;
            text-align: left;
        }

        table th {
            background: #e0e0e0;
            font-weight: bold;
            text-align: center;
        }

        table td.number {
            text-align: right;
        }

        table td.center {
            text-align: center;
        }

        .section-header {
            background: #f0f0f0;
            font-weight: bold;
        }

        .total-row {
            background: #e8e8e8;
            font-weight: bold;
        }

        .grand-total {
            background: #d0d0d0;
            font-weight: bold;
            font-size: 14px;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-space {
            height: 80px;
            border-bottom: 1px dashed #999;
            margin-bottom: 10px;
        }

        .signature-label {
            margin-bottom: 5px;
        }

        .signature-date {
            font-size: 10px;
            color: #666;
        }

        .no-print {
            text-align: center;
            margin: 20px 0;
        }

        .no-print button {
            background: #4CAF50;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .no-print button:hover {
            background: #45a049;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .slip-container {
                box-shadow: none;
                padding: 10px;
            }

            .no-print {
                display: none;
            }

            .signature-section {
                page-break-inside: avoid;
            }

            /* Ensure side-by-side tables work in print */
            table {
                page-break-inside: avoid;
            }
        }

        @page {
            margin: 10mm;
        }
    </style>
</head>
<body>
    <div class="slip-container">
        <!-- Header -->
        <div class="header">
            <!-- <h1>PT. UNIMA MULTIRASA</h1> -->
            <h2>SLIP GAJI KARYAWAN {{ indoMonth($month) }} {{ $year }}</h2>
        </div>

        <!-- Employee Information -->
        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <!-- Left Table -->
            <table class="employee-info-table" style="width: 50%;">
                <tr>
                    <td class="employee-info-label">Nama Karyawan</td>
                    <td class="employee-info-value">: {{ $employee->name }}</td>
                </tr>
                <tr>
                    <td class="employee-info-label">Jabatan</td>
                    <td class="employee-info-value">: {{ $employee->jabatan->jabatan ?? '-' }}</td>
                </tr>
            </table>

            <!-- Right Table -->
            <table class="employee-info-table" style="width: 50%;">
                <tr>
                    <td class="employee-info-label">Project</td>
                    <td class="employee-info-value">: {{ $employee->project->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="employee-info-label">Perusahaan</td>
                    <td class="employee-info-value">: {{ $employee->company->name ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <!-- Payroll Details Table - Side by Side -->
        @php
            $totalIncome = 0;
            $totalSubsidi = 0;
            $totalDeduction = 0;

            // Count rows for each table
            $incomeRows = $allowances->count() + $subsidies->count() + $bpjs->count();
            $deductionRows = $deductions->count();

            // Calculate max rows (excluding header and total rows)
            $maxRows = max($incomeRows, $deductionRows);
        @endphp

        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
            <!-- Left Table - PENDAPATAN -->
            <table style="flex: 1; margin-bottom: 0;">
                <tbody>
                    <tr class="section-header">
                        <td colspan="3" style="text-align: center;">RINCIAN GAJI</td>
                    </tr>

                    @foreach($allowances as $item)
                        @php $totalIncome += $item->amount; @endphp
                        <tr>
                            <td>{{ $item->payrollComponent->name }}</td>
                            <td style="width: 20px;">:</td>
                            <td class="number">{{ number_format($item->amount, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    @if($subsidies->count() > 0 || $bpjs->count() > 0)
                        @foreach($subsidies->merge($bpjs) as $item)
                            @php
                                $totalIncome += $item->amount;
                                $totalSubsidi += $item->amount;
                            @endphp
                            <tr>
                                <td>{{ $item->payrollComponent->name }}</td>
                                <td style="width: 20px;">:</td>
                                <td class="number">{{ number_format($item->amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @endif

                    {{-- Add empty rows to match max rows --}}
                    @php $currentIncomeRows = $incomeRows; @endphp
                    @while($currentIncomeRows < $maxRows)
                        @php $currentIncomeRows++; @endphp
                        <tr style="height: 35px;">
                            <td colspan="3">&nbsp;</td>
                        </tr>
                    @endwhile

                    <tr class="total-row">
                        <td>TOTAL PENDAPATAN</td>
                        <td style="width: 20px;">:</td>
                        <td class="number">{{ number_format($totalIncome, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Right Table - POTONGAN -->
            <table style="flex: 1; margin-bottom: 0;">
                <tbody>
                    <tr class="section-header">
                        <td colspan="3" style="text-align: center;">RINCIAN POTONGAN</td>
                    </tr>

                    @foreach($deductions as $item)
                        @php $totalDeduction += $item->amount; @endphp
                        <tr>
                            <td>{{ $item->payrollComponent->name }}</td>
                            <td style="width: 20px;">:</td>
                            <td class="number">{{ number_format($item->amount, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    {{-- Add empty rows to match max rows --}}
                    @php $currentDeductionRows = $deductionRows; @endphp
                    @while($currentDeductionRows < $maxRows)
                        @php $currentDeductionRows++; @endphp
                        <tr style="height: 35px;">
                            <td colspan="3">&nbsp;</td>
                        </tr>
                    @endwhile

                    <tr class="total-row">
                        <td>TOTAL POTONGAN</td>
                        <td style="width: 20px;">:</td>
                        <td class="number">{{ number_format($totalDeduction, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Grand Total Table -->
        <table>
            <tbody>
                <tr class="grand-total">
                    <td style="font-weight: bold; font-size: 12px;">TOTAL GAJI YANG DITERIMA</td>
                    <td class="number" style="font-weight: bold; font-size: 12px;">{{ number_format($totalIncome - $totalDeduction, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="3" style="padding-top: 10px;">
                        <em>Terbilang: <strong>{{ terbilang($totalIncome - $totalDeduction) }} Rupiah</strong></em>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Signature Section -->
        <!-- <div class="signature-section">
            <div class="signature-box">
                <p class="signature-label">Mengetahui,</p>
                <div class="signature-space"></div>
                <p>HRD Manager</p>
                <p class="signature-date">{{ date('d-m-Y') }}</p>
            </div>
            <div class="signature-box">
                <p class="signature-label">Penerima,</p>
                <div class="signature-space"></div>
                <p>{{ $employee->name }}</p>
                <p class="signature-date">{{ date('d-m-Y') }}</p>
            </div>
        </div> -->

        <!-- Print Button -->
        <div class="no-print">
            <button onclick="window.print()">Cetak Slip Gaji</button>
        </div>
    </div>
</body>
</html>