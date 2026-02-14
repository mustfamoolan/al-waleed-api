<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function index()
    {
        return response()->json(Party::with('customer', 'staff.user', 'agent')->get());
    }

    public function show(Party $party)
    {
        return response()->json($party->load('customer', 'staff.user', 'agent'));
    }
}
