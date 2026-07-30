@extends('layouts.app')
@section('title', 'Permintaan Hapus')
@section('page-title', 'Permintaan Hapus')
@section('page-subtitle', 'Tinjau dan putuskan permintaan penghapusan data dari tim')

@section('content')
<div class="d-flex gap-2 mb-3 flex-wrap">
    @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $label)
        <a href="{{ route('deletion-requests.index', ['status' => $value]) }}"
            class="btn btn-sm {{ $status === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $label }}
            <span class="badge bg-light text-dark ms-1">{{ $counts[$value] ?? 0 }}</span>
        </a>
    @endforeach
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:.82rem">
            <thead>
                <tr>
                    <th class="ps-3">Modul</th>
                    <th>Data</th>
                    <th>Diminta Oleh</th>
                    <th>Alasan</th>
                    <th>Waktu</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $deletionRequest)
                    <tr>
                        <td class="ps-3"><span class="badge bg-secondary">{{ $deletionRequest->module_title }}</span></td>
                        <td class="fw-semibold">{{ $deletionRequest->model_label ?: '#' . $deletionRequest->model_id }}</td>
                        <td>{{ $deletionRequest->requester?->name ?? '-' }}</td>
                        <td style="max-width:240px">{{ $deletionRequest->reason ?: '—' }}</td>
                        <td class="text-muted">{{ $deletionRequest->created_at->format('d M Y H:i') }}</td>
                        <td>
                            @if($deletionRequest->isPending())
                                <span class="badge bg-warning text-dark">Menunggu</span>
                            @elseif($deletionRequest->isApproved())
                                <span class="badge bg-success">Disetujui</span>
                            @else
                                <span class="badge bg-danger">Ditolak</span>
                            @endif
                            @if(!$deletionRequest->isPending() && $deletionRequest->reviewer)
                                <div class="text-muted" style="font-size:.7rem">oleh {{ $deletionRequest->reviewer->name }}</div>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            @if($deletionRequest->isPending())
                                <div class="d-flex gap-1 justify-content-end">
                                    <form method="POST" action="{{ route('deletion-requests.approve', $deletionRequest) }}"
                                        onsubmit="const note=prompt('Catatan persetujuan (opsional):');if(note===null)return false;this.elements.review_note.value=note;return confirm('Setujui dan hapus data ini?')">
                                        @csrf
                                        <input type="hidden" name="review_note">
                                        <button class="btn btn-sm btn-success" title="Setujui"><i class="fas fa-check"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('deletion-requests.reject', $deletionRequest) }}"
                                        onsubmit="const note=prompt('Alasan penolakan (opsional):');if(note===null)return false;this.elements.review_note.value=note;return confirm('Tolak permintaan ini?')">
                                        @csrf
                                        <input type="hidden" name="review_note">
                                        <button class="btn btn-sm btn-outline-danger" title="Tolak"><i class="fas fa-times"></i></button>
                                    </form>
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Tidak ada permintaan {{ strtolower(['pending' => 'menunggu', 'approved' => 'disetujui', 'rejected' => 'ditolak'][$status]) }}.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $requests->links() }}</div>
@endsection
