<?php

namespace App\Http\Controllers\VueControllers\Api\VUE;
use App\Models\job;
use App\Models\application;
use Illuminate\Http\Request;


use App\Http\Resources\VUE\JobCollection;
use App\Http\Resources\VUE\JobdetailsCollection;

class JobController extends Controller
{

    public function index()
    {
        $alljob=Job::all();
        return new JobCollection($alljob);


        // return response()->json($alljob);

        // return view('test',compact('alljob'));
    }

    public function show($slug){
         $job = Job::where('slug',$slug)->get();
         return new JobdetailsCollection($job);

    }
      public function job_apply(Request $request)
    {
        $application = new application();

        $application->job_id=$request->job_id;
        $application->user_id=$request->user_id;
        $application->save();
            return response()->json(['result' => true, 'message' => 'Applied succesfully'], 200);

    }
}
