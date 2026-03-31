<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormInteraction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LiveFeedController extends Controller
{
    public function formInteractions(Request $request)
    {
        if (!Schema::hasTable('form_interactions')) {
            return response()->json(['items' => []]);
        }

        $afterId = (int) $request->query('after_id', 0);
        $limit = (int) $request->query('limit', 30);
        $limit = max(1, min(100, $limit));

        $query = FormInteraction::query()->orderByDesc('id')->limit($limit);
        if ($afterId > 0) { 
            $query->where('id', '>', $afterId);
        }

        $items = $query->get()->sortBy('id')->values()->map(function (FormInteraction $i) {
            $locBits = [];
            if ($i->submitted_city) $locBits[] = $i->submitted_city;
            if ($i->submitted_country) $locBits[] = $i->submitted_country;
            if ($i->country_code) $locBits[] = $i->country_code;
            $location = trim(implode(', ', array_values(array_unique(array_filter($locBits)))));

            return [
                'id' => $i->id,
                'form' => $i->form,
                'action' => $i->action,
                'outcome' => $i->outcome,
                'ip' => $i->ip,
                'location' => $location !== '' ? $location : null,
                'path' => $i->path,
                'at' => optional($i->created_at)->toIso8601String(),
                'at_human' => optional($i->created_at)->diffForHumans(),
            ];
        });

        return response()->json(['items' => $items]);
    }
}

