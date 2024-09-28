<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRatingRequest;
use App\Models\EventRating;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class RatingController extends Controller
{
    public function  getAllRatings(Request $request){
        try {
            $events = EventRating::all();
            return successResponse($events, 'Event(s) fetched successfully');
        } catch (\Throwable $th) {
            return errorResponse($th->getMessage(), $th->getStatusCode(), $th->errors() );
        }
    }


    public function storeRatings(EventRatingRequest $request){

        try {
            $params = $request->validated();

            DB::beginTransaction();

            $this->resetRatings($params);

            $rating = EventRating::create($params);
            DB::commit();

            return successResponse($rating, "Event Created Successfully", 200);
        } catch (QueryException $q) {
            DB::rollBack();
            return errorResponse('Database error occurred while creating event.', 500, [$q->getMessage()]);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse('An unexpected error occurred.', 500, [$e->getMessage()]);
        }
    }

    //initially implemented only get and store other methods will be updated later
    // the recommendation engine is prio right now

    private function resetRatings($params){
        try {
            if(!$params && count($params) <= 0){
                return;
            }

            $existing_ratings = EventRating::where('event_id', $params['event_id'])->where('created_by', auth()->user()->id)->get();

            foreach ($existing_ratings as $key => $value) {
                $value->delete();
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
