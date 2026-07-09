<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsentController extends Controller
{
    public function log(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer',
            'user_type' => 'nullable|in:profesional,cliente',
            'document_type' => 'required|string|max:100',
            'document_version' => 'required|string|max:20',
            'document_hash' => 'required|string|size:64',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $log = ConsentLog::create([
            'user_id' => $request->user_id ?? ($request->user() ? $request->user()->id : null),
            'user_type' => $request->user_type ?? ($request->user() ? ($request->user() instanceof \App\Models\Cliente ? 'cliente' : 'profesional') : null),
            'document_type' => $request->document_type,
            'document_version' => $request->document_version,
            'document_hash' => $request->document_hash,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'accepted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $log
        ], 201);
    }
}
