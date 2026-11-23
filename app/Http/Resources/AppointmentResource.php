<?php

namespace App\Http\Resources;

use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        

        return [
            'id' => $this->id,
            'dateTime' => $this->date_time,
            'date' => $this->displayDate($this->date_time),
            'time' => $this->date_time ? Carbon::parse($this->date_time)->format('H:i') : null,
            'status' => $this->status,
            'userName' => $this->user?->first_name,
            'dentists' => [
                'id' => $this->dentist?->id,
                'name' => $this->dentist?->name,
            ],
            'patients' => [
                'id' => $this->patient?->id,
                'name' => $this->patient?->name,
            ]
        ];
    }

    private function displayDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        $date = Carbon::parse($value);

        if (SystemSetting::currentDateType() === SystemSetting::DATE_MILADI) {
            return $date->format('Y-m-d');
        }

        return Jalalian::fromCarbon($date)->format('Y-m-d');
    }
}
