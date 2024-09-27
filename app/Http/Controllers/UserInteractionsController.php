<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserInteractionRequest;
use App\Models\UserInteractions;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class UserInteractionsController extends Controller
{

    public function getDefaultInteractions(){
        $interactions = UserInteractions::interactions();

        return successResponse($interactions, 'Default Interactions Fetched Succesfully',  200);
    }

    public function setInteractions(UserInteractionRequest $request){

        try {
            $params = $request->validated();

            DB::beginTransaction();
            $interaction = UserInteractions::create($params);
            DB::commit();

            return successResponse($interaction, "Event Created Successfully", 200);
        } catch (QueryException $q) {
            DB::rollBack();
            return errorResponse('Database error occurred while creating event.', 500, [$q->getMessage()]);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse('An unexpected error occurred.', 500, [$e->getMessage()]);
        }
    }

    public function getAllInteractions(){
        try {
            $interactions = UserInteractions::all();
            return successResponse($interactions, 'Interaction(s) fetched successfully');
        } catch (\Throwable $th) {
            return errorResponse($th->getMessage(), $th->getStatusCode(), $th->errors() );
        }
    }

    public function getAllInteractionsForUser(Request $request, $user_id){
        try {

            $filters = null;
            $interactions = UserInteractions::where('user_id', $user_id);

            if(isset($request) && isset($request->interaction_type)){
                $filters = $request->interaction_type;
                $interactions = $interactions->where('interaction_type', $filters);
            }


            return successResponse($interactions->get(), 'Interaction(s) fetched successfully');
        } catch (\Throwable $th) {
            return errorResponse($th->getMessage(), $th->getStatusCode(), $th->errors() );
        }
    }

    public function getInteractionForEvent(Request $request, $event_id){
        try {

            $filters = null;
            $interactions = UserInteractions::where('event_id', $event_id);

            if(isset($request) && isset($request->interaction_type)){
                $filters = $request->interaction_type;
                $interactions = $interactions->where('interaction_type', $filters);
            }

            return successResponse($interactions->get(), 'Event(s) fetched successfully');
        } catch (\Throwable $th) {
            return errorResponse($th->getMessage(), $th->getStatusCode(), $th->errors() );
        }
    }

    public function getInteractionForEventAndUser(Request $request, $event_id){
        try {

            $filters = null;
            $interactions = UserInteractions::where('user_id', auth()->user()->id)->where('event_id', $event_id);

            if(isset($request) && isset($request->interaction_type)){
                $filters = $request->interaction_type;
                $interactions = $interactions->where('interaction_type', $filters);
            }

            return successResponse($interactions->get(), 'Event(s) fetched successfully');
        } catch (\Throwable $th) {
            return errorResponse($th->getMessage(), $th->getStatusCode(), $th->errors() );
        }
    }
}
