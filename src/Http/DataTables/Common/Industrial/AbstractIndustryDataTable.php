<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015 to present Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Seat\Web\Http\DataTables\Common\Industrial;

use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Services\DataTable;

/**
 * Class AbstractIndustryDataTable.
 *
 * @package Seat\Web\Http\DataTables\Common\Industrial
 */
abstract class AbstractIndustryDataTable extends DataTable
{
    /**
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function ajax(): JsonResponse
    {
        return datatables()
            ->eloquent($this->applyScopes($this->query()))
            ->editColumn('start_date', function ($row) {
                return view('web::partials.date', ['datetime' => $row->start_date])->render();
            })
            ->editColumn('end_date', function ($row) {
                return view('web::partials.date', ['datetime' => $row->end_date])->render();
            })
            ->addColumn('progress', function ($row) {
                return view('web::common.industries.partials.progress', compact('row'))->render();
            })
            ->editColumn('runs', function ($row) {
                switch ($row->status) {
                    case 'active':
                        return sprintf('<span class="badge badge-primary">%s</span>', number($row->runs, 0));
                    case 'cancelled':
                        return sprintf('<span class="badge badge-danger">%s</span>', number($row->runs, 0));
                    case 'delivered':
                        return sprintf('<span class="badge badge-secondary">%s</span>', number($row->runs, 0));
                    case 'paused':
                        return sprintf('<span class="badge badge-warning">%s</span>', number($row->runs, 0));
                    case 'ready':
                        return sprintf('<span class="badge badge-success">%s</span>', number($row->runs, 0));
                    default:
                        return number($row->runs, 0);
                }
            })
            ->editColumn('location.name', function ($row) {
                return $row->location->name;
            })
            ->editColumn('activity.activityName', function ($row) {
                // Key the icon off the numeric activityID, not the display name:
                // the SDE calls activities 3 and 4 "Time Efficiency Research" and
                // "Material Efficiency Research", so matching the old
                // "Researching ..." names silently dropped their icons.
                $icons = [
                    1 => 'fas fa-industry',       // Manufacturing
                    3 => 'fas fa-hourglass-half', // Time Efficiency Research
                    4 => 'fas fa-gem',            // Material Efficiency Research
                    5 => 'fas fa-flask',          // Copying
                    8 => 'fas fa-microscope',     // Invention
                    9 => 'fas fa-atom',           // Reactions
                ];

                $icon = $icons[$row->activity->activityID] ?? null;

                if (is_null($icon))
                    return $row->activity->activityName;

                return sprintf('<i class="%s"></i> %s', $icon, $row->activity->activityName);
            })
            ->editColumn('blueprint.typeName', function ($row) {
                return view('web::partials.type', [
                    'type_id' => $row->blueprint->typeID,
                    'type_name' => $row->blueprint->typeName,
                    'variation' => 'bpc',
                ])->render();
            })
            ->editColumn('product.typeName', function ($row) {
                return view('web::partials.type', [
                    'type_id' => $row->product->typeID,
                    'type_name' => $row->product->typeName,
                    'variation' => $row->product->group->categoryID == 9 ? 'bpc' : 'icon',
                ])->render();
            })
            ->rawColumns([
                'start_date', 'end_date', 'progress', 'runs', 'activity.activityName',
                'blueprint.typeName', 'product.typeName',
            ])
            ->toJson();
    }

    /**
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->postAjax()
            ->columns($this->getColumns())
            ->addTableClass('table-striped table-hover')
            ->parameters([
                'drawCallback' => 'function() { 
                    $("[data-toggle=tooltip]").tooltip();
                    updateProgressBar();
                    setInterval(function () { //this is to update every 15 seconds
                      updateProgressBar();
                    }, 15000);
                }',
            ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    abstract public function query();

    /**
     * @return array
     */
    public function getColumns()
    {
        return [
            ['data' => 'start_date', 'title' => trans('web::industry.start')],
            ['data' => 'end_date', 'title' => trans('web::industry.end')],
            ['data' => 'progress', 'title' => trans('web::seat.progress')],
            ['data' => 'location.name', 'title' => trans('web::industry.location')],
            ['data' => 'activity.activityName', 'title' => trans('web::industry.activity')],
            ['data' => 'runs', 'title' => trans('web::industry.runs')],
            ['data' => 'blueprint.typeName', 'title' => trans('web::industry.blueprint')],
            ['data' => 'product.typeName', 'title' => trans('web::industry.product')],
        ];
    }
}
