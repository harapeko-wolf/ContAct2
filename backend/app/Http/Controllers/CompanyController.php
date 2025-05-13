<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $companies = Company::orderBy('created_at', 'desc')
            ->paginate($perPage);
        return response()->json($companies);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'website' => 'nullable|url',
            'description' => 'nullable|string',
            'industry' => 'nullable|string|max:100',
            'employee_count' => 'nullable|integer',
            'status' => 'required|string|in:active,considering,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company = Company::create([
            'id' => Str::uuid(),
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'website' => $request->website,
            'description' => $request->description,
            'industry' => $request->industry,
            'employee_count' => $request->employee_count,
            'status' => $request->status,
        ]);

        return response()->json($company, 201);
    }

    public function show($id)
    {
        $company = Company::findOrFail($id);
        return response()->json($company);
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:companies,email,' . $id . ',id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'website' => 'nullable|url',
            'description' => 'nullable|string',
            'industry' => 'nullable|string|max:100',
            'employee_count' => 'nullable|integer',
            'status' => 'required|string|in:active,considering,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company->update($request->all());
        return response()->json($company);
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();
        return response()->json(null, 204);
    }
}
