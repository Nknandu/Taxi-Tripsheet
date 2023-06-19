<?php

namespace App\Http\Controllers\Api\User;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\RoomCreateOptions;
use Agence104\LiveKit\RoomServiceClient;
use Agence104\LiveKit\VideoGrant;
use App\Http\Controllers\Controller;
use App\Models\Live;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Image;

class LiveController extends Controller
{
    public function createLive(Request $request)
    {
        try
        {
            $lang = $request->language;
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            ini_set('upload_max_filesize', '15M');
            ini_set('post_max_size', '15M');

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|numeric',
                // 'image' => 'required|mimes:jpeg,jpg,png,gif',
                'title' => 'required|string|max:200',
                // 'live_date' => 'required|string',
                // 'start_time' => 'required|string'
            ]);
            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => $validator->messages()->first(),
                        'message_code' => 'validation_error',
                        //'errors' => $validator->errors()->all()
                    ], 200);
            }

            $time_zone = getLocalTimeZone($request->time_zone);
            $current_date_time = Carbon::now('UTC')->toDateTimeString();
            $current_date_time_local = Carbon::now($time_zone)->toDateTimeString();

            // Check user package

            $slug = Str::slug($request->title);
            $room_name = $slug."_".time();
            $image_name_in_db = null;
            if ($request->hasfile('image')) {
                $image = $request->file('image');
                $image_name = $slug."-".time().'.'.$image->extension();
                if (strtolower($image->extension()) == 'gif') {
                    $original_image_file = $thumb_image_file = file_get_contents($request->image);
                } else {
                    $original_image = Image::make($image);
                    $original_image_file = $original_image->stream()->__toString();
                }

                if (Storage::disk('public')->put('uploads/live/original/'.$image_name, $original_image_file, ['visibility' => 'public'])) {
                   $image_name_in_db = $image_name;
                }
            } else if (isset($request->image) && $request->image) {
                $img_data = base64_decode($request->image);
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $img_data, FILEINFO_MIME_TYPE);

                $extension = explode('/', $mime_type)[1];
                $type = explode('/', $mime_type)[0];
                $image = $request->image;
                $image_name = $slug."-".time().'.'.$extension;

                $image_data = "data:".$mime_type.";base64,".$request->image;
                if(strtolower($type) == "image")
                {
                    if(strtolower($extension) == 'gif')
                    {
                        $original_image_file = file_get_contents($request->image);
                    }
                    else
                    {
                        $original_image = Image::make($image);
                        $thumb_image = Image::make($image);
                        $original_image_file = $original_image->stream()->__toString();
                    }
                    if(Storage::disk('public')->put('uploads/live/original/'.$image_name, $original_image_file, ['visibility' => 'public']))
                    {
                        $image_name_in_db = $image_name;
                    }
                }
            }

            $live = new Live();
            $live->user_id = $request->user_id;
            $live->image = $image_name_in_db;
            $live->title = $request->title;
            if ($request->go_live) {
                $live->live_date = date('Y-m-d', strtotime($current_date_time_local));
                $live->start_time = date('H:i', strtotime($current_date_time_local));
                
            } else {
                $live->live_date = $request->live_date;
                $live->start_time = $request->start_time;
            }
            $live->end_time = !empty($request->end_time) ? $request->end_time : null;
            $live->timezone = $request->time_zone;
            $live->livekit_room_name = $room_name;
            $live->is_active = 1;
            $live->created_at = $current_date_time;
            $live->is_running = 0;
            $live->save();

            $liveModel = Live::where('id', $live->id)->first();
            // dump($liveModel->user->boutiques()->pluck('delivery_charge'));
            // $delivery_charge = '0.0';
            // $delivery_text = '';
            // foreach ($liveModel->user->boutiques as $boutique) {
            //     $delivery_charge = $boutique->delivery_charge;
            //     $delivery_text = (string) ($lang == 'en') ? $boutique->delivery_text : $boutique->{"delivery_text_".$lang};
            // }
            
            $liveData = $this->formatLiveData($liveModel);
            // $liveData = [
            //     'id' => $liveModel->id,
            //     'title' => (string) $liveModel->title,
            //     'image' => (string) $liveModel->file,
            //     'live_date' => (string) $liveModel->live_date,
            //     'start_time' => (string) $liveModel->start_time,
            //     'end_time' => (string) $liveModel->end_time,
            //     'is_running' => $liveModel->is_running,
            //     'delivery_time' => $delivery_charge,
            //     'delivery_cost' => $delivery_text
            // ];

            // Create livekit room and token
            $token = '';
            if ($request->go_live) {
                // If $room_name room doesn't exist, it'll be automatically created when the first client joins.

                $svc = new RoomServiceClient(env('LK_HOST'), env('LK_API_KEY'), env('LK_API_SECRET'));
                $opts = (new RoomCreateOptions())
                ->setName($room_name)
                ->setEmptyTimeout(env('LK_EMPTY_TIMEOUT'))
                ->setMaxParticipants(env('LK_MAX_PARTICIPANTS'));
                $room = $svc->createRoom($opts);

                $participantName = $liveModel->user->first_name." ".$liveModel->user->last_name;

                // Define the token options.
                $tokenOptions = (new AccessTokenOptions())
                ->setIdentity($participantName);

                // Define the video grants.
                $videoGrant = (new VideoGrant())
                ->setRoomJoin()
                ->setRoomName($room_name);

                // Initialize and fetch the JWT Token. 
                $token = (new AccessToken(env('LK_API_KEY'), env('LK_API_SECRET')))
                ->init($tokenOptions)
                ->setGrant($videoGrant)
                ->toJwt();

                $liveModel->is_running = 1;
                $liveModel->save();
            }

            return response()->json(
                [
                    'success' => true,
                    'status' => 200,
                    'message' => __('messages.user_live_success'),
                    'message_code' => 'user_live_success',
                    'data' => [
                        'live_data' => $liveData,
                        'live_api_key' => ($request->go_live == 1) ? env('LK_API_KEY') : '',
                        'live_token' => $token
                    ]
                ], 200);

        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage()
                ], 500);
        }
    }

    public function liveRooms(Request $request)
    {
        try {
            $host = env('LK_HOST');
            $svc = new RoomServiceClient($host, env('LK_API_KEY'), env('LK_API_SECRET'));
            var_dump($svc->listRooms());
        } catch (\Exception $exception) {
            return response()->json(
                [
                    'success' => false,
                    'status' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage()
                ], 500);
        }
    }

    public function joinLive(Request $request)
    {
        $lang = $request->language;
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);

        try {
            $validator = Validator::make($request->all(), [
                'live_id' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => $validator->messages()->first(),
                        'message_code' => 'validation_error',
                        //'errors' => $validator->errors()->all()
                    ], 200);
            }

            $time_zone = getLocalTimeZone($request->time_zone);

            $liveModel = Live::where('id', $request->live_id)->first();

            $user = User::find(auth('api')->id());

            // Create livekit room and token
            $token = '';
            if ($liveModel->is_running) {
                // If $room_name room doesn't exist, it'll be automatically created when the first client joins.
                $room_name = $liveModel->livekit_room_name;

                $svc = new RoomServiceClient(env('LK_HOST'), env('LK_API_KEY'), env('LK_API_SECRET'));

                $participantName = $user->first_name." ".$user->last_name;

                // Define the token options.
                $tokenOptions = (new AccessTokenOptions())
                ->setIdentity($participantName);

                // Define the video grants.
                $videoGrant = (new VideoGrant())
                ->setRoomJoin()
                ->setRoomName($room_name);

                // Initialize and fetch the JWT Token. 
                $token = (new AccessToken(env('LK_API_KEY'), env('LK_API_SECRET')))
                ->init($tokenOptions)
                ->setGrant($videoGrant)
                ->toJwt();

                return response()->json(
                    [
                        'success' => true,
                        'status' => 200,
                        'message' => __('messages.join_live_success'),
                        'message_code' => 'join_live_success',
                        'data' => [
                            'live_api_key' => env('LK_API_KEY'),
                            'live_token' => $token
                        ]
                    ], 200);
            }

            return response()->json(
                [
                    'success' => false,
                    'status' => 400,
                    'message' => __('messages.not_live_yet'),
                    'message_code' => 'not_live_yet',
                    'data' => [
                        'live_api_key' => env('LK_API_KEY'),
                        'live_token' => ''
                    ]
                ], 200);
        } catch (\Exception $exception) {
            return response()->json(
                [
                    'success' => false,
                    'status' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage()
                ], 500);
        }
    }

    public function goLive(Request $request)
    {
        $lang = $request->language;
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);
        try {
            $validator = Validator::make($request->all(), [
                'live_id' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => $validator->messages()->first(),
                        'message_code' => 'validation_error',
                        //'errors' => $validator->errors()->all()
                    ], 200);
            }

            $time_zone = getLocalTimeZone($request->time_zone);

            $user = User::find(auth('api')->id());
            // Check user package

            $liveModel = Live::where('id', $request->live_id)->first();

            if ($liveModel->user_id != auth('api')->id()) {
                return response()->json(
                    [
                        'success' => false,
                        'status' => 400,
                        'message' => __('messages.something_went_wrong'),
                        'message_code' => 'try_catch',
                        //'errors' => $validator->errors()->all()
                    ], 200);
            }

            // Create livekit room and token
            $token = '';
            if ($liveModel->is_running == 0) {
                // If $room_name room doesn't exist, it'll be automatically created when the first client joins.
                $room_name = $liveModel->livekit_room_name;

                $svc = new RoomServiceClient(env('LK_HOST'), env('LK_API_KEY'), env('LK_API_SECRET'));
                $opts = (new RoomCreateOptions())
                ->setName($room_name)
                ->setEmptyTimeout(env('LK_EMPTY_TIMEOUT'))
                ->setMaxParticipants(env('LK_MAX_PARTICIPANTS'));
                $room = $svc->createRoom($opts);

                $participantName = $user->first_name." ".$user->last_name;

                // Define the token options.
                $tokenOptions = (new AccessTokenOptions())
                ->setIdentity($participantName);

                // Define the video grants.
                $videoGrant = (new VideoGrant())
                ->setRoomJoin()
                ->setRoomName($room_name);

                // Initialize and fetch the JWT Token. 
                $token = (new AccessToken(env('LK_API_KEY'), env('LK_API_SECRET')))
                ->init($tokenOptions)
                ->setGrant($videoGrant)
                ->toJwt();

                $liveModel->is_running = 1;
                $liveModel->save();

                return response()->json(
                    [
                        'success' => true,
                        'status' => 200,
                        'message' => __('messages.go_live_success'),
                        'message_code' => 'go_live_success',
                        'data' => [
                            'live_api_key' => env('LK_API_KEY'),
                            'live_token' => $token
                        ]
                    ], 200);
            }

            return response()->json(
                [
                    'success' => false,
                    'status' => 400,
                    'message' => __('messages.already_live'),
                    'message_code' => 'already_live',
                    'data' => [
                        'live_api_key' => env('LK_API_KEY'),
                        'live_token' => ''
                    ]
                ], 200);
        } catch (\Exception $exception) {
            return response()->json(
                [
                    'success' => false,
                    'status' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage()
                ], 500);
        }
    }

    public function myLiveList(Request $request)
    {
        try {
            $lang = $request->language;
            $user = User::find(auth('api')->id());
            // Check user package

            $livesList = [];
            $lives = Live::where('is_active', 1)->where('user_id', auth('api')->id())->get();
            foreach ($lives as $live) {
                $livesList[] = $this->formatLiveData($live, $lang);
            }

            return response()->json(
                [
                    'success' => false,
                    'status' => 200,
                    'message' => __('messages.live_list'),
                    'message_code' => 'live_list',
                    'data' => [
                        'my_lives' => $livesList
                    ]
                ], 200);
        } catch (\Exception $exception) {
            return response()->json(
                [
                    'success' => false,
                    'status' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage()
                ], 500);
        }
    }

    public function liveList(Request $request)
    {
        try {
            $ongoingLives = [];
            $upcomingLives = [];
            $lang = $request->language;
            $lives = Live::where('is_active', 1)->where('is_running', 1)->get();
            foreach ($lives as $live) {
                $ongoingLives[] = $this->formatLiveData($live);
            }

            $lives = Live::where('is_active', 1)->where('is_running', 0)->get();
            foreach ($lives as $live) {
                $upcomingLives[] = $this->formatLiveData($live);
            }

            return response()->json(
                [
                    'success' => false,
                    'status' => 200,
                    'message' => __('messages.live_list'),
                    'message_code' => 'live_list',
                    'data' => [
                        'ongoing_lives' => $ongoingLives,
                        'upcoming_lives' => $upcomingLives
                    ]
                ], 200);
        } catch (\Exception $exception) {
            return response()->json(
                [
                    'success' => false,
                    'status' => 500,
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage()
                ], 500);
        }
    }

    protected function formatLiveData($liveData, $lang = 'en')
    {
        $delivery_charge = '0.0';
        $delivery_text = '';
        foreach ($liveData->user->boutiques as $boutique) {
            $delivery_charge = $boutique->delivery_charge;
            $delivery_text = (string) ($lang == 'en') ? $boutique->delivery_text : $boutique->{"delivery_text_".$lang};
        }

        $liveData = [
            'id' => $liveData->id,
            'title' => (string) $liveData->title,
            'image' => (string) $liveData->file,
            'live_date' => (string) $liveData->live_date,
            'start_time' => (string) $liveData->start_time,
            'end_time' => (string) $liveData->end_time,
            'is_running' => $liveData->is_running,
            'delivery_time' => $delivery_charge,
            'delivery_cost' => $delivery_text
        ];

        return $liveData;
    }
}
