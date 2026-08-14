<!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>{{ $title }}</title>@vite(['resources/js/app.js'])
<style>body{font-family:Tahoma,sans-serif;padding:20px}table{width:100%;border-collapse:collapse;font-size:12px}th,td{border:1px solid #999;padding:6px;text-align:right}@media print{@page{size:A4 landscape}}</style></head>
<body><h1>{{ $title }}</h1><p>تولیدکننده: {{ auth()->user()->name }} | زمان: <span data-jalali-value="{{ now()->format('Y-m-d H:i:s') }}">{{ now()->format('Y/m/d H:i') }}</span></p>
<table>@if($rows->isNotEmpty())<tr>@foreach(array_keys($rows->first()) as $h)<th>{{ $h }}</th>@endforeach</tr>@foreach($rows as $row)<tr>@foreach($row as $v)<td @if(is_string($v) && preg_match('/^\d{4}[\/-]\d{2}[\/-]\d{2}$/',$v)) data-jalali-value="{{ str_replace('/','-',$v) }}" @endif>{{ is_numeric($v)?number_format($v):$v }}</td>@endforeach</tr>@endforeach@endif</table>
<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),150))</script></body></html>
