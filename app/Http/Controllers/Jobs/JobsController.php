<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Category\Category;
use App\Models\Job\JobSaved;
use App\Models\Job\Application;
use App\Models\Job\Job;
use App\Models\Job\Search;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobsController extends Controller
{

    public function single($id) {

        $job = Job::find($id);

        // Getting Relation Jobs
        $relatedJobs = Job::where('category', $job->category)
        ->where('id', '!=', $id)
        ->take(5)
        ->get();

        $relatedJobsCount = Job::where('category', $job->category)
        ->where('id', '!=', $id)
        ->take(5)
        ->count();

        // Categories
        $categories = DB::table('categories')
        ->join('jobs', function($join) {
            $join->on(DB::raw('`jobs`.`category` COLLATE utf8mb4_unicode_ci'), '=', DB::raw('`categories`.`name` COLLATE utf8mb4_unicode_ci'));
        })
        ->select('categories.name AS name', 'categories.id AS id', DB::raw('COUNT(jobs.category) AS total'))
        ->groupBy('categories.name', 'categories.id')
        ->get();

        // Saved Job
        if (auth()->user()) {

            $savedJob = JobSaved::where('job_id', $id)
            ->where('user_id', Auth::user()->id)
            ->count();

            // Verfining If User Applied To Job
            $appliedJob = Application::where('user_id', Auth::user()->id)
            ->where('job_id', $id)
            ->count();

            return view('jobs.single', compact('job', 'relatedJobs', 'relatedJobsCount', 'savedJob', 'appliedJob', 'categories'));

        } else {

            return view('jobs.single', compact('job', 'relatedJobs', 'relatedJobsCount', 'categories'));

        }

    }

    public function saveJob(Request $request) {

        $saveJob = JobSaved::create([

            'job_id' => $request->job_id,
            'user_id' => $request->user_id,
            'job_image' => $request->job_image,
            'job_title' => $request->job_title,
            'job_region' => $request->job_region,
            'job_type' => $request->job_type,
            'company' => $request->company

        ]);

        if ($saveJob) {

            return redirect('/jobs/single/'.$request->job_id.'')->with('save', 'Job Saved Successfully');

        }

    }

    public function jobApply(Request $request) {

        if (Auth::user()->cv == 'No cv') {

            return redirect('/jobs/single/'.$request->job_id.'')->with('apply', 'Upload Your cv First in The Profile Page');

        } else {

            $applyJob = Application::create([

                'cv' => Auth::user()->cv,
                'job_id' => $request->job_id,
                'user_id' => Auth::user()->id,
                'email' => Auth::user()->email,
                'job_image' => $request->job_image,
                'job_title' => $request->job_title,
                'job_region' => $request->job_region,
                'job_type' => $request->job_type,
                'company' => $request->company

            ]);

            if ($applyJob) {

                return redirect('/jobs/single/'.$request->job_id.'')->with('applied', 'You Applied To This Job Successfully');

            }

        }

    }

    public function search(Request $request) {

        Request()->validate([

            "job_title" => "required",
            "job_region" => "required",
            "job_type" => "required",

        ]);

        Search::Create([

            "keyword" => $request->job_title

        ]);

        $job_title = $request->get('job_title');
        $job_region = $request->get('job_region');
        $job_type = $request->get('job_type');

        $searches = Job::select()->where('job_title', 'like', '%$job_title%')
        ->where('job_region', 'like', "%$job_region%")
        ->where('job_type', 'like', "%$job_type%")
        ->get();

        return view('jobs.search', compact('searches'));

    }

    public function about() {

        return view('pages.about');

    }

    public function contact() {

        return view('pages.contact');

    }

}
