<?php

namespace App\Http\Controllers;

use App\Http\Requests\ToothRequest;
use App\Http\Resources\ToothResource;
use App\Models\Tooth;
use App\Support\Media;
use Illuminate\Http\Request;

class ToothController extends Controller
{
    protected $model;
    protected $request;
    protected $resource;

    public function __construct()
    {
        $this->model = Tooth::class;
        $this->request = ToothRequest::class;
        $this->resource = ToothResource::class;
    }

    public function index(Request $request)
    {
        $perPage = $request->input("perPage", 10);
        $search = $request->input("search");

        $tooths = $this->model::with('photos')->search($search)->latest()->paginate($perPage);
        return $this->resource::collection($tooths);
    }

    public function store()
    {
        
        $validated = app($this->request)->validated();
        $tooth = $this->model::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);
        $this->storePhotos($tooth, request());
        return new $this->resource($tooth->load('photos'));
    }

    public function show(Tooth $tooth)
    {
        return new $this->resource($tooth->load('photos'));
    }

    public function update(Tooth $tooth)
    {
        $validated = app($this->request)->validated();
        $tooth->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);
        $this->removePhotos($tooth, request()->input('removeImages', []));
        $this->storePhotos($tooth, request());
        return new $this->resource($tooth->load('photos'));
    }

    public function destroy(Tooth $tooth)
    {
        $tooth->load('photos');
        $this->removePhotos($tooth, $tooth->photos->pluck('id')->all());
        $tooth->delete();
        return response()->json(['message' => 'Record deleted successfully!']);
    }

    private function storePhotos(Tooth $tooth, Request $request): void
    {
        $files = $request->file('images', []);
        if ($files instanceof \Illuminate\Http\UploadedFile) {
            $files = [$files];
        }
        foreach ($files as $file) {
            $tooth->photos()->create([
                'path' => Media::put($file, 'tooths'),
            ]);
        }
    }

    private function removePhotos(Tooth $tooth, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $photos = $tooth->photos()->whereIn('id', $ids)->get();
        foreach ($photos as $photo) {
            Media::delete($photo->path);
            $photo->delete();
        }
    }
}
