<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DispatchControlHistoryController extends Controller
{
    public function index()
    {
        // Dummy data (frontend only)
        $orders = [
            [
                'product_id' => 'ORD005-P1',
                'product_name' => 'Business Cards - Premium',
                'completed_date' => 'Jan 15, 2025',
                'remarks' => 'Perfect quality'
            ],
            [
                'product_id' => 'ORD005-P2',
                'product_name' => 'Flyers A4 - Standard',
                'completed_date' => 'Jan 14, 2025',
                'remarks' => '-'
            ],
            [
                'product_id' => 'ORD006-P1',
                'product_name' => 'Brochure Tri-fold',
                'completed_date' => 'Jan 13, 2025',
                'remarks' => 'Color correction applied'
            ],
            [
                'product_id' => 'ORD007-P1',
                'product_name' => 'Poster A2 - Glossy',
                'completed_date' => 'Jan 12, 2025',
                'remarks' => 'Rush order completed'
            ],
            [
                'product_id' => 'ORD007-P2',
                'product_name' => 'Letterhead - Corporate',
                'completed_date' => 'Jan 11, 2025',
                'remarks' => '-'
            ],
            [
                'product_id' => 'ORD007-P5',
                'product_name' => 'Banner 3×6 feet',
                'completed_date' => 'Jan 10, 2025',
                'remarks' => 'Weather resistant material'
            ],
            [
                'product_id' => 'ORD008-P1',
                'product_name' => 'Menu Cards - Restaurant',
                'completed_date' => 'Jan 09, 2025',
                'remarks' => 'Laminated finish'
            ],
            [
                'product_id' => 'ORD008-P3',
                'product_name' => 'Stickers - Custom Shape',
                'completed_date' => 'Jan 08, 2025',
                'remarks' => 'Die-cut precision'
            ],
        ];

        return view('dispatchcontrol.history', compact('orders'));
    }
}
