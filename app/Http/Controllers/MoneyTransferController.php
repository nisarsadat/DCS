<?php

namespace App\Http\Controllers;

use App\Models\MoneyTransfer;
use App\Http\Requests\MoneyTransferRequest;
use App\Http\Requests\PaginateRequest;
use App\Http\Resources\MoneyTransferResource;


class MoneyTransferController extends Controller
{

    protected $model = MoneyTransfer::class;
    protected $resource = MoneyTransferResource::class;


    public function index(PaginateRequest $request)
    {
        return $this->resource::collection($this->listRecord($request, $this->model,[
            MoneyTransfer::COLUMN_FROM_ACCOUNT_ID,
            MoneyTransfer::COLUMN_TO_ACCOUNT_ID
        ]));
    }

    public function show($id)
    {
        return new $this->resource($this->showRecord($this->model,$id));
    }

    public function store(MoneyTransferRequest $request)
    {
        $validated = $request->validated();
        $validated[MoneyTransfer::COLUMN_DESCRIPTION] = $validated[MoneyTransfer::COLUMN_DESCRIPTION] ?? '';

        $transfer = MoneyTransfer::create($validated);

        return new $this->resource($transfer);
    }

    public function update(MoneyTransferRequest $request, $id)
    {
        $transfer = MoneyTransfer::findOrFail($id);
        $validated = $request->validated();
        $validated[MoneyTransfer::COLUMN_DESCRIPTION] = $validated[MoneyTransfer::COLUMN_DESCRIPTION] ?? '';
        $transfer->update($validated);

        return new $this->resource($transfer);
    }

    public function destroy($id)
    {
        $transfer = MoneyTransfer::findOrFail($id);

        return $this->deleteRecord($transfer);
    }
}
