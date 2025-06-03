<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $companies = Company::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        return response()->json($companies);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('companies')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->user()->id);
                })
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'website' => 'nullable|url',
            'description' => 'nullable|string',
            'industry' => 'nullable|string|max:100',
            'employee_count' => 'nullable|integer',
            'status' => 'required|in:active,considering,inactive',
            'booking_link' => 'nullable|url|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'バリデーションエラー',
                    'details' => $validator->errors()
                ]
            ], 422);
        }
        $data = $validator->validated();
        $data['user_id'] = $request->user()->id;
        $company = Company::create($data);
        return response()->json(['data' => $company->toArray()], 201);
    }

    public function show($id)
    {
        $company = Company::where('user_id', request()->user()->id)
            ->findOrFail($id);
        return response()->json(['data' => $company->toArray()]);
    }

    public function update(Request $request, $id)
    {
        $company = Company::where('user_id', $request->user()->id)
            ->findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('companies')->where(function ($query) use ($request, $id) {
                    return $query->where('user_id', $request->user()->id)
                        ->where('id', '!=', $id);
                })
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'website' => 'nullable|url',
            'description' => 'nullable|string',
            'industry' => 'nullable|string|max:100',
            'employee_count' => 'nullable|integer',
            'status' => 'required|in:active,considering,inactive',
            'booking_link' => 'nullable|url|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'バリデーションエラー',
                    'details' => $validator->errors()
                ]
            ], 422);
        }
        $company->update($validator->validated());
        return response()->json(['data' => $company->toArray()]);
    }

    public function destroy($id)
    {
        $company = Company::where('user_id', request()->user()->id)
            ->findOrFail($id);
        $company->delete();
        return response()->json(null, 204);
    }
}
