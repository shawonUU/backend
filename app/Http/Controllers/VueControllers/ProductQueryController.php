<?php

namespace App\Http\Controllers\VueControllers;

use Auth;
use App\Models\job;
use App\Models\Product;
use App\Models\ProductQuery;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;

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
        $data = $request->header();
        $token = $data["token"][0];

        $token = PersonalAccessToken::findToken($token);
        if(!$token) return response()->json(["Unauthorized"], 401);
        $user = $token->tokenable;
        if(!$user) return response()->json(["Unauthorized"], 401);
         $question = $request->header("question");
        // $dataRules =[
        //     'question' => ['required|string'],
        // ];
        // return $validator = Validator::make($question, $dataRules);

        $query = new ProductQuery();
        $query->customer_id = $user->id;
        $productId = $request->header("productId");
        if($request->has('job_id')){
            $job = Job::find($request->job_id);
            $query->seller_id = $job->user_id;
            $query->job_id = $request->job_id;
        }
        else{
            $product = Product::find($productId);
            $query->product_id = $product->id;
            $query->seller_id = $product->user_id;
        }
        $query->question =  $question;
        $query->save();
        // flash(translate('Your query has been submittes successfully'))->success();
        // return redirect()->back();
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
