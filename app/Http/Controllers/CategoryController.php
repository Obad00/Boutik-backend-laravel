<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            throw new HttpException(400, 'Nom requis');
        }

        $category = Category::create(['name' => $name]);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category)
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            throw new HttpException(400, 'Nom requis');
        }

        $category->update(['name' => $name]);

        return response()->json($category->fresh());
    }

    public function destroy(Category $category)
    {
        $inUse = Product::where('category_id', $category->id)->exists();
        if ($inUse) {
            throw new HttpException(409, 'Impossible de supprimer : des articles utilisent encore cette catégorie');
        }

        $category->delete();

        return response()->json(['ok' => true]);
    }
}
