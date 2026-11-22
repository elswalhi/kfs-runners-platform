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
        <a class="stitch-btn stitch-btn-secondary" href="{{ route('admin.bus.index', $deleted ? ['tab' => 'deleted'] : []) }}">{{ __('Reset') }}</a>
    </form>
    <div class="stitch-table-scroll">
        <table class="stitch-table">
            <thead><tr><th>{{ __('Booking reference') }}</th><th>{{ __('Full name') }}</th><th>{{ __('Email address') }}</th><th>{{ __('Phone number') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Payment status') }}</th><th>{{ __('Date') }}</th><th>{{ __('Action') }}</th></tr></thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr>
                        <td><bdi>{{ $booking->booking_number }}</bdi></td><td>{{ $booking->name }}</td><td>{{ $booking->email }}</td><td><bdi>{{ $booking->phone }}</bdi></td><td>EGP {{ number_format($booking->amount, 2) }}</td><td>{{ __(ucfirst($booking->payment_status)) }}</td><td>{{ $booking->created_at->format('d M Y, H:i') }}</td>
                        <td>
                            @if ($deleted)
                                <form method="POST" action="{{ route('admin.bus.restore', $booking->id) }}">@csrf @method('PATCH')<button class="stitch-btn stitch-btn-secondary bus-action" type="submit">{{ __('Restore') }}</button></form>
                            @else
                                <form method="POST" action="{{ route('admin.bus.destroy', $booking) }}" onsubmit="return confirm(@js(__('Move this booking to deleted?')))">@csrf @method('DELETE')<button class="stitch-btn stitch-btn-secondary bus-action" type="submit">{{ __('Delete') }}</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">{{ __('No bus bookings found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="stitch-pagination-wrap">{{ $bookings->links() }}</div>
</section>
</div>
@endsection
