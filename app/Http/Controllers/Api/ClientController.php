<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Client::query();
        if ($request->filled('search')) {
            $query->where(fn($q) => $q->where('name','like',"%{$request->search}%")->orWhere('code','like',"%{$request->search}%"));
        }
        if ($request->filled('type')) $query->where('type', $request->type);
        return response()->json($query->orderBy('code')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'          => 'required|string|max:20|unique:clients',
            'name'          => 'required|string|max:100',
            'name_kana'     => 'nullable|string|max:100',
            'type'          => 'required|in:customer,vendor,both',
            'postal_code'   => 'nullable|string|max:10',
            'address'       => 'nullable|string|max:200',
            'phone'         => 'nullable|string|max:20',
            'email'         => 'nullable|email',
            'contact_person'=> 'nullable|string|max:50',
            'payment_terms' => 'nullable|integer|min:0|max:180',
            'is_active'     => 'boolean',
            'notes'         => 'nullable|string',
        ]);
        return response()->json(Client::create($data), 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json($client->load(['sales' => fn($q) => $q->latest()->limit(5), 'invoices' => fn($q) => $q->latest()->limit(5)]));
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $data = $request->validate([
            'code'          => "required|string|max:20|unique:clients,code,{$client->id}",
            'name'          => 'required|string|max:100',
            'name_kana'     => 'nullable|string|max:100',
            'type'          => 'required|in:customer,vendor,both',
            'postal_code'   => 'nullable|string|max:10',
            'address'       => 'nullable|string|max:200',
            'phone'         => 'nullable|string|max:20',
            'email'         => 'nullable|email',
            'contact_person'=> 'nullable|string|max:50',
            'payment_terms' => 'nullable|integer|min:0|max:180',
            'is_active'     => 'boolean',
            'notes'         => 'nullable|string',
        ]);
        $client->update($data);
        return response()->json($client);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();
        return response()->json(['message' => '削除しました。']);
    }
}
