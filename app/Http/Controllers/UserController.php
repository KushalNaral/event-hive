<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreferencesRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function updatePreferences(PreferencesRequest $request)
    {
        try{
            $user = auth()->user();
            $preferences = array_filter($request->validated(), function ($value) {
                return !is_null($value);
            });

            $user->updatePreferences($preferences);

            return successResponse($preferences, 'Preferences updated successfully!', 200);
        } catch (QueryException $q) {
            DB::rollBack();
            return errorResponse('Database error occurred while creating event.', 500, [$q->getMessage()]);
        } catch (Exception $e) {
            DB::rollBack();
            return errorResponse('An unexpected error occurred.', 500, [$e->getMessage()]);
        }
    }
}
