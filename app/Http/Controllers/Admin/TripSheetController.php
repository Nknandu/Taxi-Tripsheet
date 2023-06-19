<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PDF;



class TripSheetController extends Controller
{
    public function create()
    {
        return view('pages.admin.trip_sheet.create');
    }

    public function generatePDF(){

        $pdf = PDF::loadView('pages.admin.trip_sheet.trip_sheet')->setPaper('a4', 'landscape');

        return $pdf->stream('itsolutionstuff.pdf');

    }


}
