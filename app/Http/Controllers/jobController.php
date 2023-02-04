<?php

namespace App\Http\Controllers;
use App\Models\job;
use App\Models\application;
use Illuminate\Http\Request;
use App\Models\ProductQuery;
use Illuminate\Support\Str;
use Auth;

class jobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $jobs=Job::where('user_id',Auth::user()->id)->paginate('10');

        return view('seller.job.index',compact('jobs'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('seller.job.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $job = new job;
        $job->title = $request->title;
        // if(Auth::user()->user_type == 'seller'){
        //     $job->user_id = Auth::user()->id;
        //     if(get_setting('product_approve_by_admin') == 1) {
        //         $job->approved = 0;
        //     }
        // }
        // else{
        //     $job->user_id = \App\Models\User::where('user_type', 'admin')->first()->id;
        // }
        $job->user_id=Auth::user()->id;
        $job->thumbnail = $request->thumbnail_img;
        $job->description = $request->description;
        $job->seo_title = $request->meta_title;
        $job->seo_description = $request->meta_description;



        if($job->seo_title == null) {
            $job->seo_title = $job->title;
        }

        if($job->seo_description == null) {
            $job->seo_description = strip_tags($job->description);
        }


        if ($request->slug != null) {
            $job->slug = str_replace(' ', '-', $request->slug);
        }
        else {
            $job->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title)).'-'.Str::random(5);
        }


        if(Job::where('slug', $job->slug)->count() > 0){
            flash(translate('Another product exists with same slug. Please change the slug!'))->warning();
            return back();
        }
        $job->publish = 1;
        if($request->button == 'unpublish' || $request->button == 'draft') {
            $job->published = 0;
        }
        //$variations = array();

        $job->save();
        flash(translate('Job has been added successfully'))->success();
        return redirect()->route('job.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $job=Job::where('slug',$slug)->first();
         $product_queries = ProductQuery::where('job_id', $job->id)->where('customer_id', '!=', Auth::id())->latest('id')->paginate(10);
          $related_jobs=Job::where('user_id',$job->user_id)->get()->take(5);
        //   dd($related_jobs);
        return view('seller.job.show',compact('job','product_queries','related_jobs'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $job=Job::findOrFail($id);
        return view('seller.job.edit',compact('job'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $job=Job::findOrFail($id);
        $job->title = $request->title;
        // if(Auth::user()->user_type == 'seller'){
        //     $job->user_id = Auth::user()->id;
        //     if(get_setting('product_approve_by_admin') == 1) {
        //         $job->approved = 0;
        //     }
        // }
        // else{
        //     $job->user_id = \App\Models\User::where('user_type', 'admin')->first()->id;
        // }
        $job->user_id=1;
        $job->thumbnail = $request->thumbnail_img;
        $job->description = $request->description;
        $job->seo_title = $request->meta_title;
        $job->seo_description = $request->meta_description;



        if($job->seo_title == null) {
            $job->seo_title = $job->title;
        }

        if($job->seo_description == null) {
            $job->seo_description = strip_tags($job->description);
        }


        if ($request->slug != null) {
            $job->slug = str_replace(' ', '-', $request->slug);
        }
        else {
            $job->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title)).'-'.Str::random(5);
        }


        if(Job::where('slug', $job->slug)->count() > 0){
            flash(translate('Another product exists with same slug. Please change the slug!'))->warning();
            return back();
        }
        $job->publish = 1;
        if($request->button == 'unpublish' || $request->button == 'draft') {
            $job->published = 0;
        }
        //$variations = array();

        $job->save();
        flash(translate('Job has been Updated successfully'))->success();
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return $id;
    }

    public function job_apply(Request $request)
    {
        $application = new application();

        $application->job_id=$request->job_id;
        $application->user_id=$request->user_id;
        $application->save();

        flash(translate('Applied successfully'))->success();
        return back();
    }


}
