<?php

namespace App\Http\Controllers\v1;

use App\Models\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AssignCategoryToUsersRequest;
use App\Models\EventCategory;

use Exception;

class EventCategoryController extends Controller
{

    public function getAllCategories()
    {
        try {
            $categories = EventCategory::all();
            return successResponse($categories, "Categories Fetched Successfully", 200);
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

            return successResponse($request->all());
        } catch(Exception $e){
            return errorResponse($e?->getMessage(), $e?->getStatusCode(), $e?->errors() );
        }
    }
}
