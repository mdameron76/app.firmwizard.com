<?php

namespace App\Http\Controllers;

use App\Models\Firm;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the dashboard
     */
    public function index(Request $request): View
    {
        return view('pages.dashboards.index');
    }

    /**
     * Get current firm for user
     */
    protected function getCurrentFirm(Request $request): ?Firm
    {
        $firmId = session('current_firm_id');
        
        if ($firmId) {
            return $request->user()->firms()->find($firmId);
        }

        $firm = $request->user()->firms()->first();
        
        if ($firm) {
            session(['current_firm_id' => $firm->id]);
        }

        return $firm;
    }
}
