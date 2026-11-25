<?php

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://github.com/pfarr-tools/pfarrplaner
 * @version git: $Id$
 *
 *
 * Pfarrplaner is based on the Laravel framework (https://laravel.com).
 * This file may contain code created by Laravel's scaffolding functions.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesAttachedImageTrait
{
    /**
     * Store an uploaded image and point the model field at it.
     *
     * @param  int|string  $model  Identifier of the model to update.
     * @param  string|null  $field  Model field receiving the image path.
     * @return JsonResponse|null
     */
    public function attachImage(Request $request, $model, $field = null)
    {
        $modelClass = $this->model ?? $this->modelClass ?? null;
        abort_unless((bool) $modelClass, 500);
        /** @var class-string<Model> $modelClass */
        $modelObject = $modelClass::findOrFail($model);
        Gate::authorize('update', $modelObject);
        $imageField = $field ?? (is_object($modelObject) && method_exists($modelObject, 'getImageField') ? $modelObject->getImageField() : null);
        if (! is_string($imageField)) {
            return null;
        }

        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            foreach ($files as $file) {
                $fullPath = $file->store('attachments');
                $modelObject->update([$imageField => $fullPath]);
                $path = basename((string) $fullPath);

                return response()->json(['url' => route('image', compact('path')), 'image' => $fullPath,
                    'model' => $modelObject, 'id' => $model, 'field' => $imageField]);
            }
        } elseif ($request->has('uploadFromUrl')) {
            $uploadFromUrl = $request->get('uploadFromUrl');
            if (! is_string($uploadFromUrl)) {
                return null;
            }
            $fullPath = 'attachments/'.Str::random(32).'.'.pathinfo($uploadFromUrl, PATHINFO_EXTENSION);
            Storage::put($fullPath, (string) file_get_contents($uploadFromUrl));
            $modelObject->update([$imageField => $fullPath]);
            $path = basename($fullPath);

            return response()->json(['url' => route('image', compact('path')), 'image' => $fullPath,
                'model' => $modelObject, 'id' => $model, 'field' => $imageField]);
        }

        return null;
    }

    /**
     * Remove the image referenced by a model field.
     *
     * @param  int|string  $model  Identifier of the model to update.
     * @param  string|null  $field  Model field holding the image path.
     * @return JsonResponse
     */
    public function detachImage(Request $request, $model, $field = null)
    {
        $modelClass = $this->model ?? $this->modelClass ?? null;
        abort_unless((bool) $modelClass, 500);
        /** @var class-string<Model> $modelClass */
        $modelObject = $modelClass::findOrFail($model);
        Gate::authorize('update', $modelObject);
        $imageField = $field ?? (is_object($modelObject) && method_exists($modelObject, 'getImageField') ? $modelObject->getImageField() : null);
        if (! is_string($imageField)) {
            return response()->json();
        }
        $existingPath = $modelObject->$imageField;
        if ($existingPath && is_string($existingPath)) {
            Storage::delete($existingPath);
            $modelObject->update([$imageField => '']);
        }

        return response()->json();
    }
}
