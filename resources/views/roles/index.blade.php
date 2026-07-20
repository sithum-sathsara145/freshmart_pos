{{-- roles/index.blade.php — roles & their permission matrix --}}
@extends('layouts.app')
@section('title','Roles & Permissions')
@section('page-title','Roles & Permissions')
@section('content')
@php
    $ui = 'width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:8px 10px;outline:none';
@endphp
<div style="padding:14px 16px;max-width:1100px" x-data="rolesPage()">

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <div style="font-size:11px;color:var(--text-3)">
        A role is a job. Tick what that job is allowed to do — anyone with the role gets exactly that.
        You can only edit roles ranked below your own.
    </div>
    @can('create', App\Models\Role::class)
    <button type="button" @click="showCreate = true"
        style="height:32px;padding:0 12px;background:var(--primary-soft);border:.5px solid var(--primary-border);border-radius:6px;color:var(--primary-text);font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:5px;flex-shrink:0">
        <i class="ti ti-plus" style="font-size:13px"></i>New role
    </button>
    @endcan
</div>

{{-- Role cards --}}
<div style="display:flex;flex-direction:column;gap:10px">
@foreach($roles as $role)
    @php
        $editable = $role->isEditableBy($actor);
        $held     = $role->permissions->pluck('name')->all();
    @endphp
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
        {{-- Header --}}
        <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;cursor:pointer" @click="toggle({{ $role->id }})">
            <i class="ti" :class="open === {{ $role->id }} ? 'ti-chevron-down' : 'ti-chevron-right'" style="font-size:14px;color:var(--text-3)"></i>
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:7px">
                    <span style="font-size:13px;font-weight:600;color:var(--text)">{{ $role->displayName() }}</span>
                    <span style="font-size:9px;padding:1px 6px;border-radius:8px;background:var(--info-soft);color:var(--info)">Level {{ $role->level }}</span>
                    @if($role->is_system)
                        <span style="font-size:9px;padding:1px 6px;border-radius:8px;background:var(--warning-soft-4);color:var(--warning-2)" title="Built-in role — cannot be renamed or deleted">System</span>
                    @endif
                    @if($role->isSuperAdmin())
                        <span style="font-size:9px;padding:1px 6px;border-radius:8px;background:var(--primary-deep);color:var(--primary-text-2)" title="Developer only — hidden from everyone else">Developer</span>
                    @endif
                    @unless($editable)
                        <span style="font-size:9px;padding:1px 6px;border-radius:8px;background:var(--surface-2);color:var(--text-3)" title="Ranked at or above you">Read-only</span>
                    @endunless
                </div>
                <div style="font-size:10px;color:var(--text-3);margin-top:2px">
                    {{ $role->description ?: 'No description' }} ·
                    {{ $role->permissions_count }} permission(s) · {{ $role->users()->count() }} user(s)
                </div>
            </div>
            @can('delete', $role)
            <button type="button" title="Delete role" @click.stop="confirmDelete({{ $role->id }}, @js($role->displayName()))"
                style="width:26px;height:26px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;color:var(--danger);cursor:pointer;flex-shrink:0"><i class="ti ti-trash" style="font-size:12px"></i></button>
            @endcan
        </div>

        {{-- Permission matrix --}}
        <div x-show="open === {{ $role->id }}" x-cloak style="border-top:.5px solid var(--border);padding:14px">
            @if($role->isSuperAdmin())
                <div style="font-size:11px;color:var(--text-3);padding:6px 0">
                    Super Admin always has every permission, including developer options. This can't be changed.
                </div>
            @else
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf @method('PUT')

                @if($editable && ! $role->is_system)
                <div style="display:grid;grid-template-columns:1fr 2fr;gap:8px;margin-bottom:12px">
                    <div>
                        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Role name</label>
                        <input name="label" value="{{ $role->label }}" style="{{ $ui }}">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Description</label>
                        <input name="description" value="{{ $role->description }}" style="{{ $ui }}">
                    </div>
                </div>
                @endif

                @foreach($groups as $key => $group)
                <div style="margin-bottom:12px">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                        <span style="font-size:11px;font-weight:600;color:var(--text-2)">{!! $group['label'] !!}</span>
                        @if($editable)
                        <button type="button" @click="toggleGroup($el, true)" style="font-size:9px;background:none;border:none;color:var(--success);cursor:pointer">all</button>
                        <button type="button" @click="toggleGroup($el, false)" style="font-size:9px;background:none;border:none;color:var(--text-3);cursor:pointer">none</button>
                        @endif
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:5px">
                        @foreach($group['permissions'] as $name => $label)
                        <label style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-hi);background:var(--bg);border:.5px solid var(--border);border-radius:5px;padding:6px 8px;cursor:{{ $editable ? 'pointer' : 'not-allowed' }};opacity:{{ $editable ? '1' : '.55' }}">
                            <input type="checkbox" name="permissions[]" value="{{ $name }}"
                                   {{ in_array($name, $held, true) ? 'checked' : '' }}
                                   {{ $editable ? '' : 'disabled' }}
                                   style="accent-color:var(--primary);flex-shrink:0">
                            <span>{!! $label !!}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach

                @if($editable)
                <div style="border-top:.5px solid var(--border);padding-top:12px;display:flex;justify-content:flex-end">
                    <button type="submit" style="height:34px;padding:0 18px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:600;cursor:pointer">
                        <i class="ti ti-check" style="font-size:12px;margin-right:4px"></i>Save permissions
                    </button>
                </div>
                @else
                <div style="font-size:10px;color:var(--text-3);border-top:.5px solid var(--border);padding-top:10px">
                    This role ranks at or above you, so you can't change what it does.
                </div>
                @endif
            </form>
            @endif
        </div>
    </div>
