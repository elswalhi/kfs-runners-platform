@extends('admin.layout')
@section('title', __('Zayed bus bookings') . ' · KFS Runners')
@section('section', __('Zayed bus bookings'))
@push('styles')
<style>
    .bus-bookings-page .stitch-table th { font-size: 12px; }
    .bus-bookings-page .stitch-table td { font-size: 14px; }
    .bus-filters { display: flex; flex-wrap: wrap; gap: 12px; padding: 20px; align-items: end; }
    .bus-filters label { display: grid; gap: 6px; flex: 1 1 150px; font-size: 13px; font-weight: 700; }
    .bus-filters label.bus-search { flex: 2 1 230px; }
    .bus-filters .stitch-input, .bus-filters .stitch-select { width: 100%; min-width: 0; font-size: 14px; }
    .bus-tabs { display: flex; gap: 8px; padding: 18px 20px 0; flex-wrap: wrap; }
    .bus-tabs a[aria-current="page"] { color: #fff; background: #064b3e; }
    .bus-action { font-size: 13px; }
    .bus-receipt-preview { display: inline-flex; padding: 3px; border: 1px solid #cfddd5; border-radius: 8px; background: #fff; cursor: zoom-in; }
    .bus-receipt-preview img { display: block; width: 64px; height: 64px; object-fit: cover; border-radius: 5px; }
    .bus-receipt-dialog { max-width: min(940px, 95vw); max-height: 92vh; padding: 18px; border: 0; border-radius: 12px; background: #fff; color: #123d34; }
    .bus-receipt-dialog::backdrop { background: rgba(0, 20, 17, .8); }
    .bus-receipt-dialog header { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 12px; }
    .bus-receipt-dialog img { display: block; max-width: 100%; max-height: 75vh; width: auto; height: auto; margin: auto; object-fit: contain; }
</style>
@endpush
@section('content')
<div class="bus-bookings-page">
<div class="stitch-hero">
    <div class="stitch-hero-info">
        <h1 class="stitch-hero-title">{{ __('Zayed bus bookings') }}</h1>
        <p class="stitch-hero-desc">{{ __('Paid seats') }}: {{ $paidCount }}</p>
    </div>
    <a class="stitch-btn stitch-btn-secondary" href="{{ route('bus.create') }}" target="_blank" rel="noopener noreferrer">{{ __('Open booking page') }} ↗</a>
</div>
<div class="stitch-kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:24px">
    <div class="stitch-kpi-card"><div class="stitch-kpi-title">{{ __('Total booking value') }}</div><div class="stitch-kpi-value">EGP {{ number_format($totalAmount, 2) }}</div></div>
    <div class="stitch-kpi-card"><div class="stitch-kpi-title">{{ __('Confirmed payments') }}</div><div class="stitch-kpi-value">EGP {{ number_format($paidAmount, 2) }}</div></div>
    <div class="stitch-kpi-card"><div class="stitch-kpi-title">{{ __('Awaiting payment review') }}</div><div class="stitch-kpi-value">EGP {{ number_format($pendingAmount, 2) }}</div></div>
</div>
<section class="stitch-table-panel" style="padding:24px;margin-bottom:24px">
    <h2 class="stitch-panel-title">{{ __('Bus ticket price') }}</h2>
    <p class="stitch-panel-subtitle">{{ __('The updated price applies to new bookings.') }}</p>
    <form method="POST" action="{{ route('admin.bus.price') }}" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin-top:16px">
        @csrf @method('PUT')
        <label>{{ __('Price per seat (EGP)') }}<input class="stitch-input" type="number" name="price" value="{{ old('price', number_format($price, 2, '.', '')) }}" min="1.01" max="1000000" step="0.01" required></label>
        <button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Save price') }}</button>
    </form>
</section>
<section class="stitch-table-panel">
    <nav class="bus-tabs" aria-label="{{ __('Bus bookings') }}">
        <a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.bus.index') }}" @if (! $deleted) aria-current="page" @endif>{{ __('Active bookings') }}</a>
        <a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.bus.index', ['tab' => 'deleted']) }}" @if ($deleted) aria-current="page" @endif>{{ __('Deleted bookings') }} ({{ $deletedCount }})</a>
    </nav>
    <form class="bus-filters" method="GET" action="{{ route('admin.bus.index') }}">
        @if ($deleted)<input type="hidden" name="tab" value="deleted">@endif
        <label class="bus-search">{{ __('Search') }}<input class="stitch-input" type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Name, booking number, email or phone') }}"></label>
        <label>{{ __('Payment status') }}
            <select class="stitch-select" name="status">
                <option value="">{{ __('All payment statuses') }}</option>
                @foreach (['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </label>
        <label>{{ __('From date') }}<input class="stitch-input" type="date" name="from" value="{{ request('from') }}"></label>
        <label>{{ __('To date') }}<input class="stitch-input" type="date" name="to" value="{{ request('to') }}"></label>
        <button class="stitch-btn stitch-btn-primary" type="submit">{{ __('Filter') }}</button>
        <button class="stitch-btn stitch-btn-secondary" type="submit" formaction="{{ route('admin.bus.export') }}">{{ __('Export CSV') }}</button>
        <a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.bus.index', $deleted ? ['tab' => 'deleted'] : []) }}">{{ __('Reset') }}</a>
    </form>
    <div class="stitch-table-scroll">
        <table class="stitch-table">
            <thead><tr><th>{{ __('Booking reference') }}</th><th>{{ __('Full name') }}</th><th>{{ __('Email address') }}</th><th>{{ __('Phone number') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Payment status') }}</th><th>{{ __('Date') }}</th><th>{{ __('Transfer receipt image') }}</th><th>{{ __('Action') }}</th></tr></thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr>
                        <td><bdi>{{ $booking->booking_number }}</bdi></td><td>{{ $booking->name }}</td><td>{{ $booking->email ?: '—' }}</td><td><bdi>{{ $booking->phone }}</bdi></td><td>EGP {{ number_format($booking->amount, 2) }}</td><td>{{ __(ucfirst($booking->payment_status)) }}<br><small>{{ $booking->payment?->provider === 'wallet' ? __('Wallet transfer') : __('EasyKash') }}</small></td><td>{{ $booking->created_at->format('d M Y, H:i') }}</td>
                        <td>
                            @if ($booking->receipt_path)
                                <button type="button" class="bus-receipt-preview" data-receipt-src="{{ route('admin.bus.receipt.preview', $booking->id) }}" data-receipt-number="{{ $booking->booking_number }}" aria-label="{{ __('Enlarge transfer image') }} {{ $booking->booking_number }}">
                                    <img src="{{ route('admin.bus.receipt.preview', $booking->id) }}" alt="{{ __('Transfer receipt image') }} {{ $booking->booking_number }}" loading="lazy">
                                </button>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($booking->receipt_path)
                                <a class="stitch-btn stitch-btn-secondary bus-action" href="{{ route('admin.bus.receipt', $booking->id) }}">{{ __('Download transfer image') }}</a>
                            @endif
                            @if ($deleted)
                                <form method="POST" action="{{ route('admin.bus.restore', $booking->id) }}">@csrf @method('PATCH')<button class="stitch-btn stitch-btn-secondary bus-action" type="submit">{{ __('Restore') }}</button></form>
                            @else
                                @if ($booking->payment?->provider === 'wallet' && in_array($booking->payment_status, ['pending', 'paid'], true))
                                    <form method="POST" action="{{ route('admin.bus.payment', $booking) }}" onsubmit="return confirm(@js(__('Update this booking payment status?')))">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $booking->payment_status === 'paid' ? 'pending' : 'paid' }}"><button class="stitch-btn stitch-btn-secondary bus-action" type="submit">{{ $booking->payment_status === 'paid' ? __('Mark pending') : __('Confirm payment') }}</button></form>
                                @endif
                                <form method="POST" action="{{ route('admin.bus.destroy', $booking) }}" onsubmit="return confirm(@js(__('Move this booking to deleted?')))">@csrf @method('DELETE')<button class="stitch-btn stitch-btn-secondary bus-action" type="submit">{{ __('Delete') }}</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">{{ __('No bus bookings found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="stitch-pagination-wrap">{{ $bookings->links() }}</div>
</section>
<dialog id="bus-receipt-dialog" class="bus-receipt-dialog" aria-labelledby="bus-receipt-title">
    <header>
        <strong id="bus-receipt-title">{{ __('Transfer receipt image') }}</strong>
        <button type="button" class="stitch-btn stitch-btn-secondary" id="bus-receipt-close">{{ __('Close') }}</button>
    </header>
    <img id="bus-receipt-image" alt="">
</dialog>
</div>
@endsection
@push('scripts')
<script>
    (() => {
        const dialog = document.getElementById('bus-receipt-dialog');
        const image = document.getElementById('bus-receipt-image');
        const title = document.getElementById('bus-receipt-title');
        const label = @js(__('Transfer receipt image'));
        document.querySelectorAll('.bus-receipt-preview').forEach(button => {
            button.addEventListener('click', () => {
                image.src = button.dataset.receiptSrc;
                image.alt = label + ' ' + button.dataset.receiptNumber;
                title.textContent = label + ' · ' + button.dataset.receiptNumber;
                dialog.showModal();
            });
        });
        document.getElementById('bus-receipt-close').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => {
            if (event.target === dialog) dialog.close();
        });
        dialog.addEventListener('close', () => { image.removeAttribute('src'); });
    })();
</script>
@endpush
