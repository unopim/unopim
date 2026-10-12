<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $family }} — @lang('installer::app.seeders.demo.spec-sheet.title')</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1f2937; margin: 40px; }
        h1 { font-size: 20pt; margin: 0 0 4px; }
        .lede { color: #6b7280; margin: 0 0 28px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        th { width: 40%; color: #6b7280; font-weight: normal; }
        footer { margin-top: 36px; color: #9ca3af; font-size: 9pt; }
    </style>
</head>
<body>
    <h1>{{ $family }}</h1>
    <p class="lede">@lang('installer::app.seeders.demo.spec-sheet.lede')</p>

    <table>
        <tr>
            <th>@lang('installer::app.seeders.demo.spec-sheet.family-code')</th>
            <td>{{ $code }}</td>
        </tr>
        <tr>
            <th>@lang('installer::app.seeders.demo.spec-sheet.compliance')</th>
            <td>@lang('installer::app.seeders.demo.spec-sheet.compliance-value')</td>
        </tr>
        <tr>
            <th>@lang('installer::app.seeders.demo.spec-sheet.spare-parts')</th>
            <td>@lang('installer::app.seeders.demo.spec-sheet.spare-parts-value')</td>
        </tr>
        <tr>
            <th>@lang('installer::app.seeders.demo.spec-sheet.packaging')</th>
            <td>@lang('installer::app.seeders.demo.spec-sheet.packaging-value')</td>
        </tr>
    </table>

    <footer>@lang('installer::app.seeders.demo.spec-sheet.footer')</footer>
</body>
</html>
