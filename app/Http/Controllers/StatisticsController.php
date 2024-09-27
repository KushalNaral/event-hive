<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class StatisticsController extends Controller
{
    public function index()
    {
        $filePath = storage_path('logs/recommendation_engine.log');
        $users = $this->parseLogFile($filePath);
        return view('welcome', compact('users'));
    }

    public function parseLogFile($filePath)
    {
        $logContent = file_get_contents($filePath);
        $lines = explode("\n", $logContent);

        $users = [];
        $currentUser = null;
        $currentEvent = null;

        foreach ($lines as $line) {
            if (preg_match('/Starting recommendation calculation for User ID: (\d+)/', $line, $matches)) {
                $currentUser = [
                    'id' => (int)$matches[1],
                    'name' => '',
                    'events' => [],
                ];
            } elseif (preg_match('/User Name: (.+)/', $line, $matches)) {
                $currentUser['name'] = $matches[1];
            } elseif (preg_match('/Calculation started for event id: (\d+)/', $line, $matches)) {
                $currentEvent = [
                    'id' => (int)$matches[1],
                    'score' => 0,
                    'preferences' => [],
                ];
            } elseif (preg_match('/Preference: (\w+), Weight: [\d.]+, Score: ([-\d.]+)/', $line, $matches)) {
                $currentEvent['preferences'][$matches[1]] = (float)$matches[2];
            } elseif (preg_match('/Final Preference Score: ([\d.]+)/', $line, $matches)) {
                $currentEvent['score'] = (float)$matches[1];
                $currentUser['events'][] = $currentEvent;
                $currentEvent = null;
            } elseif (preg_match('/Finished recommendation calculation for User ID: (\d+)/', $line, $matches)) {
                $users[] = $currentUser;
                $currentUser = null;
            }
        }

        return $users;
    }
}

