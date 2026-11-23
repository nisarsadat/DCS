<?php

namespace App\Http\Controllers;

use App\Support\Media;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\AuditLogger;
use ResponseStatus;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        /// admin controller
    }

    public function listRecord($request, $model, $filter = [], $withTables = null)
{
    $requests    = $request->all();
    $method      = $request->has('page') ?'paginate' : 'get';
    $methodValue = $request->has('page') ? $request->get('perPage', 10) : '*';
    $requestedOrder = (string) $request->get('order_column', 'id');
    $orderColumn = preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $requestedOrder) ? $requestedOrder : 'id';
    $orderType   = strtolower((string) $request->get('order_type', 'desc')) === 'asc' ? 'asc' : 'desc';

    
    $query = $withTables ? $model::with($withTables) : $model::query();

    return $query->where(function ($query) use ($requests, $filter) {
        foreach ($requests as $key => $value) {
            if (in_array($key, $filter)) {
                $query->where($key, 'like', '%' . $value . '%');
            }
        }
    })->orderBy($orderColumn, $orderType)->$method($methodValue);
}


    public function showRecord($model , $id)
    {
        return $model::findOrFail($id);
    }


    public function storeRecord($request,$model)
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::id();
        if (isset($validated['password'])) {
            $validated['password'] = \Hash::make($validated['password']);
        } 
        $record =  $model::create($validated);
        $this->storeImage($request, $record);
        return $record;
    }

    public function updateRecord($request,$record)
    {

        // $record  = $model::findOrFail($id);s
        $validated = $request->validated();
        $this->deleteImage($record,$request);
        $validated['user_id'] = Auth::id();
        if (! filled($validated['password'] ?? null)) {
            unset($validated['password']);
        } elseif (isset($validated['password'])) {
            $validated['password'] = \Hash::make($validated['password']);
        }
        $record->update($validated);
        $this->storeImage($request, $record);
        return $record;

    }

    public function restore($model, $id)
    {
        $item = $model::withTrashed()->findOrFail($id);
        $item->restore();
    }

    public function forceDelete($model, $id)
    {
        $item = $model::withTrashed()->findOrFail($id);
        $result = $item->forceDelete();
        $this->deleteImage($result);
        return response("Number of rows affected: " . $result, 202);
    }

    public function deleteRecord($record)
    {
        $soft = in_array(SoftDeletes::class, class_uses_recursive($record), true);
        AuditLogger::log('deleted', $record::class, (int) $record->id, array_filter([
            'label' => $record->name ?? $record->reference ?? $record->phone ?? null,
        ]));
        $result = $record->delete();
        if (! $soft) {
            $this->deleteImage($record);
        }
        return response("Number of rows affected: " . $result, 202);
    }


    public function errorResponse($message,$statusCode = 200)
    {
        return response(["status" => ResponseStatus::FAILED, "message" => $message],$statusCode);
    }

    public function successResponse($message,$statusCode = 200)
    {
        return response(["status" => ResponseStatus::SUCCESS, "message" => $message],$statusCode);
    }


    private function storeImage($request, $record)
    {
        if($record->images == null) return;
        foreach ($record->images as $image) {
            if ($request->hasFile($image)) {
                $imagePath = Media::put($request->file($image), class_basename($record));
                $record->update([$image => $imagePath]);
            }
        }
    }


 private function deleteImage($model, $request = null)
{
    if ($model->images == null) return;

    foreach ($model->images as $image) {
        // Only proceed if:
        // - The model has an existing image in that field
        // - The request has a new file for that image field
        if ($model[$image] && ($request == null || $request->hasFile($image))) {
            Media::delete($model[$image]);
            $model->update([$image => null]);
        }
    }
}



}
