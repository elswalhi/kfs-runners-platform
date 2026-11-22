@if (session('status'))
    <p class="runner-message runner-message--success" role="status">{{ session('status') }}</p>
@endif
@if ($errors->any())
    <div class="runner-message runner-message--error" role="alert">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
