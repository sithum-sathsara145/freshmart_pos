{{-- hrm/self/_tabs.blade.php --}}
@php
    $tabs = [
        'index'      => ['label' => 'Overview',   'route' => 'my.index',      'icon' => 'ti-layout-dashboard'],
        'attendance' => ['label' => 'Attendance', 'route' => 'my.attendance', 'icon' => 'ti-calendar-check'],
        'leave'      => ['label' => 'Leave',      'route' => 'my.leave',      'icon' => 'ti-beach'],
        'payslips'   => ['label' => 'Payslips',   'route' => 'my.payslips',   'icon' => 'ti-file-text'],
    ];
@endphp
<div style="display:flex;gap:5px;margin-bottom:12px;border-bottom:.5px solid var(--border);padding-bottom:10px">
    @foreach($tabs as $key => $tab)
    <a href="{{ route($tab['route']) }}"
       style="height:30px;padding:0 12px;border-radius:6px;font-size:12px;display:flex;align-items:center;gap:5px;text-decoration:none;
              background:{{ $active === $key ? 'var(--primary-soft)' : 'var(--surface)' }};
              border:.5px solid {{ $active === $key ? 'var(--primary-border)' : 'var(--border)' }};
              color:{{ $active === $key ? 'var(--primary-text)' : 'var(--text-2)' }}">
        <i class="ti {{ $tab['icon'] }}" style="font-size:13px"></i>{{ $tab['label'] }}
    </a>
    @endforeach
</div>
