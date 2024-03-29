<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Salon;
use App\Models\Banners;
use App\Models\Category;
use App\Models\CategoryType;
use App\Models\User;
use App\Models\Cities;
use App\Models\Settings;
use App\Models\Individual;
use App\Models\Services;
use App\Models\Specialist;
use App\Models\Packages;
use App\Models\Commission;
use App\Models\Products;
use App\Models\ViewHistory;
use App\Models\Favorite;
use App\Models\OwnerReviews;
use Illuminate\Support\Arr;
use Validator;
use DB;
use DateTime;

class SalonController extends Controller
{
    public function save(Request $request){
        $validator = Validator::make($request->all(), [
            'uid' => 'required',
            'name' => 'required',
            'cover' => 'required',
            'categories' => 'required',
            'address' => 'required',
            'lat' => 'required',
            'lng' => 'required',
            'about' => 'required',
            'rating' => 'required',
            'total_rating' => 'required',
            'website' => 'required',
            'timing' => 'required',
            'images' => 'required',
            'zipcode' => 'required',
            'service_at_home' => 'required',
            'verified' => 'required',
            'status' => 'required',
            'have_stylist'=>'required',
            'in_home'=>'required',
            'popular'=>'required',
            'have_shop'=>'required',
            'rate' => 'required',
            'id_card' => 'required',
            'policy' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }

        $request['policy_date'] = date('Y-m-d');
        $data = Salon::create($request->all());
        if (is_null($data)) {
            $response = [
            'data'=>$data,
            'message' => 'error',
            'status' => 500,
        ];
        return response()->json($response, 200);
        }
        Commission::create([
            'uid'=>$request->uid,
            'rate'=>$request->rate,
            'status'=>1,
        ]);
        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getById(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }

        $data = Salon::where('uid',$request->id)->first();
        if($data && $data->categories && $data->categories !=null){
            $ids = explode(',',$data->categories);
            $cats = Category::WhereIn('id',$ids)->get();
            $data->web_cates_data = $cats;
        }
        if($data && $data->cid && $data->cid !=null){
            $data->city_data = Cities::find($data->cid);
        }
        if (is_null($data)) {
            $response = [
                'success' => false,
                'message' => 'Data not found.',
                'status' => 404
            ];
            return response()->json($response, 404);
        }

        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getSearchResult(Request $request){
        $str = "";
        if ($request->has('param') && $request->has('lat') && $request->has('lng')) {
            $str = $request->param;
            $lat = $request->lat;
            $lng = $request->lng;
        }
        $searchQuery = Settings::select('allowDistance','searchResultKind')->first();
        $categories = Category::where(['status'=>1])->get();
        if($searchQuery->searchResultKind == 1){
            $values = 3959; // miles
            $distanceType = 'miles';
        }else{
            $values = 6371; // km
            $distanceType = 'km';
        }

        $salon = Salon::select(DB::raw('salon.id as id,salon.uid as uid,salon.name as name,salon.rating as rating,
        salon.total_rating as total_rating,salon.address as address,salon.cover as cover,salon.lat as salon_lat,salon.lng as salon_lng, ( '.$values.' * acos( cos( radians('.$lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$lng.') ) + sin( radians('.$lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where('salon.name', 'like', '%'.$str.'%')
        ->where(['salon.status'=>1,'salon.in_home'=>1])
        ->get();

        $freelancer = Individual::select(DB::raw('individual.id as id,individual.uid as uid,individual.categories,individual.lat as lat,individual.lng as lng,users.first_name as first_name,users.last_name as last_name,users.cover as cover, ( '.$values.' * acos( cos( radians('.$lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$lng.') ) + sin( radians('.$lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->join('users','individual.uid','users.id')
        ->where('users.first_name', 'like', '%'.$str.'%')
        ->where(['individual.status'=>1,'individual.in_home'=>1])
        ->get();
        foreach($freelancer as $loop){
            $loop->distance = round($loop->distance,2);
        }
        foreach($salon as $loop){
            $loop->distance = round($loop->distance,2);
        }
        $response = [
            'salon'=>$salon,
            'individual'=>$freelancer,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getHomeData(Request $request){
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $user_id = $request->user_id;
        $searchQuery = Settings::select('allowDistance','searchResultKind')->first();
        $categories = Category::where(['status'=>1])->get();
        if($searchQuery->searchResultKind == 1){
            $values = 3959; // miles
            $distanceType = 'miles';
        }else{
            $values = 6371; // km
            $distanceType = 'km';
        }

        $salon = Salon::select(DB::raw('salon.id as id,salon.uid as uid,salon.name as name,salon.rating as rating,
        salon.total_rating as total_rating,salon.address as address,salon.cover as cover,salon.lat as salon_lat,salon.lng as salon_lng, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where(['salon.status'=>1,'salon.in_home'=>1])
        ->get();
        foreach($salon as $loop){
            $loop->userInfo = User::select('first_name','last_name','cover')->find($loop->uid);
            $loop->favorites = Favorite::where('pid', $loop->id)->count();
            $loop->review_count = OwnerReviews::where('freelancer_id', $loop->uid)->count();
            if(!empty($request->user_id)) {
                $loop->favorite_status = Favorite::where(['pid' => $loop->id, 'uid' => $request->user_id, 'type' => 'salon'])->count();
            }
        }

        $freelancer = Individual::select(DB::raw('individual.id as id,individual.uid as uid, individual.lat as lat, individual.lng as lng, individual.background as cover, individual.about as about, individual.rating as rating, individual.address as address, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where(['individual.status'=>1,'individual.in_home'=>1])
        ->get();
        foreach($freelancer as $loop){
            $loop->userInfo = User::select('first_name','last_name','cover')->find($loop->uid);
            $loop->favorites = Favorite::where('pid', $loop->uid)->count();
            $loop->review_count = OwnerReviews::where('freelancer_id', $loop->uid)->count();
            if(!empty($request->user_id)) {
                $loop->favorite_status = Favorite::where(['pid' => $loop->id, 'uid' => $request->user_id, 'type' => 'beauticians'])->count();
            }
        }

        $categories =  Category::where('status',1)->get();

        $cities  = Cities::select(DB::raw('cities.id as id,cities.name as name, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where(['cities.status'=>1])
        ->first();
        $banners =[];
        if (isset($cities) && $cities) {
            $banners =Banners::where('city_id',$cities->id)->get();
        }
        $salonUID = $salon->pluck('uid')->toArray();
        $freelancerUID = $freelancer->pluck('uid')->toArray();
        $uidArray = Arr::collapse([$salonUID,$freelancerUID]);
        // $products = Products::where('in_home',1)->WhereIn('freelacer_id',$uidArray)->limit(10)->get();
        $products = Products::limit(10)->get();
        $response = [
            'salon'=>$salon,
            'categories'=>$categories,
            'individual'=>$freelancer,
            'cities'=>$cities,
            'banners'=>$banners,
            'products'=>$products,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getHomeDataWeb(Request $request){
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $searchQuery = Settings::select('allowDistance','searchResultKind')->first();
        $categories = Category::where(['status'=>1])->get();
        if($searchQuery->searchResultKind == 1){
            $values = 3959; // miles
            $distanceType = 'miles';
        }else{
            $values = 6371; // km
            $distanceType = 'km';
        }

        $salon = Salon::select(DB::raw('salon.id as id,salon.uid as uid,salon.name as name,salon.rating as rating,
        salon.total_rating as total_rating,salon.address as address,salon.cover as cover,salon.lat as salon_lat,salon.lng as salon_lng,salon.categories, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where(['salon.status'=>1,'salon.in_home'=>1])
        ->get();
        foreach($salon as $loop){
            $ids = explode(',',$loop->categories);
            $loop->categories = Category::select('id','name','cover')->WhereIn('id',$ids)->get();
        }

        $freelancer = Individual::select(DB::raw('individual.address, individual.id as id,individual.fee_start as fee_start,individual.categories,individual.total_rating as total_rating,individual.rating as rating,individual.uid as uid,individual.lat as lat,individual.lng as lng, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where(['individual.status'=>1,'individual.in_home'=>1])
        ->get();
        foreach($freelancer as $loop){
            $loop->userInfo = User::select('first_name','last_name','cover')->find($loop->uid);
            $ids = explode(',',$loop->categories);
            $loop->categories = Category::select('id','name','cover')->WhereIn('id',$ids)->get();
        }
        // foreach($freelancer as $loop){
        //     $loop->userInfo = User::select('first_name','last_name','cover')->find($loop->uid);
        // }

        $cities  = Cities::select(DB::raw('cities.id as id,cities.name as name, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where(['cities.status'=>1])
        ->first();
        $banners =[];
        if (isset($cities) && $cities) {
            $banners =Banners::where('city_id',$cities->id)->get();
        }
        $salonUID = $salon->pluck('uid')->toArray();
        $freelancerUID = $freelancer->pluck('uid')->toArray();
        $uidArray = Arr::collapse([$salonUID,$freelancerUID]);
        $products = Products::where('in_home',1)->WhereIn('freelacer_id',$uidArray)->limit(10)->get();
        $response = [
            'salon'=>$salon,
            'categories'=>$categories,
            'individual'=>$freelancer,
            'cities'=>$cities,
            'banners'=>$banners,
            'products'=>$products,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getTopFreelancer(Request $request){
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $searchQuery = Settings::select('allowDistance','searchResultKind')->first();
        $categories = Category::where(['status'=>1])->get();
        if($searchQuery->searchResultKind == 1){
            $values = 3959; // miles
            $distanceType = 'miles';
        }else{
            $values = 6371; // km
            $distanceType = 'km';
        }

        $data = Individual::select(DB::raw('individual.id as id,individual.uid as uid,individual.categories,individual.fee_start as fee_start,
        individual.rating as rating,individual.total_rating as total_rating, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where(['individual.status'=>1,'individual.in_home'=>1])
        ->get();
        foreach($data as $loop){
            $loop->userInfo = User::select('first_name','last_name','cover')->find($loop->uid);
            $ids = explode(',',$loop->categories);
            $loop->categories = Category::select('id','name','cover')->WhereIn('id',$ids)->get();
        }

        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getTopSalon(Request $request){
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $searchQuery = Settings::select('allowDistance','searchResultKind')->first();
        $categories = Category::where(['status'=>1])->get();
        if($searchQuery->searchResultKind == 1){
            $values = 3959; // miles
            $distanceType = 'miles';
        }else{
            $values = 6371; // km
            $distanceType = 'km';
        }

        $data = Salon::select(DB::raw('salon.id as id,salon.uid as uid,salon.name as name,salon.rating as rating,
        salon.total_rating as total_rating,salon.address as address,salon.cover as cover,salon.categories as categories, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where(['salon.status'=>1,'salon.in_home'=>1])
        ->get();

        foreach($data as $loop){
            $ids = explode(',',$loop->categories);
            $loop->categories = Category::select('id','name','cover')->WhereIn('id',$ids)->get();
        }

        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getRecommenedSalons(Request $request) {
        $validator = Validator::make($request->all(), [
            'uid' => 'required',
            // 'lat' => 'required',
            // 'lng' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $searchQuery = Settings::select('allowDistance','searchResultKind')->first();
        $categories = Category::where(['status'=>1])->get();
        if($searchQuery->searchResultKind == 1){
            $values = 3959; // miles
            $distanceType = 'miles';
        }else{
            $values = 6371; // km
            $distanceType = 'km';
        }

        $user = User::find($request->uid);

        $lat = !empty($request->lat) ? $request->lat : $user->city->lat;
        $lng = !empty($request->lng) ? $request->lng : $user->city->lng;

        $data = Salon::select(DB::raw('salon.id as id,salon.uid as uid,salon.name as name,salon.rating as rating,
                salon.total_rating as total_rating,salon.address as address,salon.cover as cover,salon.categories as categories, ( '
                .$values.' * acos( cos( radians('.$lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.
                $lng.') ) + sin( radians('.$lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
            ->withCount('users')
            ->having('distance', '<', (int)$searchQuery->allowDistance)
            ->having('users_count', '>', 0)
            ->orderBy('distance')
            ->orderBy('users_count', 'desc')
            ->orderBy('salon.rating', 'desc')
            ->where(['salon.status'=>1,'salon.in_home'=>1])
            ->get();

        foreach($data as $loop){
            $ids = explode(',',$loop->categories);
            $loop->categories = Category::select('id','name','cover')->WhereIn('id',$ids)->get();
        }

        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }


    public function getDataFromCategory(Request $request){
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $searchQuery = Settings::select('allowDistance','searchResultKind')->first();
        $categories = Category::where(['status'=>1])->get();
        if($searchQuery->searchResultKind == 1){
            $values = 3959; // miles
            $distanceType = 'miles';
        }else{
            $values = 6371; // km
            $distanceType = 'km';
        }

        $salon = Salon::select(DB::raw('salon.id as id,salon.uid as uid,salon.name as name,salon.rating as rating,
        salon.total_rating as total_rating,salon.address as address,salon.cover as cover,salon.categories, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where(['salon.status'=>1,'salon.in_home'=>1])
        ->whereRaw("find_in_set('".$request->id."',salon.categories)")
        ->get();
        foreach($salon as $loop){
            $ids = explode(',',$loop->categories);
            $loop->categories = Category::select('id','name','cover')->WhereIn('id',$ids)->get();
        }

        $freelancer = Individual::select(DB::raw('individual.id as id,individual.uid as uid,individual.categories, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where(['individual.status'=>1,'individual.in_home'=>1])
        ->whereRaw("find_in_set('".$request->id."',individual.categories)")
        ->get();
        foreach($freelancer as $loop){
            $loop->userInfo = User::select('first_name','last_name','cover')->find($loop->uid);
            $ids = explode(',',$loop->categories);
            $loop->categories = Category::select('id','name','cover')->WhereIn('id',$ids)->get();
        }

        $response = [
            'salon'=>$salon,
            'individual'=>$freelancer,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getDataFromCategoryWeb(Request $request){
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
            'id' => 'required',
            'type' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $searchQuery = Settings::select('allowDistance','searchResultKind')->first();

        if($searchQuery->searchResultKind == 1){
            $values = 3959; // miles
            $distanceType = 'miles';
        }else{
            $values = 6371; // km
            $distanceType = 'km';
        }

        $salon = Salon::select(DB::raw('salon.id as id,salon.uid as uid,salon.name as name,salon.rating as rating,
        salon.total_rating as total_rating,salon.address as address,salon.cover as cover,salon.categories, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where(['salon.status'=>1,'salon.in_home'=>1]);
        if ($request->type == 0) {// category
            $category = Category::find($request->id);

            $salon = $salon->where(function($query) use ($category) {
                $query->orWhereRaw("find_in_set('".$category->id."',salon.categories)");
            });
        } else if($request->type == 1) { // category_type
            $salon = $salon->whereRaw("find_in_set('".$request->id."',salon.categories)");
        }

        $salon = $salon->get();
        foreach($salon as $loop){
            $ids = explode(',',$loop->categories);
            $loop->categories = Category::select('id','name','cover')->WhereIn('id',$ids)->get();
        }

        $freelancer = Individual::select(DB::raw('individual.id as id,individual.uid as uid,individual.categories,individual.fee_start as fee_start,
        individual.rating as rating,individual.total_rating as total_rating, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
        ->having('distance', '<', (int)$searchQuery->allowDistance)
        ->orderBy('distance')
        ->where(['individual.status'=>1,'individual.in_home'=>1])
        ->whereRaw("find_in_set('".$request->id."',individual.categories)")
        ->get();
        foreach($freelancer as $loop){
            $loop->userInfo = User::select('first_name','last_name','cover')->find($loop->uid);
            $ids = explode(',',$loop->categories);
            $loop->categories = Category::select('id','name','cover')->WhereIn('id',$ids)->get();
        }

        $response = [
            'salon'=>$salon,
            'individual'=>$freelancer,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $data = Salon::find($request->id)->update($request->all());

        if (is_null($data)) {
            $response = [
                'success' => false,
                'message' => 'Data not found.',
                'status' => 404
            ];
            return response()->json($response, 404);
        }
        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function updatePolicy(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'policy' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $salon = Salon::find($request->id);
        if (is_null($salon) || empty($salon)) {
            $response = [
                'success' => false,
                'message' => 'Data not found.',
                'status' => 404
            ];
            return response()->json($response, 404);
        }

        $date1 = new DateTime($salon->policy_date);
        $date2 = new DateTime(date('Y-m-d'));

        $interval = $date1->diff($date2);

        if ($salon->policy_date && $interval->format('%a') < 30) {
            $response = [
                'success' => false,
                'message' => 'Not allowed to change.',
                'status' => 404
            ];
            return response()->json($response, 404);
        }
        $salon->policy = $request->policy;
        $salon->policy_date = date('Y-m-d');
        $salon->update();

        $response = [
            'data'=>$salon,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getPolicy(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $salon = Salon::find($request->id);

        $response = [
            'data'=>$salon->policy,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function delete(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'uid' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $data = Salon::find($request->id);
        $data2 = User::find($request->uid);
        DB::table('commission')->where('uid',$request->uid)->delete();
        if ($data && $data2) {
            $data->delete();
            $data2->delete();
            $response = [
                'data'=>$data,
                'success' => true,
                'status' => 200,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'success' => false,
            'message' => 'Data not found.',
            'status' => 404
        ];
        return response()->json($response, 404);
    }

    public function getAll(){
        $data = Salon::all();

        if (is_null($data)) {
            $response = [
                'success' => false,
                'message' => 'Data not found.',
                'status' => 404
            ];
            return response()->json($response, 404);
        }
        foreach($data as $loop){
            if($loop && $loop->categories && $loop->categories !=null){
                $ids = explode(',',$loop->categories);
                $cats = Category::WhereIn('id',$ids)->get();
                $loop->web_cates_data = $cats;
            }
            if($loop && $loop->cid && $loop->cid !=null){
                $loop->city_data = Cities::find($loop->cid);
            }
        }
        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getListForOffers(Request $request){
        $salon = Salon::all();
        $individuals =  DB::table('individual')
                ->select('individual.*','users.first_name as first_name','users.last_name as last_name')
                ->join('users','individual.uid','users.id')
                ->get();
        $data = [];

        foreach($salon as $row) {
            array_push($data, (object)[
                    'name' => $row->name,
                    'id' => $row->uid,
            ]);
        }

        foreach($individuals as $row) {
            array_push($data, (object)[
                    'name' => $row->first_name .' '.$row->last_name,
                    'id' => $row->uid,
            ]);
        }
        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getActiveCities(Request $request){
        $data = Salon::where('status',1)->get();
        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function importData(Request $request){
        $request->validate([
            "csv_file" => "required",
        ]);
        $file = $request->file("csv_file");
        $csvData = file_get_contents($file);
        $rows = array_map("str_getcsv", explode("\n", $csvData));
        $header = array_shift($rows);
        foreach ($rows as $row) {
            if (isset($row[0])) {
                if ($row[0] != "") {

                    if(count($header) == count($row)){
                        $row = array_combine($header, $row);
                        $insertInfo =  array(
                            'id' => $row['id'],
                            'name' => $row['name'],
                            'lat' => $row['lat'],
                            'lng' => $row['lng'],
                            'status' => $row['status'],
                        );
                        $checkLead  =  Salon::where("id", "=", $row["id"])->first();
                        if (!is_null($checkLead)) {
                            DB::table('cities')->where("id", "=", $row["id"])->update($insertInfo);
                        }
                        else {
                            DB::table('cities')->insert($insertInfo);
                        }
                    }
                }
            }
        }
        $response = [
            'data'=>'Done',
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function salonDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $data = Salon::where('uid',$request->id)->first();
        $userInfo = User::where('id',$request->id)->first();
        $data['email']= $userInfo->email;
        $data['mobile'] = $userInfo->mobile;
        $services = Services::select('cate_id')->where(['uid'=>$request->id,'status'=>1])->get()->pluck('cate_id');
        $serviceList = Services::where(['uid'=>$request->id,'status'=>1])->get();
        foreach($serviceList as $loop){
            $loop->category = CategoryType::where('id',$loop->cate_id)->first();
        }
        $specialist = Specialist::where('salon_uid',$request->id)->get();
        $categoryTypes = CategoryType::where('status',1)->WhereIn('id',$services)->get();
        $categories = [];
        foreach($categoryTypes as $loop){
            $category = $loop->parent;
            $category->services = Services::where(['status'=>1,'cate_id'=>$loop->id,'uid'=>$request->id])->count();;
            array_push($categories, $category);
            info('$categories');
            info($categories);
        }
        $packages = Packages::where('uid',$request->id)->get();
        foreach($packages as $loop) {
            $specialArray = explode(',', $loop->specialist_ids);
            $serviceArray = explode(',', $loop->service_id);
            $loop->specialist = Specialist::whereIn('id', $specialArray)->get();
            $loop->serviceList = Services::whereIn('id', $serviceArray)->get();
        }
        $response = [
            'data'=>$data,
            'categories'=>$categories,
            'specialist'=>$specialist,
            'packages'=>$packages,
            'servicelist'=>$serviceList,
            'categoryTypes'=>$categoryTypes,
            'success' => true,
            'status' => 200,
        ];

        // Add salon view history
        if (!empty($request->uid)) {
            ViewHistory::create([
                'uid' => $request->uid,
                'viewable_id' => $data->id,
                'viewable_type' => Salon::class,
            ]);
        }
        return response()->json($response, 200);
    }

    function search(Request $request) {
        // $validator = Validator::make($request->all(), [
        //     'lat' => 'required',
        //     'lng' => 'required',
        //     'treatment_id' => 'required',
        //     'treatment_type' => 'required',
        // ]);
        // if ($validator->fails()) {
        //     $response = [
        //         'success' => false,
        //         'message' => 'Validation Error.', $validator->errors(),
        //         'status'=> 500
        //     ];
        //     return response()->json($response, 404);
        // }
        $data = '';
        if (isset($request->lat) && isset($request->lng)) {
            $searchQuery = Settings::select('allowDistance','searchResultKind')->first();
            $categories = Category::where(['status'=>1])->get();
            if($searchQuery->searchResultKind == 1){
                $values = 3959; // miles
                $distanceType = 'miles';
            }else{
                $values = 6371; // km
                $distanceType = 'km';
            }

            $data = Salon::select(DB::raw('salon.id as id,salon.uid as uid,salon.name as name,salon.rating as rating,salon.lat as lat,salon.lng as lng,
            salon.total_rating as total_rating,salon.address as address,salon.cover as cover,salon.categories as categories, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
            ->having('distance', '<', (int)$searchQuery->allowDistance)
            ->where(['salon.status'=>1,'salon.in_home'=>1]);


            // foreach($data as $loop){
            //     $ids = explode(',',$loop->categories);
            //     $loop->categories = Category::select('id','name','cover')->WhereIn('id',$ids)->get();
            // }
        }

        if (isset($request->category_id) && isset($request->category_type)) {
            if ($request->category_type == 0) { // main category
                // TODO
                $categories = Category::where('parent_id', $request->category_id);
            } else if ($request->category_type == 1) { // sub category
                if ($data === '') {
                    $data = Salon::whereRaw("find_in_set('".$request->category_id."',categories)");
                } else {
                    $data = $data->whereRaw("find_in_set('".$request->category_id."',categories)");
                }
            }
        }



        if ($data != '') {
            if (isset($request->sort)) {
                switch($request->sort) {
                    case 0: // recommended
                        // $data = $data->orderBy('distance');
                        break;
                    case 1: // nearest
                        $data = $data->orderBy('distance', 'desc');
                        break;
                    case 2: // top-rated
                        $data = $data->orderBy('rating', 'desc');
                        break;
                }

            }
            $data = $data->get();
        }

        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);

    }

    public function getSalonsNearby(Request $request) {
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $searchQuery = Settings::select('allowDistance','searchResultKind')->first();
        $categories = Category::where(['status'=>1])->get();
        if($searchQuery->searchResultKind == 1){
            $values = 3959; // miles
            $distanceType = 'miles';
        }else{
            $values = 6371; // km
            $distanceType = 'km';
        }

        $data = Salon::select(DB::raw('salon.id as id,salon.uid as uid,salon.name as name,salon.rating as rating,
                salon.total_rating as total_rating,salon.address as address,salon.cover as cover,salon.categories as categories, ( '
                .$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('
                .$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
            ->having('distance', '<', (int)$searchQuery->allowDistance)
            ->orderBy('distance')
            ->where(['salon.status'=>1,'salon.in_home'=>1])
            ->get();


        foreach($data as $loop){
            $ids = explode(',',$loop->categories);
            $loop->categories = Category::select('id','name','cover')->WhereIn('id',$ids)->get();
        }

        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getFreelancersNearby(Request $request) {
        $validator = Validator::make($request->all(), [
            'lat' => 'required',
            'lng' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }
        $searchQuery = Settings::select('allowDistance','searchResultKind')->first();
        $categories = Category::where(['status'=>1])->get();
        if($searchQuery->searchResultKind == 1){
            $values = 3959; // miles
            $distanceType = 'miles';
        }else{
            $values = 6371; // km
            $distanceType = 'km';
        }

        $freelancer = Individual::select(DB::raw('individual.id as id,individual.fee_start as fee_start,individual.categories,individual.total_rating as total_rating,individual.rating as rating,individual.uid as uid,individual.lat as lat,individual.lng as lng, ( '.$values.' * acos( cos( radians('.$request->lat.') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('.$request->lng.') ) + sin( radians('.$request->lat.') ) * sin( radians( lat ) ) ) ) AS distance'))
            ->having('distance', '<', (int)$searchQuery->allowDistance)
            ->orderBy('distance')
            ->where(['individual.status'=>1,'individual.in_home'=>1])
            ->get();
        foreach($freelancer as $loop){
            $loop->userInfo = User::select('first_name','last_name','cover')->find($loop->uid);
            $ids = explode(',',$loop->categories);
            $loop->categories = Category::select('id','name','cover')->WhereIn('id',$ids)->get();
        }

        $response = [
            'data'=>$freelancer,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }
}
