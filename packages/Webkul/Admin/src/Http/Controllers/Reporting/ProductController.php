<?php

namespace Webkul\Admin\Http\Controllers\Reporting;

class ProductController extends Controller
{
    /**
     * Request param functions.
     *
     * @var array
     */
    protected $typeFunctions = [

    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin::reporting.products.index')->with([

        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function view()
    {
        return view('admin::reporting.view')->with([
            'entity'    => 'products',

        ]);
    }
}
