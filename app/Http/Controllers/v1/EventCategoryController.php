<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
}
