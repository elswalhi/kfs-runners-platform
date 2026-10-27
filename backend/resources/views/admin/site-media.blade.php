@extends('admin.layout')

@section('title', __('Website Images') . ' · KFS Runners Admin')
@section('section', __('Website Images'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kfs-admin-media.css') }}">
@endpush

@section('content')
<div class="stitch-hero media-page-hero">
    <div class="stitch-hero-copy">
        <span class="stitch-eyebrow">{{ __('WEBSITE CONTENT') }}</span>
        <h1>{{ __('Manage Website Images') }}</h1>
        <p>{{ __('Choose images for each page independently. Upload a replacement or restore the original image at any time.') }}</p>
    </div>
</div>

@if (session('success'))
    <div class="media-flash media-flash-success" role="status">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="media-flash media-flash-error" role="alert">
        <strong>{{ __('Image could not be saved.') }}</strong>
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<nav class="media-page-jump" aria-label="{{ __('Website pages') }}">
    @foreach ($pages as $pageKey => $page)
        <a href="#media-page-{{ $pageKey }}">{{ __($page['label']) }} <span>{{ count($page['slots']) }}</span></a>
    @endforeach
</nav>

@foreach ($pages as $pageKey => $page)
    <section class="media-page-section" id="media-page-{{ $pageKey }}">
        <header class="media-section-heading">
            <div><span>{{ __('PAGE IMAGES') }}</span><h2>{{ __($page['label']) }}</h2><p>{{ __($page['description']) }}</p></div>
            <a href="#top" class="media-back-top">{{ __('Back to top') }} ↑</a>
        </header>

        <div class="media-slot-grid">
            @foreach ($page['slots'] as $slotKey => $slot)
                <article class="media-slot-card">
                    <div class="media-slot-preview">
                        <img src="{{ $slot['image_url'] }}" alt="{{ __($slot['label']) }}" loading="lazy">
                        <span class="media-slot-status {{ $slot['is_custom'] ? 'is-custom' : '' }}">
                            {{ $slot['is_custom'] ? __('Custom image') : __('Default image') }}
                        </span>
                    </div>
                    <div class="media-slot-content">
                        <div class="media-slot-title"><div><h3>{{ __($slot['label']) }}</h3><p>{{ __($slot['hint']) }}</p></div></div>
                        <form class="media-upload-form" method="POST" action="{{ route('admin.site-media.update', ['page' => $pageKey, 'slot' => $slotKey]) }}" enctype="multipart/form-data">
                            @csrf
                            <label class="media-file-label">
                                <span>{{ __('Choose image') }}</span>
                                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                            </label>
                            <small>{{ __('JPG, PNG, or WebP · max 5 MB') }}</small>
                            <button class="stitch-btn stitch-btn-primary" type="submit">{{ $slot['is_custom'] ? __('Replace image') : __('Upload image') }}</button>
                        </form>
                        @if ($slot['is_custom'])
                            <form method="POST" action="{{ route('admin.site-media.destroy', ['page' => $pageKey, 'slot' => $slotKey]) }}" class="media-restore-form" onsubmit="return confirm('{{ __('Restore the original image for this spot?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit">{{ __('Restore default image') }}</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endforeach

@push('scripts')
<script>
document.querySelectorAll('.media-file-label input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => {
        const label = input.closest('.media-file-label')?.querySelector('span');
        if (label && input.files?.[0]) label.textContent = input.files[0].name;
    });
});
</script>
@endpush
@endsection
