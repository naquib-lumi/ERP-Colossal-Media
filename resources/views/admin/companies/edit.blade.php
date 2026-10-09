@extends('layouts.app')
@section('title', 'Edit ' . $company->name)
@section('content')
@include('inventory._styles')

<div class="inv-wrap">
  <div class="inv-card">
    <div class="inv-hd">
      <div>
        <a href="{{ route('admin.companies.index') }}" class="inv-sub"><i class="bi bi-arrow-left"></i> Companies</a>
        <div class="inv-title">{{ $company->name }}</div>
      </div>
    </div>

    @include('inventory._flash')

    <form method="POST" action="{{ route('admin.companies.update', $company) }}" enctype="multipart/form-data" style="max-width:640px">
      @csrf
      @method('PUT')
      <div class="inv-field">
        <label class="inv-label" for="coName">Company name *</label>
        <input class="inv-control" name="name" id="coName" value="{{ old('name', $company->name) }}" required maxlength="255">
      </div>
      <div class="inv-field">
        <label class="inv-label" for="coReg">Registration no.</label>
        <input class="inv-control" name="reg_no" id="coReg" value="{{ old('reg_no', $company->reg_no) }}" maxlength="30">
      </div>
      <div class="inv-field">
        <label class="inv-label" for="coAddress">Address</label>
        <textarea class="inv-control" name="address" id="coAddress" rows="4" maxlength="1000">{{ old('address', $company->address) }}</textarea>
        <div class="inv-hint">One line per printed line.</div>
      </div>
      <div class="inv-field">
        <label class="inv-label" for="coPhone">Phone</label>
        <input class="inv-control" name="phone" id="coPhone" value="{{ old('phone', $company->phone) }}" maxlength="50">
      </div>
      <div class="inv-field">
        <label class="inv-label" for="coFax">Fax</label>
        <input class="inv-control" name="fax" id="coFax" value="{{ old('fax', $company->fax) }}" maxlength="50">
      </div>
      <div class="inv-field">
        <label class="inv-label" for="coMobile">Mobile</label>
        <input class="inv-control" name="mobile" id="coMobile" value="{{ old('mobile', $company->mobile) }}" maxlength="50">
      </div>
      <div class="inv-field">
        <label class="inv-label" for="coEmail">Email</label>
        <input class="inv-control" type="email" name="email" id="coEmail" value="{{ old('email', $company->email) }}" maxlength="255">
      </div>
      <div class="inv-field">
        <label class="inv-label" for="coLogo">Logo</label>
        @if ($company->logoPath())
          <div style="margin-bottom:8px"><img src="{{ asset($company->logo) }}" alt="Current logo" style="max-height:60px;max-width:260px"></div>
        @endif
        <input class="inv-control" type="file" name="logo" id="coLogo" accept="image/png,image/jpeg,image/webp">
        <div class="inv-hint">PNG or JPG, up to 2 MB. Leave empty to keep the current logo.</div>
      </div>
      <button class="inv-btn inv-btn-dark" type="submit"><i class="bi bi-check2"></i> Save</button>
    </form>
  </div>
</div>
@endsection
