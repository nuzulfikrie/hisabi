<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .header .range {
            color: #666;
            font-size: 14px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            background-color: #f5f5f5;
            padding: 8px;
            border-left: 4px solid #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .summary-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .total-row {
            font-weight: bold;
            font-size: 14px;
            background-color: #e9e9e9;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Financial Report</h1>
        <div class="range">Period: {{ $range }}</div>
        <div class="range">Currency: {{ $currency }}</div>
    </div>

    @foreach($sections as $sectionName => $sectionData)
        <div class="section">
            <div class="section-title">{{ Str::limit(ucfirst($sectionName), 30) }}</div>
            
            @if(is_array($sectionData) && !empty($sectionData))
                <table>
                    <thead>
                        <tr>
                            @if(isset($sectionData[0]))
                                @foreach(array_keys($sectionData[0]) as $header)
                                    <th>{{ Str::limit(ucwords(str_replace('_', ' ', $header)), 25) }}</th>
                                @endforeach
                            @else
                                <th>Item</th>
                                <th class="text-right">Amount ({{ $currency }})</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($sectionData[0]))
                            @foreach($sectionData as $row)
                                <tr>
                                    @foreach($row as $key => $value)
                                        @if(is_numeric($value) && str_contains($key, 'amount'))
                                            <td class="text-right">{{ number_format($value, 2) }}</td>
                                        @else
                                            <td>{{ $value }}</td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        @else
                            @foreach($sectionData as $key => $value)
                                @if(is_array($value))
                                    <tr class="summary-row">
                                        <td colspan="2">{{ Str::limit(ucwords(str_replace('_', ' ', $key)), 30) }}</td>
                                    </tr>
                                    @foreach($value as $subKey => $subValue)
                                        <tr>
                                            <td>{{ Str::limit(ucwords(str_replace('_', ' ', $subKey)), 30) }}</td>
                                            <td class="text-right">{{ is_numeric($subValue) ? number_format($subValue, 2) : $subValue }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td>{{ Str::limit(ucwords(str_replace('_', ' ', $key)), 30) }}</td>
                                        <td class="text-right">{{ is_numeric($value) ? number_format($value, 2) : $value }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    </tbody>
                </table>
            @else
                <p>No data available for this section.</p>
            @endif
        </div>
    @endforeach

    <div class="footer">
        <p>Generated on {{ now()->format('M j, Y g:i A') }}</p>
        <p>Hisabi</p>
    </div>
</body>
</html>
