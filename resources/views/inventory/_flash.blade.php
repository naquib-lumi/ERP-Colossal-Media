@if (session('success'))
  <div class="inv-alert ok"><i class="bi bi-check-circle me-1"></i> {{ session('success') }}</div>
@endif
@if ($errors->any())
  <div class="inv-alert bad">
    <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