@endforeach
</div>

{{-- New role --}}
@can('create', App\Models\Role::class)
<template x-teleport="body">
<div x-show="showCreate" x-cloak @keydown.escape.window="showCreate=false" @click.self="showCreate=false"
     style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:60">
    <form method="POST" action="{{ route('roles.store') }}" style="background:var(--surface);border:.5px solid var(--border);border-radius:10px;padding:18px;width:380px">
        @csrf
        <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px">New role</div>
        <div style="font-size:11px;color:var(--text-3);margin-bottom:14px">Create the role first, then tick its permissions.</div>

        <div style="margin-bottom:9px">
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Role name *</label>
            <input name="label" required placeholder="e.g. Supervisor" style="{{ $ui }}">
        </div>
        <div style="margin-bottom:9px">
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Rank *</label>
            <select name="level" required style="{{ $ui }}">
                @foreach(range(10, max(10, $actor->level() - 10), 10) as $lvl)
                    @if($actor->isSuperAdmin() || $lvl < $actor->level())
                    <option value="{{ $lvl }}">Level {{ $lvl }}</option>
                    @endif
                @endforeach
            </select>
            <div style="font-size:10px;color:var(--text-4);margin-top:4px">
                Higher rank = more senior. Must be below your own (level {{ $actor->level() }}).
                A role can only manage accounts at or below its rank.
            </div>
        </div>
        <div style="margin-bottom:14px">
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Description</label>
            <input name="description" placeholder="What is this job?" style="{{ $ui }}">
        </div>
        <div style="display:flex;gap:8px">
            <button type="button" @click="showCreate=false" style="flex:1;height:36px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Cancel</button>
            <button type="submit" style="flex:1;height:36px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:600;cursor:pointer">Create role</button>
        </div>
    </form>
</div>
</template>
@endcan

<form x-ref="deleteForm" method="POST" style="display:none">@csrf @method('DELETE')</form>
</div>

@push('scripts')
<script>
function rolesPage() {
    return {
        open: null,
        showCreate: false,

        toggle(id) { this.open = this.open === id ? null : id; },

        // Tick/untick every box in this permission group.
        toggleGroup(el, state) {
            el.closest('div').parentElement
              .querySelectorAll('input[type=checkbox]:not([disabled])')
              .forEach(cb => cb.checked = state);
        },

        confirmDelete(id, name) {
            if (!confirm(`Delete the "${name}" role?`)) return;
            const f = this.$refs.deleteForm;
            f.action = '{{ url('roles') }}/' + id;
            f.submit();
        },
    };
}
</script>
@endpush
@endsection
