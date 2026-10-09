<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

/** Admin: details of the two issuing companies, printed on quotations and delivery orders. */
class CompanyController extends Controller
{
    public function index()
    {
        return view('admin.companies.index', ['companies' => Company::orderByDesc('is_default')->orderBy('name')->get()]);
    }

    public function edit(Company $company)
    {
        return view('admin.companies.edit', ['company' => $company]);
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'reg_no'  => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'fax'     => ['nullable', 'string', 'max:50'],
            'mobile'  => ['nullable', 'string', 'max:50'],
            'email'   => ['nullable', 'email:rfc', 'max:255'],
            'logo'    => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        // The logo goes to storage/app/public (served via the storage:link symlink); Company::logo is a path under public/.
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $data['logo'] = 'storage/' . $file->storeAs('companies', $company->code . '-' . now()->format('YmdHis') . '.' . $file->extension(), 'public');
        } else {
            unset($data['logo']);
        }

        $company->update($data);

        return redirect()->route('admin.companies.index')->with('success', "{$company->name} saved.");
    }
}
