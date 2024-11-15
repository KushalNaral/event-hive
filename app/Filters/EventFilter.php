<?php

namespace App\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EventFilter
{
    protected $request;
    protected $builder;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $builder)
    {
        $this->builder = $builder;

        foreach ($this->getFilters() as $filter => $value) {
            if (method_exists($this, $filter) && !empty($value)) {
                $this->$filter($value);
            }
        }

        return $this->builder;
    }

    protected function getFilters()
    {
        return array_filter($this->request->only([
            'search',
            'category',
            'start_date',
            'end_date',
            'location',
            'timeframe', // for today, weekend, etc.
            'sort_by',
            'per_page'
        ]));
    }

    protected function search($term)
    {
        return $this->builder->where(function ($query) use ($term) {
            $query->where('title', 'LIKE', "%{$term}%")
                  ->orWhere('description', 'LIKE', "%{$term}%")
                  ->orWhere('location', 'LIKE', "%{$term}%");
        });
    }

    protected function category($categoryId)
    {
        return $this->builder->where('category_id', $categoryId);
    }

    protected function location($location)
    {
        return $this->builder->where('location', 'LIKE', "%{$location}%");
    }

    protected function timeframe($timeframe)
    {
        $today = Carbon::today();

        return match($timeframe) {
            'today' => $this->builder->whereDate('start_date', $today),
            'weekend' => $this->builder->whereBetween('start_date', [
                $today->copy()->next('Friday'),
                $today->copy()->next('Sunday')->endOfDay()
            ]),
            'upcoming' => $this->builder->where('start_date', '>=', $today),
            'past' => $this->builder->where('end_date', '<', $today),
            default => $this->builder
        };
    }

    protected function start_date($date)
    {
        return $this->builder->whereDate('start_date', '>=', $date);
    }

    protected function end_date($date)
    {
        return $this->builder->whereDate('end_date', '<=', $date);
    }

    protected function sort_by($sort)
    {
        $direction = str_contains($sort, '-') ? 'desc' : 'asc';
        $column = str_replace('-', '', $sort);

        $allowedSortColumns = [
            'start_date',
            'created_at',
            'title',
            'expected_participants'
        ];

        if (in_array($column, $allowedSortColumns)) {
            return $this->builder->orderBy($column, $direction);
        }

        return $this->builder;
    }
}
