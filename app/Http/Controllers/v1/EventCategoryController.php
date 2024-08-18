<?php

namespace App\Http\Controllers\v1;

use App\Models\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AssignCategoryToUsersRequest;
use App\Models\EventCategory;

use App\Http\Requests\CreateEventCategoryRequest;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class EventCategoryController extends Controller
{

    public function getAllCategories()
    {
        try {
            $categories = EventCategory::all();
            return successResponse($categories, "Event Categories Fetched Successfully", 200);
        } catch(Exception $e) {
            return errorResponse($e->getMessage(), $e->getStatusCode(), $e->errors() );
        }
    }

    public function assignCategoryToUsers(AssignCategoryToUsersRequest $request)
    {

        try{
            $user = User::find($request->user_id);
            $categories = $request->category_id;

            //sync automatically adds and remove cat_ids based on user_id
        $user->categories()->sync($categories);

            return successResponse($request->all(), "Event Categories Have Been Set Successfully");
        } catch(Exception $e){
            return errorResponse($e?->getMessage(), $e?->getStatusCode(), $e?->errors() );
        }
    }

    public function store(CreateEventCategoryRequest $request)
    {
        try {

            EventCategory::create(['name' => $request->name]);
            return successResponse([], "Event Category {$request->name} has been created succesfully.");

        } catch(QueryException $q){
            return errorResponse($q?->getMessage(), $q?->getStatusCode(), $q?->errors() );
        }
        catch(Exception $e) {
            return errorResponse($e?->getMessage(), $e?->getStatusCode(), $e?->errors() );
        }
    }

    public function update(CreateEventCategoryRequest $request, $id)
    {
        try {

            $eventCategory = EventCategory::where('id', $id)->first();

            if(!$eventCategory || empty($eventCategory)){
                dd('her');
                return errorResponse( 'Event Category Not Found.', 404, [] );
            }

            $init_name = $eventCategory->name;
            $eventCategory->update(['name' => $request->name]);
            return successResponse([], "Event Category {$init_name} has been updated to {$request->name} succesfully.");

        } catch (\NotFoundHttpException $n){
            return errorResponse( 'Event Category Not Found.', 404, [] );
        } catch(\QueryException $q){
            return errorResponse($q?->getMessage(), $q?->getStatusCode(), $q?->errors() );
        } catch(Exception $e) {
            return errorResponse($e?->getMessage(), $e?->getStatusCode(), $e?->errors() );
        }
    }


    public function delete(Request $request, $id)
    {
        try {

            $eventCategory = EventCategory::where('id', $id)->first();

            if(!$eventCategory || empty($eventCategory)){
                return errorResponse( 'Event Category Not Found.', 404, [] );
            }

            //remember deleting will also unlink user-category relation.
            $eventCategory->delete();

            return successResponse([], "Event Category has been deleted succesfully.");
        } catch(ModelNotFoundException $m){
            return errorResponse( 'Event Category Not Found.', 404, [] );
        } catch (NotFoundHttpException $n){
            return errorResponse( 'Event Category Not Found.', 404, [] );
        } catch(QueryException $q){
            return errorResponse($q?->getMessage(), $q?->getStatusCode(), $q?->errors() );
        } catch(Exception $e) {
            return errorResponse($e?->getMessage(), $e?->getStatusCode(), $e?->errors() );
        }
    }
}
