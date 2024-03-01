<?php

namespace App\Http\Controllers\v1;

use App\Models\Favorite;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Favorite  $favorite
     * @return \Illuminate\Http\Response
     */
    public function show(Favorite $favorite)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Favorite  $favorite
     * @return \Illuminate\Http\Response
     */
    public function edit(Favorite $favorite)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Favorite  $favorite
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Favorite $favorite)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Favorite  $favorite
     * @return \Illuminate\Http\Response
     */
    public function destroy(Favorite $favorite)
    {
        //
    }

    public function toggle(Request $request)
    {
        $favorite = Favorite::where('uid', $request->uid)->where('pid', $request->pid)->where('type', $request->type)->first();
        if ($favorite) {
            $favorite->delete();
            return response()->json(['data' => true, 'status' => 200, 'update' => true], 200);
        } else {
            $favorite = Favorite::create([
                'pid' => $request->pid,
                'type' => $request->type,
                'uid' => $request->uid,
            ]);
            return response()->json(['data' => true, 'favorite' => $favorite, 'status' => 200, 'update' => false], 200);
        }
    }

    public function getFavorites(Request $request)
    {
        $salon = DB::table('favorites')
            ->select('salon.*', 'favorites.uid as fid')
            ->join('salon', 'salon.uid', 'favorites.pid')
            ->where('favorites.uid', $request->id)
            ->where('favorites.type', 'salon')
            ->orderBy('created_at', 'desc')
            ->get();

        $beauticiation = DB::table('favorites')
            ->select('individual.*', 'favorites.id as fid')
            ->join('individual', 'individual.uid', 'favorites.pid')
            ->where('favorites.uid', $request->id)
            ->where('favorites.type', 'beauticians')
            ->orderBy('created_at', 'desc')
            ->get();
        foreach ($beauticiation as $loop) {
            $loop->userInfo = User::select('first_name', 'last_name', 'cover')->find($loop->uid);
            $ids = explode(',', $loop->categories);
            $loop->categories = Category::select('id', 'name', 'cover')->WhereIn('id', $ids)->get();
        }

        $product = DB::table('favorites')
            ->select('products.*', 'favorites.id as fid')
            ->join('products', 'products.id', 'favorites.pid')
            ->where('favorites.uid', $request->id)
            ->where('favorites.type', 'product')
            ->orderBy('created_at', 'desc')
            ->get();

        $treatments = DB::table('favorites')
            ->select('category_types.*', 'favorites.id as fid')
            ->join('category_types', 'category_types.id', 'favorites.pid')
            ->where('favorites.uid', $request->id)->where('favorites.type', 'treatment')
            ->orderBy('created_at', 'desc')->get();

        return response()->json(['salon' => $salon, 'product' => $product, 'beauticiation' => $beauticiation, 'treatments' => $treatments, 'status' => 200], 200);
    }
}
