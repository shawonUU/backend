<?php

namespace App\Http\Controllers\VueControllers;

use App\Models\Product;
use App\Models\ProductQuery;
use App\Models\job;
use Auth;
use Illuminate\Http\Request;

class ProductQueryController extends Controller
{

    /**
     * Retrieve queries that belongs to current seller
     */
    public function index()
    {
        $queries = ProductQuery::where('seller_id', Auth::id())->latest()->paginate(20);
        return view('backend.support.product_query.index', compact('queries'));
    }

    /**
     * Retrieve specific query using query id.
     */
    public function show($id)
    {
        $query = ProductQuery::find(decrypt($id));
        return view('backend.support.product_query.show', compact('query'));
    }

    /**
     * store products queries through the ProductQuery model
     * data comes from product details page
     * authenticated user can leave queries about the product
     */
    public function store(Request $request)
    {
        return $request->header();
        return "hello";
        $this->validate($request, [
            'question' => 'required|string',
        ]);


        $query = new ProductQuery();
        $query->customer_id = Auth::id();

        if($request->has('job_id')){
            $job = Job::find($request->job_id);
            $query->seller_id = $job->user_id;
            $query->job_id = $request->job_id;
        }
        else{
            $product = Product::find($request->product);
            $query->product_id = $product->id;
            $query->seller_id = $product->user_id;
        }


        $query->question = $request->question;
        $query->save();
        flash(translate('Your query has been submittes successfully'))->success();
        return redirect()->back();
    }

    /**
     * Store reply against the question from Admin panel
     */

    public function reply(Request $request, $id)
    {
        $this->validate($request, [
            'reply' => 'required',
        ]);
        $query = ProductQuery::find($id);
        $query->reply = $request->reply;
        $query->save();
        flash(translate('Replied successfully!'))->success();
        return redirect()->route('product_query.index');
    }
}
