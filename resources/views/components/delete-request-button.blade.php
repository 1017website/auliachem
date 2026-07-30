@props([
    'module',
    'modelId',
    'label' => '',
    'wrapperClass' => 'd-inline',
    'buttonClass' => 'btn btn-sm btn-outline-danger',
    'buttonStyle' => 'padding:3px 7px',
    'showText' => false,
])

@php
    $isAdministrator = auth()->user()->isAdmin();
    $pending = \App\Models\DeletionRequest::pendingFor($module, $modelId);
    $buttonText = $isAdministrator ? 'Hapus' : 'Request Hapus';
    $effectiveButtonClass = $isAdministrator
        ? $buttonClass
        : (str_contains($buttonClass, 'btn-outline-danger')
            ? str_replace('btn-outline-danger', 'btn-outline-warning', $buttonClass)
            : $buttonClass . ' btn-outline-warning');
@endphp

@if($pending && !$isAdministrator)
    <span class="badge bg-warning text-dark {{ $wrapperClass }}" style="font-size:.65rem" title="Menunggu persetujuan Administrator">
        <i class="fas fa-clock me-1"></i> Menunggu Hapus
    </span>
@else
    <form method="POST" action="{{ route('deletion-requests.store') }}" class="{{ $wrapperClass }}"
        @if($isAdministrator)
            onsubmit="return confirm(@js('Hapus ' . $label . '?'))"
        @else
            onsubmit="const reason=prompt('Alasan permintaan hapus (opsional):');if(reason===null)return false;this.elements.reason.value=reason;return confirm(@js('Ajukan permintaan hapus untuk ' . $label . '?'))"
        @endif>
        @csrf
        <input type="hidden" name="module" value="{{ $module }}">
        <input type="hidden" name="model_id" value="{{ $modelId }}">
        <input type="hidden" name="reason" value="">
        <button type="submit"
            class="{{ $effectiveButtonClass }}"
            style="{{ $buttonStyle }}"
            title="{{ $buttonText }}">
            <i class="fas {{ $isAdministrator ? 'fa-trash' : 'fa-trash-restore-alt' }}"></i>
            @if($showText)<span class="ms-1">{{ $buttonText }}</span>@endif
        </button>
    </form>
@endif
