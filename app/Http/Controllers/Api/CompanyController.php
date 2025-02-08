<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Rules\UniqueRucRule;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companies = Company::where('user_id', JWTAuth::user()->id)->get();

        return response()->json($companies, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'razon_social' => 'required|string',
            'ruc' => [
                'required',
                'string',
                'regex:/^(10|20)\d{9}$/',
                // 'unique:companies,ruc',
                new \App\rules\UniqueRucRule(JWTAuth::user()->id)
            ],
            'direccion' => 'required|string',
            'logo_path' => 'nullable|image',
            'sol_user' => 'required|string',
            'sol_pass' => 'required|string',
            'cert_path' => 'required|file|mimes:pem,txt',
            'client_id' => 'nullable|string',
            'client_secret' => 'nullable|string',
            'production' => 'nullable|boolean',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] =  $request->file('logo_path')->store('logo');;
        }
        $data['cert_path'] = $request->file('cert_path')->store('cert_paths');
        $data['user_id'] = JWTAuth::user()->id;
        // return $request->all();

        $company = Company::create($data);

        return response()->json([
            'message'  => 'empresa creada',
            'company' => $company
        ]);

        return $data;
    }

    /**
     * Display the specified resource.
     */
    public function show($company)
    {

        $company = Company::where('ruc', $company)
            ->where('user_id', JWTAuth::user()->id)
            ->firstorFail();


        return response()->json(
            $company,
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,  $company)
    {


        $company = Company::where('ruc', $company)
            ->where('user_id', JWTAuth::user()->id)

            ->firstorFail();


        $data = $request->validate([
            'razon_social' => 'required|string',
            'ruc' => [
                'nullable',
                'string',
                'regex:/^(10|20)\d{9}$/',
                // 'unique:companies,ruc',
                 new \App\rules\UniqueRucRule($company->id)
            ],
            'direccion' => 'nullable|string|min:5',
            'logo_path' => 'nullable|image',
            'sol_user' => 'nullable|string|min:5',
            'sol_pass' => 'nullable|string|min:5',
            'cert_path' => 'nullable|file|mimes:pem,txt',
            'client_id' => 'nullable|string',
            'client_secret' => 'nullable|string',
            'production' => 'nullable|boolean',
        ]);

        if ($request->hasFile('logo_path')) {
            $data['logo_path'] =  $request->file('logo_path')->store('logo');;
        }
        if ($request->hasFile('cert_path')) {
            $data['cert_path'] = $request->file('cert_path')->store('cert_paths');
        }

        $company->update($data);

        return response()->json([
            'message' => 'Empresa Actualizada',
            'company' => $company
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $company)
    {
        $company = Company::where('ruc', $company)
        ->where('user_id', JWTAuth::user()->id)

        ->firstorFail();


       $company->delete();

       return response()->json([
        'message' => 'Empresa eliminada',
        'company' => $company
    ], 200);

    }
}
